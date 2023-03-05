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


class iworks_omnibus_integration_debug_bar_panel extends Debug_Bar_Panel {

	function init() {
		$this->title( __( 'Omnibus', 'omnibus' ) );
	}

	function prerender() {
		$this->set_visible( ! is_admin() );
	}

	function render() {
		echo '<div id="debug-bar-omnibus">';
		if ( is_singular( 'product' ) ) {
		}
		echo '</div>';
	}
}

