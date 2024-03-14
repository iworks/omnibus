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
	 * Plugin version
	 *
	 * @since 3.0.0
	 */
	private $version = 'PLUGIN_VERSION';

	/**
	 * meta field name
	 *
	 * @since 1.0.0
	 */
	protected $meta_name = '_iwo_price_lowest';

	/**
	 * meta field name last change
	 *
	 * @since 1.0.0
	 */
	protected $meta_name_last_change = '_iwo_price_last_change';

	/**
	 * last price drop timestamp
	 *
	 * @since 2.0.0
	 */
	protected $last_price_drop_timestamp = '_iwo_last_price_drop_timestamp';

	/**
	 * just a price log
	 *
	 * @since 2.3.5
	 */
	protected $meta_price_log_name = '_iwo_price_log';

	/**
	 * Add price log
	 *
	 * @since 1.0.0
	 */
	private function add_price_log( $post_id, $price, $update_last_drop ) {
		/**
		 * allow to skip price log
		 *
		 * @since 2.3.0
		 */
		if ( apply_filters( 'iworks_omnibus_add_price_log_skip', false, $post_id, $price, $update_last_drop ) ) {
			return;
		}
		/**
		 * save only for published posts
		 *
		 * @since 2.0.0
		 */
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		/**
		 * do not save empty price
		 *
		 * @since 2.0.2
		 */
		if ( empty( $price ) ) {
			return;
		}
		$now  = time();
		$data = array(
			'price'     => $price,
			'timestamp' => $now,
			'user_id'   => get_current_user_id(),
		);
		/**
		 * promotion schedule
		 */
		if (
			isset( $_POST['_sale_price_dates_from'] )
			&& ! empty( $_POST['_sale_price_dates_from'] )
		) {
			$data['timestamp'] = strtotime( $_POST['_sale_price_dates_from'] );
		}
		/**
		 * if price is an array
		 *
		 * @since 2.5.4
		 */
		if ( is_array( $price ) ) {
			foreach ( $price as $key => $value ) {
				$data[ $key ] = $value;
			}
		}
		/**
		 * convert timestamp to human time
		 */
		$data['human'] = wp_date( 'c', $data['timestamp'] );
		/**
		 * filter data
		 *
		 * @since 2.3.2
		 */
		$data = apply_filters( 'iworks_omnibus_add_price_log_data', $data, $post_id );
		/**
		 * add
		 */
		if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
			do_action( 'iworks/omnibus/v3/add/log', $post_id, $data, $this->get_days() );
		} else {
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
	}

	/**
	 * Save price history
	 *
	 * @since 1.0.0
	 */
	protected function save_price_history( $post_id, $price ) {
		$price_last = $this->get_last_price( $post_id );
		if ( is_wp_error( $price_last ) || 'unknown' === $price_last ) {
			$this->add_price_log( $post_id, $price, true );
			return;
		}
		/**
		 * filter prices names
		 *
		 * @since 2.3.9
		 */
		$prices_names = apply_filters(
			'iworks_omnibus/save_price_history/prices_names',
			array(
				'price',
				'price_sale',
				'price_regular',
			)
		);
		/**
		 * check to log
		 */
		$do_log = false;
		foreach ( $prices_names as $key ) {
			if ( $do_log ) {
				continue;
			}
			if ( ! isset( $price_last[ $key ] ) ) {
				$do_log = true;
				continue;
			}
			if ( ! isset( $price_last[ $key ] ) ) {
				$do_log = true;
				continue;
			}
			if ( floatval( $price_last[ $key ] ) !== floatval( $price[ $key ] ) ) {
				$do_log = true;
				continue;
			}
		}
		if ( $do_log ) {
			$update_last_drop = false;
			if (
				isset( $price['price_sale'] )
				&& isset( $price_last['price_sale'] )
			) {
				$update_last_drop = floatval( $price['price_sale'] ) !== floatval( $price_last['price_sale'] );
			}
			$this->add_price_log( $post_id, $price, $update_last_drop );
		}
	}

	/**
	 * get last recorded price
	 *
	 * @since 1.0.0
	 */
	private function get_last_price( $post_id ) {
		$last = array();
		if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
			return apply_filters( 'iworks/omnibus/v3/get/last/price', $last, $post_id );
		} else {
			$meta = get_post_meta( $post_id, $this->meta_name );
			if ( empty( $meta ) ) {
				return 'unknown';
			}
			$old       = strtotime( sprintf( '-%d days', $this->get_days() ) );
			$timestamp = 0;
			foreach ( $meta as $data ) {
				if ( $old >= $data['timestamp'] ) {
					continue;
				}
				if ( $timestamp < $data['timestamp'] ) {
					$timestamp = $data['timestamp'];
					$last      = $data;
				}
			}
		}
		return $last;
	}

	/**
	 * Get lowest price in history
	 *
	 * @since 1.0.0
	 */
	protected function _get_lowest_price_in_history( $lowest, $post_id ) {
		if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
			$price = apply_filters( 'iworks/omnibus/v3/get/lowest/price/array', $lowest, $post_id );
		} else {
			$meta = get_post_meta( $post_id, $this->meta_name );
			if ( empty( $meta ) ) {
				return array();
			}
			uasort( $meta, array( $this, 'sort_meta_by_price' ) );
			$price_lowest              = array();
			$now                       = time();
			$price                     = array(
				'init'      => true,
				'price'     => PHP_INT_MAX,
				'timestamp' => $now,
				'from'      => $now,
			);
			$old                       = strtotime( sprintf( '-%d days', $this->get_days() ) );
			$last_price_drop_timestamp = intval( get_post_meta( $post_id, $this->last_price_drop_timestamp, true ) );
			if ( ! empty( $last_price_drop_timestamp ) ) {
				$old = strtotime( sprintf( '-%d days', $this->get_days() ), $last_price_drop_timestamp );
			}
			foreach ( $meta as $data ) {
				if ( floatval( $data['price'] ) > $price['price'] ) {
					continue;
				}
				if ( intval( $old ) >= intval( $data['timestamp'] ) ) {
					continue;
				}
				if ( intval( $last_price_drop_timestamp ) === intval( $data['timestamp'] ) ) {
					continue;
				}
				$price         = $data;
				$price['from'] = $old;
			}
			if ( isset( $price['init'] ) ) {
				return array();
			}
		}
		/**
		 * return if empty
		 *
		 * @since 3.0.0
		 */
		if ( empty( $price ) ) {
			return $price;
		}
		/**
		 * Diff in days between promotion and now.
		 *
		 * @since 2.3.9
		 */
		if ( isset( $price['timestamp'] ) ) {
			$price['diff-in-days']    = round( ( time() - $price['timestamp'] ) / DAY_IN_SECONDS );
			$price['human_timestamp'] = gmdate( 'c', $price['timestamp'] );
		}
		/**
		 * don't propagate data if there is more days
		 *
		 * @since 2.4.0
		 */
		if (
			isset( $price['diff-in-days'] )
			&& $this->get_days() < $price['diff-in-days']
		) {
			return array();
		}
		/**
		 * human reaadable data & debug
		 *
		 * @since 2.4.0
		 */
		$price['days'] = $this->get_days();
		if ( isset( $price['from'] ) ) {
			$price['human_from'] = gmdate( 'c', $price['from'] );
		}
		return $price;
	}

	/**
	 * Add Omnibus message to price.
	 *
	 * @since 1.0.0
	 * @since 2.3.2 param $message has been added.
	 *
	 * @param $price
	 * @param $price_lowest
	 * @param callback $format_price_callback Price format callback function.
	 * @param string $message Message template.
	 */
	protected function add_message( $price, $price_lowest, $format_price_callback = null, $message = null ) {
		if ( ! is_array( $price_lowest ) ) {
			return $price;
		}
		if ( ! isset( $price_lowest['price'] ) ) {
			return $price;
		}
		if ( empty( $price_lowest['price'] ) ) {
			return $price;
		}
		/**
		 * Set message template if it is needed
		 */
		if ( empty( $message ) ) {
			/* translators: %2$s: rich html price */
			$message = __( 'Previous lowest price was %2$s.', 'omnibus' );
			if (
				'custom' === get_option( $this->get_name( 'message_settings' ), 'no' )
				|| 'yes' === get_option( $this->get_name( 'message_settings' ), 'no' )
			) {
				$message = get_option(
					$this->get_name( 'message' ),
					/* translators: %2$s: rich html price */
					__( 'Previous lowest price was %2$s.', 'omnibus' )
				);
			}
		}
		/**
		 * mesage template filter
		 *
		 * @since 2.3.0
		 */
		$message = apply_filters( 'iworks_omnibus_message_template', $message, $price, $price_lowest );
		if ( empty( $message ) ) {
			return $price;
		}
		/**
		 * price to show
		 */
		$price_to_show = $price_lowest['price'];
		if ( isset( $price_lowest['price_sale'] ) ) {
			$price_to_show = $price_lowest['price_sale'];
		}
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
				} else {
					global $product;
					if ( is_object( $product ) ) {
						$tax = new WC_Tax();
						if ( ! empty( $tax ) ) {
							$taxes = $tax->get_rates( $product->get_tax_class() );
							if ( ! empty( $taxes ) ) {
								$t             = array_shift( $taxes );
								$price_to_show = ( 100 + $t['rate'] ) * $price_to_show / 100;
							}
						}
					}
				}
			}
		}
		if ( is_callable( $format_price_callback ) ) {
			$price_to_show = $format_price_callback( $price_to_show );
		}
		/**
		 * add attributes
		 */
		$attributes = array(
			'data-iwo-version' => $this->version,
		);
		foreach ( $price_lowest as $key => $value ) {
			$attributes[ sprintf( 'data-iwo-%s', $key ) ] = esc_attr( $value );
		}
		$attribute_data_string = '';
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			$attribute_data_string .= sprintf(
				' %s="%s"',
				esc_html( $attribute_name ),
				esc_attr( $attribute_value )
			);
		}
		$price .= apply_filters(
			'iworks_omnibus_message',
			sprintf(
				'<p class="iworks-omnibus"%s>%s</p>',
				$attribute_data_string,
				sprintf(
					$message,
					$this->get_days(),
					$price_to_show
				)
			)
		);
		/**
		 * replace
		 *
		 * @since 2.1.7
		 */
		$price = preg_replace( '/{days}/', $this->get_days(), $price );
		$price = preg_replace( '/{price}/', $price_to_show, $price );
		if ( isset( $price_lowest['timestamp'] ) ) {
			$price = preg_replace( '/{timestamp}/', $price_lowest['timestamp'], $price );
			$price = preg_replace( '/{when}/', date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ), $price );
		}
		/**
		 * use filter `iworks_omnibus_message_html`
		 */
		$message = apply_filters( 'iworks_omnibus_message_html', $price, $price_lowest );
		/**
		 * use filter `orphan_replace`
		 *
		 * @since 2.2.2
		 */
		$message = apply_filters( 'orphan_replace', $message );
		/**
		 * return
		 */
		return $message;
	}

	public function get_name( $name = '' ) {
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

	protected function settings_title() {
		return array(
			'title' => __( 'Omnibus Directive Settings', 'omnibus' ),
			'type'  => 'title',
			'id'    => $this->meta_name,
		);
	}

	protected function settings_days() {
		return array(
			'title'             => __( 'Number of days', 'omnibus' ),
			'desc'              => __( 'This controls the number of days to show. According to the Omnibus Directive, minimum days is 30 after curent sale was started.', 'omnibus' ),
			'id'                => $this->get_name( 'days' ),
			'default'           => '30',
			'type'              => 'number',
			'custom_attributes' => array(
				'min' => 30,
			),
		);
	}

	protected function settings_messages() {
		return array(
			array(
				'type'  => 'title',
				'title' => __( 'Messages', 'omnibus' ),
			),
			$this->settings_message_settings(),
			$this->settings_message(),
			array(
				'type' => 'sectionend',
				'id'   => $this->get_name( 'sectionend' ),
			),
		);
	}

	protected function settings_message_settings() {
		return array(
			'title'         => __( 'Price Message', 'omnibus' ),
			'checkboxgroup' => 'start',
			'type'          => 'radio',
			'default'       => 'default',
			'id'            => $this->get_name( 'message_settings' ),
			'options'       => array(
				'default' => __( 'Default messages (recommended).', 'omnibus' ),
				'custom'  => __( 'Custom messages.', 'omnibus' ),
			),
			'desc'          => __( 'Custom messages will be used only when you choose "Custom messages." option.', 'omnibus' ),
		);
	}

	protected function settings_message() {
		$description = array();
		/* translators: Do not translate {price}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {price} placeholder to display price.', 'omnibus' );
		/* translators: Do not translate {timestamp}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {timestamp} placeholder to display timestamp.', 'omnibus' );
		/* translators: Do not translate {days}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {days} placeholder to display days.', 'omnibus' );
		/* translators: Do not translate {when}, it is the replacement placeholder ! */
		$description[] = esc_html__( 'Use the {when} placeholder to display date.', 'omnibus' );
		return array(
			'type'          => 'text',
			'id'            => $this->get_name( 'message' ),
			'default'       => __( 'Previous lowest price: {price}.', 'omnibus' ),
			'checkboxgroup' => 'end',
			'desc'          => str_replace(
				array( '{', '}' ),
				array( '<code>{', '}</code>' ),
				implode( '<br />', $description )
			),
		);
	}

	/**
	 * sort log by price
	 */
	private function sort_meta_by_price( $a, $b ) {
		if ( floatval( $a['price'] ) === floatval( $b['price'] ) ) {
			return 0;
		}
		return  floatval( $a['price'] ) > floatval( $b['price'] ) ? 1 : -1;
	}

	/**
	 * check is turn on for different cases
	 *
	 * @since 2.3.2
	 */
	protected function is_on( $value ) {
		if ( empty( $value ) ) {
			return false;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_numeric( $value ) ) {
			return 0 < $value;
		}
		if ( is_string( $value ) ) {
			switch ( $value ) {
				case 'yes':
				case '1':
				case 'on':
					return true;
			}
		}
		return false;
	}

	public function filter_get_log_array( $log, $post_id ) {
		$log = array();
		if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
			$log = apply_filters( 'iworks/omnibus/v3/get/price/log/array', $log, $post_id );
		} else {
			$changes = get_post_meta( $post_id, $this->meta_price_log_name );
			if ( is_array( $changes ) ) {
				foreach ( $changes as $one ) {
					$one['post_id'] = $post_id;
					$log[]          = $one;
				}
			}
			if ( ! empty( $changes ) ) {
				usort( $log, array( $this, 'usort_log_array' ) );
			}
		}
		return $log;
	}

	protected function price_log( $post_id, $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		$data['timestamp'] = time();
		add_post_meta(
			$post_id,
			$this->meta_price_log_name,
			$data
		);
	}

	protected function usort_log_array( $a, $b ) {
		if (
			is_array( $a )
			&& is_array( $b )
			&& isset( $a['timestamp'] )
			&& isset( $b['timestamp'] )
		) {
			if ( $a['timestamp'] === $b['timestamp'] ) {
				return 0;
			}
			return $a['timestamp'] > $b['timestamp'] ? 1 : -1;
		}
		return 0;
	}

	/**
	 * message: price is not available
	 *
	 * @since 2.0.2
	 */
	protected function get_message_price_is_not_available() {
		if ( 'yes' == get_option( $this->get_name( 'message_settings' ), 'no' ) ) {
			$v = get_option( $this->get_name( 'message_no_data' ), false );
			if ( ! empty( $v ) ) {
				return $v;
			}
		}
		return __( 'The previous price is not available.', 'omnibus' );
	}

	/**
	 * get all prices
	 *
	 * @since 2.5.1
	 */
	public function filter_get_prices_array( $log, $post_id ) {
		if ( 'migrated' === apply_filters( 'iworks/omnibus/v3/get/migration/status', false ) ) {
			return apply_filters( 'iworks/omnibus/v3/get/price/log/array', array(), $post_id );
		}
		return get_post_meta( $post_id, $this->meta_name );
	}
}

