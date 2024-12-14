<?php
/*

Copyright 2024-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

include_once 'class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_learnpress extends iworks_omnibus_integration {

	public function __construct() {
		add_filter( 'learn_press_course_price_html', array( $this, 'filter_learn_press_course_price_html' ), 10, 3 );
		/**
		 * admin init
		 *
		 * @since 2.1.0
		 */
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		/**
		 * LP_Suubmenu
		 *
		 * @since 4.0.0
		 */
		add_filter( 'learn-press/admin/settings-tabs-array', array( $this, 'add_omnibus_to_settings' ) );
	}

	/**
	 * admin init
	 *
	 * @since 2.1.0
	 */
	public function action_admin_init() {
		add_action( 'updated_postmeta', array( $this, 'action_updated_postmeta_maybe_add_price_log' ), PHP_INT_MAX, 4 );
		add_filter( 'iworks/omnibus/learn-press/settings', array( $this, 'filter_learnpress_courses_settings_fields' ) );
		add_filter( 'lp/course/meta-box/fields/price', array( $this, 'filter_learnpress_admin_show_omnibus' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_add_link_omnibus_configuration' ), PHP_INT_MAX, 4 );
	}

	public function add_omnibus_to_settings( $settings ) {
		$settings['omnibus'] = include_once dirname( __FILE__ ) . '/class-iworks-omnibus-integration-learnpress-settings.php';
		return $settings;
	}

	/**
	 * Add configuration link to plugin_row_meta.
	 *
	 * @since 2.1.0
	 *
	 */
	public function filter_add_link_omnibus_configuration( $actions, $plugin_file, $plugin_data, $context ) {
		if ( 'learnpress/learnpress.php' !== $plugin_file ) {
			return $actions;
		}
		$settings_page_url  = add_query_arg(
			array(
				'page' => 'learn-press-settings',
				'tab'  => 'omnibus',
			),
			admin_url( 'admin.php' )
		);
		$actions['omnibus'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $settings_page_url ),
			__( 'Omnibus', 'omnibus' )
		);
		return $actions;
	}

	/**
	 * LearnPress: add confirmation fields
	 *
	 * @since 2.1.0
	 */
	public function filter_learnpress_courses_settings_fields( $settings ) {
		return array_merge(
			$settings,
			$this->get_settings()
		);
	}

	private function get_settings() {
		return array(
			$this->settings_title(),
			array(
				'title'   => __( 'Only on sale', 'omnibus' ),
				'id'      => $this->get_name( 'on_sale', '' ),
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
				'id'            => $this->get_name( 'product', '' ),
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'desc_tip'      => __( 'Show or hide on a single course page.', 'omnibus' ),
			),
			array(
				'id'            => $this->get_name( 'default', '' ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'desc'          => __( 'Display anywhere else', 'omnibus' ),
				'desc_tip'      => __( 'Display anywhere else that doesn\'t fit any of the above.', 'omnibus' ),
				'checkboxgroup' => 'end',
			),
			/**
			 * admin
			 */
			array(
				'title'         => __( 'Show on admin on', 'omnibus' ),
				'desc'          => __( 'Courses list', 'omnibus' ),
				'id'            => $this->get_name( 'admin_list', '' ),
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'          => __( 'Course edit', 'omnibus' ),
				'id'            => $this->get_name( 'admin_edit', '' ),
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
		);
	}

	/**
	 * Save LearnPress Course
	 *
	 * @since 4.0.0
	 */
	public function action_updated_postmeta_maybe_add_price_log( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_edit_lock' !== $meta_key ) {
			return;
		}
		if ( 'lp_course' !== get_post_type( $object_id ) ) {
			return;
		}
		$this->maybe_add_price_log( $object_id );
	}

	/**
	 * helper to add logs
	 *
	 * @since 4.0.0
	 */
	protected function maybe_add_price_log( $id ) {
		if ( 'publish' !== get_post_status( $id ) ) {
			return;
		}
		/**
		 * price regular
		 */
		$regular_price = get_post_meta( $id, '_lp_price', true ); // For LP version < 1.4.1.2
		if ( metadata_exists( 'post', $id, '_lp_regular_price' ) ) {
			$regular_price = get_post_meta( $id, '_lp_regular_price', true );
		}
		/**
		 * price sale from
		 */
		$price_sale_from = get_post_meta( $id, '_lp_sale_start', true );
		if ( empty( $price_sale_from ) ) {
			$price_sale_from = 'now';
		} else {
			$price_sale_from = date( $this->mysql_data_format, strtotime( $price_sale_from ) );
		}
		$data = array(
			'post_id'         => $id,
			'product_origin'  => 'learnpress',
			'product_type'    => get_post_type( $id ),
			'price_regular'   => $regular_price,
			'price_sale'      => get_post_meta( $id, '_lp_sale_price', true ),
			'price_sale_from' => $price_sale_from,
			'currency'        => learn_press_get_currency(),
			'user_id'         => get_current_user_id(),
		);
		$this->maybe_add_last_saved_prices( $data );
	}

	/**
	 * LearnPress: show prices in admin
	 *
	 * @since 1.0.1
	 */
	public function filter_learnpress_admin_show_omnibus( $configuration ) {
		$post_id = get_the_ID();
		if ( ! $this->should_it_show_up( $post_id ) ) {
			return $configuration;
		}
		if ( 'no' === get_option( $this->get_name( 'admin_edit' ) ) ) {
			return $configuration;
		}
		global $post_id;
		$course                                      = learn_press_get_course( $post_id );
		$price_lowest                                = $this->get_lowest_price_by_post_id( $course->get_id(), $course->get_sale_price() );
		$configuration[ $this->meta_name . 'price' ] = new LP_Meta_Box_Text_Field(
			esc_html__( 'Omnibus Price', 'omnibus' ),
			sprintf(
				/* translators: %d: nuber of days */
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
		$configuration[ $this->meta_name . 'date' ] = new LP_Meta_Box_Text_Field(
			esc_html__( 'Omnibus Date', 'omnibus' ),
			sprintf(
				/* translators: %d: nuber of days */
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
		$course = learn_press_get_course( $post_id );
		return $this->add_message_helper( $price_html, $course );
	}

	/**
	 * helper function to parent->add_message()
	 *
	 * @since 4.0.0
	 */
	private function add_message_helper( $price_html, $course, $message = null ) {
		$price_lowest = $this->get_lowest_price_by_post_id( $course->get_id(), $course->get_sale_price() );
		return $this->add_message(
			$price_html,
			$course->get_regular_price(),
			$course->get_sale_price(),
			$price_lowest,
			'learn_press_format_price',
			$message
		);
	}

	public function get_name( $name = '', $add_prefix = 'learn_press_' ) {
		if ( empty( $name ) ) {
			return $add_prefix . parent::get_name( 'lp' );
		}
		return $add_prefix . parent::get_name( 'lp_' . $name );
	}

	/**
	 * helper to decide show it or no
	 */
	protected function should_it_show_up( $post_id ) {
		if ( 'yes' === get_option( $this->get_name( 'on_sale' ), 'yes' ) ) {
			$course = learn_press_get_course( $post_id );
			if ( ! $course->has_sale_price() ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
		}
		/**
		 * for admin
		 */
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'lp_course' === $screen->id ) {
				if ( 'no' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
					return apply_filters( 'iworks_omnibus_show', false );
				}
			}
			if ( 'edit-lp_course' === $screen->id ) {
				if ( 'no' === get_option( $this->get_name( 'admin_list' ), 'yes' ) ) {
					return apply_filters( 'iworks_omnibus_show', false );
				}
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * front-end
		 */
		if (
			is_single()
			&& is_main_query()
			&& ( learn_press_is_course() )
		) {
			if ( 'no' === get_option( $this->get_name( 'single' ), 'yes' ) ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * at least add filter
		 */
		$show = 'yes' === get_option( $this->get_name( 'default' ), 'no' );
		return apply_filters( 'iworks_omnibus_show', $show );
	}

}

