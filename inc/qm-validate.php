<?php
/**
 * Validate email addresses. Check for duplicates while user is entering data.
 *
 * @package QuickMail
 * @version 3.5.4
 */

require_once 'class-quickmailutil.php';

// Removed check for login cookie. 3.5.4

$verify = ! empty( $_REQUEST['quick-mail-verify'] ) ? trim( $_REQUEST['quick-mail-verify'] ) : '';
if ( empty( $verify ) ) {
	QuickMailUtil::qm_bye(); // Failed input test.
	exit();
} // end if not logged in or missing verify.

header( 'Content-type: text/plain' );
$to      = isset( $_REQUEST['email'] ) ? strtolower( $_REQUEST['email'] ) : '';
$message = '';
if ( ! empty( $_REQUEST['one'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['one'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = $_REQUEST['one'];
	}
} // end if validating one domain
if ( ! empty( $_REQUEST['filter'] ) && ! isset( $_REQUEST['to'] ) ) {
	if ( QuickMailUtil::qm_valid_email_domain( $_REQUEST['filter'], $verify ) ) {
		$message = 'OK';
	} else {
		$message = '  ' . $_REQUEST['filter'];
	} // end if valid
	echo $message;
} elseif ( ! empty( $_REQUEST['filter'] ) && isset( $_REQUEST['to'] ) ) {
	echo QuickMailUtil::filter_email_input( $_REQUEST['to'], $_REQUEST['filter'], $_REQUEST['quick-mail-verify'] );
} else {
	if ( ! empty( $_REQUEST['dup'] ) ) {
		$all_cc = array_unique( explode( ',', strtolower( $_REQUEST['dup'] ) ) );
		if ( empty( $to ) ) {
			$message = 'OK';
		} elseif ( in_array( $to, $all_cc, true ) ) {
			$message = " {$to}";
		} // end if new recipient is a duplicate
	} // end duplicate test

	if ( empty( $message ) && ! QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
		$message = $to;
	} // end if invalid email
	echo empty( $message ) ? 'OK' : $message;
} // end else not filter

