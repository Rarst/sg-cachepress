<?php

/** @var SG_CachePress_Performance_Tool $sg_cachepress_performance_tool */
global $sg_cachepress_performance_tool;
?>

<div class="sgwrap">         
	<div class="box sgclr">	
	<h2><?php _e( 'Multisite Network Log', 'sg-cachepress' ) ?></h2>		
	<p><?php _e( 'Here, you can see a log for the last ten changes made to your SG Optimizer configurateion either by you, or the users you have granted permissions to modify the configuration.', 'sg-cachepress' ) ?></p>
	
	<div class="greybox">
		<h3><?php esc_html_e( 'Action Log', 'sg-cachepress' ); ?></h3>
		<p><pre><?php echo esc_html( $log ); ?></pre></p>
	</div>
</div>