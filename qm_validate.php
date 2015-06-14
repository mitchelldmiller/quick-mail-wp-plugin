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

	if (isset($_REQUEST['email'])) {
		$email = urldecode( $_REQUEST['email'] );
		header('Content-type: text/plain');
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) || ! QuickMailUtil::qm_valid_email_domain( $email ) ) {
			echo 'error';
		} else {
			echo 'OK';
		}
	} else  {
		qm_bye();
	} // end if
	exit;
?>