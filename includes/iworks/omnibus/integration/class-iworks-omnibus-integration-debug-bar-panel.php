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

	private $days = 30;

	function init() {
		$this->title( __( 'Omnibus', 'omnibus' ) );
	}

	function prerender() {
		$this->set_visible( ! is_admin() );
	}

	function render() {
		/**
		 * days
		 */
		$this->days = apply_filters(
			'iworks_omnibus_days',
			max( 30, intval( get_option( '_iwo_price_lowest_days', 30 ) ) )
		);
		echo '<div id="debug-bar-omnibus">';
		if ( is_singular( 'product' ) ) {
			$product = wc_get_product( get_the_ID() );
			printf(
				'<h3>%s</h3>',
				esc_html__( 'Product info', 'omnibus' )
			);
			echo '<dl>';
			printf( '<dt>%s</dt>', esc_html__( 'ID', 'omnibus' ) );
			printf( '<dd>%s</dd>', esc_html( get_the_ID() ) );
			printf( '<dt>%s</dt>', esc_html__( 'Type', 'omnibus' ) );
			printf( '<dd>%s</dd>', esc_html( $product->get_type() ) );
			printf( '<dt>%s</dt>', esc_html__( 'Title', 'omnibus' ) );
			printf( '<dd>%s</dd>', esc_html( $product->get_title() ) );
			echo '</dl>';
			/**
			 * select function
			 */
			$show_log_table_function = 'show_log_table';
			if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
				$show_log_table_function = 'show_log_table_v3';
			}
			/**
			 * Product Changes Log
			 */
			echo '<hr>';
			printf(
				'<h4>%s</h4>',
				esc_html__( 'Product changes log', 'omnibus' )
			);
			/**
			 * product type
			 */
			switch ( $product->get_type() ) {
				case 'simple':
				case 'course':
					$this->$show_log_table_function(
						apply_filters( 'iworks_omnibus_price_log_array', array(), get_the_ID() )
					);
					break;
				case 'variable':
					foreach ( $product->get_available_variations() as $variation ) {
						printf( '<h5>%s</h5>', esc_html( get_the_title( $variation['variation_id'] ) ) );
						$this->$show_log_table_function(
							apply_filters( 'iworks_omnibus_price_log_array', array(), $variation['variation_id'] )
						);
					}
					break;
				default:
					echo wpautop( __( 'The selected product type is not supported.', 'omnibus' ) );
					break;
			}
			/**
			 * Product Saved Prives
			 */
			if ( 'migrated' !== apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
				echo '<hr>';
				printf(
					'<h4>%s</h4>',
					esc_html__( 'Product saved prices', 'omnibus' )
				);
				/**
				 * product type
				 */
				switch ( $product->get_type() ) {
					case 'simple':
					case 'course':
						$this->$show_log_table_function(
							apply_filters( 'iworks_omnibus_prices_array', array(), get_the_ID() )
						);
						break;
					case 'variable':
						foreach ( $product->get_children() as $variation_id ) {
							printf(
								'<p>%s (id: %d)</p>',
								esc_html( get_the_title( $variation_id ) ),
								esc_html( $variation_id )
							);
							$this->$show_log_table_function(
								apply_filters( 'iworks_omnibus_prices_array', array(), $variation_id )
							);
						}
						break;
					default:
						echo wpautop( __( 'The selected product type is not supported.', 'omnibus' ) );
						break;
				}
			}
		}
		echo '</div>';
	}

	private function show_log_table( $log ) {
		if ( empty( $log ) ) {
			echo wpautop( esc_html__( 'There is no price history recorded.', 'omnibus' ) );
			return;
		}
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

	private function show_log_table_v3( $log ) {
		if ( empty( $log ) ) {
			echo wpautop( esc_html__( 'There is no price history recorded.', 'omnibus' ) );
			return;
		}
		echo '<table class="widefat fixed striped debug-bar-wp-query-list">';
		echo '<thead>';
		echo '<tr>';
		printf( '<th>%s</th>', esc_html__( 'ID', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Regular Price', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Sale Price', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Days', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Date', 'omnibus' ) );
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ( $log as $one ) {
			if ( isset( $one['timestamp'] ) ) {
				$one['diff-in-days'] = round( ( time() - $one['timestamp'] ) / DAY_IN_SECONDS );
			}
			if ( isset( $one['diff-in-days'] ) && $one['diff-in-days'] > $this->days ) {
				echo '<tr style="opacity:.3">';
			} else {
				echo '<tr>';
			}
			printf( '<td>%s</td>', $one['ID'] );
			printf( '<td>%s</td>', isset( $one['price_regular'] ) ? $one['price_regular'] : $one['price'] );
			printf( '<td>%s</td>', empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] );
			printf( '<td>%d</td>', isset( $one['diff-in-days'] ) ? $one['diff-in-days'] : '&mdash;' );
			printf( '<td>%s</td>', date_i18n( 'Y-m-d H:i', $one['timestamp'] ) );
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}
}

