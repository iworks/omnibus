<?php
/**
 * Notice displayed in admin panel.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?>
<div class="notice notice-error">
	<h2><?php esc_html_e( 'Two plugins are enabled: a and "WP Desk Omnibus" and "Omnibus — show the lowest price".', 'omnibus' ); ?></h2>
<?php
$content  = '';
$content .= __( 'We recommend disabling one of them to avoid problems with price display.', 'omnibus' );
$content .= PHP_EOL;
$content .= PHP_EOL;
$content .= sprintf(
	_n( 'There is %d item to convert from the "WP Desk Omnibus" plugin.', 'There are %d items to convert.', intval( $args['count'] ), 'omnibus' ),
	$args['count']
);
$content .= PHP_EOL;
$content .= PHP_EOL;
if ( 'started' === $args['status'] ) {
	$content .= '<span style="font-size:2em;font-weight:bold;">';
	$content .= __( 'The migration has already started but is not finished! It can cause problems with Omnibus prices.', 'omnibus' );
	$content .= '</span>';
	$content .= PHP_EOL;
	$content .= PHP_EOL;
}
$content .= sprintf( '<strong>%s</strong>', __( 'We highly recommend creating a copy of the database before starting the migration.', 'Omnibus' ) );
echo wpautop( wp_kses_post( $content ) );
?>
		<div class="iworks-rate-buttons">
			<p style="display: flex;flex-wrap: true;gap: 2em;align-items:center">
<a href="<?php echo esc_url( add_query_arg( 'plugin_status', 'active',  admin_url( 'plugins.php' ) ) ); ?>" class="button" ><?php echo esc_html( __( 'Go to Plugins Page', 'omnibus' ) ); ?></a>
<a href="<?php echo add_query_arg( 'page', 'omnibus-migration-v4-wp-desk', admin_url( 'tools.php' ) ); ?>" class="button button-primary" ><?php echo esc_html( __( 'Turn off "WP Desk Omnibus" and go to migration page', 'omnibus' ) ); ?></a>
</p>

		</div>
</div>
