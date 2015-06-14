// Quick Mail: quick-mail.js 1.20

function setInfo(item, info)
{
	try 	{
		localStorage.setItem(item, info);
	} catch(e) {
		console.log('cannot set: ' + item);
	}
} // end setInfo

function getInfo(item)
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
} // end getInfo

function addInfo()
{
	if (typeof(localStorage) == 'undefined' ) { return true; }
	var key = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5');
	var value = new Array('', '', '', '', '', '');
	var duplicate = 0;
	var info = jQuery('#email').val();
	for (var i = 1; i < key.length; i++)
	{
		if (!localStorage.getItem(key[i])) { continue; }
		value[i] = localStorage.getItem(key[i]);
		if (duplicate == 0 && value[i] == info) { duplicate = i; }
	} // end for

	var last_valid = getInfo('last_valid');
	if (info != last_valid) {
		setInfo('last_valid', info)
	} // end if

	if (info == value[key.length - 1]) { return true; }

	if ( (duplicate > 0) && (duplicate < key.length - 1) )
	{
		localStorage.setItem(key[duplicate], value[key.length - 1]);
		localStorage.setItem(key[key.length - 1], value[duplicate]);
		return true;
	} // end if

	for (var i = 1; i < key.length - 1; i++)
	{
		if (value[i + 1] == '') { continue; }
		localStorage.setItem(key[i], value[i + 1]);
	} // end for

	localStorage.setItem(key[key.length - 1], info);
	return true;
} // end addInfo

function load_option(t)
{
	if ( !t || !t.match(/^[\d\w]+[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]+$/) ) { return false; }
	jQuery('#qm-validate').hide();
	jQuery('#email').val(t);
	return true;
} // end load_option

function is_email(info, focus)
{
	if ( !info || !info.match(/^[\d\w]+[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]+$/) )
	{
		if (focus == true)
		{
			jQuery('#email').focus();
			jQuery('#email').select();
		} // end if focus
		jQuery('qm-validate').show();
		return false;
	} // end if

	jQuery('qm-validate').hide();
	return true;
} // end is_email

function make_select()
{
	var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5');
	var control = '<select id="qm_select" onchange="load_option(this.value)">';
    var blank = '<option value="Select" selected>Select</option>';
	control += blank;
	var bottom = '</select>';
	var got = '', nothing = 'N/A';
	var ctr = 0;
	var info = jQuery('#email').val();
	for (var i = 1; i < id.length - 1; i++)
	{
		got = getInfo(id[i]);
		if (!got || (got == nothing)) { continue; }
		ctr++;
		var line = '<option value="' + escape(got) + '" id="' + id[i] + '">' + got + '</option>';
		control += line;
	} // end if

	i = id.length - 1;
	got = getInfo(id[i]);
	if ((ctr > 0) || (got != '' && got != 'N/A'))
	{
		var line = '<option value="' + escape(got) + '" id="' + id[i] + '">' + got + '</option>';
		control += line;
		control += bottom;
	}
	else
	{
		control = '&nbsp;';
	} // end if

	jQuery('#qm_choice').html(control);
} // make_select

function saved_addresses()
{
	var ctr = 0;
	var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5');
	if (typeof(localStorage) == 'undefined' ) { return ctr; }
	for (var i = 1; i < id.length; i++)
	{
		try 	{
			if (is_email(localStorage.getItem(id[i]), false))
			{
				ctr++;
			} // end if email
		} catch(e) {
			console.log("Error reading saved addresses");
		}
	} // end for
	return ctr;
} // end saved_addresses

function clear_qm_addresses()
{
	var id = new Array('', 'qmp1', 'qmp2', 'qmp3', 'qmp4', 'qmp5', 'last_valid');
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

jQuery(document).ready(function() {
	jQuery('#qm-validate').hide();
	jQuery('#email').bind('keypress', function(event) {
		if (event.keyCode == 13) {
			jQuery('#email').blur();
			return false;
		}
	});
	if (jQuery('#success').length) {
		jQuery('#success').fadeOut( 10000, function() {});
	}
	if (jQuery('#qm_error').length) {
		jQuery('#qm_error').fadeOut( 10000, function() {	});
	}

	jQuery('#email').blur(function() {
		if (!jQuery('#qm_row').length) { return true; }
		var info = jQuery('#email').val();
		var last_valid = getInfo('last_valid');
		if ((info == '') || (last_valid == info)) { return; }
		jQuery.get(qm_validate, { email : info }, function(data) {
			if (data == 'OK') {
				if (jQuery('#qm-validate').is(':visible') ) {
					jQuery('#qm-validate').hide();
				}
				setInfo('last_valid', info)
				if (jQuery('#subject').length > 0 && jQuery('#message').length > 0) {
					jQuery('#submit').focus();
					return true;
				}
			} else {
				jQuery('#qm-validate').show();
				return false;
			}
		} );
	});

	jQuery("#Hello").submit(function( event ) {
		if (jQuery('#qm-validate').is(':visible') ) {
			event.preventDefault();
			jQuery('#email').focus();
			jQuery('#email').select();
			return false;
		} // end if error message is visible

		if (jQuery('#qm_row').length && !addInfo()) 	{
			console.log('error saving HTML storage');
		}
	});

	var sa = saved_addresses();
	if (jQuery('#qm_saved').is(':visible'))
	{
		if (sa > 0)
		{
			var sname = (sa > 1) ? 'addresses' : 'address';
			var stext = '<button class="button" name="qmr" onclick="clear_qm_addresses()">Clear ' + sa + ' saved ' + sname + '</button>';
			jQuery('#qm_saved').html(stext);
		}
		else
		{
			jQuery('#qm_saved').hide();
		}
	} // end if

	if (jQuery('#qm_row').length) {
		if (typeof(localStorage) == 'undefined') {
			jQuery('#qm_row').hide();
			return true;
		}
		if (sa > 0) {
			var cur = localStorage.getItem('last_valid');
			if (is_email(cur)) {
				jQuery('#email').val = cur;
			}
			make_select();
			jQuery('#qm_row').show();
		} else {
			jQuery('#qm_row').hide();
		} // end if need select
	} // end if row exists
	return true;
});
