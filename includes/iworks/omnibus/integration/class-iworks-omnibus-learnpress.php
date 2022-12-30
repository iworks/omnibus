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
		add_filter( 'iworks_omnibus_integration_woocommerce_settings', array( $this, 'add_settings' ) );
		add_filter( 'learn_press_course_price_html', array( $this, 'filter_learn_press_course_price_html' ), 10, 3 );
		add_filter( 'lp/course/meta-box/fields/price', array( $this, 'filter_learnpress_admin_show_omnibus' ) );
	}

	/**
	 * Add settings field to WooCmmerce settings
	 *
	 * @since 1.1.0
	 */
	public function add_settings( $settings ) {
		$settings[] = array(
			'id'   => $this->get_name( 'learnpress' ),
			'desc' => __( 'LearnPress Course', 'omnibus' ),
		);
		return $settings;
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
		if ( is_admin() ) {
			if ( 'no' === get_option( $this->get_name( 'admin_list' ) ) ) {
				return $price_html;
			}
		} else {
			if ( 'no' === get_option( $this->get_name( 'learnpress' ) ) ) {
				return $price_html;
			}
		}
		$price_lowest = $this->learnpress_get_lowest_price_in_history( $post_id );
		return $this->add_message( $price_html, $price_lowest, 'learn_press_format_price' );
	}

}

