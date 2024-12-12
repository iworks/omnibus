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
	 * Add price log
	 *
	 * @since 1.0.0
	 */
	private function add_price_log( $post_id, $price, $update_last_drop ) {
	}

	/**
	 * Save price history
	 *
	 * @since 1.0.0
	 */
	protected function save_price_history( $post_id, $price ) {
	}

	/**
	 * get last recorded price
	 *
	 * @since 1.0.0
	 */
	private function get_last_price( $post_id ) {
	}

	/**
	 * Get lowest price in history
	 *
	 * @since 1.0.0
	 */
	protected function _get_lowest_price_in_history( $lowest, $post_id ) {
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


	public function filter_get_log_array( $log, $id ) {
		return apply_filters( 'iworks/omnibus/logger/v4/get/log/array', array(), $id );
	}

	protected function _get_v4_lowest_price_in_history( $post_id ) {
		return new WP_Error( 'no_price', __( 'There is no price data in history.', 'omnibus' ) );
	}

	/**
	 * Add Omnibus message to price.
	 *
	 * @since 4.0.0
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


	protected function get_message_text( $type ) {
		switch ( $type ) {
			case 'no_data':
				$message = esc_html__( 'No Previous Price', 'omnibus' );
				if ( 'yes' === get_option( $this->get_name( 'message_settings' ) ) ) {
					$message = get_option( $this->get_name( 'message_no_data' ) );
				}
				return $this->message_wrapper( $message );
		}
		return '';
	}

	/**
	 * wrapper
	 *
	 * @since 4.0.0
	 */
	private function message_wrapper( $text ) {
		return apply_filters(
			'iworks/omnibus/message/wrapper',
			sprintf(
				'<%3$s class="iworks-omnibus" data-iwo-version="%2$s">%1$s</%3$s>',
				$text,
				esc_attr( $this->version ),
				apply_filters( 'iworks/omnibus/message/wrapper/tag', 'p' )
			)
		);
	}

	/**
	 * maybe update log price table
	 *
	 * @since 4.0.0
	 */
	protected function maybe_update_last_saved_prices( $data ) {
		l( $data );
	}

	/**
	 * get last saved prices by id
	 *
	 *
	 * @since 4.0.0
	 */
	protected function get_last_saved_prices_by_id( $object_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			sprintf(
				'select * from %s where omnibus_id = %%d order by created desc limit 1',
				$wpdb->iworks_omnibus
			),
			$object_id
		);
		$data  = $wpdb->get_row( $query );
		if ( empty( $data ) ) {
			$data = new WP_Error(
				'omnibus',
				sprintf(
					/* translators: %d object ID */
					esc_html__( 'There is no saved prices for id: %d.' . 'omnibus' ),
					$object_id
				)
			);
		}
		return $data;
	}
}

