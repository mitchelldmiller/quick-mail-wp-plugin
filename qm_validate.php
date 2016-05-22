<?php
// qm_validate.php 1.0.0

require_once 'qm_util.php';

/**
 * do not send content for invalid request
 *
 * @since 1.0.0
 */
function qm_bye() {
	header('HTTP/1.0 204 No Content');
	header('Content-Length: 0',true);
	header('Content-Type: text/html',true);
	flush();
} // end qm_bye

	$ok = false;
	foreach ($_COOKIE as $key => $val) {
		if (is_string(strstr($key, 'wordpress_logged_in'))) {
			$ok = true;
			break;
		}
	} // end foreach

	if ($ok == false) {
		qm_bye(); // failed cookie test
		exit;
	} // end if not logged in


	if ( !empty( $_REQUEST['email'] ) && !empty( $_REQUEST['quick-mail-verify'] ) ) {
		header('Content-type: text/plain');
		if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['email'], $_REQUEST['quick-mail-verify'] ) ) {
			echo 'OK';
		} else {
			echo 'error';
		}
	} else  {
		qm_bye();
	} // end if
	exit;
?>