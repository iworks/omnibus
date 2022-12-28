<?php
/*
Plugin Name: omnibus
Text Domain: omnibus
Plugin URI: http://iworks.pl/omnibus/
Description:
Version: PLUGIN_VERSION
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2018 Marcin Pietrzak (marcin@iworks.pl)

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

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * static options
 */
define( 'IWORKS_OMNIBUS_VERSION', 'PLUGIN_VERSION' );
define( 'IWORKS_OMNIBUS_PREFIX',  'iworks_omnibus_' );
$base = dirname( __FILE__ );
$vendor = $base.'/vendor';

/**
 * require: Iworksomnibus Class
 */
if ( ! class_exists( 'iworks_omnibus' ) ) {
	require_once $vendor.'/iworks/omnibus.php';
}
/**
 * configuration
 */
require_once $base.'/etc/options.php';
/**
 * require: IworksOptions Class
 */
if ( ! class_exists( 'iworks_options' ) ) {
	require_once $vendor.'/iworks/options/options.php';
}

/**
 * i18n
 */
load_plugin_textdomain( 'omnibus', false, plugin_basename( dirname( __FILE__ ) ).'/languages' );

/**
 * load options
 */
$iworks_omnibus_options = new iworks_options();
$iworks_omnibus_options->set_option_function_name( 'iworks_omnibus_options' );
$iworks_omnibus_options->set_option_prefix( IWORKS_OMNIBUS_PREFIX );

function iworks_omnibus_get_options() {
	global $iworks_omnibus_options;
	return $iworks_omnibus_options;
}

function iworks_omnibus_options_init() {
	global $iworks_omnibus_options;
	$iworks_omnibus_options->options_init();
}

function iworks_omnibus_activate() {
	$iworks_omnibus_options = new iworks_options();
	$iworks_omnibus_options->set_option_function_name( 'iworks_omnibus_options' );
	$iworks_omnibus_options->set_option_prefix( IWORKS_OMNIBUS_PREFIX );
	$iworks_omnibus_options->activate();
}

function iworks_omnibus_deactivate() {
	global $iworks_omnibus_options;
	$iworks_omnibus_options->deactivate();
}

$iworks_omnibus = new iworks_omnibus();

/**
 * install & uninstall
 */
register_activation_hook( __FILE__,   'iworks_omnibus_activate' );
register_deactivation_hook( __FILE__, 'iworks_omnibus_deactivate' );
