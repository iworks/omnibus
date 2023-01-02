=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=og&utm_medium=readme-donate
Tags: WooCommerce, omnibus, price, LMS
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: 6.1
Stable tag: PLUGIN_VERSION
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

This plugin allows your site to be compliant with the Directive of the European Parliament and of the Council (EU) 2019/2161 of November 27, 2019, known as the "Omnibus Directive."

This plugin:

* Saves the current price and keeps it for at least 30 days (depending on settings).
* Adds two additional fields in the product or course edit view: for the lowest price and the effective date.
* Display information on the product.

Omnibus plugin support plugins:

* [Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/)
* [LearnPress](https://wordpress.org/plugins/learnpress/)
* [Tutor LMS](https://wordpress.org/plugins/tutor/) with WooCommerce
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [YITH WooCommerce Product Bundles](https://wordpress.org/plugins/yith-woocommerce-product-bundles/)

Read more about [Directive 2019/2161](https://eur-lex.europa.eu/eli/dir/2019/2161/oj).

== Installation ==

There are 3 ways to install this plugin:

= 1. The super-easy way =

1. Navigate to WPA > the Plugins and click the `Add New` button.
1. Search for `PLUGIN_TITLE`.
1. Click to install.
1. Activate the plugin.
1. Check the configuration by going to WPA > WooCommerce > Settings > Products > Omnibus Directive.

= 2. The easy way =

1. Download the plugin (.zip file) on the right column of this page.
1. Navigate to WPA > the Plugins and click the `Add New` button.
1. Select the button `Upload Plugin`.
1. Upload the .zip file you just downloaded.
1. Activate the plugin.
1. Check the configuration by going to WPA > WooCommerce > Settings > Products > Omnibus Directive.

= 3. The old and reliable way (FTP) =

1. Upload the `omnibus` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Check the configuration by going to WPA > WooCommerce > Settings > Products > Omnibus Directive.

== Frequently Asked Questions ==

= How can I display the Omnibus message anywhere? =
Use the `iworks_omnibus_wc_lowest_price_message` action with the product ID or on a single product page.

For a single product:
`
do_action( 'iworks_omnibus_wc_lowest_price_message' );
`

For any WooCommerce product:
`
do_action( 'iworks_omnibus_wc_lowest_price_message', $product_ID );
`

== Screenshots ==

1. A simple WooCommerce product on the front end.
1. A simple WooCommerce product on the admin panel.
1. A variable WooCommerce product on the front end.
1. A variable WooCommerce product on the front end.
1. A variable WooCommerce product on the admin panel.
1. A Tutor LMS course on the front end.
1. A LearnPress course on the front end.
1. A YITH WooCommerce Product Bundle on the front end.
1. The WooCommerce configuration.

== Changelog ==

= 1.2.1 (2023-01-02) =

* Fixed critical exception during adding new product. Props for [rask44](https://wordpress.org/support/users/rask44/).

= 1.2.0 (2023-01-02) =

* Added support for the "Easy Digital Downloads" plugin.
* Changed plugin name from `Omnibus` to `Omnibus — Show the lowest price of a product`.
* The `iworks_omnibus_days` filter has been added to the number of days amount.
* The `iworks_omnibus_integration_woocommerce_price_lowest` filter has been added.
* The `iworks_omnibus_message` filter has been added to the message.
* The `iworks_omnibus_show` filter has been added.
* The `iworks_omnibus_wc_lowest_price_message` action has been added to show the Omnibus message by product ID.
* The ability to toggle the Omnibus message on taxonomy page has been added. By default is hidden.
* The ability to toggle the Omnibus message when price was not changed has been added. By default is shown.

= 1.1.1 (2022-12-31) =

* The ability to toggle the Omnibus message in related products has been added. By default is hidden.
* On the shop page, you can now toggle the Omnibus message. By default is hidden.
* Fixed a typo in the "Where to display" option.

= 1.1.0 (2022-12-30) =

* Control over where Omnibus messages appear has been added. By default is after the price.
* Added the ability to toggle the Omnibus message when editing an admin product. By default is shown.
* Added the ability to toggle the Omnibus message on the admin products list. By default is shown.
* The ability to toggle the Omnibus message on a single product has been added. By default is shown.
* The ability to toggle the Omnibus message on variable products has been added. By default is shown.
* The ability to toggle the Omnibus message on the variant has been added. By default is shown.
* Added support for the "YITH WooCommerce Product Bundles" plugin.

= 1.0.1 (2022-12-29) =

* Support for the "Tutor LMS" plugin has been added.
* Support for the "LearnPress" plugin has been added.

= 1.0.0 (2022-12-29) =

* Support for the "WooCommerce" plugin has been added.
* Init.

== Upgrade Notice ==
