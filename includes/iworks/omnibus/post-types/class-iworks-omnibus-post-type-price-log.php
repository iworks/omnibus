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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

include_once 'class-iworks-omnibus-post-type.php';

class iWorks_Omnibus_Post_Type_Price_Log extends iWorks_Omnibus_Post_Type {

	/**
	 * Post type key. Must not exceed 20 characters and may only contain
	 * lowercase alphanumeric characters, dashes, and underscores. See
	 * sanitize_key().
	 *
	 * https://core.trac.wordpress.org/browser/tags/6.4/src/wp-includes/post.php#L1579
	 */

	protected $post_type_name = 'iw_omnibus_price_log';

	/**
	 * prices names
	 *
	 * @ince 3.0.0
	 *
	 */
	private $prices_names = array();

	/**
	 * numer of days
	 *
	 * @since 3.0.0
	 */
	private $days = 30;

	public function __construct() {
		parent::__construct();
		/**
		 * days
		 */
		$this->days = apply_filters(
			'iworks_omnibus_days',
			max( 30, intval( get_option( '_iwo_price_lowest_days', 30 ) ) )
		);
		/**
		 * names
		 */
		$this->prices_names = apply_filters(
			'iworks/omnibus/prices/names',
			array(
				'price_sale',
				'price_regular',
			)
		);
		/**
		 * add log
		 */
		add_action( 'iworks/omnibus/v3/add/log', array( $this, 'add_log' ), 10, 3 );
		/**
		 * get the last saved price array
		 */
		add_filter( 'iworks/omnibus/v3/get/last/price', array( $this, 'get_last_price_array' ), 10, 2 );
		/**
		 * get the lowest saved price array
		 */
		add_filter( 'iworks/omnibus/v3/get/lowest/price/array', array( $this, 'get_lowest_price_array' ), 10, 2 );
	}

	public function register() {
		register_post_type( $this->post_type_name );
	}

	private function get_common_wp_query_args( $post_id ) {
		$after  = strtotime( sprintf( '-%d day', $this->days ) );
		$before = strtotime( '+1 day' );
		return array(
			'post_type'      => $this->post_type_name,
			'post_parent'    => $post_id,
			'post_status'    => 'publish',
			'date_query'     => array(
				'relation' => 'AND',
				'after'    => array(
					'year'  => gmdate( 'Y', $after ),
					'month' => gmdate( 'n', $after ),
					'day'   => gmdate( 'j', $after ),
				),
				'before'   => array(
					'year'  => gmdate( 'Y', $before ),
					'month' => gmdate( 'n', $before ),
					'day'   => gmdate( 'j', $before ),
				),
			),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
	}

	public function get_lowest_price_array( $price, $post_id ) {
		$data      = array(
			'price' => $price,
		);
		$args      = wp_parse_args(
			array(
				'meta_key' => 'price_sale',
				'orderby'  => 'meta_value_num',
				'order'    => 'ASC',
			),
			$this->get_common_wp_query_args( $post_id )
		);
		$the_query = new WP_Query( $args );
		if ( sizeof( $the_query->posts ) ) {
			$price_log_id = array_shift( $the_query->posts );
			$meta         = get_post_meta( $price_log_id );
			foreach ( $meta as $meta_key => $meta_array ) {
				$data[ $meta_key ] = array_shift( $meta_array );
			}
		}

		return $data;
	}

	public function get_last_price_array( $last_price_array, $post_id ) {
		return $this->get_last_log( $post_id );
	}

	private function get_last_log( $post_id ) {
		$args = apply_filters(
			/**
			 * Allows to change WP_Query args for get_last_log() function.
			 *
			 * @since 3.0.0
			 */
			'iworks/omnibus/v3/get_last_log/wp_query/args',
			wp_parse_args(
				array(
					'order'   => 'DESC',
					'orderby' => 'date',
				),
				$this->get_common_wp_query_args( $post_id )
			)
		);
		$the_query = new WP_Query( $args );
		if ( sizeof( $the_query->posts ) ) {
			$last_price_log_id = array_shift( $the_query->posts );
			$data              = array();
			foreach ( $this->prices_names as $price_name ) {
				$value = get_post_meta( $last_price_log_id, $price_name, true );
				if ( ! empty( $value ) ) {
					$data[ $price_name ] = $value;
				}
			}
			if ( ! empty( $data ) ) {
				return $data;
			}
		}
		return new WP_Error(
			'empty',
			__( 'The price log is empty for selected product.', 'omnibus' )
		);
	}

	private function should_be_created_log_entry( $post_id, $data ) {
		$check = $this->get_last_log( $post_id );
		if ( is_wp_error( $check ) ) {
			return true;
		}

		foreach ( $this->prices_names as $price_name ) {
			if ( ! isset( $check[ $price_name ] ) ) {
				return true;
			}
			if ( floatval( $check[ $price_name ] ) !== floatval( $data[ $price_name ] ) ) {
				return true;
			}
		}
		return false;
	}


	public function add_log( $post_id, $data ) {
		if ( ! $this->should_be_created_log_entry( $post_id, $data ) ) {
			return;
		}
		$postarr = array(
			'post_type'      => $this->post_type_name,
			'post_title'     => sprintf(
				'%s - %s',
				get_the_title( $post_id ),
				$data['timestamp']
			),
			'post_status'    => 'publish',
			'post_parent'    => $post_id,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$result  = wp_insert_post( $postarr );
		if ( $result ) {
			foreach ( $data as $meta_key => $meta_value ) {
				add_post_meta( $result, $meta_key, $meta_value, true );
			}
		}
	}

}
