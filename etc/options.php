<?php

function iworks_5o5_options() {
	$iworks_5o5_options = array();
	/**
	 * main settings
	 */
	$parent = add_query_arg( 'post_type', 'iworks_5o5_person', 'edit.php' );

	$iworks_5o5_options['index'] = array(
		'version'  => '0.0',
		'page_title' => __( 'Configuration', '5o5' ),
		'menu' => 'submenu',
		'parent' => $parent,
		'options'  => array(),
		//      'metaboxes' => array(),
		'pages' => array(
			'new-boat' => array(
				'menu' => 'submenu',
				'parent' => $parent,
				'page_title'  => __( 'Add New Boat', '5o5' ),
				'menu_slug' => htmlentities(
					add_query_arg(
						array(
							'post_type' => 'iworks_5o5_boat',
						),
						'post-new.php'
					)
				),
				'set_callback_to_null' => true,
			),
			'hull' => array(
				'menu' => 'submenu',
				'parent' => $parent,
				'page_title'  => __( 'Hulls Manufaturers', '5o5' ),
				'menu_title'  => __( 'Hulls Manufaturers', '5o5' ),
				'menu_slug' => htmlentities(
					add_query_arg(
						array(
							'taxonomy' => 'iworks_5o5_boat_manufacturer',
							'post_type' => 'iworks_5o5_person',
						),
						'edit-tags.php'
					)
				),
				'set_callback_to_null' => true,
			),
			'sail' => array(
				'menu' => 'submenu',
				'parent' => $parent,
				'page_title'  => __( 'Sails Manufaturers', '5o5' ),
				'menu_title'  => __( 'Sails Manufaturers', '5o5' ),
				'menu_slug' => htmlentities(
					add_query_arg(
						array(
							'taxonomy' => 'iworks_5o5_sails_manufacturer',
							'post_type' => 'iworks_5o5_person',
						),
						'edit-tags.php'
					)
				),
				'set_callback_to_null' => true,
			),
			'mast' => array(
				'menu' => 'submenu',
				'parent' => $parent,
				'page_title'  => __( 'Masts Manufaturers', '5o5' ),
				'menu_title'  => __( 'Masts Manufaturers', '5o5' ),
				'menu_slug' => htmlentities(
					add_query_arg(
						array(
							'taxonomy' => 'iworks_5o5_mast_manufacturer',
							'post_type' => 'iworks_5o5_person',
						),
						'edit-tags.php'
					)
				),
				'set_callback_to_null' => true,
			),
		),
	);
	return $iworks_5o5_options;
}

