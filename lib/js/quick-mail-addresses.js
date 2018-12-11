/**
 * quick-mail-addresses.js 3.5.0 RC3
 */
(function($) {
	'use strict';
	$(function() {
		if ($('#qm_saved').length < 1) {
			return;
		}
		
	   if (jQuery('#save_addresses').val() == 'N') { 
		   return ctr;
	   }

		var ctr = 0;
		var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'qmp6',
				'qmp7', 'qmp8', 'qmp9', 'qmp10', 'qmp11', 'qmp12');
		if (typeof(localStorage) == 'undefined') {
			return ctr;
		}
		for (var i = 1; i < id.length; i++) {
			try {
				if (localStorage.getItem(id[i])) {
					if (-1 != localStorage.getItem(id[i]).indexOf('@')) {
						ctr++;
					}
				} // end if email
			} catch (e) {
				console.log("Error reading saved addresses");
			}
		} // end for

		const { __, _x, _n, _nx } = wp.i18n;
		/* translators: %s: settings message. number of saved addresses */
		var msg = sprintf( _n( 'Clear %s saved address', 
				'Clear %s saved addresses', ctr, 'quick-mail'), ctr );
		if (ctr > 0) {
			var stext = '<button class="qm-button" onclick="clear_qm_addresses()">'	+ msg + '</button>';
			$('#qm_saved').html(stext);
		}
	});

})( jQuery );