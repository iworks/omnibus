<?php
/**
 * Notice displayed in admin panel.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Omnibus: Data Migration Tool', 'omnibus' ); ?></h1>
<?php if ( 0 < $args['meta'] ) { ?>
<form id="omnibus-migration-v3-form" method="post">
	<input type="hidden" name="page" value="omnibus-migration-v3">
	<input type="hidden" name="init" value="<?php echo esc_attr( $args['meta'] ); ?>" id="omnibus-migration-v3-init">
	<p class="omnibus-migration-v3-p">
	<?php
	echo
		sprintf(
			esc_html(
				/* translators: %s number of fields to migrate */
				_n(
					'There is %s field to migrate!',
					'There is %s fields to migrate!',
					$args['meta'],
					'omnibus'
				)
			),
			sprintf( '<span id="omnibus-migration-v3-p-counter">%d</span>', $args['meta'] )
		);
	?>
										</p>
<p>
	<progress max="100" value="0" id="omnibus-migration-v3-p-progress"></progress>
</p>
	<?php wp_nonce_field( 'omnibus-migration-v3', 'omnibus-migration-v3-nonce' ); ?>
<p><button id="omnibus-migration-v3-form-button" class="button button-primary"><?php esc_html_e( 'Start!', 'onibus' ); ?></button></p>
</form>
<?php } ?>
</div>
