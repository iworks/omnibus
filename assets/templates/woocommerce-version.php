<?php
/**
 * Notice displayed in admin panel.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly
?>
<div class="notice notice-error">
	<h2><?php esc_html_e( 'Omnibus: installed version of WooCommerce is too low!', 'omnibus' ); ?></h2>
<?php
/* translators: %1$s current version of WooCommerce, %2$s: required version of WooCommerce */
$content = __( 'The WooCommerce version you are using (%1$s) is too low, and our plugin cannot work with it. Please update WooCommerce to at least version %2$s for the Omnibus plugin to work properly.', 'omnibus' );
echo wpautop( wp_kses_post( sprintf( $content, $args['version-current'], $args['version-minimal'] ) ) );
?>
</div>
