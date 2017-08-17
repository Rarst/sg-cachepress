<?php

/**
 * Implements Performance Test tool.
 */
class SG_CachePress_Performance_Tool {

	/** @var array $results Stored results of current benchmark run. */
	protected $results = [];

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

		$results = [];

		// TODO sliding timeout on requests to fit within 30s web server timeout.
		// TODO conditional header for cache bypass.
		foreach ( $urls as $url ) {

			$start     = microtime( true );
			$response  = wp_remote_get( $url );

			$results[] = [
				'url'              => $url,
				'time'             => microtime( true ) - $start,
				'response_code'    => wp_remote_retrieve_response_code( $response ),
				'content-encoding' => wp_remote_retrieve_header( $response, 'content-encoding' ),
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
			'time'    => time(),
			'average' => array_sum( $results ) / count( $results ),
			'min'     => [
				'time' => $min,
				'url'  => array_search( $min, $results, true ),
			],
			'max'     => [
				'time' => $max,
				'url'  => array_search( $max, $results, true ),
			],
			'gzip'    => in_array( 'gzip', array_column( $this->results, 'content-encoding' ), true ),
		];

		return $results;
	}
}
