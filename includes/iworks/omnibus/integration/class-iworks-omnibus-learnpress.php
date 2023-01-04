<?php
/*

Copyright 2022-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_omnibus_integration_learnpress' ) ) {
	return;
}

include_once dirname( dirname( __FILE__ ) ) . '/class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_learnpress extends iworks_omnibus_integration {

	public function __construct() {
		add_action( 'save_post_lp_course', array( $this, 'action_learnpress_save_post_lp_course' ), 10, 2 );
		add_filter( 'learn_press_course_price_html', array( $this, 'filter_learn_press_course_price_html' ), 10, 3 );
		add_filter( 'lp/course/meta-box/fields/price', array( $this, 'filter_learnpress_admin_show_omnibus' ) );
		add_filter( 'learn-press/courses-settings-fields', array( $this, 'filter_learnpress_courses_settings_fields' ) );
		add_filter( 'pre_option', array( $this, 'filter_pre_option' ), 10, 3 );
	}

	/**
	 * translate options to LearnPress options
	 *
	 * @since 2.1.0
	 */
	public function filter_pre_option( $pre, $option, $default ) {
		if ( ! preg_match( '/^_iwo_price_lowest_lp_/', $option ) ) {
			return $pre;
		}
		return get_option( 'learn_press_' . $option, $default );
	}

	/**
	 * LearnPress: add confirmation fields
	 *
	 * @since 2.1.0
	 */
	public function filter_learnpress_courses_settings_fields( $settings ) {
		return array_merge(
			$settings,
			array(
				$this->settings_title(),
				array(
					'title'   => __( 'Courses on sale', 'omnibus' ),
					'id'      => $this->get_name( 'on_sale' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Display only for the course on sale.', 'omnibus' ),
				),
				/**
				 * Show on
				 */
				array(
					'title'         => __( 'Show on', 'omnibus' ),
					'desc'          => __( 'Course single', 'omnibus' ),
					'id'            => $this->get_name( 'product' ),
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'desc_tip'      => __( 'Show or hide on a single course page.', 'omnibus' ),
				),
				array(
					'desc'          => __( 'Any loop', 'omnibus' ),
					'id'            => $this->get_name( 'loop' ),
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'desc_tip'      => __( 'Show or hide on any courses list.', 'omnibus' ),
				),
				array(
					'desc'          => __( 'Taxonomy page', 'omnibus' ),
					'id'            => $this->get_name( 'tax' ),
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'desc_tip'      => __( 'Show or hide on any taxonomy (tags, categories, custom taxonomies).', 'omnibus' ),
				),
				array(
					'title'    => __( 'Default', 'omnibus' ),
					'id'       => $this->get_name( 'default' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc'     => __( 'Display anywhere else', 'omnibus' ),
					'desc_tip' => __( 'Display anywhere else that doesn\'t fit any of the above.', 'omnibus' ),
				),
				array(
					'title'   => esc_html__( 'Review courses', 'learnpress' ),
					'desc'    => esc_html__( 'Courses created by instructors will be pending review first.', 'learnpress' ),
					'id'      => 'required_review',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Auto start', 'learnpress' ),
					'id'      => 'auto_enroll',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Students will get started on courses immediately after successfully purchasing them.', 'learnpress' ),
				),
				/**
				 * admin
				 */
				array(
					'title'         => __( 'Show on admin on', 'omnibus' ),
					'desc'          => __( 'Courses list', 'omnibus' ),
					'id'            => $this->get_name( 'admin_list' ),
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
				),
				array(
					'desc'          => __( 'Course edit', 'omnibus' ),
					'id'            => $this->get_name( 'admin_edit' ),
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
				),
				$this->settings_days(),
				/**
				 * messages
				 */
				$this->settings_message_settings(),
				$this->settings_message(),
				array(
					'type' => 'sectionend',
				),
			)
		);
	}

	/**
	 * Save LearnPress Course
	 *
	 * @since 1.0.1
	 */
	public function action_learnpress_save_post_lp_course( $post_id, $post ) {
		if ( 'publish' !== get_post_status( $post ) ) {
			return;
		}
		$course = learn_press_get_course( $post_id );
		if ( ! is_a( $course, 'LP_Course' ) ) {
			return;
		}
		$price = $course->get_price();
		$this->save_price_history( $post_id, $price );
	}

	/**
	 * LearnPress: show prices in admin
	 *
	 * @since 1.0.1
	 */
	public function filter_learnpress_admin_show_omnibus( $configuration ) {
		if ( ! $this->should_it_show_up( $post_id ) ) {
			return $configuration;
		}
		if ( 'no' === get_option( $this->get_name( 'admin_edit' ) ) ) {
			return $configuration;
		}
		global $post_id;
		$price_lowest                                = $this->learnpress_get_lowest_price_in_history( $post_id );
		$configuration[ $this->meta_name . 'price' ] = new LP_Meta_Box_Text_Field(
			esc_html__( 'Omnibus Price', 'omnibus' ),
			sprintf(
				esc_html__( 'The lowest price in %d days.', 'omnibus' ),
				$this->get_days()
			),
			is_array( $price_lowest ) && isset( $price_lowest['price'] ) ? $price_lowest['price'] : esc_html__( 'no data available', 'omnibus' ),
			array(
				'type_input'        => 'text',
				'custom_attributes' => array(
					'readonly' => 'readonly',
				),
			)
		);
		$configuration[ $this->meta_name . 'date' ]  = new LP_Meta_Box_Text_Field(
			esc_html__( 'Omnibus Date', 'omnibus' ),
			sprintf(
				esc_html__( 'The date when lowest price in %d days occurred.', 'omnibus' ),
				$this->get_days()
			),
			is_array( $price_lowest ) && isset( $price_lowest['timestamp'] ) ? date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ) : esc_html__( 'no data available', 'omnibus' ),
			array(
				'type_input'        => 'text',
				'custom_attributes' => array(
					'readonly' => 'readonly',
				),
			)
		);
		return $configuration;
	}

	/**
	 * LearnPress: add Omnibus price information
	 *
	 * @since 1.0.1
	 */
	public function filter_learn_press_course_price_html( $price_html, $has_sale_price, $post_id ) {
		if ( ! $this->should_it_show_up( $post_id ) ) {
			return $price_html;
		}
		$price_lowest = $this->learnpress_get_lowest_price_in_history( $post_id );
		return $this->add_message( $price_html, $price_lowest, 'learn_press_format_price' );
	}

	/**
	 * LearnPress: get lowest price in days
	 *
	 * @since 1.0.1
	 */
	private function learnpress_get_lowest_price_in_history( $post_id ) {
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}
		$course = learn_press_get_course( $post_id );
		if ( ! is_a( $course, 'LP_Course' ) ) {
			return array();
		}
		return $this->_get_lowest_price_in_history( $course->get_price(), $post_id );
	}

	protected function get_name( $name = '', $add_prefix = '' ) {
		if ( empty( $name ) ) {
			return parent::get_name( 'lp' );
		}
		return parent::get_name( 'lp_' . $name );
	}

	/**
	 * helper to decide show it or no
	 */
	private function should_it_show_up( $post_id ) {
			// if ( 'no' === get_option( $this->get_name( 'learnpress' ), 'yes' ) ) {
				// return $price_html;
			// }
		/**
		 * for admin
		 */
		if ( is_admin() ) {
			$screen = get_current_screen();
			// d($screen);
			if ( 'lp_course' === $screen->id ) {
				if ( 'no' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
					return apply_filters( 'iworks_omnibus_show', false );
				}
			}
			if ( 'edit-product' === $screen->id ) {
				if ( 'no' === get_option( $this->get_name( 'admin_list' ), 'yes' ) ) {
					return apply_filters( 'iworks_omnibus_show', false );
				}
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * front-end
		 */
		if ( 'yes' === get_option( $this->get_name( 'on_sale' ), 'no' ) ) {
			// $product = wc_get_product( $post_id );
			// if ( ! $product->is_on_sale() ) {
				// return apply_filters( 'iworks_omnibus_show', false );
			// }
		}
		/**
		 * at least add filter
		 */
		$show = 'yes' === get_option( $this->get_name( 'default' ), 'no' );
		return apply_filters( 'iworks_omnibus_show', $show );
	}
}

