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
		 * really delete?
		 */
		if ( 'yes' !== get_option( $this->get_name( 'allow_to_delete' ) ) ) {
			return;
		}
		/**
		 * delete
		 */
		$query = $wpdb->prepare(
			sprintf(
				'DELETE FROM %s WHERE price_sale_from < ( CURRENT_DATE - INTERVAL %%d DAY )',
				$wpdb->iworks_omnibus
			),
			max( 31, intval( get_option( $this->get_name( 'days_delete' ) ) ) )
		);
		$wpdb->query( $query );
		/**
		 * do action after
		 */
		do_action( 'iworks/omnibus/action/after/delete_older_records' );
	}
}
