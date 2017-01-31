<?php
$siteurl = get_option('siteurl');
$siteurlHTTPS = SG_CachePress_SSL::switchProtocol('http', 'https', $siteurl)
?>

<div class="sgwrap">
	<div class="box" style="display: none;"></div>
	<div class="box">
            <h2><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></h2>
            <p><?php _e( 'The switch below will reconfigure your website to use an SSL certificate. First, it will redirect all your traffic through HTTPS. Thus, your site will always load through a secure connection and you will not have non-secure versions of your pages and duplicate content. Second, it will also make the connection to any resource your site includes secure. This way the visitors will not see mixed content warning in the browser.', 'sg-cachepress' ) ?></p>
            
             <p><strong><?php _e( 'Note:',  'sg-cachepress') ?></strong> <?php _e( 'If your site includes a resource from an external location that cannot be reached via HTTPS this resource will no longer be loaded on your site.', 'sg-cachepress' ) ?></strong></p>
            
            
            
            

         
            <div class="greybox">
                                    
                    <a <?php if (SG_CachePress_SSL::is_certificate_enabled()) {?>href=""<?php } ?> id="sg-cachepress-ssl-toggle<?php if (!SG_CachePress_SSL::is_certificate_enabled()) {?>disabled<?php } ?>" 
                       class="<?php  if ( 
                               SG_CachePress_SSL::is_fully_enabled()
                               ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>                    

                    <p id="sg-cachepress-ssl-text"><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></p>                    
                    
                    <?php  if ( SG_CachePress_SSL::is_partially_enabled()) {?>
                    <p id="sg-cachepress-ssl-error">
                        <?php _e( 'Warning: It seems you’ve been using another plugin or manually configured your WordPress application to work over HTTPS. Please, disable all SSL forcing plugins and remove all .htaccess rules regarding SSL before you enable the option in order to avoid potential issues.', 'sg-cachepress' ) ?>
                    </p>
                    <?php } ?> 
                    
                    <?php if (SG_CachePress_SSL::is_fully_enabled()) {?>
                    <p id="sg-cachepress-logout"><strong><?php _e( 'Important:',  'sg-cachepress') ?></strong> <?php _e( 'You may have to login again after this feature is enabled!', 'sg-cachepress' ) ?></p>
                    <?php } ?> 

                    <?php if (!SG_CachePress_SSL::is_certificate_enabled()) {?>
                    <p class="notcached" id="sg-cachepress-ssl-error">
                        <?php _e( 'Warning: You don’t have a certificate issued for '. $siteurlHTTPS .'. '
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