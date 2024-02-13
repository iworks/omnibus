<?php
/**
 * Notice displayed in admin panel.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?>
<div class="notice notice-error">
	<h2><?php esc_html_e( 'Omnibus: data migration required!', 'omnibus' ); ?></h2>
<?php
$content  = __( 'The plugin uses an old version of data collection and data migration is required for the plugin to function properly.', 'omnibus' );
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
			<p><a href="<?php echo add_query_arg( 'page', 'omnibus-migration-v3', admin_url( 'tools.php' ) ); ?>" class="button button-primary" ><?php echo esc_html( __( 'Go to migration page', 'omnibus' ) ); ?></a></p>
		</div>
</div>
