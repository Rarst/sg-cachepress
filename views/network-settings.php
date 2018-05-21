<?php
/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
global $sg_cachepress_performance_tool;

$gzip_enabled    = $sg_cachepress_performance_tool->is_gzip_enabled();
$expires_enabled = $sg_cachepress_performance_tool->is_expires_enabled();

$default_enable_cache    = get_site_option( 'sg-cachepress-default-enable-cache', 0 );
$default_autoflush_cache = get_site_option( 'sg-cachepress-default-autoflush-cache', 0 );

?>

<div class="sgwrap">         
    	<div class="box sgclr">	
    	
    	<h2><?php _e( 'SG Optimizer Multisite Cache Config', 'sg-cachepress' ) ?></h2>		
    	<p><?php _e( 'On this page, you can configure four cache options. The first two - Gzip Compression and Leverage Browser Cache affect your entire network and their state in this config show the current actual state of all sites in your network. The other two cache options - Dynamic Cache and AutoFlush can be used to configure the default setting for each newly created website.  Note, that you can always disable or enable Dynamic Cache and AutoFlush per site and those are simply defaults for newly added ones.', 'sg-cachepress' ) ?></p>



<div class="greybox">
	<h3><?php esc_html_e( 'Global settings for your Multisite Network', 'sg-cachepress' ); ?></h3>
		<br />
		<p>
			<a id="sg-cachepress-gzip-toggle" class="<?php echo $gzip_enabled ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
			<?php esc_html_e( 'Gzip Compression', 'sg-cachepress' ); ?>
		</p>
		<br />
		<p>
			<a id="sg-cachepress-browser-cache-toggle" class="<?php echo $expires_enabled ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
			<?php esc_html_e( 'Leverage Browser Cache', 'sg-cachepress' ); ?>
		</p>
</div>


<div class="greybox">
	<h3><?php esc_html_e( 'Default Settings for New Sites', 'sg-cachepress' ); ?></h3>
		<br />
		<p><a id="sg-cachepress-default-enable-cache-toggle" class="<?php echo $default_enable_cache ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
			<?php esc_html_e( 'Dynamic Cache', 'sg-cachepress' ); ?>
		</p>
		<br />
		<p><a id="sg-cachepress-default-autoflush-cache-toggle" class="<?php echo $default_autoflush_cache ? 'toggleon' : 'toggleoff'; ?>" href="#"></a>
			<?php esc_html_e( 'AutoFlush Cache', 'sg-cachepress' ); ?>
		</p>
		
		<input type="hidden" id="nonce-parameter-update" name="nonce-parameter-update" value="<?php echo wp_create_nonce( 'sg-cachepress-parameter-update' ); ?>" />
	</div>
</div>


