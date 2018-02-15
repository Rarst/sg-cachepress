<?php

/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
global $sg_cachepress_performance_tool;

$gzip_enabled    = $sg_cachepress_performance_tool->is_gzip_enabled();
$expires_enabled = $sg_cachepress_performance_tool->is_expires_enabled();

$default_enable_cache    = get_site_option( 'sg-cachepress-default-enable-cache', 0 );
$default_autoflush_cache = get_site_option( 'sg-cachepress-default-autoflush-cache', 0 );

?>
<div class="wrap">

	<h1><?php esc_html_e( 'SG Optimizer', 'sg-cachepress' ); ?></h1>

	<h2><?php esc_html_e( '.htaccess Options', 'sg-cachepress' ); ?></h2>

	<p>
		<a id="sg-cachepress-gzip-toggle" class="<?php echo $gzip_enabled ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
		<?php esc_html_e( 'gZip Compression', 'sg-cachepress' ); ?>
	</p>
	<br />
	<p>
		<a id="sg-cachepress-browser-cache-toggle" class="<?php echo $expires_enabled ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
		<?php esc_html_e( 'Leverage Browser Cache', 'sg-cachepress' ); ?>
	</p>
	<br/>

	<h2><?php esc_html_e( 'New Site Options', 'sg-cachepress' ); ?></h2>

	<p>
		<a id="sg-cachepress-default-enable-cache-toggle" class="<?php echo $default_enable_cache ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
		<?php esc_html_e( 'Dynamic Cache', 'sg-cachepress' ); ?>
	</p>
	<br />
	<p>
		<a id="sg-cachepress-default-autoflush-cache-toggle" class="<?php echo $default_autoflush_cache ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
		<?php esc_html_e( 'AutoFlush Cache', 'sg-cachepress' ); ?>
	</p>

	<input type="hidden" id="nonce-parameter-update" name="nonce-parameter-update" value="<?php echo wp_create_nonce( 'sg-cachepress-parameter-update' ); ?>" />
</div>
