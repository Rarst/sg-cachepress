<?php

/**
 * Implements functionality specific to multisite context.
 */
class SG_CachePress_Multisite {

	/** @var SG_CachePress_Log $log */
	protected $log;

	/** @var array $columns Set of sites list table columns in network admin. */
	protected $columns = [];

	/** @var array Set of options editable from site settings in network admin. */
	protected $options = [];

	/** @var array $bulk_actions Set of bulk actions for network admin. */
	protected $bulk_actions = [];

	/**
	 * SG_CachePress_Multisite constructor.
	 */
	public function __construct() {

		if ( ! is_multisite() ) {
			return;
		}

        $this->force_https();

		if ( is_network_admin() ) {

		    // TODO change log to DI to simplify tests. R.
			$this->log = new SG_CachePress_Log();

			$this->columns = [
				'sg-dynamic-cache' => esc_html__( 'Dynamic Cache', 'sg-cachepress' ),
				'sg-force-https'   => esc_html__( 'Force HTTPS', 'sg-cachepress' ),
			];

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

			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );

			// Edit Site > Settings tab.
			add_action( 'wpmueditblogaction', array( $this, 'wpmueditblogaction' ), 11 );
			add_action( 'wpmu_update_blog_options', array( $this, 'wpmu_update_blog_options' ) );

			// Sites > Sites list columns.
			add_filter( 'wpmu_blogs_columns', array( $this, 'wpmu_blogs_columns' ) );
			add_action( 'manage_sites_custom_column', array( $this, 'manage_sites_custom_column' ), 10, 2 );

			// Sites > Bulk Actions.
			add_filter( 'bulk_actions-sites-network', [ $this, 'bulk_actions' ] );
			add_filter( 'handle_network_bulk_actions-sites-network', [ $this, 'handle_network_bulk_actions' ], 10, 3 );

			// Sites > Quick Actions.
			add_filter( 'manage_sites_action_links', [ $this, 'manage_sites_action_links' ], 10, 2 );

			add_action( 'network_admin_notices', array( $this, 'network_admin_notices' ) );

			add_action( 'admin_print_footer_scripts', [ $this, 'admin_print_footer_scripts' ] );
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->log = new SG_CachePress_Log();
			add_action( 'wp_ajax_sg-purge-cache', [ $this, 'wp_ajax' ] );
		}
	}

	/**
	 * Force HTTPs in multisite context, which does not use htaccess redirects.
	 */
	public function force_https() {

		$force  = ( '1' === get_option( 'sg_cachepress_ssl_enabled' ) );
		$method = $_SERVER['REQUEST_METHOD'];

		if ( ! $force || 'GET' !== $method || is_ssl() ) {
			return;
		}

		$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		wp_safe_redirect( $redirect );
		die;
	}

	/**
	 * Registers network admin page.
	 */
	public function network_admin_menu() {

		/** @var SG_CachePress_Admin $sg_cachepress_admin */
		global $sg_cachepress_admin;

		add_menu_page(
			__( 'SG Optimizer', 'sg-cachepress' ),
			__( 'SG Optimizer', 'sg-cachepress' ),
			'manage_network_options',
			SG_CachePress::PLUGIN_SLUG,
			[ $this, 'display_network_admin_page' ],
			plugins_url( 'sg-cachepress/css/logo-white.svg' )
		);

		$sg_cachepress_admin->add_plugin_php_menu();

		add_submenu_page(
			SG_CachePress::PLUGIN_SLUG,
			__( 'Multisite Log', 'sg-cachepress' ),
			__( 'Multisite Log', 'sg-cachepress' ),
			'manage_network_options',
			SG_CachePress::PLUGIN_SLUG . '-log',
			[ $this, 'display_network_log_page' ]
		);
	}

	/**
	 * Displays network admin page.
	 */
	public function display_network_admin_page() {

		require __DIR__ . '/views/network-settings.php';
	}

	/**
	 * Displays network log page.
	 */
	public function display_network_log_page() {

		$log = $this->log->get_log();

		require __DIR__ . '/views/network-log.php';
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

		echo '<tr><th><h2>' . esc_html__( 'SG Optimizer Options', 'sg-cachepress' ) . '</h2></th></tr>';

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

		$disabled = ! SG_CachePress_SSL::is_certificate_enabled();
		?>
		<tr>
			<th>
				<label for="sg-optimizer-action-force_https"><?php echo esc_html__( 'Force HTTPS', 'sg-cachepress' ); ?></label>
			</th>
			<td>
				<input type="checkbox"
					   name="sg-actions[force_https]"
					   id="sg-optimizer-action-force_https"
					<?php checked( SG_CachePress_SSL::is_enabled_from_wordpress_options() ); ?>
					<?php if ( $disabled ) : ?> disabled<?php endif; ?>
				/>
				<?php
				if ( $disabled ) {
					esc_html_e( 'You do not have a certificate issued for this site.', 'sg-cachepress' );
				}
				?>
			</td>
		</tr>
		<?php

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

		$options = filter_input( INPUT_POST, 'sg-options', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		switch_to_blog( $id );
		$home_url = get_home_url( $id );

		foreach ( $this->options as $key => $name ) {

			if ( isset( $options[ $key ] ) && 'on' === $options[ $key ] ) {
				$sg_cachepress_options->enable_option( $key );

				// translators: Name of option and site's URL.
				$this->log->add_message( sprintf( __( 'enabled %1$s on %2$s', 'sg-cachepress' ), $name, $home_url ) );

				continue;
			}

			$sg_cachepress_options->disable_option( $key );

			// translators: Name of option and site's URL.
			$this->log->add_message( sprintf( __( 'disabled %1$s on %2$s', 'sg-cachepress' ), $name, $home_url ) );

			if ( 'enable_cache' === $key ) {
				sg_cachepress_purge_cache();
			}
		}

		$actions = filter_input( INPUT_POST, 'sg-actions', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		if ( isset( $actions['force_https'] ) && 'on' === $actions['force_https'] ) {
			SG_CachePress_SSL::enable_from_wordpress_options();

			update_option( 'sg_cachepress_ssl_enabled', 1 );
			// translators: Site's URL.
			$this->log->add_message( sprintf( __( 'enabled Force HTTPS on %s', 'sg-cachepress' ), $home_url ) );
		} elseif ( 1 === get_option( 'sg_cachepress_ssl_enabled' ) ) {
			SG_CachePress_SSL::disable_from_wordpress_options();
			update_option( 'sg_cachepress_ssl_enabled', 0 );

			// translators: Site's URL.
			$this->log->add_message( sprintf( __( 'disabled Force HTTPS on %s', 'sg-cachepress' ), $home_url ) );
		}

		restore_current_blog();
	}

	/**
	 * Adds dynamic cache status column to sites list.
	 *
	 * @param array $sites_columns Set of columns passed by filter.
	 *
	 * @return array
	 */
	public function wpmu_blogs_columns( $sites_columns ) {

		return array_merge( $sites_columns, $this->columns );
	}

	/**
	 * Outputs status in dynamic cache column.
	 *
	 * @param string $column_name Sites list column table name.
	 * @param int    $blog_id     Current row site ID.
	 */
	public function manage_sites_custom_column( $column_name, $blog_id ) {

		if ( ! array_key_exists( $column_name, $this->columns ) ) {
			return;
		}

		/** @var SG_CachePress_Options $sg_cachepress_options */
		global $sg_cachepress_options;

		switch_to_blog( $blog_id );

		switch ( $column_name ) {
			case 'sg-dynamic-cache':
				$cache     = $sg_cachepress_options->is_enabled( 'enable_cache' );
				$autoflush = $sg_cachepress_options->is_enabled( 'autoflush_cache' );

				if ( $cache && $autoflush ) {
					esc_html_e( 'Enabled (AutoFlush)', 'sg-cachepress' );
				} elseif ( $cache ) {
					esc_html_e( 'Enabled', 'sg-cachepress' );
				} else {
					esc_html_e( 'Disabled', 'sg-cachepress' );
				}

				break;

			case 'sg-force-https':
				if ( SG_CachePress_SSL::is_enabled_from_wordpress_options() ) {
					esc_html_e( 'Enabled', 'sg-cachepress' );
				} else {
					esc_html_e( 'Disabled', 'sg-cachepress' );
				}

				break;
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
					sg_cachepress_purge_cache();
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

		// translators: Action ran and number of sites affected.
		$message = sprintf( __( 'ran %1$s on %2$d sites', 'sg-cachepress' ), $this->bulk_actions[ $doaction ], count( $blogs ) );

		if ( 'sg-purge-cache' === $doaction ) {
			$argument = 'sg-cache-purged';
			// translators: Number of sites affected.
			$message  = sprintf( __( 'purged cache on %d sites', 'sg-cachepress' ), count( $blogs ) );
		}

		$this->log->add_message( $message );
		$redirect_to = add_query_arg( $argument, count( $blogs ), $redirect_to );

		return $redirect_to;
	}

	/**
	 * Adds Purge Cache to quick actions in sites list.
	 *
	 * @param array $actions Set of actions passed by filter.
	 * @param int   $blog_id Site ID to act on.
	 *
	 * @return array
	 */
	public function manage_sites_action_links( $actions, $blog_id ) {

		$url = add_query_arg( [
			'action'  => 'sg-purge-cache',
			'site_id' => $blog_id,
		], admin_url( 'admin-ajax.php' ) );

		$link    = sprintf(
			'<a href="%1$s" style="color:#a00">%2$s</a>',
			$url,
			esc_html__( 'Purge Cache', 'sg-cachepress' )
		);

		$actions = array_merge(
			array_slice( $actions, 0, 1 ),
			[ 'sg-purge-cache' => $link ],
			array_slice( $actions, 1 )
		);

		return $actions;
	}

	/**
	 * Handles ajax request.
	 */
	public function wp_ajax() {

		if ( ! current_user_can( 'manage_sites' ) ) {
			return;
		}

		$site_id = filter_input( INPUT_GET, 'site_id' );

		if ( empty( $site_id ) ) {
			return;
		}

		switch_to_blog( $site_id );
		sg_cachepress_purge_cache();
		restore_current_blog();

		// translators: Site URL.
		$this->log->add_message( sprintf( __( 'purged cache on %s', 'sg-cachepress' ), get_home_url( $site_id ) ) );

		wp_safe_redirect( add_query_arg( 'sg-cache-purged', 1, wp_get_referer() ) );
		die();
	}

	/**
	 * Outputs notices on completed bulk actions.
	 */
	public function network_admin_notices() {

		if ( filter_has_var( INPUT_GET, 'sg-settings-updated' ) ) {
			$count = filter_input( INPUT_GET, 'sg-settings-updated', FILTER_VALIDATE_INT );
			echo '<div class="updated sg-cachepress-notification"><p>';
			// translators: Count of sites.
			printf( esc_html__( 'SG Optimizer settings updated on %d sites.', 'sg-cachepress' ), $count );
			echo '</p></div>';
		}

		if ( filter_has_var( INPUT_GET, 'sg-cache-purged' ) ) {
			$count = filter_input( INPUT_GET, 'sg-cache-purged', FILTER_VALIDATE_INT );
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

	/**
	 * Hide option rows related to PHP Compat.
	 */
	public function admin_print_footer_scripts() {

		$screen = get_current_screen();

		if ( empty( $screen ) || 'site-settings-network' !== $screen->id ) {
			return;
		}

		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$( "body.site-settings-php .form-table tr:has(label:contains('Sg Wpephpcompat'))" ).hide();
			});
		</script>
		<?php
	}
}
