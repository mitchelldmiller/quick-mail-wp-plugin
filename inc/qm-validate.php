<?php
/**
 * Validate email addresses. Check for duplicates while user is entering data.
 *
 * @package QuickMail
 * @version 3.5.5
 */

require_once 'class-quickmailutil.php';

$verify = ! empty( $_REQUEST['quick-mail-verify'] ) ? trim( $_REQUEST['quick-mail-verify'] ) : '';
if ( empty( $verify ) ) {
	QuickMailUtil::qm_bye(); // Failed input test.
	exit();
} // end if not logged in or missing verify.

header( 'Content-type: text/plain' );
if ( isset( $_REQUEST['filter'] ) && isset( $_REQUEST['to'] ) ) {
	if (empty($_REQUEST['filter']) && empty($_REQUEST['to'])) {
		echo 'OK';
		exit;
	} // uhoh. should not be called with empty values.

	if (empty($_REQUEST['filter']) && !strstr($_REQUEST['to'], ' ') && !strstr($_REQUEST['to'], ',')) {
		echo QuickMailUtil::qm_valid_email_domain( $_REQUEST['to'], $verify ) ? '' : " {$_REQUEST['to']}"; // space = invalid
	} else {
		echo QuickMailUtil::filter_email_input( $_REQUEST['to'], $_REQUEST['filter'], $verify );
	}
	exit;
}
$message = '';
$to      = isset( $_REQUEST['email'] ) ? strtolower( trim( $_REQUEST['email'] ) ) : '';
if ( empty( $_REQUEST['dup'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
		$message = 'OK';
	} else {
		$message = $to;
	} // end if valid address
	echo $message;
	exit;
} // end if not testing for duplicate 3.5.5

// test one address. good for contact plugin.
if ( ! empty( $_REQUEST['one'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['one'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = $_REQUEST['one'];
	}
} // end if validating one domain

if ( ! empty( $_REQUEST['filter'] ) && empty( $_REQUEST['to'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['filter'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = '  ' . $_REQUEST['filter'];
	} // end if valid
	echo $message;
} else {
if ( ! empty( $_REQUEST['dup'] ) ) {
		echo QuickMailUtil::filter_email_input( $_REQUEST['email'], $_REQUEST['dup'], $_REQUEST['quick-mail-verify'] );
		exit;
	} elseif ( empty( $message ) && ! QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
		$message = $to;
	} // end if invalid email
	echo empty( $message ) ? 'OK' : $message;
} // end else not filter

