<div class="sgwrap performance-tool">
	<div class="box sgclr">
		<?php
		/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
		global $sg_cachepress_performance_tool;
		$is_default    = 'advanced' !== filter_input( INPUT_POST, 'scan-type' );
		$is_logged_out = 'logged-in' !== filter_input( INPUT_POST, 'login' );
		$urls          = implode( "\n", $sg_cachepress_performance_tool->get_urls() );
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

				<div id="advanced-options" <?php if ( $is_default ) : ?>style="display:none"<?php endif; ?>>
					<h4><?php esc_html_e( 'Advanced Options', 'sg-cachepress' ); ?></h4>

					<label>
						<input type="radio" name="login" value="logged-out" <?php checked( $is_logged_out ); ?> <?php disabled( $is_default ); ?> /> <?php esc_html_e( 'Test as non–logged user', 'sg-cachepress' ); ?>
					</label>
					<label>
						<input type="radio" name="login" value="logged-in" <?php checked( ! $is_logged_out ); ?> <?php disabled( $is_default ); ?> /> <?php esc_html_e( 'Test as logged user', 'sg-cachepress' ); ?>
					</label>

					<h4><?php esc_html_e( 'URLs To Test', 'sg-cachepress' ); ?></h4>

					<label for="sg-performance-test-urls"><?php esc_html_e( 'Add the URLs you want to perform your tests on. Up to 10.', 'sg-cachepress' ); ?></label>

					<br /><textarea name="urls" id="sg-performance-test-urls" cols="60" rows="10" <?php disabled( $is_default ); ?>><?php echo esc_textarea( $urls ); ?></textarea>
				</div>

				<br /><input type="submit" value="<?php esc_attr_e( 'Analyze Site', 'sg-cachepress' ); ?>" />

			</div>

			<?php if ( ! empty( $summary ) ) : ?>
				<div class="greybox">
					<h3><?php esc_html_e( 'Scan Results', 'sg-cachepress' ); ?></h3>

					<?php if ( ! $is_logged_out ) : ?>

						<h4><?php esc_html_e( 'Loading Times', 'sg-cachepress' ); ?></h4>

						<div id="loadingTimesContainer">
							<canvas id="loadingTimes" width="300" height="300"></canvas>
						</div>
						<div class="clr"></div>

					<?php endif; ?>

					<div class="whitebox scan-current">
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

					<?php if ( ! empty( $last_scan ) ) : ?>
						<div class="whitebox scan-last">
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

				<div class="greybox">
					<h3><?php esc_html_e( 'Optimization Checks', 'sg-cachepress' ); ?></h3>

					<?php
					/** @var SG_CachePress_Options $sg_cachepress_options */
					global $sg_cachepress_options;
					?>
					<ul id="optimization-checks">
						<li>
							<?php if ( $sg_cachepress_options->is_enabled( 'enable_cache' ) ) : ?>
								<span class="check-enabled"><?php esc_html_e( 'Enabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'Dynamic Cache', 'sg-cachepress' ); ?></strong>
							<?php else : ?>
								<span class="check-disabled"><?php esc_html_e( 'Disabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'Dynamic Cache', 'sg-cachepress' ); ?></strong>
								<?php
								printf(
									// translators: URL to the configuration page.
									__( 'Enable Dynamic Caching from the <a href="%s">SuperCacher Config page</a> to improve your site performance.' ),
									add_query_arg( 'page', 'caching', admin_url( 'page.php' ) )
								);
								?>
							<?php endif; ?>
						</li>
						<li>
							<?php if ( SG_WPEngine_PHPCompat::isUpToDate() ) : ?>
								<span class="check-enabled"><?php esc_html_e( 'Enabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'Latest PHP version', 'sg-cachepress' ); ?></strong>
							<?php else : ?>
								<span class="check-disabled"><?php esc_html_e( 'Disabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'Latest PHP version', 'sg-cachepress' ); ?></strong>
								<?php
								printf(
									// translators: URL to the configuration page.
									__( 'Switch to the <a href="%s">latest PHP version</a> to get the best out of your site’s performance.' ),
									add_query_arg( 'page', 'php-check', admin_url( 'page.php' ) )
								);
								?>
							<?php endif; ?>
						</li>
						<li>
							<?php if ( SG_CachePress_SSL::is_enabled_from_wordpress_options() ) : ?>
								<span class="check-enabled"><?php esc_html_e( 'Enabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'HTTP2 &amp; SSL', 'sg-cachepress' ); ?></strong>
							<?php else : ?>
								<span class="check-disabled"><?php esc_html_e( 'Disabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'HTTP2 &amp; SSL', 'sg-cachepress' ); ?></strong>
								<?php
								printf(
									// translators: URL to the configuration page.
									__( 'Enable SSL from the <a href="%s">HTTPS Config page</a> in order to start benefiting from the HTTP2 protocol improvements!' ),
									add_query_arg( 'page', 'ssl', admin_url( 'page.php' ) )
								);
								?>
							<?php endif; ?>
						</li>
						<li>
							<?php if ( $summary['gzip'] ) : ?>
								<span class="check-enabled"><?php esc_html_e( 'Enabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'GZIP Compression', 'sg-cachepress' ); ?></strong>
								<?php esc_html_e( 'Congratulations, gZIP is enabled and working on your site saving you precious seconds in loading times.', 'sg-cachepress' ); ?>
							<?php else : ?>
								<span class="check-disabled"><?php esc_html_e( 'Disabled', 'sg-cachepress' ); ?></span>
								<strong><?php esc_html_e( 'gZIP Compression', 'sg-cachepress' ); ?></strong>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			<?php endif; ?>
		</form>
	</div>
</div>