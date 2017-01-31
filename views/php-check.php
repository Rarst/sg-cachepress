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
	<div class="box" style="display:none;"></div>
	<div class="box" style="display:none;"></div>
		<div class="box" style="display:none;"></div>	<div class="box" style="display:none;"></div>
    
	<!-- START phpVersionChecker -->
	<div class="box sgclr" id="phpVersionCheckerContainer" style="display: none;">
            <h2><?php _e( 'PHP Version', 'sg-cachepress' ) ?></h2>
            <p><?php _e( 'This tool will allow you to check if your website is compatible with the recommended by SiteGround PHP version and switch to it. In addition to that, you can manually change the active PHP version for your site.', 'sg-cachepress' ) ?></p>

            <div class="greybox" >
                    <p id="phpVersionCheckerText"></p>
                             
            <input type="hidden" id="current_php_version" value="<?php echo htmlentities($current_version)?>" />
            <table class="form-table" style="display:none;">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="phptest_version">
                                <?php _e('PHP Version', 'sg-cachepress'); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            foreach ($phpversions as $name => $version) {
                                printf('<label><input type="radio" name="phptest_version" value="%s" %s /> %s</label><br>', $version, checked($recommended_php_version, $version, false), $name);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="active_plugins"><?php _e('Only Active', 'sg-cachepress'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="radio" name="active_plugins" value="yes" <?php checked($only_active, 'yes', true); ?> />
                                <?php _e('Only scan active plugins and themes', 'sg-cachepress'); ?>
                            </label>
                            <label>
                                <input type="radio" name="active_plugins" value="no" <?php checked($only_active, 'no', true); ?> />
                                <?php _e('Scan all plugins and themes', 'sg-cachepress'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p> 
            <div style="display: none;" id="developerMode">
                <b><?php //_e('Test Results:', 'sg-cachepress'); ?></b>
                <textarea readonly="readonly" id="testResults"></textarea>
            </div>
             
            </p>

            <input style="display: none; height: 40px; line-height: 40px; text-align: center; margin-left: 5px;" name="run" id="runButton" type="button" class="button-primary" />   <div style="display:none; visibility: visible; float: left;" class="spinner"></div>
            <input style="display: none; height: 40px; line-height: 40px; text-align: center; margin-left: 5px;"
                   name="upgradeButton"
                   id="upgradeButton"
                   type="button"
                   class="button-primary" />
                        
<!--	    <input style="float: left; margin-left: 5px;" name="run" id="cleanupButton" type="button" value="<?php esc_attr_e( 'Clean up', 'php-compatibility-checker' ); ?>" class="button" />-->
            
			<div class="clr"></div>
            <h2 id="phpVersionCheckerTextBelow"></h2>
            <div class="clr"></div>
            
            <div id="standardMode"></div>
            <!-- Results template -->
            <script id="result-template" type="text/x-handlebars-template">
                    <div style="border-left-color: {{#if skipped}}#999999{{else if passed}}#49587c{{else}}#e74c3c{{/if}};" class="wpe-results-card">
                            
                            <div class="inner-right">
                                    <h3 style="margin: 0px;font-weight:400">{{plugin_name}}</h3>
                                    {{#if skipped}}<?php _e('Unknown', 'sg-cachepress'); ?>{{else if passed}}PHP {{test_version}} <?php _e('compatible', 'sg-cachepress'); ?>.{{else}}<b><?php _e('Possibly not', 'sg-cachepress'); ?></b> PHP {{test_version}} <?php _e('compatible', 'sg-cachepress'); ?>.{{/if}}
                                    {{update}}
                                    
                                    
                <a class="view-details"><?php _e('See Errors', 'sg-cachepress'); ?></a>
                <textarea style="display: none; white-space: pre;">{{logs}}</textarea>
         
            </div>
            
            <?php $update_url = site_url('wp-admin/update-core.php', 'admin'); ?>
                                    <div style="position:absolute; top:5px; right:5px;float:right;">{{#if updateAvailable}}<div class="badge wpe-update"><a href="<?php echo esc_url($update_url); ?>"><?php _e('Update Available', 'sg-cachepress'); ?></a></div>{{/if}}{{#if warnings}}<div class="badge warnings">{{warnings}} <?php _e('Warnings', 'sg-cachepress'); ?></div>{{/if}}{{#if errors}}{{/if}}</div>
                            </div>
            </script>   
            
            </div>                         
	</div>     
        <!-- END phpVersionChecker -->
                                
        <!-- START manualPHPVersion -->
        <div class="box">
            <h2><?php _e( 'Manual PHP Version Change', 'sg-cachepress' ) ?></h2>
            <div class="greybox">											
                    <div class="clr"></div>
                    <p>
                        <?php _e( 'Active PHP Version: ' . 
                                '<b>PHP ' . $current_version . '</b>'
                                , 'sg-cachepress' ) ?>
                    </p>
                    <div class="clr"></div>		
            <input type="button" 
                   id="changeVersionButton"
                   name="sg-cachepress-purge" 
                   style="background: #3e4b68; color: #FFF; border: none; box-shadow: none;" 
                   class="button"
                   value="<?php _e('Switch to', 'sg-cachepress'); ?>"
                   >
                        
            <select id="manualVersionValue">                           
            <?php
            foreach ($phpversions as $name => $version) {
                $php_version_text = '';
                if (isset($prev_php_version) && $prev_php_version && $version === $prev_php_version) {
                    $php_version_text = __(' - previous version', 'sg-cachepress');
                } elseif (isset($recommended_php_version) && $recommended_php_version && $version === $recommended_php_version) {
                    $php_version_text = __(' - recommended', 'sg-cachepress');
                }
                
                printf('<option value="%s" %s>%s' . $php_version_text . '</option><label>', htmlspecialchars($version), selected($current_version, $version, false), htmlspecialchars($name));
            }
            ?>
            </select>
            </div>
	</div>
        <!-- END manualPHPVersion -->        
</div>