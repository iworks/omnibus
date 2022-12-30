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

if ( class_exists( 'iworks_omnibus_integration_easydigitaldownloads' ) ) {
	return;
}

include_once dirname( dirname( __FILE__ ) ) . '/class-iworks-omnibus-integration.php';

class iworks_omnibus_integration_easydigitaldownloads extends iworks_omnibus_integration {

	public function __construct() {
		add_action( 'updated_postmeta', array( $this, 'action_updated_postmeta' ), 10, 4 );
		add_action( 'edd_purchase_link_end', array( $this, 'action_edd_purchase_link_end' ), 10, 2 );
		add_filter( 'edd_settings_sections_gateways', array( $this, 'filter_edd_settings_sections_gateways' ), 99 );
		add_filter( 'edd_settings_gateways', array( $this, 'filter_edd_settings_gateways' ) );
	}

	public function filter_edd_settings_sections_gateways( $sections ) {
		$sections['iworks-omnibus'] = __( 'Omnibus', 'omnibus' );
		return $sections;
	}

	public function filter_edd_settings_gateways( $settings ) {
		// $settings = array(
			// $this->get_

		return $settings;
	}


	public function action_updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'edd_price' !== $meta_key ) {
			return;
		}
		$post = get_post( $object_id );
		if ( empty( $post ) ) {
			return;
		}
		if ( 'download' !== get_post_type( $post ) ) {
			return;
		}
		if ( 'publish' !== get_post_status( $post ) ) {
			return;
		}
		$this->save_price_history( $object_id, $meta_value );
	}

	public function action_edd_purchase_link_end( $post_id, $args ) {
		$this->run();
	}

	/**
	 * run helper
	 *
	 * @since 1.2.0
	 */
	private function run( $context = 'view' ) {
		if ( ! is_singular( 'download' ) ) {
			return;
		}
		$post_id      = get_the_ID();
		$price_lowest = $this->_get_lowest_price_in_history( get_post_meta( $post_id, 'edd_price', true ), $post_id );
		if ( empty( $price_lowest ) ) {
			return;
		}
		$message = $this->add_message( '', $price_lowest, 'wc_price' );
		if ( 'return' === $context ) {
			return $message;
		}
		echo $message;
	}
}


