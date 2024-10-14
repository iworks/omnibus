<?php

class iworks_omnibus_logger_v4 {


	public function __construct() {
		add_filter( 'iworks/omnibus/logger/v4/get/log/array', array( $this, 'get_log_array' ), 10, 2 );
	}

	public function get_log_array( $data, $id ) {
		return $this->get_full_log_by_id( $id );
	}

	private function get_full_log_by_id( $id ) {
		global $wpdb;
		$sql   = sprintf(
			'select * from %s where post_id = %%d order by omnibus_id',
			$wpdb->iworks_omnibus
		);
		$query = $wpdb->prepare( $sql, $id );
		return $wpdb->get_results( $query, ARRAY_A );
	}
}

