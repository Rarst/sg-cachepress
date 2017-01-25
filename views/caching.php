<div class="sgwrap">         
    	<div class="box sgclr">	
    	
    	<h2><?php _e( 'SuperCacher for WordPress by SiteGround', 'sg-cachepress' ) ?></h2>		
    	<p><?php _e( 'The SuperCacher is a system that allows you to use the SiteGround dynamic cache and Memcached to optimize the performance of your WordPress. In order to take advantage of the system you should have the SuperCacher enabled at your web host plus the required cache options turned on below. For more information on the different caching options refer to the <a href="http://www.siteground.com/tutorials/supercacher/" target="_blank">SuperCacher Tutorial</a>!', 'sg-cachepress' ) ?></p>
    		
		<h2><?php _e( 'Dynamic Cache Settings', 'sg-cachepress' ) ?></h2>
	
		<div class="three sgclr">
			<div class="greybox">
				<h3><?php _e( 'Dynamic Cache', 'sg-cachepress' ) ?></h3>
				<a href="" id="sg-cachepress-dynamic-cache-toggle" class="<?php  if ( $this->options_handler->get_option('enable_cache') ==1 ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>
				<p id="sg-cachepress-dynamic-cache-text"><?php _e( 'Enable the Dynamic caching system', 'sg-cachepress' ) ?></p>
				<p id="sg-cachepress-dynamic-cache-error" class="error"></p>
			</div>
		
			<div class="greybox">
				<h3><?php _e( 'AutoFlush Cache', 'sg-cachepress' ) ?></h3>
				<a href="" id="sg-cachepress-autoflush-cache-toggle" class="<?php  if ( $this->options_handler->get_option('autoflush_cache') ==1 ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>
				<p id="nginxcacheoptimizer-autoflush-cache-text"><?php _e( 'Automatically flush the Dynamic cache', 'sg-cachepress' ) ?></p>
				<p id="nginxcacheoptimizer-autoflush-cache-error" class="error"></p>
			</div>
		
			<div class="greybox">
				<h3><?php _e( 'Purge Cache', 'sg-cachepress' ) ?></h3>
				<form class="purgebtn" method="post" action="<?php menu_page_url( 'sg-cachepress-purge' ); ?>">
					<?php submit_button( __( 'Purge the Cache', 'sg-cachepress' ), '', 'sg-cachepress-purge', false );?>
				</form>
				<p><?php _e( 'Purge all the data cached by the Dynamic cache.', 'sg-cachepress' ) ?></p>
			</div>
			
		</div>
		<div class="greybox">
			<h3><?php _e( 'Exclude URLs From Dynamic Caching', 'sg-cachepress' ) ?></h3>
			<p><?php _e( 'Provide a list of your website URLs you would like to exclude from the cache. You can add part of the URLs that you want to exclude.', 'sg-cachepress' ) ?></p>
			
			<form method="post" action="<?php menu_page_url( 'sg-cachepress-purge' ); ?>">
				<textarea id="sg-cachepress-blacklist-textarea"><?php  echo esc_textarea($this->options_handler->get_blacklist()); ?></textarea>
				<?php submit_button( __( 'Update the Exclude List', 'sg-cachepress' ), 'primary', 'sg-cachepress-blacklist', false );?>
			</form>
		</div>
	</div>                                      
	<div class="box">
		<h2><?php _e( 'Memcached Settings', 'sg-cachepress' ) ?></h2>
		<div class="greybox">
				
			<a href="" id="sg-cachepress-memcached-toggle" class="<?php  if ( $this->options_handler->get_option('enable_memcached') ==1 ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>
			
			<p id="sg-cachepress-memcached-text"><?php _e( 'Enable Memcached', 'sg-cachepress' ) ?></p>
			<p class="error" id="sg-cachepress-memcached-error"></p>
				
			<div class="clr"></div>
			<p><?php _e( 'Store in the server\'s memory frequently executed queries to the database for a faster access on a later use.', 'sg-cachepress' ) ?></p>
			<div class="clr"></div>		
		</div>
	</div>
	
	<div class="box sgclr">
		<h2><?php _e( 'Dynamic Cache Status Checker', 'sg-cachepress' ) ?></h2>
		<div class="greybox">
			
			<form class="purgebtn" method="post" action="<?php menu_page_url( 'sg-cachepress-test' ); ?>" id="cachetest">
				<?php echo get_home_url()?>/&nbsp;<input id="testurl" type="" name="" value="" />
				<?php submit_button( __( 'Test URL', 'sg-cachepress' ), 'primary', 'sg-cachepress-test', false );?>
			</form>
			
			<div class="status_test" style="display:none;"><?php _e( 'Status:', 'sg-cachepress' ) ?> <span id="status_test_value"></span></div>
				
			<div class="clr"></div>
			<p><?php _e( 'Check if this URL is dynamic or cached. Leave empty for your index or <strong>/example/</strong> for another page.', 'sg-cachepress' ) ?></p>
			<div class="clr"></div>		
		</div>
	</div>
</div>