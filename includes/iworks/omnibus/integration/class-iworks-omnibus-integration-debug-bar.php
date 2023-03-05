<?php
/*

Copyright 2023-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_omnibus_integration_debug_bar' ) ) {
	return;
}

include_once dirname( dirname( __FILE__ ) ) . '/class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_debug_bar extends iworks_omnibus_integration {

	public function __construct() {
		add_filter( 'debug_bar_panels', array( $this, 'filter_debug_bar_panels' ) );
	}

	public function filter_debug_bar_panels( $panels ) {
		include_once __DIR__ . '/class-iworks-omnibus-integration-debug-bar-panel.php';
		$panels[] = new iworks_omnibus_integration_debug_bar_panel();
		return $panels;
	}

}
