<?php
$siteurl = get_option('siteurl');
$siteurlHTTPS = SG_CachePress_SSL::switchProtocol('http', 'https', $siteurl)
?>

<div class="sgwrap">
	<div class="box" style="display: none;"></div>
	<div class="box">
            <h2><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></h2>
            <p><?php _e( '<p>Clicking on the <strong>Force HTTPS</strong> toggle will do the following:</p>
            <ul class="ssl-ul">
            	<li>It will redirect all your traffic through HTTPS. Thus, your site will always load through a secure connection and you will avoid duplicate content.</li>
            	<li>It will also make the connection to any resource your site includes secure. This way the visitors will not see mixed content warning in the browser. <strong>Note:</strong> If your site includes a resource from an external location that cannot be reached via HTTPS this resource will no longer be loaded on your site.</li>
            	<li>Once your site is loaded through HTTPS it will also automatically take advantage of the HTTP/2 protocol too.</li>
            </ul>', 'sg-cachepress' ) ?></p>
     
            <div class="greybox">
                                    
                    <a <?php if (SG_CachePress_SSL::is_certificate_enabled() && SG_CachePress_SSL::get_htaccess_filename() !== false) {?>href=""<?php } ?> id="sg-cachepress-ssl-toggle<?php if (!SG_CachePress_SSL::is_certificate_enabled() || SG_CachePress_SSL::get_htaccess_filename() === false) {?>disabled<?php } ?>" 
                       class="<?php  if ( 
                               SG_CachePress_SSL::is_fully_enabled()
                               ) echo 'toggleon'; else echo 'toggleoff'; ?>"></a>                    

                    <p id="sg-cachepress-ssl-text"><?php _e( 'Force HTTPS', 'sg-cachepress' ) ?></p>                    
      
             <div class="clr"></div>		
            </div>
            
            <?php if (!SG_CachePress_SSL::is_certificate_enabled()) {?>
            <p class="notcached" id="sg-cachepress-ssl-error">
                <?php _e( '<strong>Warning:</strong> You don’t have a certificate issued for '. $siteurlHTTPS .'. '
                        . 'Please, install an SSL certificate before you force a HTTPS connection. '
                        . 'Check out <a href="https://www.siteground.com/tutorials/cpanel/lets-encrypt.htm" target="_blank">this tutorial</a> '
                        . 'for more information on that matter.', 'sg-cachepress' ); 
                ?>
            </p>
            <?php } ?> 
            
            <?php  if ( SG_CachePress_SSL::get_htaccess_filename() === false) {?>
            <p id="sg-cachepress-htaccess-error">
                <?php _e( '<strong>Warning:</strong> your .htaccess is not writable! Make sure it has its permissions set to 644!', 'sg-cachepress' ) ?>
            </p>
            <?php } ?> 
            
            <?php  if ( ( SG_CachePress_SSL::is_partially_enabled() ) && ( SG_CachePress_SSL::get_htaccess_filename() !== false) ) {?>
            <p id="sg-cachepress-partial-error">
                <?php _e( '<strong>Warning:</strong> It seems you’ve been using another plugin or manually configured your WordPress application to work over HTTPS. Please, disable all SSL forcing plugins and remove all .htaccess rules regarding SSL before you enable the option in order to avoid potential issues.', 'sg-cachepress' ) ?>
            </p>
            <?php } ?> 
            
            <p id="sg-cachepress-party"><strong><?php _e( 'Important:',  'sg-cachepress') ?></strong> <?php _e( 'You may have to login again if you decide to disable the force HTTPS functionality!', 'sg-cachepress' ) ?></p>
            
            <p id="sg-cachepress-logout"><strong><?php _e( 'Important:',  'sg-cachepress') ?></strong> <?php _e( 'Once you switch your site to go through HTTPS, please check all third-party services that you\'re using on your site like Gogle Analytics, social networks sharing icons, etc.', 'sg-cachepress' ) ?></p>
            
	</div>
</div>


