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
				case 'course':
				case 'grouped':
				case 'simple':
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
					echo wp_kses_post( wpautop( esc_html__( 'The selected product type is not supported.', 'omnibus' ) ) );
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
					case 'course':
					case 'grouped':
					case 'simple':
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
						echo wp_kses_post( wpautop( esc_html__( 'The selected product type is not supported.', 'omnibus' ) ) );
						break;
				}
			}
		} else {
			printf(
				'<h3>%s</h3>',
				esc_html__( 'Omnibus', 'omnibus' )
			);
			echo wp_kses_post( wpautop( esc_html__( 'The selected content is not a product.', 'omnibus' ) ) );
		}
		echo '</div>';
	}

	private function show_log_table( $log ) {
		if ( empty( $log ) ) {
			echo wp_kses_post( wpautop( esc_html__( 'There is no price history recorded.', 'omnibus' ) ) );
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
			printf( '<td>%s</td>', esc_html( $one['price'] ) );
			printf( '<td>%s</td>', esc_html( empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] ) );
			printf( '<td>%s</td>', esc_html( date_i18n( 'Y-m-d H:i', $one['timestamp'] ) ) );
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	private function show_log_table_v3( $log ) {
		if ( empty( $log ) ) {
			echo wp_kses_post( wpautop( esc_html__( 'There is no price history recorded.', 'omnibus' ) ) );
			return;
		}
		echo '<table class="widefat fixed striped debug-bar-wp-query-list">';
		echo '<thead>';
		echo '<tr>';
		printf( '<th>%s</th>', esc_html__( 'ID', 'omnibus' ) );
		printf( '<th style="color:#07d">%s</th>', esc_html__( 'Regular Price', 'omnibus' ) );
		printf( '<th style="color:#c20">%s</th>', esc_html__( 'Sale Price', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Days', 'omnibus' ) );
		printf( '<th>%s</th>', esc_html__( 'Date', 'omnibus' ) );
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ( $log as $one ) {
			if ( isset( $one['timestamp'] ) ) {
				$one['diff-in-days'] = round( ( time() - intval( $one['timestamp'] ) ) / DAY_IN_SECONDS );
			}
			if ( isset( $one['diff-in-days'] ) && $one['diff-in-days'] > $this->days ) {
				echo '<tr style="opacity:.3">';
			} else {
				echo '<tr>';
			}
			printf( '<td>%s</td>', esc_html( $one['ID'] ) );
			printf( '<td>%s</td>', esc_html( isset( $one['price_regular'] ) ? $one['price_regular'] : $one['price'] ) );
			printf( '<td>%s</td>', esc_html( empty( $one['price_sale'] ) ? '&mdash;' : $one['price_sale'] ) );
			printf( '<td>%d</td>', esc_html( isset( $one['diff-in-days'] ) ? $one['diff-in-days'] : '&mdash;' ) );
			printf( '<td>%s</td>', esc_html( date_i18n( 'Y-m-d H:i', $one['timestamp'] ) ) );
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		/**
		 * chart
		 */
		if ( 2 > count( $log ) ) {
			return;
		}
		$width             = 500;
		$step              = $width / ( count( $log ) - 1 );
		$height            = 100;
		$date_first        = $date_last = null;
		$price_regular_max = $price_regular_min = null;
		$price_sale_max    = $price_sale_min = null;
		foreach ( $log as $one ) {
			if ( isset( $one['price_regular'] ) ) {
				$price = intval( $one['price_regular'] );
				if ( 0 < $price ) {
					if ( null === $price_regular_max ) {
						$price_regular_max = $price_regular_min = $price;
					} else {
						if ( $price_regular_max < $price ) {
							$price_regular_max = $price;
						}
						if ( $price_regular_min > $price ) {
							$price_regular_min = $price;
						}
					}
				}
			}
			if ( isset( $one['price_sale'] ) ) {
				$price = intval( $one['price_sale'] );
				if ( 0 < $price ) {
					if ( null === $price_sale_max ) {
						$price_sale_max = $price_sale_min = $price;
					} else {
						if ( $price_sale_max < $price ) {
							$price_sale_max = $price;
						}
						if ( $price_sale_min > $price ) {
							$price_sale_min = $price;
						}
					}
				}
			}
			if ( isset( $one['timestamp'] ) ) {
				$timestamp = intval( $one['timestamp'] );
				if ( ! empty( $timestamp ) ) {
					if ( null === $date_first ) {
						$date_first = $date_last = $timestamp;
					} else {
						if ( $timestamp < $date_first ) {
							$date_first = $timestamp;
						}
						if ( $timestamp > $date_last ) {
							$date_last = $timestamp;
						}
					}
				}
			}
		}
		$price_max = max( $price_regular_max, $price_sale_max );
		$price_min = min( $price_regular_min, $price_sale_min );
		$max       = $price_max - $price_min;
		echo '<section class="omnibus-draw">';
		printf(
			'<svg viewBox="0 0 %d %d">',
			intval( $width ),
			intval( $height + 10 )
		);
		echo '<line x1="0" y1="0" x2="0" y2="110" stroke="black" stroke-width=".5" />';
		echo '<line x1="0" y1="110" x2="500" y2="110" stroke="black" stroke-width=".5"/>';
		$stroke = 1;
		printf(
			'<polyline fill="none" stroke="#07d" stroke-width="%d" points="',
			intval( $stroke )
		);
		$i = 1;
		foreach ( $log as $one ) {
			$i++;
			if (
				! isset( $one['timestamp'] )
				|| empty( $one['timestamp'] )
			) {
				continue;
			}
			if (
				! isset( $one['price_regular'] )
				|| empty( $one['price_regular'] )
			) {
				continue;
			}
			$timestamp = intval( $one['timestamp'] );
			if ( empty( $timestamp ) ) {
				continue;
			}
			$y = $height * ( $price_max - $one['price_regular'] ) / $max + $stroke;
			printf(
				'%d,%d%s',
				intval( $width - $i * $step ),
				intval( $y ),
				PHP_EOL
			);
		}
		echo '"/>';
		printf(
			'<polyline fill="none" stroke="#c20" stroke-width="%d" points="',
			intval( $stroke )
		);
		$i = 0;
		foreach ( $log as $one ) {
			$i++;
			if (
				! isset( $one['timestamp'] )
				|| empty( $one['timestamp'] )
			) {
				continue;
			}
			if (
				! isset( $one['price_sale'] )
				|| empty( $one['price_sale'] )
			) {
				continue;
			}
			$timestamp = intval( $one['timestamp'] );
			if ( empty( $timestamp ) ) {
				continue;
			}
			$y = $height * ( $price_max - $one['price_sale'] ) / $max + $stroke;
			printf(
				'%d,%d%s',
				intval( $width - $i * $step ),
				intval( $y ),
				PHP_EOL
			);
		}
		echo '"/>';
		echo '</svg>';
		echo '</section>';
	}
}

