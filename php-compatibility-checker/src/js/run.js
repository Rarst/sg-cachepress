// Global variables.
var test_version, only_active, timer, runAction;

jQuery( document ).ready(function($) {
        //runAction();
        checkStatus();

	$( '#developermode' ).change(function() {
		if ( $(this).is( ':checked' ) ) {
			$( '#developerMode' ).show();
			$( '#standardMode' ).hide();
		} else {
			$( '#developerMode' ).hide();
			$( '#standardMode' ).show();
		}
	});
	$( '#downloadReport' ).on( 'click', function() {
		download( $( '#testResults' ).val(), 'report.txt', 'text/plain' );
	});
	$( document ).on( 'click', '.view-details', function() {
		// Get the textarea with is on the same (dom) level.
		var textarea = $( this ).siblings( 'textarea' );
		if ( 'none' === textarea.css( 'display' ) ) {
			textarea.css( 'display' , '' );
		} else {
			textarea.css( 'display', 'none' );
		}
	});
        

        
	$( '#runButton' ).on( 'click', function() {   
          runAction();
	});
        

        
        $( '#upgradeButton' ).on( 'click', function() {
            $( '#upgradeButton' ).blur();

            // Show the ajax spinner.
            $( '#upgradeButton' ).val(window.sg_wpephpcompat.loading);
            jQuery('#runButton').addClass( "sgloading" );
            //$( '.spinner' ).show();

            upgradeTo($( '#recommended_php_version' ).val());
	});
        
        $( '#changeVersionButton' ).on( 'click', function() {
            upgradeTo($( '#manualVersionValue' ).val());
	});   

        $( '#cleanupButton' ).on( 'click', function() {
          cleanupAction();
        });

	function cleanupAction() {
		clearTimeout( timer );
		jQuery.get( ajaxurl,  { 'action': 'sg_wpephpcompat_clean_up' }, function() {
			resetDisplay();
			checkStatus();
		});
	};
        //
        
        runAction = function runAction() {
          $( '#phpVersionCheckerFooterMsg' ).html('');
          jQuery('#runButton')
                  .show()
                  .attr('disabled', true)
                  .attr('value', sgCachePressL10n.phpversion_checking)
                  .addClass( "sgloading" )
                  .blur();          

          // Empty the results textarea.
          resetDisplay();
          test_version = $( 'input[name=phptest_version]:checked' ).val();
          only_active = $( 'input[name=active_plugins]:checked' ).val();
          var data = {
                  'action': 'sg_wpephpcompat_start_test',
                  'test_version': test_version,
                  'only_active': only_active,
                  'startScan': 1
          };

          // Start the test!
          jQuery.post( ajaxurl, data ).always(function() {
                  // Start timer to check scan status.
                  checkStatus();
          });
        };        
});




function cleanupReport() {
  jQuery.get( ajaxurl,  { 'action': 'sg_wpephpcompat_clean_up' }, function() {
  });
}

function upgradeTo(version) {
  var data = {
          'action': 'sg_wpephpcompat_change_version',
          'version': version
  };

  // Start the upgrade!
  jQuery.post( ajaxurl, data, function(res) {
    //setTimeout(function() {
      window.location.reload();
    //}, 1000);
          
  });
}
/**
 * Check the scan status and display results if scan is done.
 * onDocumentReady
 */
function checkStatus() {
        var $ = jQuery; 
        $( '#phpVersionCheckerContainer' ).show();
        // show default message
        //$( '#phpVersionCheckerText' ).html(window.sg_wpephpcompat.check_your_php_version); 
        
	var data = {
		'action': 'sg_wpephpcompat_check_status'
	};
        
        var noReport = true;

	var obj;
	jQuery.post( ajaxurl, data, function( obj ) {
		/*
		 * Status false: the test is not running and has not been run yet
		 * Status 1: the test is currently running
		 * Status 0: the test as completed but is not currently running
		 */
                jQuery( '#runButton' ).show();
		if ( false === obj.results ) {                        
		  jQuery( '#runButton' ).val( window.sg_wpephpcompat.run );
		} else {
		  //jQuery( '#runButton' ).val( window.sg_wpephpcompat.rerun );
                  jQuery( '#runButton' ).hide();
                  jQuery( '#phpVersionCheckerHeaderMsgNotUpToDate' ).hide();
                  
                  
                  
		  jQuery('#runButton').removeClass( "sgloading" );
                        //jQuery( '#runButton' ).hide();
		}
                
                jQuery('#runButton').removeAttr('disabled');
                
		if ( '1' === obj.status ) {
			//jQuery( '.spinner' ).show();
			jQuery('#runButton').attr('disabled', true).attr('value', sgCachePressL10n.phpversion_checking);
		} else {
			jQuery( '.spinner' ).hide();
		}

		if ( '0' !== obj.results ) {
			if( false !== obj.results ) {
                                noReport = false;
				test_version = obj.version;
				displayReport( obj.results );
			}
                        // cron finished
		} else {
                        // cron in progress
                        jQuery('#runButton')
                          .show()
                          .attr('disabled', true)
                          .attr('value', sgCachePressL10n.phpversion_checking)
                          .addClass( "sgloading" )
                          .blur();     
                  
			jQuery( '#progressbar' ).progressbar({
				value: obj.progress
			});

			// Display the current plugin count.
			jQuery( '#wpe-progress-count' ).text( ( obj.total - obj.count + 1 ) + '/' + obj.total );

			// Display the object being scanned.
			jQuery( '#wpe-progress-active' ).text( obj.activeJob );

			// Requeue the checkStatus call.
			timer = setTimeout(function() {
				checkStatus();
			}, 5000);
		}
                
//                if (noReport) {
//                    // show default message                 
//                }

	}, 'json' ).fail(function ( xhr, status, error )
	{
		// Server responded correctly, but the response wasn't valid.
		if ( 200 === xhr.status ) {
			alert( "Error: " + error + "\nResponse: " + xhr.responseText );
		}
		else { // Server didn't respond correctly.
			alert( "Error: Plase, do not close this tab or refresh the page while the plugin is running!" );
		}
	});
}
/**
 * Clear previous results.
 */
function resetDisplay() {
	jQuery( '#progressbar' ).progressbar({
		value: 0
	});
	jQuery( '#testResults' ).text('');
	jQuery( '#standardMode' ).html('');
	jQuery( '#wpe-progress-count' ).text('');
	jQuery( '#wpe-progress-active' ).text('');
	jQuery( '#footer' ).hide();
        jQuery( '#upgradeButton' ).hide();
        jQuery( '#phpVersionCheckerTextBelow' ).text('');
        
}
/**
 * Loop through a string and count the total matches.
 * @param  {RegExp} regex Regex to execute.
 * @param  {string} log   String to loop through.
 * @return {int}          The total number of matches.
 */
function findAll( regex, log ) {
	var m;
	var count = 0;
	while ( ( m = regex.exec( log ) ) !== null ) {
		if ( m.index === regex.lastIndex ) {
			regex.lastIndex++;
		}
		if ( parseInt( m[1] ) > 0 ) {
			count += parseInt( m[1] );
		}
	}
	return count;
}
/**
 * Display the pretty report.
 * @param  {string} response Full test results.
 */
function displayReport( response ) {   
	// Clean up before displaying results.
	resetDisplay();
	var $ = jQuery;                
	var compatible = 1;        

	// Keep track of the number of failed plugins/themes.
	var failedCount = 0;
	var errorsRegex = /(\d*) ERRORS?/g;
	var warningRegex = /(\d*) WARNINGS?/g;
	var updateVersionRegex = /e: (.*?);/g;
	var currentVersionRegex = /n: (.*?);/g;

	// Grab and compile our template.
	var source = $( '#result-template' ).html();
	var template = Handlebars.compile( source );

	$( '#testResults' ).text( response );
	$( '#footer' ).show();

	// Separate plugins/themes.
	var plugins = response.replace( /^\s+|\s+$/g, '' ).split( window.sg_wpephpcompat.name + ':' );

	// Remove the first item, it's empty.
	plugins.shift();
        
	// Loop through them.
	for ( var x in plugins ) {             
		var updateVersion;
		var updateAvailable = 0;
		var passed = 1;
		var skipped = 0;
		// Extract plugin/theme name.
		var name = plugins[x].substring( 0, plugins[x].indexOf( '\n' ) );
		// Extract results.
		var log = plugins[x].substring( plugins[x].indexOf('\n'), plugins[x].length );
		// Find number of errors and warnings.
		var errors = findAll( errorsRegex, log );
		var warnings = findAll( warningRegex, log );
		// Check to see if there are any plugin/theme updates.
		if ( updateVersionRegex.exec( log ) ) {
			updateAvailable = 1;
		}
		// Update plugin and global compatibility flags.
		if ( parseInt( errors ) > 0 || parseInt( warnings ) > 0) {
			compatible = 0;
			passed = 0;
			failedCount++;
		}

		// Trim whitespace and newlines from report.
		log = log.replace( /^\s+|\s+$/g, '' );

		if ( log.search('skipped') !== -1 ) {
			skipped = 1;
		}
                
                // only if warnings or errors
                if (errors || warnings) {
                    // Use handlebars to build our template.
                    var context = {
                            plugin_name: name,
                            warnings: warnings,
                            errors: errors,
                            logs: log,
                            passed: passed,
                            skipped: skipped,
                            test_version: test_version,
                            updateAvailable: updateAvailable
                    };                                

                    var html = template( context );                
                    $('#standardMode').append( html );
                }

	}
        
        var recommendedVersionNumber = parseInt(test_version.replace(/\./, ''));   
        var current_version = $( '#current_php_version' ).val();
        var currentVersionNumber = parseInt(current_version.replace(/\./, ''));
	// Display global compatibility status.
	if ( compatible ) {            
            // is compatible and ready to upgrade
            if (currentVersionNumber < recommendedVersionNumber) {  
              $( '#phpVersionCheckerFooterMsg' ).html('');
              jQuery( '#runButton' ).hide();
              cleanupReport();
              $( '#phpVersionCheckerHeaderMsgCompatible' ).html( '<font color="green">' + window.sg_wpephpcompat.your_wp + 
                  ' PHP ' + test_version + ' ' +
                  window.sg_wpephpcompat.compatible + '. </font>');
                    
                $( '#upgradeButton' ).show();
                $( '#upgradeButton' ).val(window.sg_wpephpcompat.upgrade_to + ' PHP ' + test_version);                
                
            // Up to Date
            } else {
//                $( '#phpVersionCheckerHeaderMsgUpToDate' ).html(window.sg_wpephpcompat.you_running_running_on + ' ' +
//                    current_version + ' ' +
//                    window.sg_wpephpcompat.recommended_or_higher);             
            }
            
	} else {     
          $( '#phpVersionCheckerTextBelow' ).html(
            window.sg_wpephpcompat.not_compatible + 
            test_version + '. ' + 
            window.sg_wpephpcompat.see_details_below);
    
            var message = '';
            message = window.sg_wpephpcompat.if_you_fixed_retry;
            
            if (currentVersionNumber < 56) { 
              message = message + window.sg_wpephpcompat.recommend_to_switch;
            }
          
          $( '#phpVersionCheckerFooterMsg' ).html(message);
	}
}
