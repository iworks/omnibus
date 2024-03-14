<?php
/**
 * WooCommerce advanced settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings for API.
 */
if ( class_exists( 'iworks_omnibus_integration_woocommerce_settings', false ) ) {
	return new iworks_omnibus_integration_woocommerce_settings();
}

/**
 * WC_Settings_Advanced.
 */
class iworks_omnibus_integration_woocommerce_settings extends WC_Settings_Page {

	private $meta_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'omnibus';
		$this->label = __( 'Omnibus', 'omnibus' );
		parent::__construct();
		add_action( 'woocommerce_admin_field_omnibus_info', array( $this, 'omnibus_info' ) );
	}

	public function omnibus_info( $value ) {
		$text = str_replace(
			array( '{', '}' ),
			array( '<code>{', '}</code>' ),
			implode( PHP_EOL, $value['info'] )
		);
		echo '<tr><td colspan="2" class="' . esc_attr( $value['class'] ) . '">';
		echo wp_kses_post( wpautop( wptexturize( $text ) ) );
		echo '</td></tr>';
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''         => __( 'Display Price', 'omnibus' ),
			'where'    => __( 'Where on site', 'omnibus' ),
			'messages' => __( 'Messages', 'omnibus' ),
			'admin'    => __( 'Admin Dashboard', 'omnibus' ),
			// 'debug'    => __( 'Debug', 'omnibus' ),
		);
	}

	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		$settings = array(
			array(
				'title' => __( 'Display Price', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name(),
				'desc'  => esc_html__( 'European Union guidance requires displaying the minimal price if a product is on sale. But if you use publicly available discounts, e.g., "discount code" you should consider showing omnibus prices all the time.', 'omnibus' ),
			),
			array(
				'title'   => __( 'Display minimal price', 'omnibus' ),
				'id'      => $this->get_name( 'on_sale' ),
				'default' => 'yes',
				'type'    => 'radio',
				'options' => array(
					'yes' => esc_html__( 'Only when the product is on sale', 'omnibus' ),
					'no'  => esc_html__( 'Always (use if you have publicly available discounts)', 'omnibus' ),
				),
			),
			array(
				'title'   => __( 'Where on Single Product', 'omnibus' ),
				'desc'    => __( 'Allows you to choose where the message is displayed. According to the directive, we recommend displaying it right after the price. Some places may not work depending on the theme you are using and how your site is built.', 'omnibus' ),
				'id'      => $this->get_name( 'where' ),
				'default' => 'woocommerce_get_price_html',
				'type'    => 'select',
				'options' => array(
					'woocommerce_get_price_html'           => esc_html__( 'After the price (recommended)', 'omnibus' ),
					'do_not_show'                          => esc_html__( 'Do not show. I will handle it myself.', 'omnibus' ),
					/** meta */
					'woocommerce_product_meta_start'       => esc_html__( 'Before the product meta data', 'omnibus' ),
					'woocommerce_product_meta_end'         => esc_html__( 'After the product meta data', 'omnibus' ),
					/** product summary */
					'woocommerce_before_single_product_summary' => esc_html__( 'Before the single product summary', 'omnibus' ),
					'woocommerce_after_single_product_summary' => esc_html__( 'After the single product summary', 'omnibus' ),
					/** cart form */
					'woocommerce_before_add_to_cart_form'  => esc_html__( 'Before the add to cart form', 'omnibus' ),
					/** cart button */
					'woocommerce_before_add_to_cart_button' => esc_html__( 'Before the add to cart button', 'omnibus' ),
					'woocommerce_after_add_to_cart_button' => esc_html__( 'After the add to cart button', 'omnibus' ),
					/** cart quantity */
					'woocommerce_before_add_to_cart_quantity' => esc_html__( 'Before the add to cart quantity', 'omnibus' ),
					'woocommerce_after_add_to_cart_quantity' => esc_html__( 'After the add to cart quantity', 'omnibus' ),
					// 'woocommerce_single_product_summary'        => esc_html__( 'Single product summary', 'omnibus' ),
					/** content */
					'the_content_start'                    => esc_html__( 'At the begining of the content', 'omnibus' ),
					'the_content_end'                      => esc_html__( 'At the end of the content', 'omnibus' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'sectionend' ),
			),
		);
		/**
		 * WooCommerce Tax
		 */
		if (
			'yes' === get_option( 'woocommerce_calc_taxes', 'no' )
			&& 'no' === get_option( 'woocommerce_prices_include_tax', 'no' )
		) {
			$settings[] = array(
				'title' => __( 'Tax', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name( 'tax' ),
			);
			$settings[] = array(
				'title'   => __( 'Include tax', 'omnibus' ),
				'id'      => $this->get_name( 'include_tax' ),
				'default' => 'yes',
				'type'    => 'checkbox',
				'desc'    => __( 'Display price with tax', 'omnibus' ),
			);
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'tax-sectionend' ),
			);
		}
		/**
		 * Products
		 */
			$settings[] = array(
				'title' => __( 'Type of Product', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name( 'types' ),
			);
			$products   = array(
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
				$tutor_option = get_option( 'tutor_option' );
				if (
					is_array( $tutor_option )
					&& isset( $tutor_option['monetize_by'] )
					&& 'wc' === $tutor_option['monetize_by']
				) {
					$products[] = array(
						'desc' => __( 'Tutor course', 'omnibus' ),
						'id'   => $this->get_name( 'tutor' ),
					);
				}
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
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'types-sectionend' ),
			);
			return apply_filters( 'iworks_omnibus_settings', $settings );
	}

	/**
	 * Get settings for the messages section.
	 *
	 * @return array
	 */
	protected function get_settings_for_where_section() {
		$settings = array(
			/**
			 * Show on
			 */
			array(
				'title' => __( 'Where to display on site', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name( 'show-on' ),
				'desc'  => __( 'Select the places where you want to display information about prices. Some options may not work properly depending on the theme you\'re using or how your site is built.', 'omnibus' ),
			),
			array(
				'title'   => __( 'Single Product', 'omnibus' ),
				'id'      => $this->get_name( 'product' ),
				'default' => 'yes',
				'type'    => 'checkbox',
				'desc'    => __( 'Show a single product page.', 'omnibus' ),
			),
			array(
				'title'    => __( 'Shop', 'omnibus' ),
				'id'       => $this->get_name( 'shop' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => sprintf(
					/* translators: %s WC Settings/Products admin url */
					__( 'Show on the <a href="%s#woocommerce_shop_page_id" target="_blank">Shop Page</a>.', 'omnibus' ),
					add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => 'products',
						),
						admin_url( 'admin.php' )
					)
				),
				'desc_tip' => __( 'This setting is only for WooCommerce Shop Page. It will not work if you use something else, such as a page builder products page.', 'omnibus' ),
			),
			array(
				'title'    => __( 'Cart', 'omnibus' ),
				'id'       => $this->get_name( 'cart' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => sprintf(
					/* translators: %s WC Settings/Advance admin url */
					__( 'Show on the <a href="%s#woocommerce_cart_page_id" target="_blank">Cart Page</a>.', 'omnibus' ),
					add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => 'advanced',
						),
						admin_url( 'admin.php' )
					)
				),
				'desc_tip' => __( 'This setting is only for WooCommerce Cart Page. It will not work if you use something else, such as a page builder cart page.', 'omnibus' ),
			),
			array(
				'title'   => __( 'Any loop', 'omnibus' ),
				'id'      => $this->get_name( 'loop' ),
				'default' => 'no',
				'type'    => 'checkbox',
				'desc'    => __( 'Show on any product list.', 'omnibus' ),
			),
			array(
				'title'   => __( 'Taxonomy Page', 'omnibus' ),
				'id'      => $this->get_name( 'tax' ),
				'default' => 'no',
				'type'    => 'checkbox',
				'desc'    => __( 'Show on any taxonomy (tags, categories, custom taxonomies).', 'omnibus' ),
			),
			array(
				'title'    => __( 'Related Products List', 'omnibus' ),
				'id'       => $this->get_name( 'related' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => __( 'Show on the related products box.', 'omnibus' ),
				'desc_tip' => __( 'This setting is only for WooCommerce related products box. It will not work if you use something else, such as a page builder related products.', 'omnibus' ),
			),
			array(
				'title'    => __( 'Everywhere Else', 'omnibus' ),
				'id'       => $this->get_name( 'default' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => __( 'Display anywhere else', 'omnibus' ),
				'desc_tip' => __( 'Display anywhere else that doesn\'t fit any of the above.', 'omnibus' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'show-on-end' ),
			),
		);
		return apply_filters( 'iworks_omnibus_where_settings', $settings );
	}

	/**
	 * Get settings for the messages section.
	 *
	 * @return array
	 */
	protected function get_settings_for_messages_section() {
		$description = array();
		/* translators: Do not translate {price}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {price} placeholder to display price.', 'omnibus' );
		/* translators: Do not translate {timestamp}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {timestamp} placeholder to display timestamp.', 'omnibus' );
		/* translators: Do not translate {days}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {days} placeholder to display days.', 'omnibus' );
		/* translators: Do not translate {when}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {when} placeholder to display date.', 'omnibus' );
		$settings      =
			array(
				array(
					'title' => esc_html__( 'Messages Settings', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'messages' ),
				),
				array(
					'title'    => __( 'No Previous Price', 'omnibus' ),
					'id'       => $this->get_name( 'missing' ),
					'default'  => 'current',
					'type'     => 'radio',
					'options'  => array(
						'current' => esc_html__( 'Display current sale price', 'omnibus' ),
						'regular' => esc_html__( 'Display current regular price', 'omnibus' ),
						'inform'  => esc_html__( 'Inform about it', 'omnibus' ),
						'no'      => esc_html__( 'Do not display anything', 'omnibus' ),
					),
					'desc_tip' => esc_html__( 'What do you want to show when no data is available?', 'omnibus' ),
				),
				array(
					'title'    => __( 'Short Term Product', 'omnibus' ),
					'id'       => $this->get_name( 'short_message' ),
					'default'  => 'not applicable',
					'type'     => 'radio',
					'options'  => array(
						'not applicable' => esc_html__( 'I don\'t have this type of products', 'omnibus' ),
						'inform'         => esc_html__( 'Inform about it', 'omnibus' ),
						'no'             => esc_html__( 'Do not display anything', 'omnibus' ),
					),
					'desc_tip' => esc_html__( 'What should I do for a product with a short term life?', 'omnibus' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => $this->get_name( 'messages-end' ),
				),
				array(
					'title' => esc_html__( 'Messages', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'messages-custom' ),
				),
				array(
					'title'   => __( 'Custom', 'omnibus' ),
					'type'    => 'checkbox',
					'default' => 'no',
					'id'      => $this->get_name( 'message_settings' ),
					'desc'    => __( 'Allow to use custom messages', 'omnibus' ),
					'class'   => 'iworks_omnibus_messages_settings',
				),
				array(
					'type'  => 'omnibus_info',
					'info'  => $description,
					'class' => 'iworks_omnibus_messages_settings_field',
				),
				array(
					'title'   => __( 'Omnibus Message', 'omnibus' ),
					'type'    => 'text',
					'id'      => $this->get_name( 'message' ),
					'default' => __( 'Previous lowest price: {price}.', 'omnibus' ),
					'class'   => 'iworks_omnibus_messages_settings_field',
					'desc'    => __( 'A message displaying the last lowest price before the promotion was introduced.', 'omnibus' ),
				),
				array(
					'title'   => __( 'No Data Message', 'omnibus' ),
					'type'    => 'text',
					'id'      => $this->get_name( 'message_no_data' ),
					'default' => __( 'The previous price is not available.', 'omnibus' ),
					'class'   => 'iworks_omnibus_messages_settings_field',
					'desc'    => __( 'A message informing about the lack of price data for the selected product.', 'omnibus' ),
				),
				array(
					'title'   => __( 'Short Term Product Message', 'omnibus' ),
					'type'    => 'text',
					'id'      => $this->get_name( 'message_short' ),
					'default' => __( 'This is short term product.', 'omnibus' ),
					'class'   => 'iworks_omnibus_messages_settings_field',
					'desc'    => __( 'A message informing that there is no need to inform about the price due to the short expiry date.', 'omnibus' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => $this->get_name( 'where-custom-end' ),
				),
			);
		return apply_filters( 'iworks_omnibus_where_settings', $settings );
	}

	/**
	 * Get settings for the admin section.
	 *
	 * @return array
	 */
	protected function get_settings_for_admin_section() {
		$settings =
			array(
				array(
					'title' => esc_html__( 'Product List', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'admin-list' ),
				),
				array(
					'title'           => __( 'Admin Dashboard List', 'omnibus' ),
					'id'              => $this->get_name( 'admin_list' ),
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc'            => __( 'Show on products list screen', 'omnibus' ),
					'show_if_checked' => 'option',
					'checkboxgroup'   => 'start',
				),
				array(
					'id'              => $this->get_name( 'admin_list_short' ),
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc'            => sprintf(
						/* translators: %1$s: example of price,  %2$s: the code tag begin, %3$s: the end of the code tag */
						__( 'Show short message: %2$sOD: $1$s%3$s', 'omnibus' ),
						wc_price( 11.70 ),
						'<code>',
						'</code>'
					),
					'show_if_checked' => 'yes',
					'checkboxgroup'   => 'end',
				),
				array(
					'type' => 'sectionend',
					'id'   => $this->get_name( 'admin-list-end' ),
				),
				array(
					'title' => esc_html__( 'Product Edit', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'admin-edit' ),
				),
				array(
					'title'   => __( 'Admin Edit Screen', 'omnibus' ),
					'id'      => $this->get_name( 'admin_edit' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Show on product edit screen', 'omnibus' ),
				),
				array(
					'desc'     => __( 'Show checkbox to allow turn off Omnibus message', 'omnibus' ),
					'desc_tip' => __( 'You can not display the message for Goods which are liable to deteriorate or expire rapidly.', 'omnibus' ),
					'id'       => $this->get_name( 'admin_short' ),
					'default'  => 'yes',
					'type'     => 'checkbox',
					'title'    => __( 'Short Term Goods', 'omnibus' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => $this->get_name( 'admin-edit-end' ),
				),
				array(
					'title' => esc_html__( 'Other', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'admin-other' ),
				),
				array(
					'title'             => __( 'Number Of Days', 'omnibus' ),
					'desc'              => __( 'This controls the number of days to show. According to the Omnibus Directive, minimum days is 30 after curent sale was started.', 'omnibus' ),
					'id'                => $this->get_name( 'days' ),
					'default'           => '30',
					'type'              => 'number',
					'css'               => 'width: 80px;',
					'custom_attributes' => array(
						'min' => 30,
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => $this->get_name( 'admin-other-end' ),
				),
			);
		return apply_filters( 'iworks_omnibus_admin_settings', $settings );
	}

	/**
	 * get name
	 *
	 * @since 2.3.0
	 */
	private function get_name( $name = '' ) {
		$this->meta_name = apply_filters( 'iworks_omnibus_get_name', '' );
		if ( empty( $name ) ) {
			return $this->meta_name;
		}
		return sanitize_title(
			sprintf(
				'%s_%s',
				$this->meta_name,
				$name
			)
		);
	}

	/**
	 * Get settings for the debug section.
	 *
	 * @since 2.4.0
	 *
	 * @return array
	 */
	protected function get_settings_for_debug_section() {
		$settings = array(
			array(
				'title' => esc_html__( 'Debug', 'omnibus' ),
				'type'  => 'title',
			),
			array(
				'title' => __( 'Settings', 'omnibus' ),
				'type'  => 'textarea',
				'desc'  => __( 'Please copy this field to support question', 'omnibus' ),
			),
			array(
				'title' => __( 'Product', 'omnibus' ),
				'id'    => $this->get_name( 'product_debug' ),
				'type'  => 'checkbox',
				'desc'  => __( 'Display debug information in product content', 'omnibus' ),
			),
			array(
				'type' => 'sectionend',
			),
		);
		return apply_filters( 'iworks_omnibus_debug_settings', $settings );
	}
}

return new iworks_omnibus_integration_woocommerce_settings();
// phpcs:enable
