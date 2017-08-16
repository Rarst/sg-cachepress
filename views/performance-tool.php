<div class="sgwrap performance-tool">
	<div class="box sgclr">
		<?php
		/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
		global $sg_cachepress_performance_tool;
		$is_default    = empty( $_POST ) || ( 'advanced' !== filter_input( INPUT_POST, 'scan-type' ) );
		$is_logged_out = empty( $_POST ) || ( 'logged-in' !== filter_input( INPUT_POST, 'login' ) );
		$hide_advanced = $is_default ? 'display:none' : '';
		$urls          = $sg_cachepress_performance_tool->get_urls();
		$summary       = $sg_cachepress_performance_tool->get_summary_results();
		$last_scan     = get_option( 'sg_cachepress_last_scan' );
		?>

		<h2><?php esc_html_e( 'Site Performance Test', 'sg-cachepress' ); ?></h2>

		<p><?php esc_html_e( 'Here, you can perform a speed test of your website. Our plugin will analyse the way it loads and provide you with valuable information about the resources you’re using and the time it takes for them to load. In addition to that, you can see valuable performance optimization tips that will help you improve the speed of your site.', 'sg-cachepress' ); ?></p>

		<p><?php esc_html_e( 'The Default Scan will run through the typical WordPress pages in a way that your visitors will most likely visit your site. The Advanced Scan will allow you to select the particular pages you want to test as well as whether you want to test to be performed as logged user or not.', 'sg-cachepress' ); ?></p>

		<form method="post">

			<div class="greybox">
				<h3><?php esc_html_e( 'Scan Options', 'sg-cachepress' ); ?></h3>

				<label>
					<input type="radio" name="scan-type" value="default" <?php checked( $is_default ); ?> /> <?php esc_html_e( 'Default Scan', 'sg-cachepress' ); ?>
				</label>
				<label>
					<input type="radio" name="scan-type" value="advanced" <?php checked( ! $is_default ); ?> /> <?php esc_html_e( 'Advanced Scan', 'sg-cachepress' ); ?>
				</label>

				<div id="advanced-options" style="<?php echo esc_attr( $hide_advanced ); ?>">
					<h4><?php esc_html_e( 'Advanced Options', 'sg-cachepress' ); ?></h4>

					<label>
						<input type="radio" name="login" value="logged-out" <?php checked( $is_logged_out ); ?> <?php disabled( (bool) $hide_advanced ); ?> /> <?php esc_html_e( 'Test as non–logged user', 'sg-cachepress' ); ?>
					</label>
					<label>
						<input type="radio" name="login" value="logged-in" <?php checked( ! $is_logged_out ); ?> <?php disabled( (bool) $hide_advanced ); ?> /> <?php esc_html_e( 'Test as logged user', 'sg-cachepress' ); ?>
					</label>

					<h4><?php esc_html_e( 'URLs To Test', 'sg-cachepress' ); ?></h4>

					<label for="sg-performance-test-urls"><?php esc_html_e( 'Add the URLs you want to perform your tests on. Up to 10.', 'sg-cachepress' ); ?></label>

					<br /><textarea name="urls" id="sg-performance-test-urls" cols="60" rows="10" <?php disabled( (bool) $hide_advanced ); ?>><?php echo esc_textarea( implode( "\n", $urls ) ); ?></textarea>
				</div>

				<br /><input type="submit" value="<?php esc_attr_e( 'Analyze Site', 'sg-cachepress' ); ?>" />

			</div>

			<?php if ( ! empty( $summary ) ) : ?>
				<div class="greybox">
					<h3><?php esc_html_e( 'Scan Results', 'sg-cachepress' ); ?></h3>

					<div class="whitebox">
						<h4>
							<?php
							// translators: Formatted time of the scan.
							printf( esc_html__( 'Current Scan: %s', 'sg-cachepress' ), date_i18n( 'H:i:s Y-m-d', $summary['time'] + ( 3600 * get_option( 'gmt_offset' ) ) ) );
							?>
						</h4>
						<p>
							<?php
							// translators: Formatted average time of the scan.
							printf( esc_html__( 'Average Load Time: %.2fs', 'sg-cachepress' ), $summary['average'] );
							?>
						</p>
						<p>
							<?php
							// translators: URL and formatted time.
							printf( esc_html__( 'Slowest Page: %1$s %2$.2fs', 'sg-cachepress' ), esc_url( $summary['max']['url'] ), $summary['max']['time'] );
							?>
						</p>
						<p>
							<?php
							// translators: URL and formatted time.
							printf( esc_html__( 'Fastest Page: %1$s %2$.2fs', 'sg-cachepress' ), esc_url( $summary['min']['url'] ), $summary['min']['time'] );
							?>
						</p>
					</div>

					<?php if ( ! empty( $last_scan ) ): ?>
						<div class="whitebox">
							<h4>
								<?php
								// translators: Formatted time of the scan.
								printf( esc_html__( 'Last Scan: %s', 'sg-cachepress' ), date_i18n( 'H:i:s Y-m-d', $last_scan['time'] + ( 3600 * get_option( 'gmt_offset' ) ) ) );
								?>
							</h4>
							<p>
								<?php
								// translators: Formatted average time of the scan.
								printf( esc_html__( 'Average Load Time: %.2fs', 'sg-cachepress' ), $last_scan['average'] );
								?>
							</p>
							<p>
								<?php
								// translators: URL and formatted time.
								printf( esc_html__( 'Slowest Page: %1$s %2$.2fs', 'sg-cachepress' ), esc_url( $last_scan['max']['url'] ), $last_scan['max']['time'] );
								?>
							</p>
							<p>
								<?php
								// translators: URL and formatted time.
								printf( esc_html__( 'Fastest Page: %1$s %2$.2fs', 'sg-cachepress' ), esc_url( $last_scan['min']['url'] ), $last_scan['min']['time'] );
								?>
							</p>
						</div>
					<?php endif; ?>

					<div class="clr"></div>
					<input type="submit" value="<?php esc_attr_e( 'Repeat Test', 'sg-cachepress' ); ?>" />
				</div>


			<?php endif; ?>
		</form>
	</div>
</div>
