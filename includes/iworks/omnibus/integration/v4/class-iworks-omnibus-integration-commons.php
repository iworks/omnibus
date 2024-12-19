<?php
/*

Copyright 2024-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_omnibus_integration_commons' ) ) {
	return;
}

include_once 'class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_commons extends iworks_omnibus_integration {

	public function __construct() {
		/**
		 * delete older records
		 *
		 * @since 4.0.0
		 */
		add_action( 'shutdown', array( $this, 'action_shutdown_maybe_delete_older_records' ) );
	}

	/**
	 * Delete older records
	 *
	 * @since 4.0.0
	 */
	public function action_shutdown_maybe_delete_older_records() {
		remove_action( 'shutdown', array( $this, 'action_shutdown_maybe_delete_older_records' ) );
		global $wpdb;
		/**
		 * do action before
		 */
		do_action( 'iworks/omnibus/action/before/delete_older_records' );
		/**
		 * not in ajax
		 */
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		/**
		 * configuration
		 */
		$configuration = apply_filters(
			'iworks/omnibus/action/delete_older_records/configuration',
			array()
		);
		if ( empty( $configuration ) ) {
			return;
		}
		/**
		 * delete
		 */
		foreach ( $configuration as $one ) {
			do_action( 'iworks/omnibus/action/before/delete_older_records/' . $one['product_origin'] );
			$query = $wpdb->prepare(
				"delete from $wpdb->iworks_omnibus where product_origin = %s and price_sale_from < ( current_date - interval %d year )",
				$one['product_origin'],
				max( 1, intval( $one['delete_years'] ) )
			);
			$wpdb->query( $query );
			do_action( 'iworks/omnibus/action/after/delete_older_records/' . $one['product_origin'] );
		}
		/**
		 * do action after
		 */
		do_action( 'iworks/omnibus/action/after/delete_older_records' );
	}

}
