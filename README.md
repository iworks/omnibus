# Omnibus - WordPress Plugin

This plugin allows your WooCommerce shop to be compliant with the Directive of the European Parliament and of the Council (EU) 2019/2161 of November 27, 2019, known as the "Omnibus Directive".

This plugin saves the current price and stores it at least for 30 days.

This plugin adds two additional fields in the product edit view – for the lowest price and the effective date.

This information is displayed on the product page.

Read more about [Directive 2019/2161](https://eur-lex.europa.eu/eli/dir/2019/2161/oj).

## Installation

### Standard installation (from WP repository)

This plugin is available in the WordPress repository [Omnibus — show the lowest price](https://wordpress.org/plugins/omnibus/) for free, so you can find it there.

You can also find it in your existing WordPress installation (WPA > Plugins > Add new > Search).

### GitHub release version

Please click [Releases](https://github.com/iworks/omnibus/releases) and download the selected version.

### Development version (from GitHub)

Clone this repository:
```
git clone git@github.com:iworks/omnibus.git
```

It is ready to use.

## How does it work?

This plugin logs prior prices in the custom field `_iwo_price_lowest`. Every time you update your product, the price will be stored in the log (if it differs from the last stored price).

On the `_iwo_last_price_drop_timestamp` plugin, it store a time when the last price was lowered.

On the front end, on a single product page and product listings page, just under the product price, your visitors will see the lowest price in the 30 days before promotion:

![Single Product Page screenshot](https://ps.w.org/omnibus/assets/screenshot-1.png?rev=2840826)

### Shortcode

If you want to display the lowest product price in another place than the default, you can use the shortcode `omnibus_price_message`. A few examples:

Display the lowest price message on the WooCommerce single product page (without passing the product ID as an argument), currency symbol attached:
```
[omnibus_price_message]
```

will show:
```
Previous lowest price: $18.00.
```

Display the lowest price of the other WooCommerce product, along with the currency symbol:

The product with ID 11 had the lowest price:
```
[omnibus_price_message id="11"]
```

### Action

You can also use action for the WooCommerce product:
```
<?php
do_action( 'iworks_omnibus_wc_lowest_price_message' );
?>
```

For any WooCommerce product:
`
do_action( 'iworks_omnibus_wc_lowest_price_message', $product_ID );
`
