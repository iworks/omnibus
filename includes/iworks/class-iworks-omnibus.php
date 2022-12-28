<?php
/*

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

if ( class_exists( 'iworks_omnibus' ) ) {
	return;
}

class iworks_omnibus {

	private $version   = 'PLUGIN_VERSION';
	private $meta_name = '_iwo_price_lowest';

	public function __construct() {
		/**
		 * WooCommerce
		 */
		add_action( 'woocommerce_product_options_pricing', array( $this, 'action_woocommerce_product_options_pricing' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'action_woocommerce_variation_options_pricing' ), 10, 3 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'action_woocommerce_save_price_history' ), 10, 1 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_woocommerce_get_price_html' ), 10, 2 );
		/**
		 * WwordPress
		 */
		add_action( 'shutdown', array( $this, 'action_shutdown_maybe_delete_old' ) );
	}

	private function get_price_html_for_variable( $price, $product ) {
		$price_lowest = array();
		foreach ( $product->get_available_variations() as $variable ) {
			$o = $this->get_lowest_price_in_30_days( $variable['variation_id'] );
			if ( ! isset( $price_lowest['price'] ) ) {
				$price_lowest = $o;
				continue;
			}
			if ( $o['price'] < $price_lowest['price'] ) {
				$price_lowest = $o;
			}
		}
		return $this->add_message( $price, $price_lowest );
	}

	public function filter_woocommerce_get_price_html( $price, $product ) {
		if ( 'variable' === $product->get_type() ) {
			return $this->get_price_html_for_variable( $price, $product );
		}
		$price_lowest = $this->get_lowest_price_in_30_days( $product->get_id() );
		if ( empty( $price_lowest ) ) {
			return $price;
		}
		if ( $price_lowest['price'] >= $product->get_price() ) {
			return $price;
		}
		return $this->add_message( $price, $price_lowest );
	}

	private function add_message( $price, $price_lowest ) {
		if (
			is_array( $price_lowest )
			&& isset( $price_lowest['price'] )
		) {
			$price .= sprintf(
				'<p class="iworks-omnibus">%s</p>',
				sprintf(
					__( 'The lowest price in 30 days: %s', 'omnibus' ),
					wc_price( $price_lowest['price'] )
				)
			);
		}
		return $price;
	}

	public function action_shutdown_maybe_delete_old() {
	}

	public function action_woocommerce_save_price_history( $product ) {
		$price = $product->get_price();
		if ( empty( $price ) ) {
			return;
		}
		$product_id = $product->get_id();
		$price_last = $this->get_last_price( $product_id );
		if ( 'unknown' === $price_last ) {
			$this->add_price_log( $product_id, $price );
		}
		if (
			is_array( $price_last )
			&& $price !== $price_last['price']
		) {
			$this->add_price_log( $product_id, $price );
		}
	}

	public function action_woocommerce_product_options_pricing() {
		$product_id   = intval( $_GET['post'] );
		$price_lowest = $this->get_lowest_price_in_30_days( $product_id );
		echo '<hr />';
		printf( '<p class="description">%s</p>', esc_html__( 'Omnibus Price', 'omnibus' ) );
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : $price_lowest['price'],
				'data_type'         => 'price',
				'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc_tip'          => true,
				'description'       => __( 'The lowest price in 30 days', 'omnibus' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ),
				'data_type'         => 'text',
				'label'             => __( 'Date', 'omnibus' ),
				'desc_tip'          => true,
				'description'       => __( 'The date when lowest price in 30 days occurred.', 'omnibus' ),
			)
		);
	}

	public function action_woocommerce_variation_options_pricing( $loop, $variation_data, $variation ) {
		$product_id   = $variation->ID;
		$price_lowest = $this->get_lowest_price_in_30_days( $product_id );
		echo '</div>';
		echo '<div>';
		printf( '<p class="form-row form-row-full">%s</p>', esc_html__( 'Omnibus Price', 'omnibus' ) );
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : $price_lowest['price'],
				'data_type'         => 'price',
				'label'             => __( 'Price', 'omnibus' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc_tip'          => true,
				'description'       => __( 'The lowest price in 30 days', 'omnibus' ),
				'wrapper_class'     => 'form-row form-row-first',
			)
		);
		woocommerce_wp_text_input(
			array(
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'value'             => empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ),
				'data_type'         => 'text',
				'label'             => __( 'Date', 'omnibus' ),
				'desc_tip'          => true,
				'description'       => __( 'The date when lowest price in 30 days occurred.', 'omnibus' ),
				'wrapper_class'     => 'form-row form-row-last',
			)
		);
	}

	public function get_version( $file = null ) {
		if ( defined( 'IWORKS_DEV_MODE' ) && IWORKS_DEV_MODE ) {
			if ( null != $file ) {
				$file = dirname( $this->base ) . $file;
				if ( is_file( $file ) ) {
					return md5_file( $file );
				}
			}
			return rand( 0, PHP_INT_MAX );
		}
		return $this->version;
	}

	private function get_last_price( $product_id ) {
		$meta = get_post_meta( $product_id, $this->meta_name );
		if ( empty( $meta ) ) {
			return 'unknown';
		}
		$old       = strtotime( '-30 days' );
		$timestamp = 0;
		$last      = array();
		foreach ( $meta as $data ) {
			if ( $old > $data['timestamp'] ) {
				// delete_post_meta( $product_id, $this->meta_name, $data );
				continue;
			}
			if ( $timestamp < $data['timestamp'] ) {
				$timestamp = $data['timestamp'];
				$last      = $data;
			}
		}
		return $last;
	}

	private function add_price_log( $product_id, $price ) {
		$data = array(
			'price'     => $price,
			'timestamp' => time(),
		);
		add_post_meta( $product_id, $this->meta_name, $data );
	}

	private function get_lowest_price_in_30_days( $product_id ) {
		$meta         = get_post_meta( $product_id, $this->meta_name );
		$price_lowest = array();
		if ( empty( $meta ) ) {
			return $price_lowest;
		}
		$product = wc_get_product( $product_id );
		$lowest  = $product->get_price();
		$price   = array();
		$old     = strtotime( '-30 days' );
		foreach ( $meta as $data ) {
			if ( $old > $data['timestamp'] ) {
				// delete_post_meta( $product_id, $this->meta_name, $data );
				continue;
			}
			if ( $data['price'] <= $lowest ) {
				$price  = $data;
				$lowest = $data['price'];
			}
		}
		return $price;
	}

}
