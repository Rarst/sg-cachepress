<?php

class SG_CachePress_Multisite {

	/**
	 * Run (de)activation logic for all blogs on the network;
	 *
	 * @param bool $active True to activate, false to deactivate.
	 */
	public function toggle_network_activation( $active ) {

		$blog_ids = $this->get_blog_ids();

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			if ( $active ) {
				SG_CachePress::single_activate();
			} else {
				SG_CachePress::single_deactivate();
			}

			restore_current_blog();
		}
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 *  * not archived
	 *  * not spam
	 *  * not deleted
	 *
	 * @since 1.1.0
	 *
	 * @return array|false The blog ids, false if no matches.
	 */
	public function get_blog_ids() {
		global $wpdb;

		$sql = "SELECT blog_id FROM {$wpdb->blogs} WHERE archived = '0' AND spam = '0' AND deleted = '0'";

		return $wpdb->get_col( $sql );
	}
}
