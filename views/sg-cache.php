<div class="sgwrap">         
     	<div class="box sgclr">
     	
 		<h2><?php _e( 'SG Optimizer by SiteGround', 'sg-cachepress' ) ?></h2>		
 		<p><?php _e( 'SG Optimizer is a plugin that allows you to use the major performance optimisations for WordPress, which <a href="https://www.siteground.com/wordpress-hosting.htm" target="_blank">SiteGround hosting</a> is providing on its servers.', 'sg-cachepress' ) ?></p>

		<div class="three sgclr">
		<?php
		global $sg_cachepress_options;
		if ( ! $this->options_handler->is_enabled( 'disallow_cache_config' ) ) :
		?>
 			<div class="greybox">
 				<h3><?php _e( 'SuperCacher Config', 'sg-cachepress' ) ?></h3>
 				<a href="./admin.php?page=caching"><img src="<?php echo plugin_dir_url( __FILE__ )?>../css/cache.png" alt="Caching Settings" title="Caching Settings" /></a>
 				<a id="caching-link" class="button" href="./admin.php?page=caching">Configure</a>
 			</div>
		<?php endif; ?>

		    <?php
		    if ( ! $this->options_handler->is_enabled( 'disallow_https_config' ) ) :
			?>
 			<div class="greybox">
 					<h3><?php _e( 'HTTPS Config', 'sg-cachepress' ) ?></h3>
 					<a href="./admin.php?page=ssl"><img src="<?php echo plugin_dir_url( __FILE__ )?>../css/ssl.png" alt="HTTPS Config" title="HTTPS Config" /></a>
 					<a id="caching-link" class="button" href="./admin.php?page=ssl">Configure</a>
 			</div>
			<?php endif; ?>

			<?php if ( ! is_multisite() ) : ?>
 			<div class="greybox">
 					<h3><?php _e( 'PHP Config', 'sg-cachepress' ) ?></h3>
 					<a href="./admin.php?page=php-check"><img src="<?php echo plugin_dir_url( __FILE__ )?>../css/php.png" alt="PHP Config" title="PHP Config" /></a>
 					<a id="caching-link" class="button" href="./admin.php?page=php-check">Configure</a>
 			</div>
 			<?php endif; ?>
 		</div>
</div>