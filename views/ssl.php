<?php     
// php version checker variables
$phpversions = SG_WPEngine_PHPCompat::get_available_php_versions();                        
$current_version = SG_WPEngine_PHPCompat::get_current_php_version();
$recommended_php_versions = SG_WPEngine_PHPCompat::get_recommended_php_versions();
$recommended_php_version = $recommended_php_versions[0];
$only_active = 'yes';
$prev_php_version = SG_WPEngine_PHPCompat::get_prev_php_version();

?>
<div class="sgwrap">
	<h2><?php _e( 'Enable HTTPS for Your Site', 'sg-cachepress' ) ?></h2>		
	<p><?php _e( 'Switching on will reconfigure your WordPress site to work entirely through HTTPS. Furthermore, all insecure resource requests will be fixed automatically. Important: You will have to login anew after this feature is enabled.', 'sg-cachepress' ) ?></p>
    
        <div class="box" style="display: none;"></div>
        <!-- START enableSSLandHTTP2 -->
	<div class="box">
            <h2><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></h2>
            <div class="greybox">				
                    <a href="" id="sg-cachepress-ssl-toggle" 
                       class="<?php  if ( 
                               SG_CachePress_SSL::is_fully_enabled()
                               ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>

                    <p id="sg-cachepress-ssl-text"><?php _e( 'Toggle HTTPS', 'sg-cachepress' ) ?></p>
                    <p class="notcached" id="sg-cachepress-ssl-error"><?php  if ( SG_CachePress_SSL::is_partially_enabled()) 
                        _e( 'Warning: It seems you’ve been using another plugin or manually configured your WordPress application to work over HTTPS. Please, disable all SSL forcing plugins and remove all .htaccess rules regarding SSL before you enable the option in order to avoid potential issues.', 'sg-cachepress' ) ?></p>
                    
                    <div class="clr"></div>		
            </div>
	</div>
        <!-- END enableSSLandHTTP2 -->
    
        
</div>