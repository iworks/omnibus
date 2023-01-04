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

if ( class_exists( 'iworks_omnibus_integration' ) ) {
	return;
}

abstract class iworks_omnibus_integration {

	/**
	 * meta field name
	 *
	 * @since 1.0.0
	 */
	protected $meta_name = '_iwo_price_lowest';

	/**
	 * last price drop timestamp
	 *
	 * @since 2.0.0
	 */
	protected $last_price_drop_timestamp = '_iwo_last_price_drop_timestamp';

	/**
	 * Add price log
	 *
	 * @since 1.0.0
	 */
	private function add_price_log( $post_id, $price, $update_last_drop ) {
		/**
		 * save only for published posts
		 *
		 * @since 2.0.0
		 */
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		$now  = time();
		$data = array(
			'price'     => $price,
			'timestamp' => $now,
		);
		add_post_meta( $post_id, $this->meta_name, $data );
		/**
		 * update last price drop timestamp
		 *
		 * @since 2.0.0
		 */
		if ( $update_last_drop ) {
			if ( ! update_post_meta( $post_id, $this->last_price_drop_timestamp, $now ) ) {
				add_post_meta( $post_id, $this->last_price_drop_timestamp, $now, true );
			}
		}
	}

	/**
	 * Save price history
	 *
	 * @since 1.0.0
	 */
	protected function save_price_history( $post_id, $price ) {
		$price_last = $this->get_last_price( $post_id );
		if ( 'unknown' === $price_last ) {
			$this->add_price_log( $post_id, $price, true );
		}
		if (
			is_array( $price_last )
			&& $price !== $price_last['price']
		) {
			$this->add_price_log( $post_id, $price, $price < $price_last['price'] );
		}
	}

	/**
	 * get last recorded price
	 *
	 * @since 1.0.0
	 */
	private function get_last_price( $post_id ) {
		$meta = get_post_meta( $post_id, $this->meta_name );
		if ( empty( $meta ) ) {
			return 'unknown';
		}
		$old       = strtotime( sprintf( '-%d days', $this->get_days() ) );
		$timestamp = 0;
		$last      = array();
		foreach ( $meta as $data ) {
			if ( $old >= $data['timestamp'] ) {
				continue;
			}
			if ( $timestamp < $data['timestamp'] ) {
				$timestamp = $data['timestamp'];
				$last      = $data;
			}
		}
		return $last;
	}

	/**
	 * LearnPress: get lowest price in days
	 *
	 * @since 1.0.1
	 */
	protected function learnpress_get_lowest_price_in_history( $post_id ) {
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}
		$course = learn_press_get_course( $post_id );
		if ( ! is_a( $course, 'LP_Course' ) ) {
			return array();
		}
		return $this->_get_lowest_price_in_history( $course->get_price(), $post_id );
	}

	/**
	 * Get lowest price in history
	 *
	 * @since 1.0.0
	 */
	protected function _get_lowest_price_in_history( $lowest, $post_id ) {
		$meta         = get_post_meta( $post_id, $this->meta_name );
		$price_lowest = array();
		if ( empty( $meta ) ) {
			return $price_lowest;
		}
		$now                       = time();
		$price                     = array(
			'init'      => true,
			'price'     => PHP_INT_MAX,
			'timestamp' => $now,
			'from'      => $now,
		);
		$old                       = strtotime( sprintf( '-%d days', $this->get_days() ) );
		$last_price_drop_timestamp = get_post_meta( $post_id, $this->last_price_drop_timestamp, true );
		if ( ! empty( $last_price_drop_timestamp ) ) {
			$old = strtotime( sprintf( '-%d days', $this->get_days() ), $last_price_drop_timestamp );
		}
		foreach ( $meta as $data ) {
			if ( $old >= $data['timestamp'] ) {
				continue;
			}
			if ( $last_price_drop_timestamp <= $data['timestamp'] ) {
				continue;
			}
			if ( $data['price'] <= $price['price'] ) {
				$price         = $data;
				$price['from'] = $old;
			}
		}
		if ( isset( $price['init'] ) ) {
			return array();
		}
		return $price;
	}

	/**
	 * Add Omnibus message to price.
	 *
	 * @since 1.0.0
	 */
	protected function add_message( $price, $price_lowest, $format_price_callback = null ) {
		if (
			is_array( $price_lowest )
			&& isset( $price_lowest['price'] )
			&& ! empty( $price_lowest['price'] )
		) {
			$message = __( 'Previous lowest price: %2$s.', 'omnibus' );
			if ( 'custom' === get_option( $this->get_name( 'message_settings' ), 'default' ) ) {
				$message = get_option(
					$this->get_name( 'message' ),
					__( 'Previous lowest price: %2$s.', 'omnibus' )
				);
			}
			$price_to_show = $price_lowest['price'];
			/**
			 * WooCommerce: include tax
			 */
			if ( 'no' === get_option( 'woocommerce_prices_include_tax' ) ) {
				if ( 'yes' === get_option( $this->get_name( 'include_tax' ), 'yes' ) ) {
					if (
						isset( $price_lowest['price_including_tax'] )
						&& $price_lowest['price_including_tax'] > $price_to_show
					) {
						$price_to_show = $price_lowest['price_including_tax'];
					}
				}
			}
			if ( is_callable( $format_price_callback ) ) {
				$price_to_show = $format_price_callback( $price_to_show );
			}
			$price .= apply_filters(
				'iworks_omnibus_message',
				sprintf(
					'<p class="iworks-omnibus" data-previous-timestamp="%d">%s</p>',
					esc_attr( isset( $price_lowest['from'] ) ? $price_lowest['from'] : '' ),
					sprintf(
						$message,
						$this->get_days(),
						$price_to_show
					)
				)
			);
		}
		return $price;
	}

	protected function get_name( $name = '' ) {
		if ( empty( $name ) ) {
			return $this->meta_name;
		}
		return sanitize_title(
			sprintf(
				'%s_%s',
				$this->meta_name,
				$name
			)
		);
	}

	/**
	 * get numbers of days
	 *
	 * @since 1.1.0
	 */
	protected function get_days() {
		return apply_filters(
			'iworks_omnibus_days',
			max( 30, intval( get_option( $this->get_name( 'days' ), 30 ) ) )
		);
	}

	/**
	 * Header helper
	 *
	 * @since 1.1.0
	 */
	protected function print_header( $class = '' ) {
		printf(
			'<h3%s>%s</h3>',
			empty( $class ) ? '' : sprintf( ' class="%s"', esc_attr( $class ) ),
			esc_html__( 'Omnibus Directive', 'omnibus' )
		);
	}

}

