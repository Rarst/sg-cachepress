<?php
// php version checker variables
$phpversions = SG_WPEngine_PHPCompat::get_available_php_versions();
$current_version = SG_WPEngine_PHPCompat::get_current_php_version();
$recommended_php_versions = SG_WPEngine_PHPCompat::get_recommended_php_versions();
$recommended_php_version = $recommended_php_versions[0];
$only_active = 'yes';
$prev_php_version = SG_WPEngine_PHPCompat::get_prev_php_version();
$is_up_to_date = version_compare($current_version, $recommended_php_version, '>=');
?>
<div class="sgwrap">
  <div class="box" style="display:none;"></div>
  <div class="box" style="display:none;"></div>
  <div class="box" style="display:none;"></div>	<div class="box" style="display:none;"></div>

  <!-- START phpVersionChecker -->
  <div class="box sgclr" id="phpVersionCheckerContainer" style="display: none;">
    <h2><?php _e('PHP Config', 'sg-cachepress') ?></h2>
    <p><?php _e('This tool will allow you to check if your website is compatible with the recommended by SiteGround PHP version and switch to it with a click. It is highly advisable to keep your WordPress running on the recommended PHP for best security and performance.', 'sg-cachepress') ?></p>
    
     <p><?php _e('<strong>Notice:</strong> checking your site for PHP 7.0 compatibility is a time consuming process that depends on the number of active plugins you have on your site. Please, don’t close your browser until the check is completed.', 'sg-cachepress') ?></p>
   

    <div class="greybox" >
      <p id="phpVersionCheckerText"><?php
        if ($is_up_to_date) {
          echo __('Site is running on', 'sg-cachepress') . ' <strong>PHP ' .
          $current_version . ' ' .
          __('</strong> which is our recommended PHP version or higher.', 'sg-cachepress');
        }
        ?></p>

      <input type="hidden" id="current_php_version" value="<?php echo htmlentities($current_version) ?>" />
      <input type="hidden" id="recommended_php_version" value="<?php echo htmlentities($recommended_php_version) ?>" />
      
      <table class="form-table" style="display:none;">
        <tbody>
          <tr>
            <th scope="row">
              <label for="phptest_version">
                <?php _e('PHP Config', 'sg-cachepress'); ?>
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
        <b><?php //_e('Test Results:', 'sg-cachepress');    ?></b>
        <textarea readonly="readonly" id="testResults"></textarea>
      </div>
      </p>
      
      <p id="phpVersionCheckerHeaderMsgCompatible"></p>
      <p id="phpVersionCheckerHeaderMsgUpToDate"></p>
            
      <?php if (!$is_up_to_date) { ?>
        <?php if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) { ?>      
        <strong>Error:</strong> <?php echo __('Compatibility checker relies on the WordPress Cron '
                . 'functionality to operate which is disabled for this site. '
                . 'Please, enable the WordPress Cron and refresh this page. Check out <a href="https://www.siteground.com/kb/disable-enable-wordpress-cron/#enable" target="_blank">this article</a> for more information on that matter.'); ?> 
        <?php } else { ?>
          <p id="phpVersionCheckerHeaderMsgNotUpToDate"><?php echo __('Your site is using ', 'sg-cachepress') . 
                ' <strong>PHP ' . $current_version . '</strong> '
                . __('which is below the recommended <strong>PHP ', 'sg-cachepress') . $recommended_php_version . '</strong>.'
                ?></p>
      
          <input style="display: none; height: 40px; line-height: 40px; text-align: center; margin-left: 5px;" 
                 name="run" 
                 id="runButton" 
                 type="button" 
                 class="button-primary" />   

          <input style="display: none; height: 40px; line-height: 40px; text-align: center; margin-left: 5px;"
                 name="upgradeButton"
                 id="upgradeButton"
                 type="button"
                 class="button-primary" />
        <?php } ?>
        <?php } ?>

<!--      <input style="float: left; margin-left: 5px;" name="run" id="cleanupButton" type="button" value="<?php esc_attr_e('Clean up', 'php-compatibility-checker'); ?>" class="button" />-->

      <div class="clr"></div>
      <h3 id="phpVersionCheckerTextBelow"></h3>
      <div class="clr"></div>

      <div id="standardMode"></div>
      <!-- Results template -->
      <script id="result-template" type="text/x-handlebars-template">
        <div style="border-left-color: {{#if skipped}}#999999{{else if passed}}#49587c{{else}}#e74c3c{{/if}};" class="wpe-results-card">

        <div class="inner-right">
        <h3 style="margin: 0px;font-weight:400;float:left">{{plugin_name}}</h3>
        {{#if skipped}}<?php _e('Unknown', 'sg-cachepress'); ?>{{else if passed}}PHP {{test_version}} <?php _e('compatible', 'sg-cachepress'); ?>.{{else}}{{/if}}
        {{update}}


        <a class="view-details"><?php _e('See Errors', 'sg-cachepress'); ?></a>
        <textarea style="display: none; white-space: pre;">{{logs}}</textarea>

        </div>
      </script>   
      
      <p id="phpVersionCheckerFooterMsg"></p>

    </div>                         
  </div>     
  <!-- END phpVersionChecker -->

  <!-- START manualPHPVersion -->
  <?php if ($is_up_to_date) { ?>
    <div class="box">
      <h2><?php _e('Manual PHP Version Change', 'sg-cachepress') ?></h2>
      <p>In case you want to experiment with another PHP version you can use the switch below. Please note, that your site will NOT be checked for compatibility before the change is made. Please be advised that in some rare cases,  if you choose a version that is not compatible with your WordPress your admin may become inaccessible and you may need to roll back to a proper PHP version by editing your .htaccess file.</p>
      <div class="greybox">											
        <div class="clr"></div>		
        <input type="button" 
          id="changeVersionButton"
          name="sg-cachepress-purge" 
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
  <?php } ?>
  <!-- END manualPHPVersion -->        
</div>
