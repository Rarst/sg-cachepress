<?php
wp_enqueue_style( 'SGOptimizer', plugins_url( '../css/admin.css', __FILE__ ), array(), SG_CachePress::VERSION );
/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
global $sg_cachepress_performance_tool;
?>
<div class="sgwrap" style="max-width:900px;">         
	<div class="box sgclr">	
	<h2><?php _e( 'SG Optimizer Multisite Log ', 'sg-cachepress' ) ?></h2>		
	<p><?php _e( 'Here, you can see a log for the last ten changes made to your SG Optimizer configuration either by you, or the users you have granted permissions to modify the configuration.', 'sg-cachepress' ) ?></p>
	
	<div class="greybox">
		<h3><?php esc_html_e( 'Actions', 'sg-cachepress' ); ?></h3>
		<pre><?php echo esc_html( $log ); ?></pre>
	</div>
</div>
