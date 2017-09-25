<?php

/**
 * Collects time information for performance test.
 */
class SG_CachePress_Time_Collector {

	const COMMENT_START = '<!-- SG Optimizer time data';
	const COMMENT_END = '-->';

	/** @var array $data Record of captured times. */
	protected $data = [];

	/** @var array $events Set of events to process and label. */
	protected $events = [];

	/**
	 * SG_CachePress_Time_Collector constructor.
	 */
	public function __construct() {

		$this->events = [
			'core-plugins-load' => [
				'label' => esc_html__( 'Core and plugins load', 'sg-cachepress' ),
				'start' => 'timestart',
				'end'   => 'plugins_loaded',
			],
			'theme-load'        => [
				'label' => esc_html__( 'Theme load', 'sg-cachepress' ),
				'start' => 'setup_theme',
				'end'   => 'after_setup_theme',
			],
			'init'              => [
				'label' => esc_html__( 'Init stage', 'sg-cachepress' ),
				'start' => 'init',
				'end'   => 'wp_loaded',
			],
			'main-loop'         => [
				'label' => esc_html__( 'Main Loop', 'sg-cachepress' ),
				'start' => 'loop_start',
				'end'   => 'loop_end',
			],
		];

		$ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $ajax || is_admin() ) {
			return;
		}

		if ( 1 !== filter_input( INPUT_COOKIE, 'wpSGCacheBypass', FILTER_VALIDATE_INT ) ) {
			return;
		}

		if ( empty( $_SERVER['HTTP_X_SG_OPTIMIZER_TEST'] ) || '1' !== $_SERVER['HTTP_X_SG_OPTIMIZER_TEST'] ) {
			return;
		}

		global $timestart;

		$this->data['timestart'] = $timestart;

		add_action( 'plugins_loaded', [ $this, 'tick' ], 20 );
		add_action( 'setup_theme', [ $this, 'tick' ], 0 );
		add_action( 'after_setup_theme', [ $this, 'tick' ], 20 );
		add_action( 'init', [ $this, 'tick' ], - 1 );
		add_action( 'wp_loaded', [ $this, 'tick' ], 20 );
		add_action( 'loop_start', [ $this, 'tick' ], 1 );
		add_action( 'loop_end', [ $this, 'tick' ], 20 );
		add_action( 'wp_footer', [ $this, 'wp_footer' ] );
	}

	/**
	 * Retrieves set of available events and their localized labels.
	 *
	 * @return array
	 */
	public function get_event_labels() {

		$labels = [];

		foreach ( $this->events as $name => $event ) {
			$labels[ $name ] = $event['label'];
		}

		return $labels;
	}

	/**
	 * Marks the time at current hook.
	 */
	public function tick() {

		$this->data[ current_action() ] = microtime( true );
	}

	/**
	 * Compiles results from captured data.
	 *
	 * @return array
	 */
	protected function get_compiled_data() {

		$data = [];

		foreach ( $this->events as $name => $event ) {
			if ( ! empty( $this->data[ $event['start'] ] ) && ! empty( $this->data[ $event['end'] ] ) ) {
				$data[ $name ] = $this->data[ $event['end'] ] - $this->data[ $event['start'] ];
			}
		}

		return $data;
	}

	/**
	 * Extracts data from HTML page source.
	 *
	 * @param string $html Page source.
	 *
	 * @return array
	 */
	public function extract_data( $html ) {

		$start = strpos( $html, self::COMMENT_START );

		if ( false === $start ) {
			return [];
		}

		$start  += strlen( self::COMMENT_START );
		$length = strpos( $html, self::COMMENT_END, $start ) - $start;

		return (array) json_decode( trim( substr( $html, $start, $length ) ) );
	}

	/**
	 * Outputs data in page source.
	 */
	public function wp_footer() {

		echo self::COMMENT_START . "\n"
			. wp_json_encode( $this->get_compiled_data() )
			. self::COMMENT_END;
	}
}
