<?php
/*
Plugin Name: PLUGIN_TITLE
Text Domain: omnibus
Plugin URI: PLUGIN_URI
Description: PLUGIN_DESCRIPTION
Version: PLUGIN_VERSION
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Copyright 2022-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

/**
 * require: iworks_omnibus class
 */
if ( ! class_exists( 'iworks_omnibus' ) ) {
	require_once dirname( __FILE__ ) . '/includes/iworks/class-iworks-omnibus.php';
}

/**
 * Load Plugin Class
 */

$omnibus = new iworks_omnibus();

/**
 * install & uninstall
 */
register_activation_hook( __FILE__, array( $omnibus, 'register_activation_hook' ) );
register_deactivation_hook( __FILE__, array( $omnibus, 'register_deactivation_hook' ) );

/**
 * iWorks Rate
 *
 * @since 1.0.2
 */
add_action(
	'init',
	function() {
		include_once dirname( __FILE__ ) . '/includes/iworks/rate/rate.php';
		do_action( 'iworks-register-plugin', plugin_basename( __FILE__ ), __( 'Omnibus', 'omnibus' ), 'omnibus' );
	}
);

