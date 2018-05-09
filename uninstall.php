<?php
// uninstall.php 1.00 5-9-18

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_metadata( 'user', 1, 'show_quick_mail_users', '', true );
delete_metadata( 'user', 1, 'show_quick_mail_commenters', '', true );
delete_metadata( 'user', 1, 'limit_quick_mail_commenters', '', true );
delete_metadata( 'user', 1, 'want_quick_mail_privacy', '', true );
delete_metadata( 'user', 1, 'save_quick_mail_addresses', '', true );
if ( is_multisite() ) {
	$sites = get_sites();
	foreach ( $sites as $site ) {
		delete_blog_option( $site->blog_id, 'show_quick_mail_users' );
		delete_blog_option( $site->blog_id, 'hide_quick_mail_admin' );
		delete_blog_option( $site->blog_id, 'editors_quick_mail_privilege' );
		delete_blog_option( $site->blog_id, 'authors_quick_mail_privilege' );
		delete_blog_option( $site->blog_id, 'quick_mail_cannot_reply' );
		delete_blog_option( $site->blog_id, 'verify_quick_mail_addresses' );
		delete_blog_option( $site->blog_id, 'replace_quick_mail_sender' );
	} // end foreach
} else {
	delete_option( 'show_quick_mail_users' );
	delete_option( 'hide_quick_mail_admin' );
	delete_option( 'editors_quick_mail_privilege' );
	delete_option( 'authors_quick_mail_privilege' );
	delete_option( 'quick_mail_cannot_reply' );
	delete_option( 'verify_quick_mail_addresses' );
	delete_option( 'replace_quick_mail_sender' );
} // end if multisite

?>