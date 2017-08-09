<?php

/**
 * Stores a log of recent admin actions.
 */
class SG_CachePress_Log {

	/**
	 * Append message to log.
	 *
	 * @param string $message Message to store.
	 */
	public function add_message( $message ) {

		$option = get_site_option( 'sg-log' );

		if ( empty( $option ) || ! is_array( $option ) ) {
			$option = [];
		}

		$date = date_i18n( DATE_ATOM );
		$user = wp_get_current_user();
		$name = $user->user_login;

		$entry = "{$date} {$name} {$message}";

		$option[] = esc_html( $entry );

		update_site_option( 'sg-log', array_slice( $option, - 10 ) );
	}

	/**
	 * Retrieve log messages.
	 *
	 * @return string
	 */
	public function get_log() {

		$option = get_site_option( 'sg-log' );

		if ( empty( $option ) || ! is_array( $option ) ) {
			return '';
		}

		return implode( "\n", $option );
	}
}
