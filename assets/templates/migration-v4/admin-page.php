<?php
/**
 * Notice displayed in admin panel.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly
?>
<div class="wrap omnibus-migration">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Omnibus: Data Migration Tool', 'omnibus' ); ?></h1>
<?php if ( 0 < $args['count'] ) { ?>
<form id="omnibus-migration-v4-form" method="post">
	<input type="hidden" name="page" value="omnibus-migration-v4">
	<input type="hidden" name="init" value="<?php echo esc_attr( $args['count'] ); ?>" id="omnibus-migration-v4-init">
	<input type="hidden" name="items" value="<?php echo esc_attr( $args['count'] ); ?>" id="omnibus-migration-v4-items">
	<p class="omnibus-migration-v4-p">
	<?php
	echo
		sprintf(
			esc_html(
				/* translators: %s number of items to migrate */
				_n(
					'There is %s item to migrate!',
					'There is %s items to migrate!',
					$args['count'],
					'omnibus'
				)
			),
			sprintf( '<span id="omnibus-migration-v4-p-counter">%d</span>', $args['count'] )
		);
	?>
	</p>
<p>
	<progress max="100" value="0" id="omnibus-migration-v4-p-progress"></progress>
</p>
	<?php wp_nonce_field( 'omnibus-migration-v4', 'omnibus-migration-v4-nonce' ); ?>
	<ul>
		<li class="req"><label><input type="checkbox" required name="backup" id="omnibus-migration-v4-form-backup" /><?php esc_html_e( 'I confirm that I have made a backup copy of the website database.', 'omnibus' ); ?></label></li>
		<li><label><input type="checkbox" name="older" id="omnibus-migration-v4-form-older" /> <?php esc_html_e( 'Do not import price changes older than 40 days. They will be deleted.', 'omnibus' ); ?></label></li>
	</ul>
	<p><button id="omnibus-migration-v4-form-button" class="button button-primary" disabled><?php esc_html_e( 'Start!', 'onibus' ); ?></button></p>
</form>
	<?php
} elseif ( 'migrated' === $args['status'] ) {
	echo '<div class="notice notice-info">';
	echo wpautop( esc_html__( 'Data migration to version 4.0.0 of the database was successfully completed!', 'omnibus' ) );
	echo '</div>';
} else {
	echo '<div class="notice notice-error">';
	echo wpautop( esc_html__( 'Something went wrong.', 'omnibus' ) );
	echo '</div>';
}
echo '<p class="omnibus-migration-admin-dashboard">';
	printf(
		'<a class="button" href="%s">%s</a>',
		admin_url(),
		esc_html__( 'Go to Admin Dashboard', 'omnibus' )
	);
	echo '</p>';
	?>
</div>
