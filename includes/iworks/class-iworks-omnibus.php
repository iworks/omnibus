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

	private $objects = array();
	private $root;

	/**
	 * Are logs migrated to version 3?
	 *
	 * @since 3.0.0
	 */
	protected $are_logs_migrated_to_version_3 = false;

	/**
	 * option name form migration to v3 status
	 *
	 * @since 3.0.0
	 */
	 private $option_name_migration_3_status = 'iworks_omnibus_data_migration_v3';

	public function __construct() {
		/**
		 * add database table name
		 */
		global $wpdb;
		$wpdb->iworks_omnibus = $wpdb->prefix . 'iworks_omnibus';
		/**
		 * set plugin root
		 *
		 * @since 2.3.4
		 */
		$this->root = dirname( dirname( dirname( __FILE__ ) ) );
		/**
		 * plugins screen
		 */
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
		/**
		 * iWorks Rate Class
		 *
		 * Allow to change iWorks Rate logo for admin notice.
		 *
		 * @since 1.0.2
		 *
		 * @param string $logo Logo, can be empty.
		 * @param object $plugin Plugin basic data.
		 */
		add_filter( 'iworks_rate_notice_logo_style', array( $this, 'filter_plugin_logo' ), 10, 2 );
		/**
		 * check migration to v3
		 *
		 * @since 3.0.0
		 */
		add_filter( 'iworks/omnibus/v3/get/migration/status', array( $this, 'migration_v3_filter_get_migration_status_to_version_3' ) );
		add_action( 'wp_ajax_iworks_omnibus_migrate_v3', array( $this, 'migration_v3_action_wp_ajax_iworks_omnibus_migrate_v3' ) );
		/**
		 * check migration to v4
		 *
		 * @since 4.0.0
		 */
		if ( $this->is_migrated_v4() ) {
			include_once dirname( __FILE__ ) . '/omnibus/class-iworks-omnibus-logger-v4.php';
			new iworks_omnibus_logger_v4();
		} else {
			include_once dirname( __FILE__ ) . '/omnibus/migration/class-iworks-omnibus-migration-v4.php';
			new iworks_omnibus_migration_v4();
		}
		add_filter( 'iworks/omnibus/v4/get/migration/status', array( $this, 'migration_v4_filter_get_migration_status_to_version_4' ) );
		/**
		 * db install
		 *
		 * @since 4.0.0
		 */
		add_action( 'admin_init', array( $this, 'db_install' ) );
		/**
		 * i18n
		 *
		 * @since 4.0.0
		 */
		add_action( 'init', array( $this, 'action_init_load_plugin_textdomain' ), PHP_INT_MAX );
	}

	public function action_plugins_loaded() {
		$dir = dirname( __FILE__ ) . '/omnibus';
		/**
		 * WooCommerce
		 *
		 * @since 1.0.0
		 */
		if (
			defined( 'WC_PLUGIN_FILE' )
			&& defined( 'WC_VERSION' )
		) {
			$v4_directory = $this->is_migrated_v4() ? '/v4' : '';
			/**
			 * Check minimal WooCommerce version to run.
			 *
			 * @since 2.3.4
			 *
			 */
			if ( version_compare( WC_VERSION, '5.5', '<' ) ) {
				add_action( 'admin_notices', array( $this, 'action_admin_notices_show_woocommerce_version' ) );
			} else {
				if ( 'migrated' !== apply_filters(
					'iworks_omnibus_integration_woocommerce/omnibus/get/migration/status/3',
					get_option( $this->option_name_migration_3_status, false )
				) ) {
					add_action( 'admin_notices', array( $this, 'action_admin_notices_show_migration_message_v3' ) );
					add_action( 'admin_menu', array( $this, 'action_admin_menu_add_migration_v3_page' ) );
				} elseif ( ! $this->is_migrated_v4() ) {
					do_action( 'iworks/omnibus/action/migration/v4/plugins_loaded' );
				}
				/**
				 * Add Settings link on the Plugins list
				 *
				 * @since 1.0.2
				 */
				add_filter( 'plugin_action_links_' . basename( $this->root ) . '/omnibus.php', array( $this, 'add_settings_link' ), 90, 4 );
				include_once $dir . '/integration' . $v4_directory . '/class-iworks-omnibus-integration-woocommerce.php';
				$this->objects['woocommerce'] = new iworks_omnibus_integration_woocommerce();
			}
		}
		/**
		 * LearnPress
		 *
		 * @since 1.0.1
		 */
		if ( defined( 'LP_PLUGIN_FILE' ) ) {
			include_once $dir . '/integration/class-iworks-omnibus-integration-learnpress.php';
			$this->objects['learnpress'] = new iworks_omnibus_integration_learnpress();
		}
		/**
		 * Easy Digital Downloads
		 *
		 * @since 1.1.1
		 */
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			include_once $dir . '/integration/class-iworks-omnibus-integration-class-easydigitaldownloads.php';
			$this->objects['easydigitaldownloads'] = new iworks_omnibus_integration_easydigitaldownloads();
		}
		/**
		 * Debug Bar
		 *
		 * @since 2.4.0
		 */
		if ( isset( $GLOBALS['debug_bar'] ) ) {
			include_once $dir . '/integration' . $v4_directory . '/class-iworks-omnibus-integration-debug-bar.php';
			$this->objects['debug-bar'] = new iworks_omnibus_integration_debug_bar();
		}
		/**
		 * Price log as post type not a meta fields
		 *
		 * @since 3.0.0
		 */
		if ( ! $this->is_migrated_v4() ) {
			include_once $dir . '/post-types/class-iworks-omnibus-post-type-price-log.php';
			$this->objects['iw_omnibus_price_log'] = new iWorks_Omnibus_Post_Type_Price_Log();
		}
		/**
		 * Omnibus loaded action
		 *
		 * @since 2.1.4
		 */
		do_action( 'omnibus/loaded' );
	}

	/**
	 * Ask for rating.
	 *
	 * @since 1.0.2
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( isset( $plugin_data['slug'] ) && 'omnibus' == $plugin_data['slug'] ) {
			$plugin_meta['rating'] = sprintf(
				/* translators: %1$s: A tag begin, %2$s: the A tag close */
				__( 'If you like <strong>Omnibus</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. A huge thanks in advance!', 'omnibus' ),
				'<a href="https://wordpress.org/support/plugin/omnibus/reviews/?rate=5#new-post" target="_blank">',
				'</a>'
			);
		}
		return $plugin_meta;
	}

	/**
	 * Plugin logo for rate messages
	 *
	 * @since 1.0.2
	 *
	 * @param string $logo Logo, can be empty.
	 * @param object $plugin Plugin basic data.
	 */
	public function filter_plugin_logo( $logo, $plugin ) {
		if ( is_object( $plugin ) ) {
			$plugin = (array) $plugin;
		}
		if ( 'omnibus' === $plugin['slug'] ) {
			return plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/logo.svg';
		}
		return $logo;
	}

	/**
	 * Add settings link to plugin_row_meta.
	 *
	 * @since 1.0.2
	 *
	 * @param array  $actions An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
	 */
	public function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		$actions['settings'] = sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab'  => 'omnibus',
				),
				admin_url( 'admin.php' )
			),
			__( 'Settings', 'omnibus' )
		);
		return $actions;
	}

	/**
	 * get template
	 *
	 * @since 2.3.4
	 */
	private function get_file( $file, $group = '' ) {
		return sprintf(
			'%s/assets/templates/%s%s.php',
			$this->root,
			'' === $group ? '' : sanitize_title( $group ) . '/',
			sanitize_title( $file )
		);
	}

	/**
	 * Show minimal WooCommerce version to run.
	 *
	 * @since 2.3.4
	 *
	 */
	public function action_admin_notices_show_woocommerce_version() {
		$file = $this->get_file( 'woocommerce-version' );
		$args = array(
			'version-current' => WC_VERSION,
			'version-minimal' => '5.5.0',
		);
		load_template( $file, true, $args );
	}

	/**
	 * check is nessary to make log conversion to v3
	 *
	 * @since 3.0.0
	 */
	public function migration_v3_filter_get_migration_status_to_version_3( $status ) {
		if ( $this->are_logs_migrated_to_version_3 ) {
			return 'migrated';
		}
		$is_migrated_v3 = get_option( $this->option_name_migration_3_status, false );
		if ( 'migrated' === $is_migrated_v3 ) {
			$this->are_logs_migrated_to_version_3 = true;
			return 'migrated';
		}
		return $is_migrated_v3;
	}

	/**
	 * Show migration message to v3
	 *
	 * @since 3.0.0
	 *
	 */
	public function action_admin_notices_show_migration_message_v3() {
		if ( 'dashboard' === get_current_screen()->base ) {
			$file = $this->get_file( 'message', 'migration-v3' );
			$args = array(
				'meta'   => $this->migration_v3_count_number_of_meta_fields(),
				'status' => get_option( $this->option_name_migration_3_status ),
			);
			load_template( $file, true, $args );
		}
	}

	/**
	 * add data migration page
	 *
	 * @since 3.0.0
	 */
	public function action_admin_menu_add_migration_v3_page() {
		$hook = add_management_page(
			__( 'Omnibus Migration', 'omnibus' ),
			__( 'Omnibus Migration', 'omnibus' ),
			'manage_options',
			'omnibus-migration-v3',
			array( $this, 'migration_v3_callback_omnibus_admin_page' )
		);
		add_action( 'load-' . $hook, array( $this, 'action_load_omnibus_migration_v3_admin_page' ) );
	}

	public function action_load_omnibus_migration_v3_admin_page() {
		wp_register_script(
			__FUNCTION__,
			plugins_url( 'assets/scripts/admin/migrate-v3.min.js', dirname( __DIR__ ) ),
			array( 'jquery' ),
			'PLUGIN_VERSION'
		);
		wp_enqueue_script( __FUNCTION__ );
	}

	private function build_place_holder( $array ) {
		$placeholders = array();
		foreach ( $array as $x ) {
			$placeholders[] = '%s';
		}
		return implode( ', ', $placeholders );
	}

	private function migration_v3_meta_keys_array() {
		return array(
			'_iwo_price_lowest',
			'_iwo_price_last_change',
			'_iwo_last_price_drop_timestamp',
			'_iwo_price_log',
			// '_iwo_price_lowest_is_short',
		);
	}

	private function migration_v3_migrate_batch() {
		global $wpdb;
		$names   = $this->migration_v3_meta_keys_array();
		$query   = sprintf(
			'select * from %s where meta_key in ( %s ) order by rand() limit 10',
			$wpdb->postmeta,
			$this->build_place_holder( $names )
		);
		$query   = $wpdb->prepare( $query, $names );
		$results = $wpdb->get_results( $query, 'ARRAY_A' );
		foreach ( $results as $one ) {
			switch ( $one['meta_key'] ) {
				case '_iwo_price_lowest':
				case '_iwo_price_log':
				case '_iwo_price_last_change':
					$data              = maybe_unserialize( $one['meta_value'] );
					$data['post_date'] = $data['timestamp'];
					do_action( 'iworks/omnibus/v3/add/log', $one['post_id'], $data );
					delete_metadata_by_mid( 'post', $one['meta_id'] );
					break;
				case '_iwo_last_price_drop_timestamp':
					delete_metadata_by_mid( 'post', $one['meta_id'] );
					break;
				default:
			}
		}
	}

	private function migration_v3_count_number_of_meta_fields() {
		global $wpdb;
		$names = $this->migration_v3_meta_keys_array();
		$query = sprintf(
			'select count(*) from %s where meta_key in ( %s )',
			$wpdb->postmeta,
			$this->build_place_holder( $names )
		);
		$query = $wpdb->prepare( $query, $names );
		return intval( $wpdb->get_var( $query ) );
	}

	public function migration_v3_callback_omnibus_admin_page() {
		$file = $this->get_file( 'admin-page', 'migration-v3' );
		$args = array(
			'meta'    => $this->migration_v3_count_number_of_meta_fields(),
			'status'  => get_option( $this->option_name_migration_3_status ),
			'message' => '',
		);
		if ( 0 === $args['meta'] ) {
			$this->migration_v3_update_status( 'migrated' );
			$args['status'] = 'migrated';
		}
		load_template( $file, true, $args );
	}

	private function migration_v3_update_status( $status ) {
		switch ( $status ) {
			case 'started':
			case 'migrated':
				$result = update_option( $this->option_name_migration_3_status, $status );
				if ( ! $result ) {
					add_option( $this->option_name_migration_3_status, $status );
				}
				return;
		}
		delete_option( $this->option_name_migration_3_status );
	}

	public function migration_v3_action_wp_ajax_iworks_omnibus_migrate_v3() {
		$nonce_value = filter_input( INPUT_POST, '_wpnonce' );
		if ( ! wp_verify_nonce( $nonce_value, 'omnibus-migration-v3' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed Security Check', 'omnibus' ) ) );
		}
		$this->migration_v3_migrate_batch();
		$this->migration_v3_update_status( 'started' );
		$count = $this->migration_v3_count_number_of_meta_fields();
		if ( 0 < $count ) {
			wp_send_json_success(
				array(
					'action' => 'continue',
					'count'  => $count,
				)
			);
		} else {
			$this->migration_v3_update_status( 'migrated' );
			wp_send_json_success(
				array(
					'action'  => 'done',
					'message' => esc_html__( 'Data migration to version 3 of the database was successfully completed!', 'omnibus' ),
				)
			);
		}
		wp_send_json_error( array( 'message' => esc_html__( 'Unknown Error Occurred', 'omnibus' ) ) );
	}

	public function db_install() {
		global $wpdb;
		$option_name_db_version = 'iworks_omnibus_db_version';
		/**
		 * get Version
		 */
		$version = intval( get_option( $option_name_db_version ) );
		if ( empty( $version ) ) {
			add_option( $option_name_db_version, 0, '', 'no' );
		}
		/**
		 * 20241011
		 */
		$install = 20241011;
		if ( $install > $version ) {
			$charset_collate = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$table_name = $wpdb->iworks_omnibus;
			$sql        = "CREATE TABLE $table_name (
				omnibus_id bigint unsigned not null auto_increment,
				post_id bigint unsigned not null,
				user_id bigint unsigned not null,
				created datetime not null,
				currency varchar(5) not null,
				price_regular decimal(26, 8) unsigned default null,
				price_sale decimal(26, 8) unsigned default null,
				primary key ( omnibus_id ),
				key ( post_id ),
				key ( currency ),
				key ( created )
			) $charset_collate;";
			dbDelta( $sql );
			update_option( $option_name_db_version, $install );
		}
	}

	/**
	 * check is nessary to make log conversion to v4
	 *
	 * @since 4.0.0
	 */
	public function is_migrated_v4() {
		return get_option( 'iworks_omnibus_data_migration_v4', false ) === 'migrated';
	}

	/**
	 * check is nessary to make log conversion to v4
	 *
	 * @since 4.0.0
	 */
	public function migration_v4_filter_get_migration_status_to_version_4( $status ) {
		return $this->is_migrated_v4() ? 'migrated' : false;
	}

	/**
	 * register_activation_hook
	 *
	 * @since 4.0.0
	 */
	public function register_activation_hook() {
		delete_option( '_iwo_price_lowest_delete' );
		$this->db_install();
		do_action( 'iworks/omnibus/register_activation_hook' );
	}

	/**
	 * register_deactivation_hook
	 *
	 * @since 4.0.0
	 */
	public function register_deactivation_hook() {
		delete_option( 'iworks_omnibus_data_migration_v3' );
		delete_option( 'iworks_omnibus_data_migration_v4' );
		do_action( 'iworks/omnibus/register_deactivation_hook' );
	}

	/**
	 * Load translation
	 *
	 * @since 4.0.0
	 */
	public function action_init_load_plugin_textdomain() {
		load_plugin_textdomain( 'omnibus', false, plugin_basename( $this->root ) . '/languages' );
	}
}
