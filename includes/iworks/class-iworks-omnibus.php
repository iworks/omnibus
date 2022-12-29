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

if ( class_exists( 'iworks_omnibus' ) ) {
	return;
}

class iworks_omnibus {

	private $meta_name = '_iwo_price_lowest';

	public function __construct() {
		/**
		 * WooCommerce
		 */
		add_action( 'woocommerce_product_options_pricing', array( $this, 'action_woocommerce_product_options_pricing' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'action_woocommerce_variation_options_pricing' ), 10, 3 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'action_woocommerce_save_price_history' ), 10, 1 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_woocommerce_get_price_html' ), 10, 2 );
		/**
		 * Tutor LMS
		 */
		add_filter( 'tutor_course_details_wc_add_to_cart_price', array( $this, 'filter_tutor_course_details_wc_add_to_cart_price' ), 10, 2 );
		/**
		 * LearnPress
		 */
		add_action( 'save_post_lp_course', array( $this, 'action_learnpress_save_post_lp_course' ), 10, 2 );
		add_filter( 'lp/course/meta-box/fields/price', array( $this, 'filter_learnpress_admin_show_omnibus' ) );
		add_filter( 'learn_press_course_price_html', array( $this, 'filter_learn_press_course_price_html' ), 10, 3 );
	}

	/**
	 * LearnPress: add Omnibus price information
	 *
	 * @since 1.0.1
	 */
	public function filter_learn_press_course_price_html( $price_html, $has_sale_price, $post_id ) {
		$price_lowest = $this->learnpress_get_lowest_price_in_30_days( $post_id );
		return $this->add_message( $price_html, $price_lowest, 'learn_press_format_price' );
	}

	/**
	 * LearnPress: save course
	 *
	 * @since 1.0.1
	 */
	public function action_learnpress_save_post_lp_course( $post_id, $post ) {
		if ( 'publish' !== get_post_status( $post ) ) {
			return;
		}
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}
		$course = learn_press_get_course( $post_id );
		$price  = $course->get_price();
		$this->save_price_history( $post_id, $price );
	}
	/**
	 * LearnPress: show prices in admin
	 *
	 * @since 1.0.1
	 */
	public function filter_learnpress_admin_show_omnibus( $configuration ) {
		global $post_id;
		$price_lowest                                = $this->learnpress_get_lowest_price_in_30_days( $post_id );
		$configuration[ $this->meta_name . 'price' ] = new LP_Meta_Box_Text_Field(
			esc_html__( 'Omnibus Price', 'omnibus' ),
			esc_html__( 'The lowest price in 30 days', 'omnibus' ),
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
			esc_html__( 'The date when lowest price in 30 days occurred.', 'omnibus' ),
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
	 * Tutor LMS with WooCommerce integration
	 *
	 * @since 1.0.1
	 */
	public function filter_tutor_course_details_wc_add_to_cart_price( $content, $product ) {
		return $this->filter_woocommerce_get_price_html( $content, $product );
	}

	/**
	 * WooCommerce: filter for HTML price
	 *
	 * @since 1.0.0
	 */
	public function filter_woocommerce_get_price_html( $price, $product ) {
		if ( 'variable' === $product->get_type() ) {
			return $this->woocommerce_get_price_html_for_variable( $price, $product );
		}
		$price_lowest = $this->woocommerce_get_lowest_price_in_30_days( $product->get_id() );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	/**
	 * WooCommerce: save price history
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_save_price_history( $product ) {
		$price = $product->get_price();
		if ( empty( $price ) ) {
			return;
		}
		$product_id = $product->get_id();
		$this->save_price_history( $product_id, $price );
	}

	/**
	 * WooCommerce: show Omnibus price & date for regular product
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_product_options_pricing() {
		$product_id   = intval( $_GET['post'] );
		$price_lowest = $this->woocommerce_get_lowest_price_in_30_days( $product_id );
		echo '<hr />';
		printf( '<p class="description">%s</p>', esc_html__( 'Omnibus Price', 'omnibus' ) );
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : $price_lowest['price'],
				'data_type'         => 'price',
				'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc_tip'          => true,
				'description'       => __( 'The lowest price in 30 days', 'omnibus' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ),
				'data_type'         => 'text',
				'label'             => __( 'Date', 'omnibus' ),
				'desc_tip'          => true,
				'description'       => __( 'The date when lowest price in 30 days occurred.', 'omnibus' ),
			)
		);
	}

	/**
	 * WooCommerce: show Omnibus price & date for variable product
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_variation_options_pricing( $loop, $variation_data, $variation ) {
		$product_id   = $variation->ID;
		$price_lowest = $this->woocommerce_get_lowest_price_in_30_days( $product_id );
		echo '</div>';
		echo '<div>';
		printf( '<p class="form-row form-row-full">%s</p>', esc_html__( 'Omnibus Price', 'omnibus' ) );
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : $price_lowest['price'],
				'data_type'         => 'price',
				'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc_tip'          => true,
				'description'       => __( 'The lowest price in 30 days', 'omnibus' ),
				'wrapper_class'     => 'form-row form-row-first',
			)
		);
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ),
				'data_type'         => 'text',
				'label'             => __( 'Date', 'omnibus' ),
				'desc_tip'          => true,
				'description'       => __( 'The date when lowest price in 30 days occurred.', 'omnibus' ),
				'wrapper_class'     => 'form-row form-row-last',
			)
		);
	}

	/**
	 * Save price history
	 *
	 * @since 1.0.0
	 */
	private function save_price_history( $product_id, $price ) {
		$price_last = $this->get_last_price( $product_id );
		if ( 'unknown' === $price_last ) {
			$this->add_price_log( $product_id, $price );
		}
		if (
			is_array( $price_last )
			&& $price !== $price_last['price']
		) {
			$this->add_price_log( $product_id, $price );
		}
	}

	/**
	 * WooCommerce: get price HTML for variable product
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_get_price_html_for_variable( $price, $product ) {
		$price_lowest = array();
		foreach ( $product->get_available_variations() as $variable ) {
			$o = $this->woocommerce_get_lowest_price_in_30_days( $variable['variation_id'] );
			if ( ! isset( $price_lowest['price'] ) ) {
				$price_lowest = $o;
				continue;
			}
			if ( $o['price'] < $price_lowest['price'] ) {
				$price_lowest = $o;
			}
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	/**
	 * Add Omnibus message to price.
	 *
	 * @since 1.0.0
	 */
	private function add_message( $price, $price_lowest, $format_price_callback = null ) {
		if (
			is_array( $price_lowest )
			&& isset( $price_lowest['price'] )
		) {
			if ( is_callable( $format_price_callback ) ) {
				$price_lowest['price'] = $format_price_callback( $price_lowest['price'] );
			}
			$price .= sprintf(
				'<p class="iworks-omnibus">%s</p>',
				sprintf(
					__( 'The lowest price in 30 days: %s', 'omnibus' ),
					$price_lowest['price']
				)
			);
		}
		return $price;
	}

	/**
	 * get last recorded price
	 *
	 * @since 1.0.0
	 */
	private function get_last_price( $product_id ) {
		$meta = get_post_meta( $product_id, $this->meta_name );
		if ( empty( $meta ) ) {
			return 'unknown';
		}
		$old       = strtotime( '-30 days' );
		$timestamp = 0;
		$last      = array();
		foreach ( $meta as $data ) {
			if ( $old > $data['timestamp'] ) {
				// delete_post_meta( $product_id, $this->meta_name, $data );
				continue;
			}
			if ( $timestamp < $data['timestamp'] ) {
				$timestamp = $data['timestamp'];
				$last      = $data;
			}
		}
		return $last;
	}

	/**
	 * Add price log
	 *
	 * @since 1.0.0
	 */
	private function add_price_log( $post_id, $price ) {
		$data = array(
			'price'     => $price,
			'timestamp' => time(),
		);
		add_post_meta( $post_id, $this->meta_name, $data );
	}

	/**
	 * WooCommerce: get lowest price in 30 days
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_get_lowest_price_in_30_days( $post_id ) {
		$product = wc_get_product( $post_id );
		$lowest  = $product->get_price();
		return $this->_get_lowest_price_in_30_days( $lowest, $post_id );
	}

	/**
	 * LearnPress: get lowest price in 30 days
	 *
	 * @since 1.0.1
	 */
	private function learnpress_get_lowest_price_in_30_days( $post_id ) {
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}
		$course = learn_press_get_course( $post_id );
		if ( ! is_a( $course, 'LP_Course' ) ) {
			return array();
		}
		return $this->_get_lowest_price_in_30_days( $course->get_price(), $post_id );
	}

	/**
	 * Get lowest price in 30 days
	 *
	 * @since 1.0.0
	 */
	private function _get_lowest_price_in_30_days( $lowest, $product_id ) {
		$meta         = get_post_meta( $product_id, $this->meta_name );
		$price_lowest = array();
		if ( empty( $meta ) ) {
			return $price_lowest;
		}
		$price = array();
		$old   = strtotime( '-30 days' );
		foreach ( $meta as $data ) {
			if ( $old > $data['timestamp'] ) {
				// delete_post_meta( $product_id, $this->meta_name, $data );
				continue;
			}
			if ( $data['price'] <= $lowest ) {
				$price  = $data;
				$lowest = $data['price'];
			}
		}
		return $price;
	}

}
