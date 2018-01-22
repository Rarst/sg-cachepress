<?php

/**
 * Implements Performance Test tool.
 */
class SG_CachePress_Performance_Tool {

	/** @var integer TIME_LIMIT Maximum time limit for a test to run in seconds. */
	const TIME_LIMIT = 25;

	/** @var array $results Stored results of current benchmark run. */
	protected $results = [];

	/** @var  SG_CachePress_Time_Collector $time_collector Instance of performance info collector. */
	protected $time_collector;

	/** @var SG_CachePress_Htaccess_Editor $htaccess_editor Instance of .htaccess editor. */
	protected $htaccess_editor;

	/**
	 * SG_CachePress_Performance_Tool constructor.
	 */
	public function __construct() {

		$this->time_collector = new SG_CachePress_Time_Collector();

		// Ideally should be smarter about path, but consistent with what PHP version edit does for now. R.
		$this->htaccess_editor = new SG_CachePress_Htaccess_Editor( ABSPATH . '.htaccess' );

		add_action( 'wp_ajax_sg-cachepress-htaccess-update', array( $this, 'wp_ajax' ) );
	}

	/**
	 * Adds Performance Test admin menu entry.
	 *
	 * @return false|string Page hook name.
	 */
	public function admin_menu() {

		return add_submenu_page(
			SG_CachePress::PLUGIN_SLUG,
			esc_html__( 'Performance Test', 'sg-cachepress' ),
			esc_html__( 'Performance Test', 'sg-cachepress' ),
			'manage_options',
			'performance-test',
			[ $this, 'display' ]
		);
	}

	/**
	 * Outputs Performance Test template.
	 */
	public function display() {

		$submit = filter_has_var( INPUT_POST, 'scan-type' );

		if ( $submit ) {
			$this->test_urls( $this->get_urls() );

			if ( 'logged-in' === filter_input( INPUT_POST, 'login', FILTER_SANITIZE_STRING ) ) {

				wp_localize_script(
					SG_CachePress::PLUGIN_SLUG . '-chart',
					'sgOptimizerLoadingTimes',
					$this->get_chart_data()
				);
			}
		}

		require __DIR__ . '/views/performance-tool.php';

		if ( $submit ) {
			update_option( 'sg_cachepress_last_scan', $this->get_summary_results() );
		}
	}

	/**
	 * Retrieves and times set of URLs.
	 *
	 * @param array $urls Set of URLs.
	 */
	public function test_urls( $urls ) {

		global $timestart; // WP core global.

		$results      = [];
		$args         = [
			'headers' => [ 'x-sg-optimizer-test' => 1 ],
		];
		$bypass_cache = ( 'logged-in' === filter_input( INPUT_POST, 'login', FILTER_SANITIZE_STRING ) );

		if ( $bypass_cache ) {
			$args['cookies'] = [ 'wpSGCacheBypass' => 1 ];
		}

		foreach ( $urls as $url ) {

			$request_start = microtime( true );
			$elapsed       = $request_start - $timestart;

			if ( $elapsed > self::TIME_LIMIT ) {
				break;
			}

			$args['timeout'] = min( 5, (int) floor( self::TIME_LIMIT - $elapsed ) );
			$response        = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				continue;
			}

			$results[] = [
				'url'              => $url,
				'time'             => microtime( true ) - $request_start,
				'response-code'    => wp_remote_retrieve_response_code( $response ),
				'content-encoding' => wp_remote_retrieve_header( $response, 'content-encoding' ),
				'time-data'        => $this->time_collector->extract_data( wp_remote_retrieve_body( $response ) ),
			];
		}

		$this->results = $results;
	}

	/**
	 * Retrieves URLs from the form with fallback to the default set.
	 *
	 * @return array
	 */
	public function get_urls() {

		$urls = filter_input( INPUT_POST, 'urls' );
		$urls = explode( "\n", $urls );
		$urls = array_map( 'trim', $urls );
		$urls = array_filter( $urls, [ $this, 'is_valid_url' ] );
		$urls = array_slice( $urls, 0, 10 );

		if ( ! empty( $urls ) ) {
			return $urls;
		}

		return $this->get_default_urls();
	}

	/**
	 * Checks URL for sanity and locality.
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 */
	public function is_valid_url( $url ) {

		static $home_domain;

		if ( empty( $home_domain ) ) {
			$home_domain = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		if ( empty( $url ) ) {
			return false;
		}

		if ( esc_url( $url ) !== $url ) {
			return false;
		}

		if ( wp_parse_url( $url, PHP_URL_HOST ) !== $home_domain ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate set of varied default URLs.
	 *
	 * @return array
	 */
	public function get_default_urls() {

		$urls = [];

		$urls[] = home_url();
		$urls[] = home_url( '404' );

		$posts = get_posts( [
			'numberposts' => 1,
			'fields'      => 'ids',
		] );

		if ( ! empty( $posts ) ) {
			$urls[] = get_permalink( $posts[0] );
		}

		$pages = get_posts( [
			'numberposts' => 1,
			'fields'      => 'ids',
			'post_type'   => 'page',
		] );

		if ( ! empty( $pages ) ) {
			$urls[] = get_permalink( $pages[0] );
		}

		$categories = get_categories( [
			'number'  => 1,
			'orderby' => 'count',
			'order'   => 'desc',
			'fields'  => 'ids',
		] );

		if ( ! empty( $categories ) ) {
			$urls[] = get_category_link( $categories[0] );
		}

		$tags = get_tags( [
			'number'  => 1,
			'orderby' => 'count',
			'order'   => 'desc',
			'fields'  => 'ids',
		] );

		if ( ! empty( $tags ) ) {
			$urls[] = get_tag_link( $tags[0] );
		}

		return $urls;
	}

	/**
	 * Retrieves generated test results.
	 *
	 * @return array
	 */
	public function get_results() {

		return $this->results;
	}

	/**
	 * Retrieves summary of test results.
	 *
	 * @return array
	 */
	public function get_summary_results() {

		/** @var SG_CachePress_Options $sg_cachepress_options */
		global $sg_cachepress_options;

		static $results;

		if ( isset( $results ) ) {
			return $results;
		}

		if ( empty( $this->results ) ) {
			return [];
		}

		$results = array_column( $this->results, 'time', 'url' );

		$min = min( $results );
		$max = max( $results );

		$results = [
			'time'          => time(),
			'average'       => array_sum( $results ) / count( $results ),
			'min'           => [
				'time' => $min,
				'url'  => array_search( $min, $results, true ),
			],
			'max'           => [
				'time' => $max,
				'url'  => array_search( $max, $results, true ),
			],
			'dynamic-cache' => $sg_cachepress_options->is_enabled( 'enable_cache' ),
			'php'           => PHP_VERSION,
			'gzip'          => $this->is_gzip_enabled(),
			'expires'       => $this->is_expires_enabled(),
			'checksum'      => $this->get_form_checksum(),
		];

		return $results;
	}

	/**
	 * Calculates checksum of current POST data.
	 *
	 * @return string
	 */
	public function get_form_checksum() {

		return md5( wp_json_encode( filter_input_array( INPUT_POST ) ) );
	}

	/**
	 * Compiles data for display in the loading times chart.
	 *
	 * @return array
	 */
	public function get_chart_data() {

		$data = [];

		$total_time = array_sum( array_column( $this->results, 'time' ) );
		$time_data  = array_column( $this->results, 'time-data' );
		$labels     = $this->time_collector->get_event_labels();

		foreach ( $labels as $name => $label ) {

			$event_total      = array_sum( array_column( $time_data, $name ) );
			$data['labels'][] = $label;
			$data['data'][]   = round( $event_total / $total_time * 100, 2 );
		}

		$data['labels'][] = esc_html__( 'Other', 'sg-cachepress' );
		$data['data'][]   = round( 100 - array_sum( $data['data'] ), 2 );

		return $data;
	}

	/**
	 * Checks for gzip compression directive in .htaccess.
	 *
	 * @return bool
	 */
	public function is_gzip_enabled() {

		return false !== $this->htaccess_editor->has_directive( 'GZIP enabled by SG-Optimizer', 'END GZIP' );
	}

	/**
	 * Checks for expires headers directive in .htaccess.
	 *
	 * @return bool
	 */
	public function is_expires_enabled() {

		return false !== $this->htaccess_editor->has_directive( 'Leverage Browser Caching by SG-Optimizer', 'END LBC' );
	}

	/**
	 * Ajax handler for htaccess changes from UI.
	 */
	public function wp_ajax() {

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$name = filter_input( INPUT_POST, 'parameterName' );

		if ( 'gzip' === $name ) {
			$enabled = $this->is_gzip_enabled();

			if ( $enabled ) {
				$result = $this->htaccess_editor->remove_directive( 'GZIP enabled by SG-Optimizer', 'END GZIP' );
				sg_cachepress_purge_cache();
				die( $result ? '0' : '1' );
			}

			$result = $this->htaccess_editor->add_directive(
				'GZIP enabled by SG-Optimizer',
				'END GZIP',
				file_get_contents( __DIR__ . '/gzip.tpl' )
			);
			sg_cachepress_purge_cache();
			die( $result ? '1' : '0' );
		}

		if ( 'browser-cache' === $name ) {
			$enabled = $this->is_expires_enabled();

			if ( $enabled ) {
				$result = $this->htaccess_editor->remove_directive( 'Leverage Browser Caching by SG-Optimizer', 'END LBC' );
				sg_cachepress_purge_cache();
				die( $result ? '0' : '1' );
			}

			$result = $this->htaccess_editor->add_directive(
				'Leverage Browser Caching by SG-Optimizer',
				'END LBC',
				file_get_contents( __DIR__ . '/expires.tpl' )
			);
			sg_cachepress_purge_cache();
			die( $result ? '1' : '0' );
		}
	}
}
