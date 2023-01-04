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
		 * Show message
		 *
		 * @since 1.2.3
		 */
		add_shortcode( 'omnibus_price_message', array( $this, 'shortcode' ) );
		/**
		 * own action
		 */
		add_action( 'iworks_omnibus_wc_lowest_price_message', array( $this, 'action_get_message' ) );
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
		 * WooCommerce: exclude meta
		 *
		 * @since 2.0.3
		 */
		add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'filter_woocommerce_duplicate_product_exclude_meta' ), 10, 2 );
		/**
		 * WooCommerce bind message
		 *
		 * @since 1.1.0
		 */
		$where = get_option( $this->get_name( 'where' ), 'woocommerce_get_price_html' );
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
		$price = $this->get_price( $product );
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
		global $post_id;
		if ( ! $this->should_it_show_up( $post_id ) ) {
			return;
		}
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
		$post_id = $variation->ID;
		if ( ! $this->should_it_show_up( $post_id ) ) {
			return;
		}
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
	 * helper to decide show it or no
	 */
	private function should_it_show_up( $post_id ) {
		/**
		 * for admin
		 */
		if ( is_admin() ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if (
					isset( $_POST['action'] )
					&& 'woocommerce_load_variations' === $_POST['action']
				) {
					if ( 'no' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
						return apply_filters( 'iworks_omnibus_show', false );
					}
				}
			} else {
				$screen = get_current_screen();
				if ( 'product' === $screen->id ) {
					if ( 'no' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
						return apply_filters( 'iworks_omnibus_show', false );
					}
				}
				if ( 'edit-product' === $screen->id ) {
					if ( 'no' === get_option( $this->get_name( 'admin_list' ), 'yes' ) ) {
						return apply_filters( 'iworks_omnibus_show', false );
					}
				}
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * front-end
		 */
		if ( 'yes' === get_option( $this->get_name( 'on_sale' ), 'no' ) ) {
			$product = wc_get_product( $post_id );
			if ( ! $product->is_on_sale() ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
		}
		if ( is_single() && is_main_query() ) {
			if ( is_product() ) {
				global $woocommerce_loop;
				if (
					is_array( $woocommerce_loop )
					&& isset( $woocommerce_loop['name'] )
					&& 'related' === $woocommerce_loop['name']
				) {
					if ( 'no' === get_option( $this->get_name( 'related' ), 'no' ) ) {
						return apply_filters( 'iworks_omnibus_show', false );
					}
				}
			}
			if ( 'no' === get_option( $this->get_name( 'single' ), 'yes' ) ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * shop page
		 */
		if ( is_shop() ) {
			if ( 'no' === get_option( $this->get_name( 'shop' ), 'no' ) ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * Taxonomy Page
		 */
		if ( is_tax() ) {
			if ( 'no' === get_option( $this->get_name( 'tax' ), 'no' ) ) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
			return apply_filters( 'iworks_omnibus_show', true );
		}
		/**
		 * any loop
		 */
		if ( in_the_loop() ) {
			if ( 'no' === get_option( $this->get_name( 'loop' ), 'no' ) ) {
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

	/**
	 * WooCommerce: filter for HTML price
	 *
	 * @since 1.0.0
	 */
	public function filter_woocommerce_get_price_html( $price, $product ) {
		if ( ! $this->should_it_show_up( $product->id ) ) {
			return $price;
		}
		$price_lowest = $this->get_lowest_price( $product );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	private function get_lowest_price( $product ) {
		/**
		 * get price
		 *
		 * @since 2.0.2
		 */
		$price = $this->get_price( $product );
		if ( empty( $price ) ) {
			return;
		}
		$product_type = $product->get_type();
		switch ( $product_type ) {
			case 'variable':
				$price_lowest = $this->woocommerce_get_price_html_for_variable( $price, $product );
				return apply_filters( 'iworks_omnibus_integration_woocommerce_price_lowest', $price_lowest, $product );
			default:
				if (
				get_post_type() === $product_type
				|| get_post_type() === 'product'
				) {
					if (
					'no' === get_option( $this->get_name( $product_type ), 'yes' )
					) {
						return $price;
					}
				} else {
					if ( 'courses' === get_post_type() ) {
						if (
						defined( 'TUTOR_VERSION' )
						&& 'no' === get_option( $this->get_name( 'tutor' ), 'yes' )
						) {
							return $price;
						}
					}
				}
		}
		$price_lowest = $this->woocommerce_get_lowest_price_in_history( $product->get_id() );
		return apply_filters( 'iworks_omnibus_integration_woocommerce_price_lowest', $price_lowest, $product );
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
		/**
		 * check is object
		 *
		 * @since 1.2.1
		 */
		if ( ! is_object( $product ) ) {
			return;
		}
		/**
		 * get price
		 *
		 * @since 2.0.2
		 */
		$price  = $this->get_price( $product );
		$lowest = $this->_get_lowest_price_in_history( $price, $post_id );
		if ( empty( $lowest ) ) {
			$lowest = array(
				'price'     => $price,
				'timestamp' => time(),
			);
		}
		if ( isset( $lowest['price'] ) ) {
			$lowest['qty']                 = 1;
			$lowest['price_including_tax'] = wc_get_price_including_tax( $product, $lowest );
		}
		return $lowest;
	}

	/**
	 * WooCommerce: get price HTML for variable product
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_get_price_html_for_variable( $price, $product ) {
		if ( 'no' === get_option( $this->get_name( 'variable' ), 'yes' ) ) {
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
		return $price_lowest;
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
	 * WooCommerce: Settings Page
	 *
	 * @since 1.1.0
	 */
	public function filter_woocommerce_get_settings_for_section( $settings, $section_id ) {
		if ( $section_id !== $this->meta_name ) {
			return $settings;
		}
		$settings = array(
			$this->settings_title(),
			array(
				'title'   => __( 'Products on sale', 'omnibus' ),
				'id'      => $this->get_name( 'on_sale' ),
				'default' => 'yes',
				'type'    => 'checkbox',
				'desc'    => __( 'Display only for the product on sale', 'omnibus' ),
			),
			/**
			 * Show on
			 */
			array(
				'title'         => __( 'Show on', 'omnibus' ),
				'desc'          => __( 'Product single', 'omnibus' ),
				'id'            => $this->get_name( 'product' ),
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'desc_tip'      => __( 'Show or hide on a single product page.', 'omnibus' ),
			),
			array(
				'desc'          => __( 'WooCommerce Shop page', 'omnibus' ),
				'id'            => $this->get_name( 'shop' ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc_tip'      => sprintf(
					__( 'Show or hide on the shop page (<a href="%s#woocommerce_shop_page_id">WooCommerce Shop Page ID</a>).', 'omnibus' ),
					add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => 'products',
						),
						admin_url( 'admin.php' )
					)
				),
			),
			array(
				'desc'          => __( 'Any loop', 'omnibus' ),
				'id'            => $this->get_name( 'loop' ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc_tip'      => __( 'Show or hide on any product list.', 'omnibus' ),
			),
			array(
				'desc'          => __( 'Taxonomy page', 'omnibus' ),
				'id'            => $this->get_name( 'tax' ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc_tip'      => __( 'Show or hide on any taxonomy (tags, categories, custom taxonomies).', 'omnibus' ),
			),
			array(
				'desc'          => __( 'Related products list', 'omnibus' ),
				'id'            => $this->get_name( 'related' ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'desc_tip'      => __( 'Show or hide on the related products box.', 'omnibus' ),
			),
			array(
				'title'    => __( 'Default', 'omnibus' ),
				'id'       => $this->get_name( 'default' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => __( 'Display anywhere else', 'omnibus' ),
				'desc_tip' => __( 'Display anywhere else that doesn\'t fit any of the above.', 'omnibus' ),
			),
		);
		if ( 'no' === get_option( 'woocommerce_prices_include_tax', 'no' ) ) {
			$settings[] = array(
				'title'   => __( 'Include tax', 'omnibus' ),
				'id'      => $this->get_name( 'include_tax' ),
				'default' => 'yes',
				'type'    => 'checkbox',
				'desc'    => __( 'Display price with tax', 'omnibus' ),
			);
		}
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
				$one['title']         = __( 'Show for type', 'omnibus' );
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
		$settings[] = $this->settings_days();
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
				'the_content_end'                => __( 'At the end of the content', 'omnibus' ),
			),
		);
		/**
		 * messages
		 */
		$settings[] = $this->settings_message_settings();
		$settings[] = $this->settings_message();

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
	public function run( $context = 'view', $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		$product = wc_get_product( $post_id );
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
		switch ( get_option( $this->get_name( 'where' ), 'woocommerce_get_price_html' ) ) {
			case 'the_content_start':
				$content = $message . $content;
				break;
			case 'the_content_end':
				$content .= $message;
				break;
		}
		return $content;
	}

	/**
	 * get message by id
	 */
	public function action_get_message( $post_id = null ) {
		$this->run( 'view', $post_id );
	}

	/**
	 * shortcode to get message
	 *
	 * @since 1.2.3
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array( 'id' => null ),
			$atts,
			'iworks_omnibus_wc_lowest_price_message'
		);
		if ( empty( $atts['id'] ) ) {
			$atts['id'] = get_the_ID();
		}
		if ( empty( $atts['id'] ) ) {
			return;
		}
		return $this->run( 'return', $atts['id'] );
	}

	/**
	 * get price helper
	 *
	 * @since 2.0.2
	 */
	private function get_price( $product ) {
		/**
		 * check method_exists
		 *
		 * @since 1.2.1
		 */
		if ( ! is_object( $product ) ) {
			return;
		}
		/**
		 * check method_exists
		 *
		 * @since 1.2.1
		 */
		if ( ! method_exists( $product, 'get_sale_price' ) ) {
			return;
		}
		$price = $product->get_sale_price();
		if ( empty( $price ) ) {
			$price = $product->get_price();
		}
		return $price;
	}

	/**
	 * Filter to allow us to exclude meta keys from product duplication..
	 *
	 * @param array $exclude_meta The keys to exclude from the duplicate.
	 * @param array $existing_meta_keys The meta keys that the product already has.
	 *
	 * @since 2.0.3
	 */
	public function filter_woocommerce_duplicate_product_exclude_meta( $meta_to_exclude, $existing_meta_keys ) {
		$meta_to_exclude[] = $this->meta_name;
		$meta_to_exclude[] = $this->last_price_drop_timestamp;
		return $meta_to_exclude;
	}

}
