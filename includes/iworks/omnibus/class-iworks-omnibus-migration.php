<?php

abstract class iworks_omnibus_migration {
	/**
	 * option name form migration to v3 status
	 *
	 * @since 4.0.0
	 */
	protected $option_name_migration_4_status = 'iworks_omnibus_data_migration_v4';

	/**
	 * root
	 */
	protected $root;

	/**
	 * plugin_file
	 */
	protected $plugin_file;


	protected function __construct() {
		/**
		 * set plugin root
		 *
		 * @since 2.3.4
		 */
		$this->root        = dirname( __DIR__, 3 );
		$this->plugin_file = $this->root . '/omnibus.php';
	}

	/**
	 * get template
	 *
	 * @since 2.3.4
	 */
	protected function get_file( $file, $group = '' ) {
		$filename      = sprintf(
			'%s/assets/templates/%s%s.php',
			$this->root,
			'' === $group ? '' : $group . '/',
			$file
		);
		$template_file = '';
		foreach ( explode( '/', $filename ) as $part ) {
			if ( empty( $part ) ) {
				continue;
			}
			$template_file .= '/';
			$template_file .= sanitize_file_name( $part );
		}
		return $template_file;
	}

	protected function migration_update_status( $name, $status ) {
		switch ( $status ) {
			case 'started':
			case 'migrated':
				$result = update_option( $name, $status );
				if ( ! $result ) {
					add_option( $name, $status );
				}
				return;
		}
		delete_option( $name );
	}
}
