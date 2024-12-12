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

include_once 'class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_woocommerce extends iworks_omnibus_integration {

	public function __construct() {
		/**
		 * add Settings Section
		 *
		 * @since 2.3.0
		 */
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'filter_woocommerce_get_settings_pages' ) );
		/**
		 * Show message
		 *
		 * @since 1.2.3
		 */
		add_shortcode( 'omnibus_price_message', array( $this, 'shortcode' ) );
		/**
		 * get lowest price
		 *
		 * @since 2.3.2
		 */
		add_filter( 'iworks_omnibus_wc_get_lowest_price', array( $this, 'filter_wc_get_lowest_price' ), 10, 2 );
		/**
		 * own action
		 */
		add_action( 'iworks_omnibus_wc_lowest_price_message', array( $this, 'action_get_message' ) );
		add_filter( 'iworks_omnibus_get_name', array( $this, 'get_name' ) );
		add_filter( 'iworks_omnibus_message_template', array( $this, 'filter_iworks_omnibus_message_template_for_admin_list' ) );
		add_filter( 'iworks_omnibus_message_template', array( $this, 'filter_iworks_omnibus_message_template_for_product' ), 10, 3 );
		/**
		 * action to call save_price_history()
		 *
		 * @since 2.5.2
		 */
		add_action( 'iworks_omnibus/wc/save_price_history/action', array( $this, 'action_iworks_omnibus_wc_save_price_history' ), 10, 2 );
		/**
		 * WooCommerce Omnibus Price History Meta Box
		 *
		 * @since 2.4.0
		 */
		add_action( 'add_meta_boxes_product', array( $this, 'action_add_meta_boxes_product_history' ), 4000 );
		/**
		 * admin init
		 *
		 * @since 2.1.0
		 */
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		/**
		 * maybe save initial data
		 *
		 * @since 2.2.2
		 */
		add_action( 'shutdown', array( $this, 'action_shutdown_maybe_save_product_price' ) );
		/**
		 * WooCommerce
		 *
		 * @since 1.0.0
		 */
		add_action( 'woocommerce_after_product_object_save', array( $this, 'action_woocommerce_save_maybe_save_short' ), 10, 1 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'action_woocommerce_save_price_history' ), 10, 1 );
		add_action( 'woocommerce_product_options_pricing', array( $this, 'action_woocommerce_product_options_pricing' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'action_woocommerce_variation_options_pricing' ), 10, 3 );
		add_action( 'save_post_product', array( $this, 'action_save_post_product' ), 10, 3 );
		/**
		 * Settings
		 *
		 * @depreacated since 2.3.0
		 */
		add_filter( 'woocommerce_get_sections_products', array( $this, 'filter_woocommerce_get_sections_products' ), 999 );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'filter_woocommerce_get_settings_for_section' ), 10, 2 );
		/**
		 * WooCommerce bind message
		 *
		 * @since 1.1.0
		 */
		$where = get_option( $this->get_name( 'where' ), 'woocommerce_get_price_html' );
		switch ( $where ) {
			case 'do_not_show':
				break;
			case 'woocommerce_after_add_to_cart_button':
			case 'woocommerce_after_add_to_cart_quantity':
			case 'woocommerce_after_single_product_summary':
			case 'woocommerce_before_add_to_cart_button':
			case 'woocommerce_before_add_to_cart_form':
			case 'woocommerce_before_add_to_cart_quantity':
			case 'woocommerce_before_single_product_summary':
			case 'woocommerce_product_meta_end':
			case 'woocommerce_product_meta_start':
			case 'woocommerce_single_product_summary':
				add_action( $where, array( $this, 'action_check_and_add_message' ) );
				break;
			case 'the_content_start':
			case 'the_content_end':
				add_filter( 'the_content', array( $this, 'filter_the_content' ) );
				break;
			default:
				add_filter( 'woocommerce_get_price_html', array( $this, 'filter_woocommerce_get_price_html' ), 10, 2 );
		}
		/**
		 * WooCommerce show in cart
		 *
		 * @since 2.1.5
		 */
		if ( 'yes' === get_option( $this->get_name( 'cart' ), 'no' ) ) {
			add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_woocommerce_cart_item_price' ), 10, 3 );
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
		/**
		 * get price log
		 */
		add_filter( 'iworks_omnibus_price_log_array', array( $this, 'filter_get_log_array' ), 10, 2 );
		add_filter( 'iworks_omnibus_prices_array', array( $this, 'filter_get_prices_array' ), 10, 2 );
		/**
		 * register hooks
		 *
		 * @since 4.0.0
		 */
		add_action( 'iworks/omnibus/register_deactivation_hook', array( $this, 'register_deactivation_hook' ) );
	}

	public function action_admin_head() {
		?>
<style type="text/css" media="screen" id="iworks_omnibus">
.woocommerce_variable_attributes .iworks_omnibus_field_checkbox {
	display: grid;
	grid-template-columns: 2em auto;
	grid-template-areas: "checkbox label" "description description";
	align-items: center;
	clear: both;
}
.woocommerce_variable_attributes .iworks_omnibus_field_checkbox .checkbox {
	grid-area: checkbox;
}
.woocommerce_variable_attributes .iworks_omnibus_field_checkbox .description {
	grid-area: description;
}
#omnibus-migration-v3-form progress {
	border-radius: 0;
	height: 20px;
	max-width: 400px;
	width: 100%;
}
</style>
		<?php
	}

	/**
	 * Save Product
	 *
	 * @since 2.3.1
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 *
	 */
	public function action_save_post_product( $post_id, $post, $update ) {
		if ( 'product' !== get_post_type( $post ) ) {
			return;
		}
		if ( 'publish' !== get_post_status( $post ) ) {
			return;
		}
		/**
		 * maybe update price log
		 */
		$product = wc_get_product( $post );
		$data    = array(
			'omnibus_id'   => $post_id,
			'origin'       => 'woocommerce',
			'product_type' => $product->get_type(),
			'price'        => $product->get_regular_price(),
			'price_sale'   => $product->get_sale_price(),
			'sale_from'    => $product->get_date_on_sale_from(),
		);
		$this->maybe_update_last_saved_prices( $data );
	}

	/**
	 * admin init
	 *
	 * @since 2.1.0
	 */
	public function action_admin_init() {
		add_filter( 'plugin_action_links', array( $this, 'filter_add_link_omnibus_configuration' ), PHP_INT_MAX, 4 );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_register' ) );
		add_action( 'load-woocommerce_page_wc-settings', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'action_admin_head' ) );
		/**
		 * add extra data to price log
		 */
		add_filter( 'iworks_omnibus_add_price_log_data', array( $this, 'ilter_add_price_log_data' ), 10, 2 );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->get_name( __CLASS__ ) );
	}

	/**
	 * Enqueue scripts for all admin pages.
	 *
	 * @since 2.3.0
	 */
	public function action_admin_enqueue_scripts_register() {
		wp_register_script(
			$this->get_name( __CLASS__ ),
			plugins_url( 'assets/scripts/admin/woocommerce.min.js', dirname( dirname( dirname( __DIR__ ) ) ) ),
			array( 'jquery' ),
			'PLUGIN_VERSION'
		);
	}

	/**
	 * WooCommerce: save price history
	 *
	 * @since 1.0.0
	 */
	public function action_woocommerce_save_price_history( $product ) {
		$this->save_price_history(
			$product->get_id(),
			$this->get_prices_by_product( $product )
		);
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
		$this->woocommerce_wp_checkbox_short( $post_id );
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
		$this->print_header( 'form-row form-row-full' );
		$configuration = array(
			'wrapper_class' => 'form-row form-row-first',
		);
		$this->woocommerce_wp_text_input_price( $price_lowest, $configuration );
		$configuration = array(
			'wrapper_class' => 'form-row form-row-last',
		);
		$this->woocommerce_wp_text_input_date( $price_lowest, $configuration );
		// echo '</div>';
		// echo '<div>';
		$configuration = array(
			'wrapper_class' => 'form-row form-row-full',
		);
		$this->woocommerce_wp_checkbox_short( $post_id, $configuration, $loop );
	}

	public function maybe_add_text( $state ) {
		if ( $state && 'inform' === get_option( $this->get_name( 'missing' ) ) ) {
			return $this->get_message_text( 'no_data' );
		}
		return $state;
	}

	/**
	 * helper to decide show it or no
	 */
	private function should_it_show_up( $post_id ) {
		add_filter( 'iworks_omnibus_show', array( $this, 'maybe_add_text' ) );
		/**
		 * for admin
		 */
		if ( is_admin() ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( isset( $_POST['action'] ) ) {
					if ( 'woocommerce_load_variations' === $_POST['action'] ) {
						check_ajax_referer( 'load-variations', 'security' );
						if ( 'yes' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
							return apply_filters( 'iworks_omnibus_show', true );
						}
					}
				}
			} elseif ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( 'product' === $screen->id ) {
					if ( 'yes' === get_option( $this->get_name( 'admin_edit' ), 'yes' ) ) {
						return apply_filters( 'iworks_omnibus_show', true );
					}
				}
				if ( 'edit-product' === $screen->id ) {
					if ( 'yes' === get_option( $this->get_name( 'admin_list' ), 'no' ) ) {
						return apply_filters( 'iworks_omnibus_show', true );
					}
				}
			}
			return apply_filters( 'iworks_omnibus_show', false );
		}
		/**
		 * front-end short term good
		 */
		if ( 'yes' === get_option( $this->get_name( 'admin_short' ), 'no' ) ) {
			if ( 'yes' === get_post_meta( $post_id, $this->get_name( 'is_short' ), true ) ) {
				if ( 'no' === get_option( $this->get_name( 'short_message' ), 'no' ) ) {
					return apply_filters( 'iworks_omnibus_show', false );
				}
			}
		}
		/**
		 * front-end on sale
		 */
		if ( 'yes' === get_option( $this->get_name( 'on_sale' ), 'yes' ) ) {
			$product = wc_get_product( $post_id );
			if (
				! is_object( $product )
				|| ! $product->is_on_sale()
			) {
				return apply_filters( 'iworks_omnibus_show', false );
			}
		}
		/**
		 * single product
		 */
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
				/**
				 * variation
				 */
				$product = wc_get_product( $post_id );
				if ( 'variation' === $product->get_type() ) {
					if ( 'no' === get_option( $this->get_name( 'variation' ), 'yes' ) ) {
						return apply_filters( 'iworks_omnibus_show', false );
					}
					return apply_filters( 'iworks_omnibus_show', true );
				}
			}
			if ( 'yes' === get_option( $this->get_name( 'single' ), 'yes' ) ) {
				return apply_filters( 'iworks_omnibus_show', true );
			}
			return apply_filters( 'iworks_omnibus_show', false );
		}
		/**
		 * shop page
		 */
		if ( is_shop() ) {
			if ( 'yes' === get_option( $this->get_name( 'shop' ), 'no' ) ) {
				return apply_filters( 'iworks_omnibus_show', true );
			}
			return apply_filters( 'iworks_omnibus_show', false );
		}
		/**
		 * Taxonomy Page
		 */
		if ( is_tax() ) {
			if ( 'yes' === get_option( $this->get_name( 'tax' ), 'no' ) ) {
				return apply_filters( 'iworks_omnibus_show', true );
			}
			return apply_filters( 'iworks_omnibus_show', false );
		}
		/**
		 * any loop
		 */
		if ( in_the_loop() ) {
			if ( 'yes' === get_option( $this->get_name( 'loop' ), 'no' ) ) {
				return apply_filters( 'iworks_omnibus_show', true );
			}
			return apply_filters( 'iworks_omnibus_show', false );
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
		if ( ! is_object( $product ) ) {
			return $price;
		}
		$should_it_show_up = $this->should_it_show_up( $product->get_id() );
		if ( false === $should_it_show_up ) {
			return $price;
		}
		if ( is_string( $should_it_show_up ) ) {
			return $price . $should_it_show_up;
		}
		$price_lowest = $this->get_lowest_price( $product );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	/**
	 * get lowest price
	 */
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
			case 'variation':
				break;
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
		if ( is_admin() ) {
			if ( empty( $lowest ) ) {
				$value = get_post_meta( $post_id, $this->get_name() );
				if ( empty( $value ) ) {
					return $lowest;
				}
			}
		}
		switch ( get_option( $this->get_name( 'missing' ), 'current' ) ) {
			/**
			 * Regular price when it was no lower in history
			 *
			 * @since 2.5.0
			 */
			case 'regular':
				if ( empty( $lowest ) ) {
					$lowest                        = array(
						'price'     => $product->get_regular_price(),
						'qty'       => 1,
						'timestamp' => time(),
					);
					$lowest['price_including_tax'] = wc_get_price_including_tax( $product, $lowest );
				}
				break;
			case 'current':
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
				break;
		}
		if ( is_array( $lowest ) ) {
			$lowest['product_id'] = $product->get_ID();
			if ( $product->is_type( 'variation' ) ) {
				$lowest['variation_id'] = $lowest['product_id'];
				$lowest['product_id']   = $product->get_parent_id();
			}
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
		$price_lowest = $this->woocommerce_get_lowest_price_in_history( $product->get_ID() );
		foreach ( $product->get_available_variations() as $variable ) {
			$o = $this->woocommerce_get_lowest_price_in_history( $variable['variation_id'] );
			if ( empty( $o ) ) {
				continue;
			}
			if (
				! isset( $price_lowest['price'] )
				|| empty( $price_lowest['price'] )
			) {
				$price_lowest = $o;
				continue;
			}
			if ( $o['price'] < $price_lowest['price'] ) {
				$price_lowest = $o;
			}
		}
		return $price_lowest;
	}

	/**
	 * WooCommerce: price HTML input
	 *
	 * @since 1.0.0
	 */
	private function woocommerce_wp_text_input_price( $price_lowest, $configuration = array() ) {
		$value = __( 'no data', 'omnibus' );
		if ( ! empty( $price_lowest )
			&& isset( $price_lowest['price_sale'] )
			&& ! empty( $price_lowest['price_sale'] )
		) {
			$value = $price_lowest['price_sale'];
		}
		woocommerce_wp_text_input(
			wp_parse_args(
				array(
					'id'                => $this->meta_name . '_price',
					'custom_attributes' => array( 'disabled' => 'disabled' ),
					'value'             => $value,
					'data_type'         => 'price',
					'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
					'desc_tip'          => true,
					'description'       => sprintf(
						/* translators: %d: nuber of days */
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
					'value'             => empty( $price_lowest ) ?
						esc_html__( 'no data', 'omnibus' ) :
						date_i18n( get_option( 'date_format' ), isset( $price_lowest['timestamp'] ) ? $price_lowest['timestamp'] : '' ),
					'data_type'         => 'text',
					'label'             => __( 'Date', 'omnibus' ),
					'desc_tip'          => true,
					'description'       => sprintf(
						/* translators: %d: nuber of days */
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
			array(
				'title' => __( 'Settings has been moved', 'omnibus' ),
				'id'    => $this->get_name( 'moved' ),
				'type'  => 'title',
				'desc'  => sprintf(
					/* translators: %1$s: a tag begin, %2$s: a tag end */
					esc_html__( 'Please visit new %1$ssettings page%2$s.', 'omnibus' ),
					sprintf( '<a href="%s">', remove_query_arg( 'section', add_query_arg( 'tab', 'omnibus' ) ) ),
					'</a>'
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'sectionend' ),
			),
		);
		return $settings;
	}

	/**
	 * run helper
	 *
	 * @since 1.1.0
	 * @since 2.3.2 Param $message has been added.
	 *
	 * @param string $context Content: view or return.
	 * @param integer $post_id Product ID.
	 * @param string $message Message template.
	 */
	public function run( $context = 'view', $post_id = null, $message = null ) {
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
		$message = $this->add_message( '', $price_lowest, 'wc_price', $message );
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
		if ( 'product' !== get_post_type() ) {
			return $content;
		}
		if ( ! $this->should_it_show_up( get_the_ID() ) ) {
			return $content;
		}
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
	 *
	 * @param array $atts Array of param
	 * @param string $content Shortcode content
	 */
	public function shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'id'         => null,
				'strip_tags' => 'no',
				'template'   => null,
			),
			$atts,
			'iworks_omnibus_wc_lowest_price_message'
		);
		if ( empty( $atts['id'] ) ) {
			$atts['id'] = get_the_ID();
		}
		if ( empty( $atts['id'] ) ) {
			return $content;
		}
		if ( ! $this->should_it_show_up( $atts['id'] ) ) {
			return $content;
		}
		$content = $this->run( 'return', $atts['id'], $atts['template'] );
		/**
		 * strip html
		 *
		 * @since 2.3.2
		 */
		if ( $this->is_on( $atts['strip_tags'] ) ) {
			$content = wp_strip_all_tags( $content );
		}
		/**
		 * return
		 */
		return $content;
	}

	/**
	 * get price helper
	 *
	 * @since 2.0.2
	 */
	private function get_price( $product ) {
		switch ( $product->get_type() ) {
			case 'simple':
				$price = $this->_get_v4_lowest_price_in_history( $product->get_id() );
				break;
			default:
				l( $product->get_type() );
		}
	}


	/**
	 * Add configuration link to plugin_row_meta.
	 *
	 * @since 2.1.0
	 *
	 */
	public function filter_add_link_omnibus_configuration( $actions, $plugin_file, $plugin_data, $context ) {
		if ( 'woocommerce/woocommerce.php' !== $plugin_file ) {
			return $actions;
		}
		$settings_page_url  = add_query_arg(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'products',
				'section' => $this->get_name(),
			),
			admin_url( 'admin.php' )
		);
		$actions['omnibus'] = sprintf(
			'<a href="%s">%s</a>',
			$settings_page_url,
			__( 'Omnibus', 'omnibus' )
		);
		return $actions;
	}

	/**
	 * WooCommerce show in cart
	 *
	 * @since 2.1.5
	 */
	public function filter_woocommerce_cart_item_price( $price, $cart_item, $cart_item_key ) {
		if ( 'yes' === get_option( $this->get_name( 'on_sale' ), 'yes' ) ) {
			if ( ! $cart_item['data']->is_on_sale() ) {
				return $price;
			}
		}
		$price_lowest = $this->get_lowest_price( $cart_item['data'] );
		l( $price_lowest );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest, 'wc_price' );
	}

	/**
	 * WooCommerce show at start or end of product meta
	 *
	 * @since 2.1.7
	 */
	public function action_check_and_add_message() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		if ( ! is_main_query() ) {
			return;
		}
		if ( ! $this->should_it_show_up( get_the_ID() ) ) {
			return;
		}
		$this->run( get_the_ID() );
	}

	/**
	 * Reviews Settings
	 *
	 * @since 2.3.0
	 */
	private function settings_review() {
		if ( 'yes' !== get_option( 'woocommerce_enable_reviews', 'yes' ) ) {
			return array();
		}
		$settings = array(
			array(
				'title' => __( 'Reviews', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name( 'reviews' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'reviews_sectionend' ),
			),
		);
		return $settings;
	}

	/**
	 * maybe save product price
	 */
	public function action_shutdown_maybe_save_product_price() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
	}

	public function filter_woocommerce_get_settings_pages( $settings ) {
		$settings[] = include __DIR__ . '/class-iworks-omnibus-integration-woocommerce-settings.php';
		return $settings;
	}

	public function filter_iworks_omnibus_message_template_for_admin_list( $template ) {
		if ( ! is_admin() ) {
			return $template;
		}
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return $template;
		}
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return $template;
		}
		if ( 'product' !== $screen->post_type ) {
			return $template;
		}
		if ( 'no' === get_option( $this->get_name( 'admin_list_short' ), 'no' ) ) {
			return $template;
		}
		return __( 'OD: {price}', 'omnibus' );
	}

	/**
	 * change & replace message
	 *
	 * @since 2.3.0
	 */
	public function filter_iworks_omnibus_message_template_for_product( $template, $price, $price_lowest ) {
		if ( is_admin() ) {
			return $template;
		}
		/**
		 * short term product
		 */
		$post_id = get_the_ID();
		if ( isset( $price_lowest['variation_id'] ) ) {
			$post_id = $price_lowest['variation_id'];
		}
		if ( 'yes' === get_post_meta( $post_id, $this->get_name( 'is_short' ), true ) ) {
			switch ( get_option( $this->get_name( 'short_message' ), 'no' ) ) {
				case 'no':
					return '';
				case 'inform':
					if ( 'yes' == get_option( $this->get_name( 'message_settings' ), 'no' ) ) {
						$v = get_option( $this->get_name( 'message_short' ), false );
						if ( ! empty( $v ) ) {
							return $v;
						}
					}
					return __( 'This is short term product.', 'omnibus' );
			}
		}
		/**
		 * no price
		 */
		if (
			empty( $price_lowest )
			|| (
				isset( $price_lowest['type'] )
				&& 'autosaved' === $price_lowest['type']
			)
		) {
			switch ( get_option( $this->get_name( 'missing' ), 'current' ) ) {
				case 'no':
					return '';
				case 'inform':
					return $this->get_message_price_is_not_available();
			}
		}
		return $template;
	}

	public function woocommerce_wp_checkbox_short( $post_id, $configuration = array(), $loop = null ) {
		if ( 'not applicable' === get_option( $this->get_name( 'is_short' ), 'no' ) ) {
			return;
		}
		$name = $id = $this->get_name( 'is_short' );
		if ( is_numeric( $loop ) ) {
			$id   .= sprintf( '_%d', $loop );
			$name .= sprintf( '[%d]', $loop );
		}
		woocommerce_wp_checkbox(
			wp_parse_args(
				array(
					'id'            => $id,
					'name'          => $name,
					'value'         => get_post_meta( $post_id, $this->get_name( 'is_short' ), true ),
					'label'         => __( 'Short Term', 'omnibus' ),
					'description'   => __( 'This is a short-term product.', 'omnibus' ),
					'wrapper_class' => 'iworks_omnibus_field_checkbox',
				),
				$configuration
			)
		);
	}

	/**
	 * Add or update post_meta short term product
	 *
	 * @since 2.5.2
	 *
	 * @param integer $post_id post id
	 * @param string $meta_value meta value to save, but only "yes".
	 */
	private function update_post_meta_short( $post_id, $meta_value ) {
		$meta_key = $this->get_name( 'is_short' );
		if ( 'yes' === $meta_value ) {
			if ( ! update_post_meta( $post_id, $meta_key, 'yes' ) ) {
				add_post_meta( $post_id, $meta_key, 'yes', true );
			}
			return;
		}
		delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Save short term product
	 *
	 * @since 2.3.0
	 */
	public function action_woocommerce_save_maybe_save_short( $product ) {
		$id       = $product->get_id();
		$meta_key = $this->get_name( 'is_short' );
		/**
		 * variation
		 */
		switch ( $product->get_type() ) {
			case 'variable':
			case 'variation':
				if ( ! isset( $_POST['variable_post_id'] ) ) {
					return;
				}
				if ( ! isset( $_POST[ $meta_key ] ) ) {
					return;
				}
				foreach ( $_POST['variable_post_id'] as $index => $post_id ) {
					$meta_value = 'no';
					if ( isset( $_POST[ $meta_key ][ $index ] ) ) {
						$meta_value = $_POST[ $meta_key ][ $index ];
					}
					$this->update_post_meta_short( $post_id, $meta_value );
				}
				return;
		}
		/**
		 * any other
		 */
		$this->update_post_meta_short( $id, filter_input( INPUT_POST, $meta_key ) );
	}


	/**
	 * get WooCommerce product lowest price
	 *
	 * @sinc 2.3.2
	 *
	 */
	public function filter_wc_get_lowest_price( $price = array(), $product_id = null ) {
		if ( empty( $product_id ) ) {
			$product_id = get_the_ID();
		}
		$product = wc_get_product( $product_id );
		if ( empty( $product ) ) {
			return $price;
		}
		return $this->get_lowest_price( $product );
	}

	/**
	 * add extra data to price log
	 *
	 * @since 2.3.2
	 *
	 */
	public function filter_add_price_log_data( $data, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( empty( $product ) ) {
			return $data;
		}
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		return wp_parse_args(
			$this->get_prices_by_product( $product ),
			$data
		);
	}

	/**
	 * get product prices
	 *
	 * @since 3.0.0
	 */
	private function get_prices_by_product( $product ) {
		return array(
			'price_regular' => $product->get_regular_price(),
			'price_sale'    => $product->get_sale_price(),
		);
	}

	/**
	 * WooCommerce Omnibus Price History Meta Box
	 *
	 * @since 2.4.0
	 */
	public function action_add_meta_boxes_product_history( $post ) {
		if ( true ) {
			return;
		}
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		add_meta_box(
			'iworks-omnibus-history',
			__( 'Omnibus Price History', 'omnibus' ),
			array( $this, 'meta_box_history_html' )
		);
	}

	public function meta_box_history_html( $post ) {
		$products = $this->get_products_ids( $post );
		$log      = array();
		foreach ( $products as $post_id ) {
			$changes = get_post_meta( $post_id, $this->meta_price_log_name );
			if ( is_array( $changes ) ) {
				foreach ( $changes as $one ) {
					$one['post_id'] = $post_id;
					$log[]          = $one;
				}
			}
		}
		if ( empty( $log ) ) {
			esc_html_e( 'There is no price history recorded.', 'omnibus' );
			return;
		}
		echo '<table class="widefat fixed striped">';
		echo '<thead>';
		echo '<tr>';
		printf( '<th>%s</th>', esc_html__( 'Product', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Regular Price', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Sale Price', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Date', 'omnibus' ) );
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		usort( $log, array( $this, 'usort_log_array' ) );
		foreach ( $log as $one ) {
			echo '<tr>';
			printf( '<td>%s</td>', get_the_title( $one['post_id'] ) );
			printf( '<td>%s</td>', $one['price'] );
			printf( '<td>%s</td>', empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] );
			printf( '<td>%s</td>', date_i18n( 'Y-m-d H:i', $one['timestamp'] ) );
			echo '</tr>';

			d( $one );
		}
		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Get products list
	 *
	 * @param \WP_Post $post
	 *
	 * @return array a
	 */
	public function get_products_ids( $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product ) {
			return array();
		}
		if ( $product instanceof WC_Product_Variable ) {
			return $product->get_children();
		}
		return array( $post->ID );
	}

	/**
	 * action to call save_price_history()
	 *
	 * @since 2.5.2
	 */
	public function action_iworks_omnibus_wc_save_price_history( $post_id, $price ) {
		$this->save_price_history( $post_id, $price );
	}

	/**
	 * register_deactivation_hook
	 *
	 * @since 4.0.0
	 */
	public function register_deactivation_hook() {
		if ( 'yes' === get_option( $this->get_name( 'delete' ) ) ) {
			$this->delete_all();
		}
	}

	/**
	 * delete all data
	 *
	 * @since 4.0.0
	 */
	private function delete_all_data() {
	}

	/**
	 * delete all!!!
	 *
	 * @since 4.0.0
	 */
	private function delete_all() {
		$this->delete_settings();
		$this->drop_tables();
	}

	/**
	 * drop Omnibus tables
	 *
	 * @since 4.0.0
	 */
	private function drop_tables() {
		global $wpdb;
		$sql   = 'drop table %s';
		$query = sprintf( $sql, $wpdb->iworks_omnibus );
		$wpdb->query( $query );
		delete_option( 'iworks_omnibus_db_version' );
	}

	/**
	 * delete settings
	 *
	 * @since 4.0.0
	 */
	private function delete_settings() {
		global $wpdb;
		$sql   = sprintf(
			'delete from %s where option_name like %%s',
			$wpdb->options
		);
		$query = $wpdb->prepare( $sql, '_iwo_%' );
		$wpdb->query( $query );
	}

}
