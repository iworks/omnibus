<?php

use LearnPress\Helpers\Config;

/**
 * Class LP_Settings_Permalink
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings
 * @since 4.1.7.3.2
 * @version 1.0.0
 */
class iworks_omnibus_integration_learnpress_settings extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'omnibus';
		$this->text = esc_html__( 'Omnibus', 'omnibus' );

		parent::__construct();
	}

	/**
	 * Return fields for settings page.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		return apply_filters( 'iworks/omnibus/learn-press/settings', array() );
	}

}

return new iworks_omnibus_integration_learnpress_settings();
