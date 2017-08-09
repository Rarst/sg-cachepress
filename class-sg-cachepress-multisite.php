<?php

/**
 * Implements functionality specific to multisite context.
 */
class SG_CachePress_Multisite {

	/** @var array $bulk_actions Set of bulk actions for network admin. */
	protected $bulk_actions = [];

	/** @var array Set of options editable from site settings in network admin. */
	protected $options = [];

	/**
	 * SG_CachePress_Multisite constructor.
	 */
	public function __construct() {

		if ( ! is_multisite() ) {
			return;
		}

		if ( is_network_admin() ) {

			$this->options = [
				'disallow_cache_config' => esc_html__( 'Disallow Cache Configuration', 'sg-cachepress' ),
				'disallow_https_config' => esc_html__( 'Disallow HTTPS Configuration', 'sg-cachepress' ),
				'enable_cache'          => esc_html__( 'Enable Cache', 'sg-cachepress' ),
				'autoflush_cache'       => esc_html__( 'AutoFlush Cache', 'sg-cachepress' ),
			];

			$this->bulk_actions = [
				'sg-enable-cache'            => esc_html__( 'Enable Dynamic Cache', 'sg-cachepress' ),
				'sg-disable-cache'           => esc_html__( 'Disable Dynamic Cache', 'sg-cachepress' ),
				'sg-enable-autoflush-cache'  => esc_html__( 'Enable AutoFlush Cache', 'sg-cachepress' ),
				'sg-disable-autoflush-cache' => esc_html__( 'Disable AutoFlush Cache', 'sg-cachepress' ),
				'sg-purge-cache'             => esc_html__( 'Purge Cache', 'sg-cachepress' ),
			];

			add_action( 'wpmueditblogaction', array( $this, 'wpmueditblogaction' ) );
			add_action( 'wpmu_update_blog_options', array( $this, 'wpmu_update_blog_options' ) );
			add_filter( 'bulk_actions-sites-network', [ $this, 'bulk_actions' ] );
			add_filter( 'handle_network_bulk_actions-sites-network', [ $this, 'handle_network_bulk_actions' ], 10, 3 );
			add_action( 'network_admin_notices', array( $this, 'network_admin_notices' ) );
		}
	}

	/**
	 * Adds plugin’s options to the site settings form.
	 *
	 * @param int $id Site ID to switch to.
	 */
	public function wpmueditblogaction( $id ) {

		/** @var SG_CachePress_Options $sg_cachepress_options */
		global $sg_cachepress_options;

		switch_to_blog( $id );

		foreach ( $this->options as $key => $name ) {
			?>
			<tr>
				<th>
					<label for="sg-optimizer-option-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></label>
				</th>
				<td>
					<input type="checkbox"
						   name="sg-options[<?php echo esc_attr( $key ); ?>]"
						   id="sg-optimizer-option-<?php echo esc_attr( $key ); ?>"
						<?php checked( $sg_cachepress_options->is_enabled( $key ) ); ?>
					/>
				</td>
			</tr>
			<?php
		}

		restore_current_blog();
	}

	/**
	 * Saves plugin’s options from the site settings form submit.
	 *
	 * @param int $id Site ID to switch to.
	 */
	public function wpmu_update_blog_options( $id ) {

		/** @var SG_CachePress_Options $sg_cachepress_options */
		global $sg_cachepress_options;

		if ( empty( $_POST['sg-options'] ) ) {
			return;
		}

		$options = $_POST['sg-options'];

		switch_to_blog( $id );

		foreach ( array_keys( $this->options ) as $key ) {

			if ( isset( $options[ $key ] ) && 'on' === $options[ $key ] ) {
				$sg_cachepress_options->enable_option( $key );
				continue;
			}

			$sg_cachepress_options->disable_option( $key );
		}

		restore_current_blog();
	}

	/**
	 * Appends bulk actions to network admin sites table.
	 *
	 * @param array $actions List of actions passed by filter.
	 *
	 * @return array
	 */
	public function bulk_actions( $actions ) {

		return array_merge( $actions, $this->bulk_actions );
	}

	/**
	 * Handles network admin bulk actions of the plugin.
	 *
	 * @param string $redirect_to URL destination.
	 * @param string $doaction    Bulk action slug.
	 * @param array  $blogs       Set of site IDs to act on.
	 *
	 * @return string
	 */
	public function handle_network_bulk_actions( $redirect_to, $doaction, $blogs ) {

		$redirect_to = remove_query_arg( 'sg-settings-updated', $redirect_to );
		$redirect_to = remove_query_arg( 'sg-cache-purged', $redirect_to );

		if ( ! array_key_exists( $doaction, $this->bulk_actions ) ) {
			return $redirect_to;
		}

		/** @var SG_CachePress_Options $sg_cachepress_options */
		global $sg_cachepress_options;

		foreach ( $blogs as $site_id ) {

			switch_to_blog( $site_id );

			switch ( $doaction ) {
				case 'sg-enable-cache':
					$sg_cachepress_options->enable_option( 'enable_cache' );
					break;
				case 'sg-disable-cache':
					$sg_cachepress_options->disable_option( 'enable_cache' );
					break;
				case 'sg-enable-autoflush-cache':
					$sg_cachepress_options->enable_option( 'autoflush_cache' );
					break;
				case 'sg-disable-autoflush-cache':
					$sg_cachepress_options->disable_option( 'autoflush_cache' );
					break;
				case 'sg-purge-cache':
					sg_cachepress_purge_cache();
					break;
			}

			restore_current_blog();
		}

		$argument = 'sg-settings-updated';

		if ( 'sg-purge-cache' === $doaction ) {
			$argument = 'sg-cache-purged';
		}

		$redirect_to = add_query_arg( $argument, count( $blogs ), $redirect_to );

		return $redirect_to;
	}

	/**
	 * Outputs notices on completed bulk actions.
	 */
	public function network_admin_notices() {

		if ( ! empty( $_REQUEST['sg-settings-updated'] ) ) {
			$count = (int) $_REQUEST['sg-settings-updated'];
			echo '<div class="updated sg-cachepress-notification"><p>';
			// translators: Count of sites.
			printf( esc_html__( 'SG Optimizer settings updated on %d sites.', 'sg-cachepress' ), $count );
			echo '</p></div>';
		}

		if ( ! empty( $_REQUEST['sg-cache-purged'] ) ) {
			$count = (int) $_REQUEST['sg-cache-purged'];
			echo '<div class="updated sg-cachepress-notification"><p>';
			// translators: Count of sites.
			printf( esc_html__( 'SG Optimizer cache purged on %d sites.', 'sg-cachepress' ), $count );
			echo '</p></div>';
		}
	}

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
