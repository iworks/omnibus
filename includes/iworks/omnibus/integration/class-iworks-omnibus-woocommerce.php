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

if ( class_exists( 'iworks_omnibus_integration_woocommerce' ) ) {
	return;
}

include_once dirname( dirname( __FILE__ ) ) . '/class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_woocommerce extends iworks_omnibus_integration {

	public function __construct() {
		/**
		 * WooCommerce
		 *
		 * @since 1.0.0
		 */
		add_action( 'woocommerce_after_product_object_save', array( $this, 'action_woocommerce_save_price_history' ), 10, 1 );
		add_action( 'woocommerce_product_options_pricing', array( $this, 'action_woocommerce_product_options_pricing' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'action_woocommerce_variation_options_pricing' ), 10, 3 );
		add_filter( 'woocommerce_get_sections_products', array( $this, 'filter_woocommerce_get_sections_products' ), 999 );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'filter_woocommerce_get_settings_for_section' ), 10, 2 );
		/**
		 * WooCommerce bind message
		 *
		 * @since 1.1.0
		 */
		$where = get_option( $this->get_name( 'where' ) );
		switch ( $where ) {
			case 'woocommerce_product_meta_start':
			case 'woocommerce_product_meta_end':
				add_action( $where, array( $this, 'run' ) );
				break;
			case 'the_content_start':
			case 'the_content_end':
				add_filter( 'the_content', array( $this, 'filter_the_content' ) );
				break;
			default:
				add_filter( 'woocommerce_get_price_html', array( $this, 'filter_woocommerce_get_price_html' ), 10, 2 );
		}
		/**
		 * Tutor LMS (as relatedo to WooCommerce)
		 *
		 * @since 1.0.1
		 */
		add_filter( 'tutor_course_details_wc_add_to_cart_price', array( $this, 'filter_tutor_course_details_wc_add_to_cart_price' ), 10, 2 );
		/**
		 * YITH WooCommerce Product Bundles
		 *
		 * @since 1.1.0
		 */
		add_action( 'yith_wcpb_after_product_bundle_options_tab', array( $this, 'action_woocommerce_product_options_pricing' ) );
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
		$post_id = $product->get_id();
		$this->save_price_history( $post_id, $price );
	}

	/**
	 * WooCommerce: show Omnibus price & date for regular product
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_product_options_pricing() {
		if ( 'no' === get_option( $this->get_name( 'admin_edit' ) ) ) {
			return;
		}
		global $post_id;
		$price_lowest = $this->woocommerce_get_lowest_price_in_history( $post_id );
		$this->print_header( 'description' );
		$this->woocommerce_wp_text_input_price( $price_lowest );
		$this->woocommerce_wp_text_input_date( $price_lowest );
	}

	/**
	 * WooCommerce: show Omnibus price & date for variable product
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_variation_options_pricing( $loop, $variation_data, $variation ) {
		if ( 'no' === get_option( $this->get_name( 'admin_edit' ) ) ) {
			return;
		}
		$post_id      = $variation->ID;
		$price_lowest = $this->woocommerce_get_lowest_price_in_history( $post_id );
		echo '</div>';
		echo '<div>';
		$this->print_header( 'form-row form-row-full' );
		$configuration = array(
			'wrapper_class' => 'form-row form-row-first',
		);
		$this->woocommerce_wp_text_input_price( $price_lowest, $configuration );
		$configuration = array(
			'wrapper_class' => 'form-row form-row-last',
		);
		$this->woocommerce_wp_text_input_date( $price_lowest, $configuration );
	}

	/**
	 * WooCommerce: filter for HTML price
	 *
	 * @since 1.0.0
	 */
	public function filter_woocommerce_get_price_html( $price, $product ) {
		if (
			is_admin()
			&& 'no' === get_option( $this->get_name( 'admin_list' ) )
		) {
			return $price;
		}
		$price_lowest = $this->get_lowest_price( $product );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	private function get_lowest_price( $product ) {
		$product_type = $product->get_type();
		switch ( $product_type ) {
			case 'variable':
				return $this->woocommerce_get_price_html_for_variable( $price, $product );
			default:
				if (
				get_post_type() === $product_type
				|| get_post_type() === 'product'
				) {
					if (
					'no' === get_option( $this->get_name( $product_type ) )
					) {
						return $price;
					}
				} else {
					if ( 'courses' === get_post_type() ) {
						if (
						defined( 'TUTOR_VERSION' )
						&& 'no' === get_option( $this->get_name( 'tutor' ) )
						) {
							return $price;
						}
					}
				}
		}
		return $this->woocommerce_get_lowest_price_in_history( $product->get_id() );
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
	 * Add section tab to WooCommerce Settings
	 *
	 * @since 1.1.0
	 */
	public function filter_woocommerce_get_sections_products( $sections ) {
		$sections[ $this->meta_name ] = __( 'Omnibus Directive', 'omnibus' );
		return $sections;
	}

	/**
	 * WooCommerce: get lowest price in history
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_get_lowest_price_in_history( $post_id ) {
		$product = wc_get_product( $post_id );
		$lowest  = $product->get_price();
		return $this->_get_lowest_price_in_history( $lowest, $post_id );
	}

	/**
	 * WooCommerce: get price HTML for variable product
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_get_price_html_for_variable( $price, $product ) {
		if ( 'no' === get_option( $this->get_name( 'variable' ) ) ) {
			return $price;
		}
		$price_lowest = array();
		foreach ( $product->get_available_variations() as $variable ) {
			$o = $this->woocommerce_get_lowest_price_in_history( $variable['variation_id'] );
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

	private function woocommerce_wp_text_input_price( $price_lowest, $configuration = array() ) {
		woocommerce_wp_text_input(
			wp_parse_args(
				array(
					'id'                => $this->meta_name . '_price',
					'custom_attributes' => array( 'disabled' => 'disabled' ),
					'value'             => empty( $price_lowest ) ? '' : $price_lowest['price'],
					'data_type'         => 'price',
					'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'desc_tip'          => true,
					'description'       => sprintf(
						__( 'The lowest price in %d days.', 'omnibus' ),
						$this->get_days()
					),
				),
				$configuration
			)
		);
	}

	/**
	 * WooCommerce text field helper
	 *
	 * @since 1.1.0
	 */
	private function woocommerce_wp_text_input_date( $price_lowest, $configuration = array() ) {
		woocommerce_wp_text_input(
			wp_parse_args(
				array(
					'id'                => $this->meta_name . '_date',
					'custom_attributes' => array( 'disabled' => 'disabled' ),
					'value'             => empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ),
					'data_type'         => 'text',
					'label'             => __( 'Date', 'omnibus' ),
					'desc_tip'          => true,
					'description'       => sprintf(
						__( 'The date when lowest price in %d days occurred.', 'omnibus' ),
						$this->get_days()
					),
				),
				$configuration
			)
		);
	}

	/**
	 * WooCommerce header helper
	 *
	 * @since 1.1.0
	 */
	private function print_header( $class ) {
		printf(
			'<h3 class="%s">%s</h3>',
			esc_attr( $class ),
			esc_html__( 'Omnibus Directive', 'omnibus' )
		);
	}

	/**
	 * WooCommerce: Settings Page
	 *
	 * @since 1.1.0
	 */
	public function filter_woocommerce_get_settings_for_section( $settings, $section_id ) {
		if ( $section_id !== $this->meta_name ) {
			return $settings;
		}
		$settings = array(
			array(
				'title' => __( 'Omnibus Directive Settings', 'omnibus' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => $this->meta_name,
			),
		);
		$products = array(
			array(
				'desc' => __( 'Simple product', 'omnibus' ),
				'id'   => $this->get_name( 'simple' ),
			),
			array(
				'desc' => __( 'Variable product: global', 'omnibus' ),
				'id'   => $this->get_name( 'variable' ),
			),
			array(
				'desc' => __( 'Variable product: variation', 'omnibus' ),
				'id'   => $this->get_name( 'variation' ),
			),
		);
		/**
		 * Tutor LMS (as relatedo to WooCommerce)
		 *
		 * @since 1.0.1
		 */
		if ( defined( 'TUTOR_VERSION' ) ) {
			$products[] = array(
				'desc' => __( 'Tutor course', 'omnibus' ),
				'id'   => $this->get_name( 'tutor' ),
			);
		}
		/**
		 * YITH WooCommerce Product Bundles
		 *
		 * @since 1.1.0
		 */
		if ( defined( 'YITH_WCPB_VERSION' ) ) {
			$products[] = array(
				'desc' => __( 'YITH Bundle', 'omnibus' ),
				'id'   => $this->get_name( 'yith_bundle' ),
			);
		}
		/**
		 * filter avaialble products list
		 *
		 * @since 1.1.0
		 */
		$products = apply_filters( 'iworks_omnibus_integration_woocommerce_settings', $products );
		/**
		 * add to Settings
		 */
		foreach ( $products as $index => $one ) {
			if ( 0 === $index ) {
				$one['title']         = __( 'Show on front-end for', 'omnibus' );
				$one['checkboxgroup'] = 'start';
			}
			$one = wp_parse_args(
				$one,
				array(
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
				)
			);
			if ( ( 1 + $index ) === count( $products ) ) {
				$one['checkboxgroup'] = 'end';
			}
			$settings[] = $one;
		}
		/**
		 * admin
		 */
		$settings[] = array(
			'title'         => __( 'Show on admin on', 'omnibus' ),
			'desc'          => __( 'Products list', 'omnibus' ),
			'id'            => $this->get_name( 'admin_list' ),
			'default'       => 'yes',
			'type'          => 'checkbox',
			'checkboxgroup' => 'start',
		);
		$settings[] = array(
			'desc'          => __( 'Product edit', 'omnibus' ),
			'id'            => $this->get_name( 'admin_edit' ),
			'default'       => 'yes',
			'type'          => 'checkbox',
			'checkboxgroup' => 'end',
		);
		$settings[] = array(
			'title'             => __( 'Number of days', 'omnibus' ),
			'desc'              => __( 'This controls the number of days to show. According to the Omnibus Directive, minimum days is 30.', 'omnibus' ),
			'id'                => $this->get_name( 'days' ),
			'default'           => '30',
			'type'              => 'number',
			'desc_tip'          => true,
			'custom_attributes' => array(
				'min' => 30,
			),
		);
		$settings[] = array(
			'title'   => __( 'Where to display', 'omnibus' ),
			'desc'    => __( 'Change if you have only single products.', 'omnibus' ),
			'id'      => $this->get_name( 'where' ),
			'default' => 'woocommerce_get_price_html',
			'type'    => 'select',
			'options' => array(
				'woocommerce_get_price_html'     => __( 'After price (recommnded)', 'omnibus' ),
				'woocommerce_product_meta_start' => __( 'Before product meta data', 'omnibus' ),
				'woocommerce_product_meta_end'   => __( 'After product meta data', 'omnibus' ),
				'the_content_start'              => __( 'At the begining of the content', 'omnibus' ),
				'the_content_end'                => __( 'At the begining of the content', 'omnibus' ),
			),
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => $this->get_name( 'sectionend' ),
		);
		return $settings;
	}

	/**
	 * run helper
	 *
	 * @since 1.1.0
	 */
	public function run( $context = 'view' ) {
		if ( ! is_single() ) {
			return;
		}
		$product = wc_get_product( get_the_ID() );
		if ( empty( $product ) ) {
			return;
		}
		$price_lowest = $this->get_lowest_price( $product );
		if ( empty( $price_lowest ) ) {
			return;
		}
		$message = $this->add_message( '', $price_lowest, 'wc_price' );
		if ( 'return' === $context ) {
			return $message;
		}
		echo $message;
	}

	/**
	 * the_content filter
	 *
	 * @since 1.1.0
	 */
	public function filter_the_content( $content ) {
		$message = $this->run( 'return' );
		switch ( get_option( $this->get_name( 'where' ) ) ) {
			case 'the_content_start':
				$content = $message . $content;
				break;
			case 'the_content_end':
				$content .= $message;
				break;
		}
		return $content;
	}

}
