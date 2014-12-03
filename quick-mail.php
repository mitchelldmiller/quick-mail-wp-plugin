<?php
/*
Plugin Name: Quick Mail
Description: send email with attachment from WordPress
Version: 1.0.2
Author: Mitchell D. Miller
Author URI: http://mitchelldmiller.com/
Plugin URI: http://wheredidmybraingo.com/how-to-send-email-from-wordpress-admin/
Text Domain: quick-mail
Domain Path: /lang
*/

function quick_mail_form()
{
	$success = '';
	$error = '';
	$to = '';
	$subject = '';
	$message = '';
	$no_uploads = '';
	$file = '';
	$attachments = array();
	$you = wp_get_current_user();
	$from = "From: \"{$you->user_firstname} {$you->user_lastname}\" <{$you->user_email}>";
	if ( 'GET' == $_SERVER['REQUEST_METHOD'] && empty( $_GET['quick-mail-uploads'] ) ) {
		$can_upload = strtolower( ini_get( 'file_uploads' ) );
		if ( '1' != $can_upload && 'true' != $can_upload && 'on' != $can_upload ) {
			$no_uploads = __( 'Uploads are disabled', 'quick-mail' );
		}
	}
	if ( empty( $you->user_firstname ) || empty( $you->user_lastname ) || empty( $you->user_email ) ) {
		$error = '<a href="/wp-admin/profile.php">' . __( 'Error: Incomplete User Profile', 'quick-mail' ) . '</a>';
	}
	elseif ( ! empty( $_POST['quick-mail'] ) ) {
		if ( ! wp_verify_nonce( $_POST['quick-mail'], 'quick-mail' ) ) {
			wp_die( '<h2>' . __( 'System error', 'quick-mail' ). '</h2>' );
		}
		$to = sanitize_email( $_POST['email'] );
		$subject = sanitize_text_field( htmlspecialchars_decode( stripslashes( $_POST['subject'] ) ) );
		$message = sanitize_text_field( htmlspecialchars_decode( stripslashes( $_POST['message'] ) ) );
		if ( ! empty( $_FILES['attachment'] ) ) {
			if ( ( 0 == $_FILES['attachment']['error'] ) && ( 0 < $_FILES['attachment']['size'] ) ) {
				$temp = ini_get('upload_tmp_dir');
				if ( empty( $temp) ) {
					if ( is_dir( DIRECTORY_SEPARATOR . 'tmp' )) {
						$temp = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR; // Linux, Unix, MacOs
					}
					elseif ( false !== getenv( 'TEMP' )) {
						$temp = getenv( 'TEMP' ); // Windows
					}
					elseif ( false !== getenv( 'TMP' )) {
						$temp = getenv( 'TMP' ); // Windows
					}
					else {
						$error = __( 'Missing temporary directory', 'quick-mail' );
					}
				}
				if ( empty( $error ) ) {
					$file = "{$temp}{$_FILES['attachment']['name']}";
					if ( move_uploaded_file( $_FILES['attachment']['tmp_name'], $file ) ) {
						array_push( $attachments, $file );
					}
					else {
						$error = __( 'Error moving file to', 'quick-mail' ) . " : {$file}";
					}
				}
			}
			elseif ( 4 != $_FILES['attachment']['error'] ) {
				$error = __( 'File Upload Error', 'quick-mail' ); // Error 4 = No file was uploaded
			}
		} // end if has attachment
		if ( empty( $error ) ) {
			if ( ! wp_mail( $to, $subject, $message, $from, $attachments ) ) {
				$error = __( 'Error sending mail', 'quick-mail' );
			}
			else {
				$success = __( 'Message Sent', 'quick-mail' );
			}
			if ( ! empty( $file ) ) {
				$e = '<br>' . __( 'Error Deleting Upload', 'quick-mail' );
				if ( ! unlink( $file ) ) {
					if ( empty( $error ) ) {
						$success .= $e;
					}
					else {
						$error .= $e;
					}
				} // end if unlink error
			} // end if file uploaded
		} // end if no error
	} // end if form submitted
	ob_start();
?>
	<h2 class="quick-mail-title"><?php _e( 'Quick Mail', 'quick-mail' ); ?></h2>
		<?php if ( ! empty( $no_uploads ) ) : ?>
			<div class="update-nag"><p><?php echo $no_uploads; ?></p></div>
		<?php elseif ( ! empty( $success ) ) : ?>
			<div class="updated"><p><?php echo $success; ?></p></div>
		<?php elseif ( ! empty( $error ) ) : ?>
			<div class="error"><p><?php echo $error; ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $you->user_firstname ) && ! empty( $you->user_lastname ) && ! empty( $you->user_email ) ) : ?>
			<form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<?php wp_nonce_field( 'quick-mail', 'quick-mail', false, true ); ?>
				<?php if ( ! empty( $no_uploads ) || ! empty( $_POST['quick-mail-uploads'] ) ) : ?>
				<input type="hidden" name="quick-mail-uploads" value="No">
				<?php endif; ?>
				<table id="quick-mail" class="form-table">
					<tr>
						<td class="quick-mail"><?php _e( 'From', 'quick-mail' ); ?>:</td>
						<td><?php echo htmlspecialchars( substr( $from, 6 ), ENT_QUOTES ); ?></td>
					</tr>
					<tr>
						<td class="quick-mail"><?php _e( 'To', 'quick-mail' ); ?>:</td>
						<td><input value="<?php echo $to?>" name="email" type="email" required size="35" placeholder="<?php _e( 'Enter mail address', 'quick-mail' ); ?>" tabindex="1" autofocus></td>
					</tr>
					<tr>
						<td class="quick-mail"><?php _e( 'Subject', 'quick-mail' ); ?>:</td>
						<td><input value="<?php echo htmlspecialchars( $subject, ENT_QUOTES ); ?>" name="subject" type="text" required size="35" placeholder="<?php _e( 'Subject', 'quick-mail' ); ?>" tabindex="2"></td>
					</tr>
					<?php if ( empty( $no_uploads ) && empty( $_POST['quick-mail-uploads'] ) ) : ?>
					<tr>
						<td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
						<td><input name="attachment" type="file" tabindex="3"></td>
					</tr>
					<?php endif; ?>
					<tr>
						<td class="quick-mail-message"><?php _e( 'Message', 'quick-mail' ); ?>:</td>
						<td><textarea name="message" placeholder="<?php _e( 'Enter your message', 'quick-mail' ); ?>" required rows="4" cols="35" tabindex="4"><?php echo htmlspecialchars( $message, ENT_QUOTES ); ?></textarea></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td class="submit"><input type="submit" name="submit" title="<?php _e( 'Send Mail', 'quick-mail' ); ?>" tabindex="5" value="<?php _e( 'Send Mail', 'quick-mail' ); ?>"></td>
					</tr>
				</table>
			</form>
		<?php endif; ?>
<?php
		echo ob_get_clean();
} // end quick_mail_form

function init_quick_mail() {
	wp_register_style( 'quick-mail', plugins_url( 'quick-mail.css', __FILE__ ) );
} // end init_quick_mail

function init_quick_mail_menu() {
	$title = __( 'Quick Mail', 'quick-mail' );
	$page = add_submenu_page( 'tools.php', $title, $title, apply_filters( 'quick_mail_user_capability', 'publish_posts' ), 'quick_mail_form', 'quick_mail_form' );
	add_action( 'admin_print_styles-' . $page, 'init_quick_mail_style' );
} // end init_quick_mail_menu

function init_quick_mail_style() {
	wp_enqueue_style( 'quick-mail' );
} // end init_quick_mail_style

function init_quick_mail_translation() {
	load_plugin_textdomain( 'quick-mail', '', dirname( __FILE__ ) . '/lang' );
} // end init_quick_mail_translation

add_action( 'admin_init', 'init_quick_mail' );
add_action( 'admin_menu', 'init_quick_mail_menu' );
add_action( 'plugins_loaded', 'init_quick_mail_translation' );
?>