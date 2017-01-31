<?php
$siteurl = get_option('siteurl');
$siteurlHTTPS = SG_CachePress_SSL::switchProtocol('http', 'https', $siteurl)
?>

<div class="sgwrap">
	<div class="box" style="display: none;"></div>
	<div class="box">
            <h2><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></h2>
            <p><?php _e( 'Switching on will reconfigure your WordPress site to work entirely through HTTPS and will fix any potential mixed content issues. Note, that if you\'re including a source from a site that cannot be reached via HTTPS, this resource will not be loaded on your site.', 'sg-cachepress' ) ?></p>
            <p><strong><?php _e( 'Important: You may have to login again after this feature is enabled!', 'sg-cachepress' ) ?></strong></p>
         
            <div class="greybox">
                                    
                    <a <?php if (SG_CachePress_SSL::is_certificate_enabled()) {?>href=""<?php } ?> id="sg-cachepress-ssl-toggle<?php if (!SG_CachePress_SSL::is_certificate_enabled()) {?>disabled<?php } ?>" 
                       class="<?php  if ( 
                               SG_CachePress_SSL::is_fully_enabled()
                               ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>                    

                    <p id="sg-cachepress-ssl-text"><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></p>                    
                    <p class="notcached" id="sg-cachepress-ssl-error"><?php  if ( SG_CachePress_SSL::is_partially_enabled()) 
                        _e( 'Warning: It seems you’ve been using another plugin or manually configured your WordPress application to work over HTTPS. Please, disable all SSL forcing plugins and remove all .htaccess rules regarding SSL before you enable the option in order to avoid potential issues.', 'sg-cachepress' ) ?>
                    </p>
                    


                    <?php if (!SG_CachePress_SSL::is_certificate_enabled()) {?>
                    <p class="notcached" id="sg-cachepress-ssl-error">
                        <?php _e( 'Warning: You don’t have a certificate issued for '. $siteurlHTTPS .' . '
                                . 'Please, install an SSL certificate before you force a HTTPS connection. '
                                . 'Check out <a href="https://www.siteground.com/tutorials/cpanel/lets-encrypt.htm" target="_blank">this tutorial</a> '
                                . 'for more information on that matter.', 'sg-cachepress' ); 
                        ?>
                    </p>
                    <?php } ?>   
                    
                    
                    <div class="clr"></div>		
            </div>
	</div>
</div>