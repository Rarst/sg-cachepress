<?php

/**
 * Implements .htaccess edits and checks.
 */
class SG_CachePress_Htaccess_Editor {

	/** @var string $path Path to .htaccess. */
	protected $path;

	/**
	 * SG_CachePress_Htaccess_Editor constructor.
	 *
	 * @param string $path Path to .htaccess.
	 */
	public function __construct( $path ) {

		if ( '.htaccess' !== basename( $path ) ) {
			return;
		}

		$this->path = $path;
	}

	/**
	 * Checks for presence of a directive by wrapping comments.
	 *
	 * @param string $start Opening comment.
	 * @param string $end   Closing comment.
	 *
	 * @return array|bool False on failure or array with start/end positions, inclusive.
	 */
	public function has_directive( $start, $end ) {

		if ( empty( $this->path ) || ! file_exists( $this->path ) ) {
			return false;
		}

		$contents = file_get_contents( $this->path );

		$start_pos = strpos( $contents, '# ' . $start );

		if ( false === $start_pos ) {
			return false;
		}

		$end_pos = strpos( $contents, '# ' . $end, $start_pos + strlen( $start ) + 2 );

		if ( false === $end_pos ) {
			return false;
		}

		return [ $start_pos, $end_pos + strlen( $end ) + 2 ];
	}

	/**
	 * Adds a directive, wrapped in comments.
	 *
	 * @param string $start     Opening comment.
	 * @param string $end       Closing comment.
	 * @param string $directive Directive contents.
	 *
	 * @return false|int False on failure or file_put_contents() call result.
	 */
	public function add_directive( $start, $end, $directive ) {

		if ( empty( $this->path ) ) {
			return false;
		}

		$contents = '';

		if ( file_exists( $this->path ) ) {
			$contents = file_get_contents( $this->path );
		}

		$contents .= "\n\n"
			. '# ' . $start . "\n"
			. $directive . "\n"
			. '# ' . $end . "\n";

		return file_put_contents( $this->path, $contents );
	}

	/**
	 * Removes a directive by wrapping comments.
	 *
	 * @param string $start Opening comment.
	 * @param string $end   Closing comment.
	 *
	 * @return false|int False on failure or file_put_contents() call result.
	 */
	public function remove_directive( $start, $end ) {

		if ( empty( $this->path ) || ! file_exists( $this->path ) ) {
			return false;
		}

		$position = $this->has_directive( $start, $end );

		if ( false === $position ) {
			return false;
		}

		list( $start_pos, $end_pos ) = $position;

		$contents = file_get_contents( $this->path );
		$contents = substr( $contents, 0, $start_pos ) . substr( $contents, $end_pos );

		return file_put_contents( $this->path, $contents );
	}
}
