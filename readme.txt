=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=omnibus&utm_medium=readme-donate
Tags: WooCommerce, omnibus, price, LMS, shop, e-commerce, product, course, download, history
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: PLUGIN_TESTED_WORDPRESS
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later

PLUGIN_TAGLINE

== Description ==

This plugin allows your site to be compliant with the Directive of the European Parliament and of the Council (EU) 2019/2161 of November 27, 2019, known as the "Omnibus Directive."

This plugin:

* Saves the current price and keeps it.
* Adds two additional fields in the product or course edit view: the lowest price and the effective date.
* Display information on the item (product, course, etc).

Omnibus plugin support plugins:

* **[WooCommerce](https://wordpress.org/plugins/woocommerce/)**
* [Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/)
* [LearnPress](https://wordpress.org/plugins/learnpress/)
* [Tutor LMS](https://wordpress.org/plugins/tutor/) with WooCommerce
* [YITH WooCommerce Product Bundles](https://wordpress.org/plugins/yith-woocommerce-product-bundles/)

= Directive (EU) 2019/2161 of the European Parliament =

> Article 6a
> 1. Any announcement of a price reduction shall indicate the prior price applied by the trader for a determined period of time prior to the application of the price reduction.
> 2. The prior price means the lowest price applied by the trader during a period of time not shorter than 30 days prior to the application of the price reduction.

Read more: [Directive 2019/2161](https://eur-lex.europa.eu/eli/dir/2019/2161/oj).

= See room for improvement? =

Great! There are several ways you can get involved to help make OG better:

1. **Report Bugs:** If you find a bug, error or other problem, please report it! You can do this by [creating a new topic](https://wordpress.org/support/plugin/omnibus/) in the plugin forum. Once a developer can verify the bug by reproducing it, they will create an official bug report in GitHub where the bug will be worked on.
2. **Suggest New Features:** Have an awesome idea? Please share it! Simply [create a new topic](https://wordpress.org/support/plugin/omnibus/) in the plugin forum to express your thoughts on why the feature should be included and get a discussion going around your idea.
3. **Issue Pull Requests:** If you're a developer, the easiest way to get involved is to help out on [issues already reported](https://github.com/iworks/omnibus/issues) in GitHub. Be sure to check out the [contributing guide](https://github.com/iworks/omnibus/blob/master/contributing.md) for developers.

Thank you for wanting to make OG better for everyone!

== Installation ==

There are 3 ways to install this plugin:

= 1. The super-easy way =

1. Navigate to WPA > the Plugins and click the `Add New` button.
1. Search for `PLUGIN_TITLE`.
1. Click to install.
1. Activate the plugin.
1. WooCommerce: Check the configuration by going to WPA > WooCommerce > Settings > Omnibus.
1. LearnPress: Check the configuration by going to WPA > LearnPress > Settings > Courses > Omnibus Directive Settings.

= 2. The easy way =

1. Download the plugin (.zip file) on the right column of this page.
1. Navigate to WPA > the Plugins and click the `Add New` button.
1. Select the button `Upload Plugin`.
1. Upload the .zip file you just downloaded.
1. Activate the plugin.
1. WooCommerce: Check the configuration by going to WPA > WooCommerce > Settings > Omnibus.
1. LearnPress: Check the configuration by going to WPA > LearnPress > Settings > Courses > Omnibus Directive Settings.

= 3. The old and reliable way (FTP) =

1. Upload the `omnibus` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. WooCommerce: Check the configuration by going to WPA > WooCommerce > Settings > Omnibus.
1. LearnPress: Check the configuration by going to WPA > LearnPress > Settings > Courses > Omnibus Directive Settings.

== Frequently Asked Questions ==

= How can I display the Omnibus message anywhere? =

You can use the `omnibus_price_message` shortcode:

Just edit your product and insert it into the content:

`
[omnibus_price_message]
`

If you need the Omnibus message outside of a product, you have to add the `id` param with the product ID.

`
[omnibus_price_message id="example-id"]
`

= How can I use an action to display the Omnibus message anywhere? =

Use the `iworks_omnibus_wc_lowest_price_message` action with the product ID or on a single product page.

For a single product:
`
do_action( 'iworks_omnibus_wc_lowest_price_message' );
`

For any WooCommerce product:
`
do_action( 'iworks_omnibus_wc_lowest_price_message', $product_ID );
`

= I have a problem with the plugin, or I want to suggest a feature. Where can I do this? =

You can do it on [Support Threads](https://wordpress.org/support/plugin/omnibus/#new-topic-0), but please add your ticket to [Github Issues](https://github.com/iworks/omnibus/issues).


= How can I avoid saving the price log? =

When you want to skip saving a price during the insert or update product, you can use `iworks_omnibus_add_price_log_skip` filter to avoid price logging.

`
<?php
add_filter( 'iworks_omnibus_add_price_log_skip', '__return_false' );
?>
`

= How can I get lowest price log data? =

You should use `iworks_omnibus_wc_get_lowest_price` filter to get array.

On single product page, without product ID:
`
<?php
$lowest_price_log = apply_filters( 'iworks_omnibus_wc_get_lowest_price', array() );
?>
`
Anywhere with the product ID:
`
<?php
$lowest_price_log = apply_filters( 'iworks_omnibus_wc_get_lowest_price', array(), $product_ID );
?>
`

= How can I strip HTML from a shortcode message? =

Please set the "strip_tags" parameter to "yes":
`
[omnibus_price_message strip_tags="yes"]
`

= How can I use my own template in the shortcode message? =

Please add `template` param with needed format:
`
[omnibus_price_message template="This is price: {price}!"]
`

= How can I remove all this plugin related data? =

To remove all data saved by this plugin, you should use the SQL command.

Warning: This operation can be undone.

Please be sure you have the database backup before you try to use the command below:

`
delete from {$wpdb->postmeta} where meta_key in (
    '_iwo_price_lowest_is_short',
    '_iwo_last_price_drop_timestamp',
    '_iwo_price_last_change',
    '_iwo_price_lowest'
);
`

= What is the minimum WooCommerce version required? =

The minimum WooCommerce version required is 5.5.0.

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

Project maintained on github at [iworks/omnibus](https://github.com/iworks/omnibus).

= 2.5.0 (2023-11-19) =
* The ability to show the regular price as the last one available before the promotion was introduced.
* The check for function `get_current_screen()` has been added. Props for [mic22info](https://wordpress.org/support/users/mic22info/).

= 2.4.1 (2023-11-09) =
* The critical error on product edit page has been removed.

= 2.4.0 (2023-11-09) =
* Visibility of very old price has been fixed.
* Prices changes logging has been added.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.3.

= 2.3.9 (2023-06-22) =

* Proper handling of the `$content` param has been added to the `omnibus_price_message` shortcode.
* The data `date-iwo-diff-in-days` has been added.
* A warning during editing has been removed. Props for [slkaz](https://wordpress.org/support/users/slkaz/).

= 2.3.8 (2023-06-14) =

* The product check added in version 2.3.5 has been removed.

= 2.3.7 (2023-06-13) =

* The issue with missing Omnibus price has been fixed. Props for [Adrian](https://wordpress.org/support/users/cometto/).

= 2.3.6 (2023-06-13) =

* The unnecessary extra argument from the `get_price()` function has been removed.

= 2.3.5 (2023-06-12) =

* A full log of price changes has been added.
* An extra check for WooCommerce integration has been added to avoid critical errors when a shortcode is called with the wrong post type. Props for (wapozon11)[https://wordpress.org/support/users/wapozon11/].
* The critical error for old PHP has been fixed. Props for [mlodyno](https://github.com/mlodyno). [Issue #2](https://github.com/iworks/omnibus/issues/2).
* The deprecated `get_product` function has been updated with the `wc_get_product` function. Props for [kanlukasz](https://github.com/kanlukasz).
* The handling of products in the shortcode has been improved.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.2.
* The settings link on the plugins screen has been changed.
* The user ID has been added to the price log.
* Unnecessary use of the function `sprintf()` has been fixed. Props for [Aleksander Mirecki](https://geekroom.pl/).

= 2.3.4 (2023-02-04) =

* Checking for the WooCommerce version has been added. The minimum WooCommerce version required is 5.5.0.

= 2.3.3 (2023-02-03) =

* The fatal error on the options page has been fixed. Props for [nekodo88](https://wordpress.org/support/users/nekodo88/).

= 2.3.2 (2023-01-27) =

* The `iworks_omnibus_add_price_log_data` filter has been added to allow for the modification of logged data.
* The `iworks_omnibus_wc_get_lowest_price` filter has been added to allow getting the lowest price log entry.
* The `strip_tags` param has been added to the `omnibus_price_message` to allow stripe HTML tags.
* The `template` param has been added to the `omnibus_price_message` to allow users to use their own message template.

= 2.3.1 (2023-01-25) =

* An issue with the inform message when taxes are enabled has been fixed. Props for [Niko Vittaniemi](https://wordpress.org/support/users/nikov/).
* The `_iwo_price_last_change` custom field has been added.

= 2.3.0 (2023-01-24) =

* The configuration for WooCommerce has been heavily remodeled.
* The plugin configuration has been moved from "WooCommerce/Settings/Products/Omnibus Directive" to "WooCommerce/Settings/Omnibus."
* The `iworks_omnibus_message_template` filter has been added to allow changing the message template.
* The `iworks_omnibus_add_price_log_skip` filter has been added to allow skip logging of prices.
* The short format for the admin products list page has been added.
* An issue with the shortcode ignoring configuration settings has been fixed. Props for [marktylczynski](https://wordpress.org/support/users/marktylczynski/).
* The integration with the "Tutor LMS" plugin has been improved.

= 2.2.3 (2023-01-12) =

* To avoid incorrect filter calls without a second parameter, the `woocommerce_duplicate_product_exclude_meta` filter function now has a default value for the second parameter.
* Unquoted attribute values in HTML have been fixed. Props for [Michał](https://wordpress.org/support/users/mkrawczykowski/).

= 2.2.2 (2023-01-10) =

* When browsing the products, the plugin will try to complete the data; if it is missing, it will save the current price with the date of the last modification of the product.
* The filter `orphan_replace` from the [Orphans](https://wordpress.org/plugins/sierotki/) plugins has been added to the price message.

= 2.2.1 (2023-01-10) =

* An issue with ignoring taxes by price is replaced by a placeholder. Props for [Agniesz Kalukoszek](https://wordpress.org/support/users/agnieszkalukoszek/).

= 2.2.0 (2023-01-09) =

* Placeholders: `{price}`, `{timestamp}`, `{days}` and `{when}` has been added to the price message.
* A few new message display positions have been added.
* An issue with saving the last price change has been fixed. It was saved only when the sale price was changed, not always when the price was changed. Props for [Rafał Bieleniewicz](https://wordpress.org/support/users/bielen2k/).

= 2.1.6 (2023-01-08) =

* In order to reduce confusion, the default displayed data for a product in the admin panel that does not have a previous price saved has been changed.
* Resolved an issue with retrieving Omnibus price for variant. Props for [Mychal](https://wordpress.org/support/users/mychal/).
* The ability to toggle the Omnibus message when we do not have enough previous data available has been added. By default, it shows the current price.

= 2.1.5 (2023-01-08) =

* Resolved an issue with retrieving Omnibus price for variants. Props for [Mychal](https://wordpress.org/support/users/mychal/).
* The ability to toggle the Omnibus message on WooCommerce Cart Page has been added. By default is hidden.
* The settings screen has been slightly improved.

= 2.1.4 (2023-01-07) =

* Missing check for content has been fixed. Props for [kowaliix](https://wordpress.org/support/users/kowaliix/).
* Resolved an issue with retrieving Omnibus data for variants. Props for [Mychal](https://wordpress.org/support/users/mychal/).
* The action `omnibus/loaded` has been added. It is fired at the and of `plugins_loaded` action.
* The "do not show" option has been added for anybody who wants to use the action or the shortcode.

= 2.1.3 (2023-01-05) =

* Fixed issue with getting ID. Props for [shamppi](https://wordpress.org/support/users/shamppi/).

= 2.1.2 (2023-01-05) =

* Fixed typo.

= 2.1.1 (2023-01-05) =

* The wrong default for the "Display only for the product on sale" field has been fixed. Props for [krzyszt](https://wordpress.org/support/users/krzyszt/).

= 2.1.0 (2023-01-05) =

* Default values from the LearnPress configuration have been added.
* Exclude meta keys from WooCommerce product duplication has been added.
* The LearnPress plugin configuration has been added.
* The LearnPress plugin configuration has been removed from the WooCommerce configuration.
* The link to the LearnPress Omnibus configuration has been added to the plugin row actions.
* The link to the WooCommerce Omnibus configuration has been added to the plugin row actions.

= 2.0.2 (2023-01-04) =

* Price-checking so as not to log it if it's not there has been added.
* The regular price is saved now when the on-sale price is empty.

= 2.0.1 (2023-01-04) =

* Resolved a problem with empty prices in history.

= 2.0.0 (2023-01-04) =

* Adequate implementation of Directive (EU) 2019/2161 - Article 6a.
* Custom tax-related messages have been removed.
* Data saving has been restricted to only published items.
* The `get_sale_price()` function has been used instead of `get_price()`.

= 1.2.6 (2023-01-03) =

* Renamed the plugin "Omnibus — show the lowest price" instead of "Omnibus — Show the lowest price of a product."
* The ability to configure custom messages has been added.
* The options have been reordered.

= 1.2.5 (2023-01-03) =

* The ability to handle product prices with or without taxes has been added.
* The ability to toggle the Omnibus message only for products on sale has been added. This is turned off by default.
* Two tax-related versions of the message have been added.

= 1.2.4 (2023-01-02) =

* Clarified the meaning of the "Shop Page" setting.
* The ability to toggle the Omnibus message in any other place has been added. By default is hidden.
* The `is_main_query()` function check has been added to checking `is_single()`.

= 1.2.3 (2023-01-02) =

* The ability to toggle the Omnibus message on any loop has been added. By default is hidden.
* The `omnibus_price_message` shortcode has been added.

= 1.2.2 (2023-01-02) =

* A warning in the related products loop check has been fixed.  Props for [pietrzyk25](https://wordpress.org/support/users/pietrzyk25/).
* If no data is available, displaying the current price as the lowest has been added.

= 1.2.1 (2023-01-02) =

* Corrected a critical error that occurred while adding a new product. Props for [rask44](https://wordpress.org/support/users/rask44/).

= 1.2.0 (2023-01-02) =

* Added support for the "Easy Digital Downloads" plugin.
* Renamed the plugin "Omnibus — Show the lowest price of a product" instead of "Omnibus."
* The `iworks_omnibus_days` filter has been added to the number of days amount.
* The `iworks_omnibus_integration_woocommerce_price_lowest` filter has been added.
* The `iworks_omnibus_message` filter has been added to the message.
* The `iworks_omnibus_show` filter has been added.
* The `iworks_omnibus_wc_lowest_price_message` action has been added to show the Omnibus message by product ID.
* The ability to toggle the Omnibus message on the taxonomy page has been added. By default is hidden.
* The ability to toggle the Omnibus message when the price was not changed has been added. By default is shown.

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

= 2.5.0 =
* The ability to show the regular price as the last one available before the promotion was introduced. Check it on WordPress Admin -> WooCommerce -> Settings -> Omnibus -> Messages.

= 2.1.0 =

Better integration with the LearnPress plugin has been added.

= 2.0.0 =

The most important change is the correct implementation of the directive, which says that the last lowest price from 30 days after the activation of the promotion should be presented. This means that the price may be earlier than the last 30 days and may be higher than the current promotional price.

