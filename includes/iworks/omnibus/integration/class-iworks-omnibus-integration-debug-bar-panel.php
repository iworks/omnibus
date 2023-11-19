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
			printf(
				'<h3>%s</h3>',
				esc_html__( 'Product info', 'omnibus' )
			);
			printf(
				'<h4>%s</h4>',
				esc_html__( 'Product changes log', 'omnibus' )
			);
			$log = apply_filters( 'iworks_omnibus_price_log_array', array(), get_the_ID() );
			if ( empty( $log ) ) {
				esc_html_e( 'There is no price history recorded.', 'omnibus' );
			} else {
				echo '<table class="widefat fixed striped debug-bar-wp-query-list">';
				echo '<thead>';
				echo '<tr>';
				printf( '<th>%s</th>', esc_html__( 'Regular Price', 'omnibus' ) );
				printf( '<th>%s</th>', esc_html__( 'Sale Price', 'omnibus' ) );
				printf( '<th>%s</th>', esc_html__( 'Date', 'omnibus' ) );
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ( $log as $one ) {
					echo '<tr>';
					printf( '<td>%s</td>', $one['price'] );
					printf( '<td>%s</td>', empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] );
					printf( '<td>%s</td>', date_i18n( 'Y-m-d H:i', $one['timestamp'] ) );
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
			}
			printf(
				'<h4>%s</h4>',
				esc_html__( 'Product saved prices', 'omnibus' )
			);
			$log = apply_filters( 'iworks_omnibus_prices_array', array(), get_the_ID() );
			if ( empty( $log ) ) {
				esc_html_e( 'There is no price history recorded.', 'omnibus' );
			} else {
				echo '<table class="widefat fixed striped debug-bar-wp-query-list">';
				echo '<thead>';
				echo '<tr>';
				printf( '<th>%s</th>', esc_html__( 'Regular Price', 'omnibus' ) );
				printf( '<th>%s</th>', esc_html__( 'Sale Price', 'omnibus' ) );
				printf( '<th>%s</th>', esc_html__( 'Date', 'omnibus' ) );
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ( $log as $one ) {
					echo '<tr>';
					printf( '<td>%s</td>', $one['price'] );
					printf( '<td>%s</td>', empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] );
					printf( '<td>%s</td>', date_i18n( 'Y-m-d H:i', $one['timestamp'] ) );
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
			}
		}
		echo '</div>';
	}
}

