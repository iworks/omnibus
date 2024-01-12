<?php
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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

abstract class iWorks_Omnibus_Post_Type {

	/**
	 * post type name
	 *
	 * @since 3.0.0
	 *
	 * @var string $post_type_name
	 */
	protected $post_type_name;

	protected function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register custom post types and/or taxonomies.
	 *
	 * @since 1.0.0
	 */
	abstract public function register();

	/**
	 * update or delet meta field
	 *
	 * @since 3.0.0
	 *
	 * @param integer $post_id Post ID
	 * @param string $meta_key Post Meta Key
	 * @param mixed $meta_value Post Meta Value
	 */
	protected function update_or_delete_single_post_meta( $post_id, $meta_key, $meta_value ) {
		if ( empty( $meta_value ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}
		update_post_meta( $post_id, $meta_key, $meta_value );
	}

}

