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
		add_action( 'edd_after_price_field', array( $this, 'action_edd_after_price_field' ) );
		add_action( 'edd_purchase_link_end', array( $this, 'action_edd_purchase_link_end' ), 10, 2 );
		add_action( 'updated_postmeta', array( $this, 'action_updated_postmeta' ), 10, 4 );
		add_filter( 'edd_download_price_after_html', array( $this, 'filter_edd_download_price_after_html' ), 10, 4 );
		add_filter( 'edd_settings_gateways', array( $this, 'filter_edd_settings_gateways' ) );
		add_filter( 'edd_settings_sections_gateways', array( $this, 'filter_edd_settings_sections_gateways' ), 99 );
		add_filter( 'option__iwo_price_lowest_edd_days', array( $this, 'get_edd_days' ), 10, 2 );
		add_filter( 'option_edd_settings', array( $this, 'set_defaults' ), 10, 2 );
		add_filter( 'pre_option__iwo_price_lowest_edd_days', array( $this, 'get_edd_days' ), 10, 2 );
	}

	public function get_edd_days( $value, $option ) {
		return $this->get_setting( 'days' );
	}

	public function set_defaults( $value, $option ) {
		if ( isset( $value[ $this->get_name( 'days' ) ] ) ) {
			return $value;
		}
		$value[ $this->get_name['days'] ]       = 30;
		$value[ $this->get_name['download'] ]   = 1;
		$value[ $this->get_name['admin_list'] ] = 1;
		$value[ $this->get_name['admin_edit'] ] = 1;
		return $value;
	}
	private function get_setting( $setting_name ) {
		$settings = get_option( 'edd_settings' );
		$name     = $this->get_name( $setting_name );
		if ( isset( $settings[ $name ] ) ) {
			if ( 'days' === $setting_name ) {
				return max( 30, intval( $settings[ $name ] ) );
			}
			return 'yes';
		}
		if ( 'days' === $setting_name ) {
			return 30;
		}
		return 'no';
	}

	public function filter_edd_download_price_after_html( $formatted_price, $download_id, $price, $price_id ) {
		if ( ! $this->should_it_show_up() ) {
			return $formatted_price;
		}
		$price_lowest = $this->get_lowest_price( $download_id );
		if ( empty( $price_lowest ) ) {
			return $formatted_price;
		}
		return $formatted_price . $this->add_message( '', $price_lowest, 'wc_price' );
	}

	private function get_lowest_price( $post_id ) {
		$product = new EDD_Download( $post_id );
		return $this->_get_lowest_price_in_history( $product->get_price(), $post_id );
	}

	public function action_edd_after_price_field( $post_id ) {
		if ( ! $this->should_it_show_up() ) {
			return;
		}
		$price_lowest = $this->get_lowest_price( $post_id );
		$this->print_header();
		?>
	<div class="edd-form-group">
		<div class="edd-form-group__control">
			<table>
				<tr>
					<td><?php esc_html_e( 'Price', 'omnibus' ); ?></td>
					<td>
					<input type="text" class="edd-form-group__input" value="<?php echo empty( $price_lowest ) ? '' : esc_attr( $price_lowest['price'] ); ?>" readonly="readonly" />
						<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php printf( __( 'The lowest price in %d days.', 'omnibus' ), $this->get_days() ); ?>"></span>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Date', 'omnibus' ); ?></td>
					<td>
					<input type="text" class="edd-form-group__input" value="<?php echo empty( $price_lowest ) ? '' : date_i18n( get_option( 'date_format' ), $price_lowest['timestamp'] ); ?>" readonly="readonly" />
						<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php printf( __( 'The date when lowest price in %d days occurred.', 'omnibus' ), $this->get_days() ); ?>"></span>
					</td>
				</tr>
			</table>
		</div>
	</div>
		<?php
	}

	public function filter_edd_settings_sections_gateways( $sections ) {
		$sections['iworks-omnibus'] = __( 'Omnibus', 'omnibus' );
		return $sections;
	}

	public function filter_edd_settings_gateways( $settings ) {
		$settings['iworks-omnibus'] = array(
			$this->get_name( 'download' )   => array(
				'id'   => $this->get_name( 'download' ),
				'name' => __( 'Show on', 'omnibus' ),
				'desc' => __( 'Single Download', 'easy-digital-downloads' ),
				'type' => 'checkbox',
			),
			$this->get_name( 'admin_list' ) => array(
				'id'   => $this->get_name( 'admin_list' ),
				'desc' => __( 'Admin Download List', 'easy-digital-downloads' ),
				'type' => 'checkbox',
			),
			$this->get_name( 'admin_edit' ) => array(
				'id'   => $this->get_name( 'admin_edit' ),
				'desc' => __( 'Admin Edit Download', 'easy-digital-downloads' ),
				'type' => 'checkbox',
			),
			$this->get_name( 'days' )       => array(
				'id'   => $this->get_name( 'days' ),
				'name' => __( 'Number of days', 'omnibus' ),
				'desc' => __( 'This controls the number of days to show. According to the Omnibus Directive, minimum days is 30.', 'omnibus' ),
				'std'  => 30,
				'type' => 'number',
				'size' => 'small',
				'min'  => 30,
			),
		);
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
		if ( ! $this->should_it_show_up() ) {
			return;
		}
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
		$price_lowest = $this->get_lowest_price( $post_id );
		if ( empty( $price_lowest ) ) {
			return;
		}
		$message = $this->add_message( '', $price_lowest, 'wc_price' );
		if ( 'return' === $context ) {
			return $message;
		}
		echo $message;
	}

	protected function get_name( $name = '' ) {
		if ( empty( $name ) ) {
			return parent::get_name( 'edd' );
		}
		return parent::get_name( 'edd_' . $name );
	}

	/**
	 * helper to decide show it or no
	 */
	private function should_it_show_up() {
		/**
		 * for admin
		 */
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'download' === $screen->id ) {
				if ( 'no' === $this->get_setting( 'admin_edit' ) ) {
					return false;
				}
			}
			if ( 'edit-download' === $screen->id ) {
				if ( 'no' === $this->get_setting( 'admin_list' ) ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * front-end
		 */
		if ( is_single() ) {
			if ( 'no' === $this->get_setting( 'download' ) ) {
				return false;
			}
			return true;
		}
		return true;
	}
}


