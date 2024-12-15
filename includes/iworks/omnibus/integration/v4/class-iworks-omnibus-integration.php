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

	protected string $mysql_data_format = 'Y-m-d H:i:s';

	/**
	 * helper to decide show it or no
	 *
	 * @since 4.0.0
	 */
	abstract protected function should_it_show_up( $post_id );

	/**
	 * helper to add logs
	 *
	 * @since 4.0.0
	 */
	abstract protected function maybe_add_price_log( $element );

	public function get_name( $name = '' ) {
		if ( empty( $name ) ) {
			return $this->meta_name;
		}
		$name = sanitize_title(
			sprintf(
				'%s_%s',
				$this->meta_name,
				$name
			)
		);
		return apply_filters(
			'iworks/omnibus/option/name/' . $name,
			$name
		);
	}

	/**
	 * Add price log
	 *
	 * @since 4.0.0
	 */
	private function add_log( $data ) {
		global $wpdb;
		if ( empty( $data['price_sale_from'] ) ) {
			$data['price_sale_from'] = date( $this->mysql_data_format );
		} else {
			$this->delete_future_logs( $data['post_id'] );
		}
		if (
			empty( $data['price_sale'] )
			|| 'now' === $data['price_sale_from']
		) {
			$data['price_sale_from'] = date( $this->mysql_data_format );
		}
		$wpdb->insert(
			$wpdb->iworks_omnibus,
			$data,
			array( '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%d' )
		);
	}

	/**
	 * helper to delete futore log when sale has be chenged
	 *
	 * @since 4.0.0
	 */
	private function delete_future_logs( $post_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"delete from $wpdb->iworks_omnibus where post_id = %d and price_sale_from > %s",
				$post_id,
				date( $this->mysql_data_format )
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
			array(
				'title'             => __( 'Number of days', 'omnibus' ),
				'desc'              => __( 'This controls the number of days to show. According to the Omnibus Directive, minimum days is 30 after curent sale was started.', 'omnibus' ),
				'id'                => $this->get_name( 'days', '' ),
				'default'           => '30',
				'type'              => 'number',
				'css'               => 'width: 80px;',
				'custom_attributes' => array(
					'min' => 30,
				),
			),
			array(
				'title'   => __( 'Delete Log Items', 'omnibus' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'id'      => $this->get_name( 'allow_to_delete', '' ),
				'desc'    => __( 'Allow to delete older items', 'omnibus' ),
				'class'   => 'iworks_omnibus_delete_older',
			),
			array(
				'title'             => __( 'Delete After', 'omnibus' ),
				'desc'              => __( 'This controls the number of days to delete changes.', 'omnibus' ),
				'id'                => $this->get_name( 'days_delete', '' ),
				'default'           => '45',
				'type'              => 'number',
				'css'               => 'width: 80px;',
				'custom_attributes' => array(
					'min' => 30,
				),
				'class'             => 'iworks_omnibus_delete_older_field',
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

	public function filter_get_log_array( $log, $id ) {
		return apply_filters( 'iworks/omnibus/logger/v4/get/log/array', array(), $id );
	}

	protected function get_lowest_price_by_post_id( $post_id, $sale_price ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"select * from $wpdb->iworks_omnibus where
				post_id = %d
				and price_sale <> %f
				and price_sale_from <= %s
				and price_sale_from > %s
			order by price_sale asc limit 1",
			$post_id,
			$sale_price,
			date( $this->mysql_data_format ),
			date( $this->mysql_data_format, strtotime( sprintf( '-%d days', $this->get_days() ) ) )
		);
		$data  = $wpdb->get_row( $query, ARRAY_A );
		if ( empty( $data ) ) {
			return new WP_Error( 'no_price', __( 'There is no price data in history.', 'omnibus' ) );
		}
		return $data;
	}

	/**
	 * Add Omnibus message to price.
	 *
	 * @since 4.0.0
	 *
	 * @param $price_html
	 * @param $price_sale
	 * @param $price_lowest
	 * @param callback $format_price_callback Price format callback function.
	 */
	protected function add_message( $price_html, $price_regular, $price_sale, $price_lowest, $format_price_callback = null, $message = null ) {
		if ( is_wp_error( $price_lowest ) ) {
			$missing_price_messsage_status = get_option( $this->get_name( 'missing' ) );
			switch ( $missing_price_messsage_status ) {
				case 'inform':
				case 'current':
				case 'regular':
					return $this->add_message_runner(
						$price_html,
						$price_regular,
						$price_sale,
						$price_lowest,
						$format_price_callback,
						$this->get_message_text( $missing_price_messsage_status, $price_regular, $price_sale, $format_price_callback )
					);
			}
			return $price_html;
		}
		if ( ! is_array( $price_lowest ) ) {
			return $price_html;
		}
		if ( ! isset( $price_lowest['price_sale'] ) ) {
			return $price_html;
		}
		if ( empty( $price_lowest['price_sale'] ) ) {
			return $price_html;
		}
		/**
		 * run
		 */
		return $this->add_message_runner( $price_html, $price_regular, $price_sale, $price_lowest, $format_price_callback, $message );
	}

	private function add_message_runner( $price_html, $price_regular, $price_sale, $price_lowest, $format_price_callback, $message ) {
		/**
		 * Set message template if it is needed
		 */
		if ( empty( $message ) ) {
			/* translators: do not translate placeholders in braces */
			$message         = __( '{price} Lowest price from {days} days before the discount.', 'omnibus' );
			$message_setting = get_option( $this->get_name( 'message_settings' ), 'no' );
			if ( 'custom' === $message_setting || $this->is_on( $message_setting ) ) {
				$message = get_option( $this->get_name( 'message' ), $message );
			}
		}
		/**
		 * mesage template filter
		 *
		 * @since 2.3.0
		 */
		$message = apply_filters( 'iworks_omnibus_message_template', $message, $price_html, $price_regular, $price_sale, $price_lowest, $format_price_callback );
		if ( empty( $message ) ) {
			return $price_html;
		}
		/**
		 * handle no price but message
		 */
		if ( is_wp_error( $price_lowest ) ) {
			return $price_html . $message;
		}
		/**
		 * price to show
		 */
		$price_to_show = $price_lowest['price_sale'];
		if ( is_callable( $format_price_callback ) ) {
			$price_to_show = $format_price_callback( $price_to_show );
		}
		$price_html .= $this->message_wrapper(
			sprintf(
				$message,
				$this->get_days(),
				$price_to_show
			)
		);
		/**
		 * replace
		 *
		 * @since 2.1.7
		 */
		$price_html = $this->replace_options_based_placeholders( $price_html );
		$price_html = preg_replace( '/{price}/', $price_to_show, $price_html );
		if ( isset( $price_lowest['timestamp'] ) ) {
			$price_html = preg_replace( '/{timestamp}/', $price_lowest['timestamp'], $price_html );
			$price_html = preg_replace( '/{when}/', date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ), $price_html );
		}
		/**
		 * use filter `iworks_omnibus_message_html`
		 */
		$message = apply_filters( 'iworks_omnibus_message_html', $price_html, $price_lowest );
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

	protected function get_message_text( $type, $price_regular = null, $price_sale = null, $format_price_callback = null ) {
		$message = '';
		switch ( $type ) {
			case 'no_data':
			case 'inform':
				$message = esc_html__( 'There is no recorded price from {days} days before the discount.', 'omnibus' );
				if ( $this->is_on( get_option( $this->get_name( 'message_settings' ) ) ) ) {
					$message = get_option( $this->get_name( 'message_no_data' ) );
				}
				break;
			case 'current':
			case 'regular':
				$message = __( '{price} Lowest price from {days} days before the discount.', 'omnibus' );
				if ( $this->is_on( get_option( $this->get_name( 'message_settings' ) ) ) ) {
					$message = get_option( $this->get_name( 'message' ) );
				}
				$price_to_show = 'regular' === $type ? $price_regular : $price_sale;
				if ( is_callable( $format_price_callback ) ) {
					$price_to_show = $format_price_callback( $price_to_show );
				}
				$message = preg_replace( '/{price}/', $price_to_show, $message );
				break;
		}
		if ( ! empty( $message ) ) {
			$message = $this->replace_options_based_placeholders( $message );
			$message = $this->message_wrapper( $message );
		}
		return $message;
	}

	/**
	 * wrapper
	 *
	 * @since 4.0.0
	 */
	protected function message_wrapper( $text ) {
		return apply_filters(
			'iworks/omnibus/message/wrapper',
			sprintf(
				'<br class="iworks-omnibus-br"><%3$s class="iworks-omnibus" data-iwo-version="%2$s">%1$s</%3$s>',
				$text,
				esc_attr( $this->version ),
				apply_filters( 'iworks/omnibus/message/wrapper/tag', 'span' )
			)
		);
	}

	/**
	 * maybe update log price table
	 *
	 * @since 4.0.0
	 */
	protected function maybe_add_last_saved_prices( $data ) {
		$last = $this->get_last_saved_prices_by_id( $data['post_id'] );
		/**
		 * there is no data, nothing to compare
		 */
		if ( is_wp_error( $last ) ) {
			$this->add_log( $data );
			return;
		}
		if (
			floatval( $last['price_regular'] ) === floatval( $data['price_regular'] )
			&& floatval( $last['price_sale'] ) === floatval( $data['price_sale'] )
		) {
			return;
		}
		$this->add_log( $data );
	}

	/**
	 * get last saved prices by id
	 *
	 *
	 * @since 4.0.0
	 */
	protected function get_last_saved_prices_by_id( $post_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"select * from $wpdb->iworks_omnibus where
				post_id = %d
				and price_sale_from <= %s
			order by price_sale_from desc limit 1",
			$post_id,
			date( $this->mysql_data_format )
		);
		$data  = $wpdb->get_row( $query, ARRAY_A );
		if ( empty( $data ) ) {
			$data = new WP_Error(
				'omnibus',
				sprintf(
					/* translators: %d object ID */
					esc_html__( 'There is no saved prices for id: %d.', 'omnibus' ),
					$post_id
				)
			);
		}
		return $data;
	}

	public function _get_lowest_price_in_history( $post_id ) {
	}

	/**
	 * replace options based placeholder in a message
	 *
	 * @since 4.0.0
	 */
	private function replace_options_based_placeholders( $message ) {
		$message = preg_replace( '/{days}/', $this->get_days(), $message );

		return $message;
	}
}

