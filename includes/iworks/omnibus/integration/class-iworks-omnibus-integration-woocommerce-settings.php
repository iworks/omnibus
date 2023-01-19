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
		$this->notices();
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''         => __( 'Display', 'omnibus' ),
			'messages' => __( 'Messages', 'omnibus' ),
			'admin'    => __( 'Admin', 'omnibus' ),
			'comments' => __( 'Comments', 'omnibus' ),
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
				'title'   => __( 'In the product', 'omnibus' ),
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
				'title'   => __( 'No previous price', 'omnibus' ),
				'id'      => $this->get_name( 'missing' ),
				'default' => 'current',
				'type'    => 'radio',
				'options' => array(
					'current' => esc_html__( 'Display current price', 'omnibus' ),
					'no'      => esc_html__( 'Do not display anything', 'omnibus' ),
				),
				'desc'    => esc_html__( 'What do you want to show when no data is available?', 'omnibus' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'sectionend' ),
			),
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
				'desc'    => __( 'Show or hide on a single product page.', 'omnibus' ),
			),
			array(
				'title'    => __( 'Shop', 'omnibus' ),
				'id'       => $this->get_name( 'shop' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => sprintf(
					__( 'Show or hide on the <a href="%s#woocommerce_shop_page_id" target="_blank">Shop Page</a>.', 'omnibus' ),
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
					__( 'Show or hide on the <a href="%s#woocommerce_cart_page_id" target="_blank">Cart Page</a>.', 'omnibus' ),
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
				'desc'    => __( 'Show or hide on any product list.', 'omnibus' ),
			),
			array(
				'title'   => __( 'Taxonomy page', 'omnibus' ),
				'id'      => $this->get_name( 'tax' ),
				'default' => 'no',
				'type'    => 'checkbox',
				'desc'    => __( 'Show or hide on any taxonomy (tags, categories, custom taxonomies).', 'omnibus' ),
			),
			array(
				'title'    => __( 'Related products list', 'omnibus' ),
				'id'       => $this->get_name( 'related' ),
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => __( 'Show or hide on the related products box.', 'omnibus' ),
				'desc_tip' => __( 'This setting is only for WooCommerce related products box. It will not work if you use something else, such as a page builder related products.', 'omnibus' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'show-on-end' ),
			),
			array(
				'title' => __( 'Fallback', 'omnibus' ),
				'type'  => 'title',
				'id'    => $this->get_name( 'show-on' ),
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
				'type' => 'sectionend',
				'id'   => $this->get_name( 'show-on-end' ),
			),
		);
		return apply_filters( 'iworks_omnibus_settings', $settings );
	}

	/**
	 * Get settings for the WooCommerce.com section.
	 *
	 * @return array
	 */
	protected function get_settings_for_admin_section() {
		$tracking_info_text = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://woocommerce.com/usage-tracking', esc_html__( 'WooCommerce.com Usage Tracking Documentation', 'omnibus' ) );

		$settings =
			array(
				array(
					'title' => esc_html__( 'Product List', 'omnibus' ),
					'type'  => 'title',
					'id'    => $this->get_name( 'admin-list' ),
				),
				array(
					'title'           => __( 'Show', 'omnibus' ),
					'id'              => $this->get_name( 'admin_list' ),
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc'            => __( 'Show on products list screen', 'omnibus' ),
					'show_if_checked' => 'option',
					'checkboxgroup'   => 'start',
				),
				array(
					'title'           => __( 'Show', 'omnibus' ),
					'id'              => $this->get_name( 'admin_list_short' ),
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc'            => __( 'Show short message: "OD: $10"', 'omnibus' ),
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
					'title'   => __( 'Show', 'omnibus' ),
					'id'      => $this->get_name( 'admin_edit' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Show on product edit screen', 'omnibus' ),
				),
				array(
					'desc'     => __( 'Show or hide checkbox to allow turn off Omnibus message', 'omnibus' ),
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
			);
		return apply_filters( 'iworks_omnibus_admin_settings', $settings );
	}

	/**
	 * Get settings for the legacy API section.
	 *
	 * @return array
	 */
	protected function get_settings_for_legacy_api_section() {
		$settings =
			array(
				array(
					'title' => '',
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'legacy_api_options',
				),
				array(
					'title'   => __( 'Legacy API', 'omnibus' ),
					'desc'    => __( 'Enable the legacy REST API', 'omnibus' ),
					'id'      => 'woocommerce_api_enabled',
					'type'    => 'checkbox',
					'default' => 'no',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'legacy_api_options',
				),
			);

		return apply_filters( 'woocommerce_settings_rest_api', $settings );
	}

	/**
	 * Form method.
	 *
	 * @deprecated 3.4.4
	 *
	 * @param  string $method Method name.
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		return 'post';
	}

	/**
	 * Notices.
	 */
	private function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['section'] ) && 'webhooks' === $_GET['section'] ) {
			WC_Admin_Webhooks::notices();
		}
		if ( isset( $_GET['section'] ) && 'keys' === $_GET['section'] ) {
			WC_Admin_API_Keys::notices();
		}
		// phpcs:enable
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		if ( 'webhooks' === $current_section ) {
			WC_Admin_Webhooks::page_output();
		} elseif ( 'keys' === $current_section ) {
			WC_Admin_API_Keys::page_output();
		} else {
			parent::output();
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		global $current_section;

		if ( apply_filters( 'woocommerce_rest_api_valid_to_save', ! in_array( $current_section, array( 'keys', 'webhooks' ), true ) ) ) {
			// Prevent the T&Cs and checkout page from being set to the same page.
			if ( isset( $_POST['woocommerce_terms_page_id'], $_POST['woocommerce_checkout_page_id'] ) && $_POST['woocommerce_terms_page_id'] === $_POST['woocommerce_checkout_page_id'] ) {
				$_POST['woocommerce_terms_page_id'] = '';
			}

			// Prevent the Cart, checkout and my account page from being set to the same page.
			if ( isset( $_POST['woocommerce_cart_page_id'], $_POST['woocommerce_checkout_page_id'], $_POST['woocommerce_myaccount_page_id'] ) ) {
				if ( $_POST['woocommerce_cart_page_id'] === $_POST['woocommerce_checkout_page_id'] ) {
					$_POST['woocommerce_checkout_page_id'] = '';
				}
				if ( $_POST['woocommerce_cart_page_id'] === $_POST['woocommerce_myaccount_page_id'] ) {
					$_POST['woocommerce_myaccount_page_id'] = '';
				}
				if ( $_POST['woocommerce_checkout_page_id'] === $_POST['woocommerce_myaccount_page_id'] ) {
					$_POST['woocommerce_myaccount_page_id'] = '';
				}
			}

			$this->save_settings_for_current_section();
			$this->do_update_options_action();
		}
		// phpcs:enable
	}


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

}

return new iworks_omnibus_integration_woocommerce_settings();
// phpcs:enable
