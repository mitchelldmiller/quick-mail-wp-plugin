<?php
// qm_validate.php 2.0.4

require_once 'qm_util.php';

/**
 * do not send content for invalid request
 * @since 1.0.0
 */
function qm_bye() {
	header('HTTP/1.0 204 No Content');
	header('Content-Length: 0', true);
	header('Content-Type: text/html', true);
	flush();
} // end qm_bye

// check for login cookie
$logged_in = false;
foreach ($_COOKIE as $k => $v) {
	if ( preg_match( "/wordpress_logged_in/", $k ) ) {
		$logged_in = true;
		break;
	} // end if matched login cookie
} // end foreach

$verify = !empty($_REQUEST['quick-mail-verify']) ? trim( $_REQUEST['quick-mail-verify'] ) : '';
if ( !$logged_in || empty( $verify ) ) {
	qm_bye(); // failed cookie or input test
	exit();
} // end if not logged in or missing verify

header('Content-type: text/plain');
$to = isset( $_REQUEST['email'] ) ? strtolower( $_REQUEST['email'] ) : '';
$message = '';
if ( !empty( $_REQUEST['one'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['one'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = $_REQUEST['one'];
	}
} // end if validating one domain
if ( !empty( $_REQUEST['filter'] ) && !isset( $_REQUEST['to'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['filter'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = '  ' . $_REQUEST['filter'];
	} // end if valid
	echo $message;
} elseif ( !empty( $_REQUEST['filter'] ) && isset( $_REQUEST['to'] ) ) {
	echo QuickMailUtil::filter_email_input($_REQUEST['to'], $_REQUEST['filter'], $_REQUEST['quick-mail-verify'] );
} else {
	if (!empty($_REQUEST['dup'])) {
		$all_cc = array_unique( explode( ',', strtolower($_REQUEST['dup']) ) );
		if ( empty( $to ) ) {
			$message = 'OK';
		} elseif ( in_array( $to, $all_cc ) ) {
			$message = " {$to}";
		} // end if new recipient is a duplicate
	} // end duplicate test

	if ( empty( $message ) && !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
		$message = $to;
	} // end if invalid email
	echo empty( $message ) ? 'OK' : $message;
} // end else not filter
?>