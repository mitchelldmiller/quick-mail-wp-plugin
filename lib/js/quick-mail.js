// Welcome to quick-mail.js 3.2.9

/**
 * set local storage
 * @param {String} item id
 * @param {String} info data
 */
function set_qm_info(item, info)
{
   try 	{
      localStorage.setItem(item, info);
   } catch(e) {
      console.log('cannot set: ' + item);
   }
} // end set_qm_info

/**
 * get info from html storage
 * @param {String} item ID
 * @returns {String} item's value
 */
function get_qm_info(item)
{
   var got = '', nothing = 'N/A';
   try 	{
      got = localStorage.getItem(item);
      if (got == null) {
         got = nothing;
         localStorage.setItem(item, got);
      }
   } catch(e) {
      got = nothing;
   }

   return got;
} // end get_qm_info

/**
 * add saved address
 * @param {String} raw_info email address
 */
function add_qm_info(raw_info)
{
   if (raw_info == '' || typeof(localStorage) == 'undefined' ) { return true; }
   var key = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'qmp6', 'qmp7', 'qmp8', 'qmp9', 'qmp10', 'qmp11', 'qmp12');
   var value = new Array('', '', '', '', '', '', '', '', '', '', '', '', '');
   var duplicate = false;
   var info = jQuery.trim(raw_info);
   for (var i = 1; i < key.length && duplicate == false; i++)
   {
      if (!localStorage.getItem(key[i])) { continue; }
      value[i] = localStorage.getItem(key[i]);
      if (value[i] == info) { 
    	  	duplicate = true;
    	  } // end if duplicate
   } // end for

   var last_valid = get_qm_info('last_valid');
   if (info != last_valid) {
      set_qm_info('last_valid', info)
   } // end if

   if (duplicate) { 
	   return;
   } // end if last

   for (var i = 1; i < key.length - 1; i++)
   {
	   localStorage.setItem(key[i], value[i + 1]);
   } // end for

   localStorage.setItem(key[key.length - 1], info);
} // end add_qm_info

/**
 * load email address from select menu
 * @param {String} t email address
 * @returns {Boolean} valid address
 */
function load_qm_email_option(t)
{
	if ( !t ) {
		return false; // match anything with content for IDN domains @since 1.3.0
	}
	if (jQuery('#qm-success').is(':visible') ) {
		jQuery('#qm-success').hide();
	} // end if
	
	if (jQuery('#qm-duplicate').is(':visible') ) {
		jQuery('#qm-duplicate').hide();
	} // end if

	var email = unescape(t);
	var cc = jQuery('#qm-cc').val();
	if (cc == '') {
		jQuery('#qm-email').val(email);
		return true;
	}

	// split cc on comma and check for duplicate
	var mtest = cc.split(',');
	var dup = false;
	for (var i = 0; (i < mtest.length) && (dup == false); i++) {
		if (email == mtest[i]) {
			dup = true;
		}
	} // end for

	clear_qm_select('#qm_to_select'); // clear selection
	if (dup == true) {
		jQuery('#qm-email').val('');
		jQuery('#qm-duplicate').show();
		return false;
	} // end if duplicate

	jQuery('#qm-email').val(email);
	return true;
} // end load_qm_email_option

/**
 * check if cc selection equals recipient
 * @returns {Boolean} valid
 */
function is_qm_email_dup() {
	clear_qm_msgs();
	var selection = jQuery('#qm-primary').val();
	var info = selection.toLowerCase();
	var qcc = jQuery('#qm-secondary').find('option:selected');
	if (info == '' || qcc.length == 1 && qcc[0] == '') {
		return false;
	}

	var result = true;
	for (var i = 0; (i < qcc.length) && (result == true); i++) {
		result = (qcc[i].value.toLowerCase() != info);
	} // end for
		
	if (result == false) {
		jQuery('#qm-primary').val('');
		jQuery('#qm-duplicate').show();
		clear_qm_select('#qm-primary');
	} // end if
	
	return result;
} // end is_qm_email_dup

/**
 * update cc address with selected option.
 * @param info selected cc address
 * @returns {Boolean} contains duplicate?
 * @since 1.45
 */
function update_qm_cc(selection) {
	var info = selection.toLowerCase();
	if (info.length < 5 || info.length > 254) {
		if (info.length > 45) {
			info = substr(info, 0, 45) + '...';
		}
		if (info > " ") {
			jQuery('#qm-ima').html('<br>' + info);
			jQuery('#qm-validate').show();
		}
		return false;
	} // ignore "Select" or invalid

	var recipient = jQuery('#qm-email').val().toLowerCase();
	var no_spaces = jQuery('#qm-cc').val().toLowerCase().replace(/ /g, ',');
	var raw_cc = no_spaces.split(',');
	var blank = (raw_cc.length < 2) && (raw_cc[0].length < 4);
	
	// short circuit if selection is recipient or cc is empty
	if (info == recipient) {
		jQuery('#qm-dma').html('<br>' + info);
		jQuery('#qm-duplicate').show();
		clear_qm_select('#qm_cc_select');
		return false;
	} // end if
	
	// check for duplicates
	var fixed = '';
	var duplicate = '';
	for (var i = 0; i < raw_cc.length; i++) {
		if (raw_cc[i].length < 5) {
			continue;
		}
		if (raw_cc[i] == info || raw_cc[i] == recipient) {
			duplicate += raw_cc[i].concat('<br>');
		} else {
			fixed += raw_cc[i].concat(',');
		}
	} // end for
	
	if (duplicate == '') {
		jQuery('#qm-cc').val(info.concat(',', fixed));
		clear_qm_select('#qm_cc_select');
		return true;
	} // end if

	var str = "/" + info + "/";
	if ((fixed.search(str) == -1) && (info != recipient)) {
		fixed = info + "," + fixed + ",";
	} // restore duplicate, if all were deleted
	
	var qlen = fixed.length - 1;
	clear_qm_select('#qm_cc_select');
	jQuery('#qm-cc').val(fixed.substr(0, qlen));
	jQuery('#qm-dma').html('<br>' + duplicate);
	jQuery('#qm-duplicate').show();
	return false;
} // end update_qm_cc

function sort_qm_select(select) {
    jQuery(select).html(jQuery(select).children('option').sort(function (x, y) {
        return jQuery(x).text().toUpperCase() < jQuery(y).text().toUpperCase() ? -1 : 1;
    }));
    jQuery(select).get(0).selectedIndex = 0;
} // end sort_qm_select from code by Alex Bezuska http://codepen.io/AlexBezuska/pen/kCwvJ

/**
 * reset selected option to "select"
 * @param {String} select jQuery id of select. #qm_to_select or #qm_cc_select
 * @since 1.5.2
 */
function clear_qm_select(select) {
	jQuery(select)[0].selectedIndex = 0;
} // end clear_qm_select

/**
 * make select for recipients from addresses in HTML storage
 * @param {String} source element ID
 * @param {String} location select element ID
 * @param {Boolean} is_cc is it CC or email?
 */
function make_qm_to_select(source, location, is_cc) {
	var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'qmp6', 'qmp7', 'qmp8', 'qmp9', 'qmp10', 'qmp11', 'qmp12');
	var lby = (location == 'qm_cc_choice') ? 'qcc2_label' : 'qtc_label';
	var control = is_cc ? '<select aria-labelledby="' + lby + '" size="1" id="qm_cc_select" onchange="return update_qm_cc(this.value)">' : '<select size="1" id="qm_to_select" onchange="return load_qm_email_option(this.value)">';
	var blank = '<option value="" selected> Select</option>';
	control += blank;
	var bottom = '</select>';
	var got = '', nothing = 'N/A';
	var ctr = 0;
	var data = jQuery(source).val();
	var info = data;
	for (var i = 1; i < id.length; i++) {
		got = get_qm_info(id[i]);
		if (!got || (got == nothing) || got == 'undefined') {
			continue;
		}
		ctr++;
		var line = '<option value="' + escape(got) + '" id="' + id[i] + '">'
				+ got + '</option>';
		control += line;
	} // end if

	if (ctr > 0) {
		control += bottom;
	} else {
		control = '&nbsp;';
	} // end if

	jQuery(location).html(control);
} // make_qm_to_select

/**
 * clear stored addresses in HTML storage
 * @returns {Boolean} true for event
 */
function clear_qm_addresses() {
   var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'qmp6', 'qmp7', 'qmp8', 'qmp9', 'qmp10', 'qmp11', 'qmp12', 'last_valid');
   for (var i = 1; i < id.length; i++)
   {
      try 	{
      localStorage.removeItem(id[i]);
      } catch(e) {
         console.log('cannot remove: ' + id[i]);
      }
   } /// end for
   jQuery('#qm_saved').hide();
   return true;
} // end clear_qm_addresses

/**
 * are there any saved addresses?
 * @returns {Boolean}
 * @since 2.0.3
 */
function got_saved_qm_addresses() {
	   var ctr = false;
	   var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'qmp6', 'qmp7', 'qmp8', 'qmp9', 'qmp10', 'qmp11', 'qmp12');
	   if (typeof(localStorage) == 'undefined' ) { 
		   return ctr;
	   }
	   
	   for (var i = 1; i < id.length && ctr == false; i++)
	   {
	      try {
	         if (localStorage.getItem(id[i]))
	         {
	            if (-1 != localStorage.getItem(id[i]).indexOf('@') ) {
	               ctr = true;
	            }
	         } // end if email
	      } catch(e) {
	         console.log("Error reading saved addresses");
	      }
	   } // end for
	   return ctr;
} // end got_saved_qm_addresses

/**
 * check result of email validation
 * @param string data
 * @param string userdata
 * @returns {Boolean} valid email address?
 */
function check_validate_qm_email(data, userdata) {
	if (data == 'OK') {
		jQuery('#qm-email').val(userdata);
		jQuery('#qm-invalid').val('0');
		if (jQuery('#qm-validate').is(':visible') ) {
			jQuery('#qm-validate').hide();
		}
		return true;
    } // end if OK
	if (jQuery('#qm-success').is(':visible') ) {
		jQuery('#qm-success').hide();
	} // end if
	
	if (data.charAt(0) == ' ') {
		jQuery('#qm-duplicate').show();
		jQuery('#qm-dma').html('<br>' + data);
	} else {
		jQuery('#qm-validate').show();
		jQuery('#qm-ima').html('<br>' + data);
		jQuery('#qm-invalid').val('1');
	}
	jQuery('#qm-email').val('');
} // check_validate_qm_email

/**
 * validate recipient address and check for duplicate with cc 
 * @param info email address
 * @param dup cc address
 * @param val_option 'Y' or 'N'
 * @returns {Boolean}
 */
function validate_qm_address(info, dup, val_option) {
	var userdata = jQuery.trim(info.replace(/<\/?[^>]+(>|$)/g, "")); // strip tags
	if (userdata != info) {
		jQuery('#qm-email').val(userdata);
	}
	var result = false;
	jQuery.when(perform_qm_validate_email(dup, userdata, val_option)).done(function(text, status, obj)
	{
		if (status == 'success') {
			check_validate_qm_email(text, userdata);					
		}
	});
} // end validate_qm_address

function perform_qm_validate_email(dup, userdata, val_option) {
	return jQuery.ajax(
			{	method: "GET",
				async: true,
				scriptCharset: "UTF-8",
				dataType: "text",
				url: qm_validate,
				data: { dup: dup, email: userdata, 'quick-mail-verify' : val_option } }
			);
} // end perform_qm_validate_email

/**
 * check result from CC validation
 * @param string data OK or error message
 * @return boolean validate address
 */
function check_qm_filter_response(data) {
	var qtest = data.toString();
	var mtest = qtest.split("\t");
	var tab = qtest.indexOf("\t");
	var retval = true;
	clear_qm_msgs();
	if (tab < 1) {
		if (qtest.charAt(0) == ' ') {
			retval = false;
			if (qtest.charAt(1) == ' ') {
				jQuery('#qm-ima').html('<br>' + qtest);
				jQuery('#qm-validate').show();
			} else {
				jQuery('#qm-dma').html('<br>' + qtest);
				jQuery('#qm-duplicate').show();
			} // end if error or duplicate
		} else if (jQuery('#qm-cc').val() != qtest && 'OK' != qtest) {
			jQuery('#qm-cc').val(qtest);
		} // end if content needed update
	} else {
		retval = false;
		jQuery('#qm-cc').val(mtest[1]); // update
		if (mtest[0].charAt(0) == ' ') {
			jQuery('#qm-dma').html('<br>' + mtest[0]);
			jQuery('#qm-duplicate').show();
		} else {
			jQuery('#qm-ima').html('<br>' + mtest[0]);
			jQuery('#qm-validate').show();
		}
	} // end if error 

	if (!retval) {
		jQuery('#qm-cc').focus();
		try { jQuery('#qm-cc').get(0).setSelectionRange(0,0); }
		catch(e) { }
	} // end if error
} // end check_qm_filter_response

/**
 * filter user's cc input with PHP. remove duplicates, validate addresses.
 * 
 * sets appropriate error messages from remote response.
 * 
 * @param to recipient
 * @param cc string|array
 * @param val_option validate? 'Y" or 'N'
 * @returns {Boolean} valid input
 */
function filter_qm_cc_input(to, cc, val_option) {
	if (cc == '') {
		return;
	}
	var userdata = jQuery.trim(cc.replace(/<\/?[^>]+(>|$)/g, "")); // strip tags
	if (userdata != cc) {
		jQuery('#qm-cc').val(userdata);
	} // end if has html
	jQuery.when(perform_qm_cc_filter(to, userdata, val_option)).done(function(text, status, obj)
	{
		if (status == 'success') {
			check_qm_filter_response(text);
		} // end if success
	});
} // end filter_qm_cc_input

function perform_qm_cc_filter(to, cc, val_option) {
	return jQuery.ajax(
			{ 	method: "GET",
				async: true,
				scriptCharset: "UTF-8",
				dataType: "text",
				url: qm_validate,
				data: { to: to, filter: cc, 'quick-mail-verify' : val_option } 
			});
} // end perform_qm_cc_filter

/**
 * save manually entered addresses
 * @since 2.0.0
 */
function update_saved_cc_addresses() {
	if (jQuery('#save_addresses').val() == 'N') {
		return;
	}
	
	if (jQuery('#qm-email').length && jQuery('#qm-email').val() != '') {
  		add_qm_info(jQuery('#qm-email').val());
  	}

	var qcc = '';
	if (jQuery('#qm-cc').length && jQuery('#qm-cc').val() != '') {
		qcc = jQuery('#qm-cc').val();
	} else {
		return;
	}
	
  	var all_info = qcc.split(',');
  	for (var i = 0; i < all_info.length; i++) {
  		add_qm_info(all_info[i]);    
  	} // end for
} // end update_saved_cc_addresses

/**
 * clear status messages
 */
function clear_qm_msgs() {
	if (jQuery('#qm-duplicate').is(':visible') ) {
		jQuery('#qm-duplicate').hide();
	}
    
	if (jQuery('#qm_error').is(':visible') ) {
	jQuery('#qm_error').hide();
	}
    
	if (jQuery('#qm-success').is(':visible') ) {
		   jQuery('#qm-success').hide();
	} // end if

	if (jQuery('#qm-validate').is(':visible') ) {
		if (jQuery('#qm-invalid').val() == '0') {
			jQuery('#qm-validate').hide();
		} // end if
	} // end if
} // clear_qm_msgs

jQuery(document).ready(function() {
	if (jQuery('#quick_mail_cannot_reply').length) {
		if (jQuery('#quick_mail_cannot_reply').prop('checked')) {
		   jQuery('#show_commenters_row').hide();
		   jQuery('#limit_commenters_row').hide();
		} else {
			jQuery('#show_commenters_row').show();
		} // hide show commenters if admin disabled comment replies
		
		// hide show commenters on admin, if admin disabled replies
		jQuery('#quick_mail_cannot_reply').click(function() {
		   if (jQuery('#quick_mail_cannot_reply').prop('checked')) {
			   jQuery('#show_commenters_row').hide();
			   jQuery('#limit_commenters_row').hide();
			} else {
				jQuery('#show_commenters_row').show();
				if (jQuery('#show_quick_mail_commenters').prop('checked')) {
					jQuery('#limit_commenters_row').show();
				}
			}
		});
	} // end if cannot reply exists
	
	if (jQuery('#show_quick_mail_commenters').length) {
		if (jQuery('#show_quick_mail_commenters').prop('checked')) {
			jQuery('#limit_commenters_row').show();
		}

		jQuery('#show_quick_mail_commenters').click(function() {
			   if (jQuery('#show_quick_mail_commenters').prop('checked')) {
				   jQuery('#limit_commenters_row').show();
				} else {
					jQuery('#limit_commenters_row').hide();
				}
		});
	} // end if replying to comments is visible
	
	// save_addresses
	var save_addresses = jQuery('#save_addresses').val();
	update_saved_cc_addresses();
	if (save_addresses == 'N' && got_saved_qm_addresses()) {
		clear_qm_addresses();
	} // end if settings were changed

	jQuery('#qm-invalid').val('0');
	jQuery("#qm-submit").prop('disabled', false);
	if (jQuery('#qm-email').length) {
		jQuery('#qm-email').bind('keypress', function(event) {
			if (event.keyCode == 13) {
				jQuery('#qm-email').blur();
				return false;
			}
		});
	} // end if

	if (jQuery('#qm-cc').length) {
		jQuery('#qm-cc').bind('keypress', function(event) {
			if (event.keyCode == 13) {
				jQuery('#qm-cc').blur();
				return false;
			}
		});
	} // end if

   jQuery('#qm-first').click(function() {
	   clear_qm_msgs();
	});

   jQuery('#qm-email').blur(function() {
      if (!jQuery('#qm_row').length) { 
    	  	return true;
    	  }
      validate_qm_address(jQuery('#qm-email').val(), jQuery('#qm-cc').val(), val_option);
      return true;
   });
   
   jQuery('#qm-cc').blur(function() {
	   if (!jQuery('#qm-cc').length || jQuery('#qm-cc').val() == '') {
		   return true;
	   }
	   filter_qm_cc_input(jQuery('#qm-email').val(), jQuery('#qm-cc').val(), val_option);
	   return (!jQuery('#qm-validate').is(':visible') && !jQuery('#qm-duplicate').is(':visible') );
   });
   
   jQuery('#qm-subject').focus(function() {
	   if (!jQuery('#qm-cc').length || jQuery('#qm-cc').val() == '') {
		   return true;
	   }
	   filter_qm_cc_input(jQuery('#qm-email').val(), jQuery('#qm-cc').val(), val_option);
	   return true;
   });

   jQuery('#quickmailmessage').focus(function() {
	   if (!jQuery('#qm-cc').length || jQuery('#qm-cc').val() == '') {
		   return true;
	   }
	   filter_qm_cc_input(jQuery('#qm-email').val(), jQuery('#qm-cc').val(), val_option);
	   return true;
   });
   
   jQuery('#qm-file-first').change(function() {
	   if (this.value != '') {
		   jQuery('.qm-second').show();
		   jQuery('.qm-row-second').show();
		   jQuery('#qm-file-second').focus();
	   }
   });
   
   jQuery('#qm-second-file').change(function() {
	   if (this.value != '') {
		   jQuery('.qm-third').show();
		   jQuery('.qm-row-third').show();
		   jQuery('#qm-file-third').focus();
	   }
   });
   
   jQuery('#qm-third-file').change(function() {
	   if (this.value != '') {
		   jQuery('.qm-fourth').show();
		   jQuery('.qm-row-fourth').show();
		   jQuery('#qm-file-fourth').focus();
	   }
   });
   
   jQuery('#qm-fourth-file').change(function() {
	   if (this.value != '') {
		   jQuery('.qm-fifth').show();
		   jQuery('.qm-row-fifth').show();
		   jQuery('#qm-file-fifth').focus();
	   }
   });
   
   jQuery('#qm-fifth-file').change(function() {
	   if (this.value != '') {
		   jQuery('.qm-sixth').show();
		   jQuery('.qm-row-sixth').show();
		   jQuery('#qm-file-sixth').focus();
	   }
   });

   jQuery("#Hello").submit(function( event ) {
	   jQuery('#qm-success').hide();
	   if (jQuery('#qm-invalid').val() == '1') {
		   event.preventDefault();
		   jQuery('#qm-validate').show();
		   jQuery('#qm-email').focus();
		   jQuery('#qm-email').select();
		   return false;
      } // end if error message is visible
	   
      if (!jQuery('#qm-cc').length) {
    	  	return true;
      } // end if multiple is showing
   });

   if (jQuery('#qm_row').length) {
      if (typeof(localStorage) == 'undefined') {
         jQuery('#qm_row').hide();
         return true;
      }
      if (jQuery('#save_addresses').val() != 'N' && got_saved_qm_addresses()) {
         var cur = localStorage.getItem('last_valid');
         if (cur) {
            jQuery('#qm-email').val = cur;
         }
         make_qm_to_select('#qm-email', '#qm_to_choice', false);
         sort_qm_select('#qm_to_select');
         jQuery('#qm_row').show();
         make_qm_to_select('#qm-cc', '#qm_cc_choice', true);
         sort_qm_select('#qm_cc_select');
         jQuery('#qm_cc_row').show();
      } else {
         jQuery('#qm_row').hide();
         jQuery('#qm_cc_row').hide();
      } // end if need select
   } // end if row exists
   return true;
});
