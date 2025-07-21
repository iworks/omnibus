<?php
/*
 * Data Migration Class for WP Desk Omnibus
 *
 * Migrate data to version 4 from WP Desk Omnibus plugin.
 *
 * @since 4.0.0
 */
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
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_omnibus_data_migration_v4' ) ) {
	return;
}

require dirname( __DIR__, 2 ) . '/class-iworks-omnibus-migration.php';

class iworks_omnibus_migration_wp_desk extends iworks_omnibus_migration {

	/**
	 * Are logs migrated to version 3?
	 *
	 * @since 3.0.0
	 */
	private string $option_name_migration_wp_desc_omnibus = 'iwo_wp_desk_omnibus_status';

	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_iworks_omnibus_migrate_wp_desk_omnibus', array( $this, 'action_wp_ajax_iworks_omnibus_migrate' ) );
		add_action( 'plugins_loaded', array( $this, 'action_admin_init' ), PHP_INT_MAX );
	}

	/**
	 * check is nessary to make log conversion to v4
	 *
	 * @since 4.0.0
	 */
	public function get_migration_status( $status ) {
		if ( $this->are_logs_migrated ) {
			return $this->are_logs_migrated;
		}
		if ( 'migrated' === get_option( $this->option_name_migration_status, false ) ) {
			$this->are_logs_migrated = 'migrated';
			return $this->are_logs_migrated;
		}
		return $status;
	}

	/**
	 * define admin actions
	 */
	public function action_admin_init() {
		global $wpdb;
		$wpdb->wp_desk_omnibus = $wpdb->prefix . 'omnibus_price_logger';
		add_action( 'admin_notices', array( $this, 'action_admin_notices_show_migration_message' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu_add_migration_page' ) );
	}

	/**
	 * add data migration page
	 *
	 * @since 4.0.0
	 */
	public function action_admin_menu_add_migration_page() {
		$hook = add_management_page(
			__( 'Omnibus Migration', 'omnibus' ),
			__( 'Omnibus Migration', 'omnibus' ),
			'manage_options',
			'omnibus-migration-v4-wp-desk',
			array( $this, 'callback_omnibus_migration' )
		);
		add_action( 'load-' . $hook, array( $this, 'action_load_omnibus_migration_admin_page' ) );
	}

	public function callback_omnibus_migration() {
		$file = $this->get_file( 'admin-page', 'migration/v4/wp-desk-omnibus' );
		load_template( $file, true, $this->get_args() );
	}

	public function action_load_omnibus_migration_admin_page() {
		wp_register_script(
			__FUNCTION__,
			plugins_url( 'assets/scripts/admin/migrate-v4.min.js', $this->plugin_file ),
			array( 'jquery' ),
			'PLUGIN_VERSION'
		);
		wp_enqueue_script( __FUNCTION__ );
		wp_register_style(
			__FUNCTION__,
			plugins_url( 'assets/styles/admin/migrate.css', $this->plugin_file ),
			array(),
			'PLUGIN_VERSION'
		);
		wp_enqueue_style( __FUNCTION__ );
	}

	/**
	 * Show migration message to v4
	 *
	 * @since 4.0.0
	 *
	 */
	public function action_admin_notices_show_migration_message() {
		if ( 'dashboard' === get_current_screen()->base ) {
			switch ( get_option( $this->option_name_migration_wp_desc_omnibus ) ) {
				case 'active':
					$file = $this->get_file( 'is-turn-on', 'migration/v4/wp-desk-omnibus' );
					load_template( $file, true, $this->get_args() );
					break;
			}
			// $file = $this->get_file( 'message', 'migration-v4' );
			// load_template( $file, true, $this->get_args() );
		}
		// if ( 0 === $this->count_number_of_data_to_migrate() ) {
			// $this->migration_update_status( $this->option_name_migration_4_status, 'migrated' );
			// return;
		// }
	}

	private function get_args() {
		return array(
			'count'  => $this->count_number_of_data_to_migrate(),
			'status' => get_option( $this->option_name_migration_4_status ),
		);
	}

	public function action_admin_notices_show_is_on() {
		if ( 'dashboard' === get_current_screen()->base ) {
		}
	}

	/**
	 * count data to Migrate
	 *
	 * @since 4.0.0
	 */
	private function count_number_of_data_to_migrate() {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"select count(*) from $wpdb->wp_desk_omnibus"
			)
		);
	}

	public function action_wp_ajax_iworks_omnibus_migrate() {
		$nonce_value = filter_input( INPUT_POST, '_wpnonce' );
		if ( ! wp_verify_nonce( $nonce_value, 'omnibus-migration-v4' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed Security Check', 'omnibus' ) ) );
		}
		$this->batch();
		$this->migration_update_status( $this->option_name_migration_4_status, 'started' );
		$count = $this->count_number_of_data_to_migrate();
		if ( 0 < $count ) {
			if ( intval( filter_input( INPUT_POST, 'items' ) ) === $count ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Something went wrong. Please check server error log.', 'omnibus' ) ) );
			} else {
				wp_send_json_success(
					array(
						'action' => 'continue',
						'count'  => $count,
					)
				);
			}
		} else {
			$this->rename_option_names();
			$this->migration_update_status( $this->option_name_migration_4_status, 'migrated' );
			wp_send_json_success(
				array(
					'action'  => 'done',
					'message' => esc_html__( 'The migration was successful.', 'omnibus' ),
				)
			);
		}
		wp_send_json_error( array( 'message' => esc_html__( 'Unknown Error Occurred', 'omnibus' ) ) );
	}

	private function rename_option_names() {
		global $wpdb;
		$query = "update $wpdb->options set option_name = substring( option_name, 13 ) where option_name like 'learn_press__iwo%'";
		$wpdb->query( $query );
	}

	private function batch() {
		global $wpdb;
		$delete_older_40_data = 'true' === filter_input( INPUT_POST, 'older' );
		/**
		 * get v3 log items
		 */
		$wp_query_args = array(
			'post_type'      => 'iw_omnibus_price_log',
			'post_status'    => 'any',
			'posts_per_page' => 12,
			'order'          => 'ASC',
		);
		$wp_query      = new WP_Query( $wp_query_args );
		foreach ( $wp_query->posts as $price_log_item ) {
			$fields = array(
				'product_origin'  => '%s',
				'product_type'    => '%s',
				'post_id'         => '%d',
				'user_id'         => '%d',
				'currency'        => '%s',
				'price_regular'   => '%f',
				'price_sale'      => '%f',
				'price_sale_from' => '%s',
			);
			/**
			 * get target post id
			 */
			$post_id = $price_log_item->ID;
			/**
			 * delete older logs
			 */
			if (
				$delete_older_40_data
				&& 40 < ( time() - strtotime( $price_log_item->post_date ) ) / DAY_IN_SECONDS
			) {
				wp_delete_post( $post_id, true );
				continue;
			}
			/**
			 * Prices
			 */
			$price_regular = floatval( get_post_meta( $post_id, 'price_regular', true ) );
			if ( empty( $price_regular ) ) {
				$price_regular = floatval( get_post_meta( $post_id, 'price', true ) );
			}
			$price_sale = floatval(
				get_post_meta( $post_id, 'price_sale', true )
			);
			/**
			 * missing post_parent
			 */
			if ( empty( get_post_type( $price_log_item->post_parent ) ) ) {
				wp_delete_post( $post_id, true );
				continue;
			}
			/**
			 * both are empty
			 */
			if ( empty( $price_regular ) && empty( $price_sale ) ) {
				wp_delete_post( $post_id, true );
				continue;
			}
			/**
			 * check prices
			 */
			if ( empty( $price_regular ) ) {
				unset( $fields['price_regular'] );
			}
			if ( empty( $price_sale ) ) {
				unset( $fields['price_sale'] );
			}
			/**
			 * check already imported
			 * do not move duplicate
			 */
			$query  = $wpdb->prepare(
				sprintf(
					'select post_id, price_regular, price_sale from %s where post_id = %%d order by omnibus_id desc limit 1',
					$wpdb->iworks_omnibus
				),
				$price_log_item->post_parent
			);
			$result = $wpdb->get_row( $query, ARRAY_A );
			if ( isset( $result['post_id'] ) ) {
				if ( floatval( $price_regular ) === floatval( $result['price_regular'] ) ) {
					if ( floatval( $price_sale ) === floatval( $result['price_sale'] ) ) {
						wp_delete_post( $post_id, true );
						continue;
					} elseif (
						null === $result['price_sale']
						&& null === $price_sale
					) {
						wp_delete_post( $post_id, true );
						continue;
					}
				}
			}
			/**
			 * build query
			 */
			$sql = sprintf(
				'insert into %s ( %s ) values ( %s )',
				$wpdb->iworks_omnibus,
				implode( ', ', array_keys( $fields ) ),
				implode( ', ', array_values( $fields ) )
			);
			l( $sql );
			/**
			 * user ID
			 */
			$user_id = get_post_meta( $post_id, 'user_id', true );
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}
			/**
			 * currency
			 */
			$currency = get_post_meta( $post_id, 'currency', true );
			if ( empty( $currency ) ) {
				$currency = get_woocommerce_currency();
			}
			/**
			 * determine product_origin and product_type
			 */
			$product_type   = get_post_type( $price_log_item->post_parent );
			$product_origin = 'unknown';
			switch ( $product_type ) {
				case 'lp_course':
				case 'lp_lesson':
				case 'lp_question':
				case 'lp_quiz':
					$product_origin = 'learnpress';
					break;
				case 'product':
				case 'product_variation':
					$product_origin = 'woocommerce';
					break;
			}
			/**
			 * do not move empty prices
			 */
			$query = null;
			if ( empty( $price_sale ) ) {
				$query = $wpdb->prepare(
					$sql,
					$product_origin,
					$product_type,
					$price_log_item->post_parent,
					$user_id,
					$currency,
					$price_regular,
					$price_log_item->post_date
				);
			} elseif ( empty( $price_regular ) ) {
				$query = $wpdb->prepare(
					$sql,
					$product_origin,
					$product_type,
					$price_log_item->post_parent,
					$user_id,
					$currency,
					$price_sale,
					$price_log_item->post_date,
				);
			} else {
				$query = $wpdb->prepare(
					$sql,
					$product_origin,
					$product_type,
					$price_log_item->post_parent,
					$user_id,
					$currency,
					$price_regular,
					$price_sale,
					$price_log_item->post_date
				);
			}
			if ( $query ) {
				$wpdb->query( $query );
				if ( $wpdb->insert_id ) {
					wp_delete_post( $post_id, true );
				}
			}
		}
	}
}
