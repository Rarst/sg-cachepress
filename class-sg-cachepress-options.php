<?php
/**
 * SG CachePress
 *
 * @package   SG_CachePress
 * @author    SiteGround
 * @author    George Penkov
 * @author    Gary Jones <gamajo@gamajo.com>
 * @link      http://www.siteground.com/
 * @copyright 2014 SiteGround
 */

/** SG CachePress options class */

class SG_CachePress_Options {

	/**
	 * Holds the options key, under which all SGCP settings are stored.
	 *
	 * @since 1.1.0
	 *
	 * @type string
	 */
	protected $options_key = 'sg_cachepress';

	/**
	 * Retrieve the whole array of settings, or one individual value.
	 *
	 * @since 1.1.0
	 *
	 * @todo Could implement an extra layer of caching here, to avoid calls to get_option().
	 *
	 * @todo Split get_option() out to get_all_options(), so return type is consistent?
	 *
	 * @param  string $key Optional. Setting field key.
	 *
	 * @return array|int
	 */
	public function get_option( $key = null ) {
		$options = get_option( $this->options_key );

		if ( $key && isset( $options[ $key ] ) )
			return (int) $options[ $key ];
		return $options;
	}

	/**
	 * Enable a single boolean setting.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $key Setting field key.
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function enable_option( $key ) {
		return $this->update_option( $key, 1 );
	}

	/**
	 * Disable a single boolean setting.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $key Setting field key.
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function disable_option( $key ) {
		return $this->update_option( $key, 0 );
	}

	/**
	 * Update a single setting.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $key   Setting field key.
	 * @param  mixed  $value Setting field value.
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function update_option( $key, $value ) {
		$options = $this->get_option();
		$options[ $key ] = $value;
		return update_option( $this->options_key, $options );
	}

	/**
	 * Check if a single boolean setting is enabled.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $key Setting field key.
	 *
	 * @return boolean True if the setting is enabled, false otherwise.
	 */
	public function is_enabled( $key ) {
		if ( 1 === $this->get_option( $key ) )
			return true;
		return false;
	}

	/**
	 * Handle upgrade from old individual settings to new single array setting.
	 *
	 * @since 1.1.0
	 */
	public function upgrade() {
		// If the new key already exists, then we've either already upgraded, or saved some settings, so abort now.
		if ( $this->get_option() )
			return;
		// Set the defaults
		$this->init_options();
		$this->remove_old_options();
	}

	/**
	 * Initialize the values in the single setting array.
	 *
	 * @since 1.1.0
	 */
	public function init_options() {
		add_option( $this->options_key, $this->get_defaults() );
	}

	/**
	 * Get all of the setting field keys and the default values.
	 *
	 * Tries to use the old settings if they still exist.
	 *
	 * @since 1.1.0
	 */
	public function get_defaults() {
		return array(
			'enable_cache'               => get_option( 'SGCP_Use_SG_Cache', 0 ),
			'autoflush_cache'            => get_option( 'SGCP_Autoflush', 1 ),
			'enable_memcached'           => get_option( 'SGCP_Memcached', 1 ),
		    'show_notice'                => get_option( 'SGCP_ShowNotice', 0 ),
		    'is_nginx'                   => get_option( 'SGCP_IsNginx', 0),
		    'checked_nginx'              => get_option( 'SGCP_CheckedNginx', 0),
		    'first_run'                  => get_option( 'SGCP_FristRun', 0),
		    'last_fail'                  => get_option( 'SGCP_LastFail', 0)
		);
	}

	/**
	 * Remove the old settings.
	 *
	 * @since 1.1.0
	 */
	protected function remove_old_options() {
		delete_option( 'SGCP_Use_SG_Cache' );
		delete_option( 'SGCP_Autoflush' );
		delete_option( 'SGCP_Memcached' );
		delete_option( 'SGCP_ShowNotice' );
		delete_option( 'SGCP_IsNginx' );
		delete_option( 'SGCP_CheckedNginx' );
		delete_option( 'SGCP_FristRun' );
		delete_option( 'SGCP_LastFail' );
	}

	/**
	 * Gets the blacklisted urls
	 *
	 * @since 1.1.1
	 * @return string The blacklist
	 */
	public function get_blacklist()
	{
		$options = get_option( $this->options_key );

		if ( isset( $options[ 'blacklist' ] ) && strlen($options[ 'blacklist' ]) )
			return $options[ 'blacklist' ];

		return '';
	}
}
