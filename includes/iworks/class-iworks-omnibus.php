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

	public function __construct() {
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
			/**
			 * Check minimal WooCommerce version to run.
			 *
			 * @since 2.3.4
			 *
			 */
			if ( version_compare( WC_VERSION, '5.5', '<' ) ) {
				add_action( 'admin_notices', array( $this, 'action_admin_notices_show_woocommerce_version' ) );
			} else {
				/**
				 * Add Settings link on the Plugins list
				 *
				 * @since 1.0.2
				 */
				add_filter( 'plugin_action_links_' . basename( $this->root ) . '/omnibus.php', array( $this, 'add_settings_link' ), 90, 4 );
				include_once $dir . '/integration/class-iworks-omnibus-integration-woocommerce.php';
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
			include_once $dir . '/integration/class-iworks-omnibus-integration-debug-bar.php';
			$this->objects['debug-bar'] = new iworks_omnibus_integration_debug_bar();
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
			$plugin_meta['rating'] = sprintf( __( 'If you like <strong>Omnibus</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. A huge thanks in advance!', 'omnibus' ), '<a href="https://wordpress.org/support/plugin/omnibus/reviews/?rate=5#new-post" target="_blank">', '</a>' );
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
			'' === $group ? '' : $group . '/',
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
}
