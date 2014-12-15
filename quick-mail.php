<?php
/*
Plugin Name: Quick Mail
Description: send email with attachment from WordPress
Version: 1.1.0
Author: Mitchell D. Miller
Author URI: http://wheredidmybraingo.com/
Plugin URI: http://wheredidmybraingo.com/wordpress-plugin-quick-mail-send-email-from-wordpress/
Text Domain: quick-mail
Domain Path: /lang
*/

/**
 * create and display recipient input. user list or text input.
 *
 * @param string $to recipient email
 * @param int $id user ID
 * @return void displays input
 */
function quick_mail_recipient_input($to, $id) {
	$template = '<input value="%s" name="email" type="email" required size="35" placeholder="%s" tabindex="1" autofocus>';
	$option = qm_get_option('show_quick_mail_users', 'X');
	if ($option != 'X') {
		// check if site permissions were changed
		if ( 'Y' != get_option( 'editors_quick_mail_privilege', 'N' ) ) {
			// only admins can see list
			if ( ! qm_is_admin( $id ) ) {
				$option = 'X';
			}
		} // end admin check
	} // end if wants user list

	if ($option != 'A' && $option != 'N') {
		echo sprintf($template, $to, __( 'Enter mail address', 'quick-mail' ) );
		return;
	}
	$args = ($option == 'A') ?
	array('orderby' => 'user_nicename', 'count_total' => true) :
	array('count_total' => true);
	$hide_admin = get_option('hide_quick_mail_admin', 'Y');
	$user_query = new WP_User_Query($args);
	$users = array();
	foreach ( $user_query->results as $user ) {
		if ( 'Y' == $hide_admin ) {
			$meta = get_user_meta( $user->ID );
			$cap = unserialize( $meta['wp_capabilities'][0] );
			if ( isset($cap['administrator']) ) {
				continue;
			}
		} // end admin test

		if ($option == 'A') {
			$users[] = ucfirst("{$user->user_nicename}\t{$user->user_email}");
		} // end if all users
		else {
			$last = ucfirst(get_user_meta( $user->ID, 'last_name', true ));
			$first = get_user_meta( $user->ID, 'first_name', true );
			if ( ! empty($first) && ! empty($last) && ! empty($user->user_email) ) {
				$users[] = "{$last}\t{$first}\t{$user->ID}\t{$user->user_email}";
			} // end if valid name
		} // end else named only
	} // end for

	$j = count($users);
	if (1 > $j) {
		echo sprintf($template, $to, __( 'Enter mail address', 'quick-mail' ) );
		return;
	} // end if no matches

	sort($users);
	$letter = '';
	ob_start();
	echo '<select name="email" required>';
	for ( $i = 0; $i < $j; $i++ ) {
		$row = explode( "\t", $users[$i] );
		if ($option == 'A') 	{
			$address = urlencode("\"{$row[0]}\" <{$row[1]}>");
		}
		else {
			$address = urlencode("\"{$row[1]} {$row[0]}\" <{$row[3]}>");
		} // end if

		if ( $letter != $row[0][0] ) {
			if ( ! empty($letter) ) {
				echo '</optgroup>';
			} // end if not first letter group
			$letter = $row[0][0];
			echo "<optgroup label='{$letter}'>";
		} // end if first letter changed

		if ( 'A' == $option ) {
			$selected = ($row[1] != $to) ? ' ' : ' selected ';
			echo "<option{$selected}value='{$address}'>{$row[0]}</option>";
		}
		else {
			$selected = ($row[3] != $to) ? ' ' : ' selected ';
			echo "<option{$selected}value='{$address}'>{$row[1]} {$row[0]}</option>";
		}
	} // end for
	echo '</optgroup></select>';
	return ob_get_clean();
} // end quick_mail_recipient_input

/**
 * display data entry form to enter recipient, subject, message
 *
 */
function quick_mail_form() {
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
		if ( ! wp_verify_nonce( $_POST['quick-mail'], 'quick-mail' ) || empty( $_POST['email'] ) ) {
			wp_die( '<h2>' . __( 'System error', 'quick-mail' ). '</h2>' );
		}

		if ( preg_match('/<(.+@.+[.].+)>/', urldecode($_POST['email']), $raw_email) ) {
			$to = $raw_email[1];
		} else {
			$to = sanitize_email( urldecode( $_POST['email'] ) );
			if ( empty( $to ) || ! is_email($to) || ! qm_valid_email_domain( $to ) ) {
				$error = __( 'Invalid mail address', 'quick-mail' );
			}
		}

		$subject = sanitize_text_field( htmlspecialchars_decode( stripslashes( $_POST['subject'] ) ) );
		$message = sanitize_text_field( htmlspecialchars_decode( stripslashes( $_POST['message'] ) ) );
		if ( empty( $error ) ) {
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
		} // end if valid email address

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
						<td><?php echo quick_mail_recipient_input( $to, $you->ID ); ?></td>
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

/**
 * display form to edit plugin options
 *
 */
function quick_mail_options() {
	global $wpdb;
	$updated = false;
	if ( ! empty($_POST['show_quick_mail_users']) && 1 == strlen($_POST['show_quick_mail_users']) ) {
		$previous = qm_get_option( 'show_quick_mail_users', 'X' );
		if ( $previous != $_POST['show_quick_mail_users'] ) {
			qm_update_option( 'show_quick_mail_users', $_POST['show_quick_mail_users'] );
			echo '<div class="updated">', _e('Option Updated', 'quick-mail'), '</div>';
			$updated = true;
		}
	}
	if ( ! empty($_POST['showing_quick_mail_admin']) ) {
		$previous = get_option( 'hide_quick_mail_admin', 'Y' );
		$current = empty($_POST['hide_quick_mail_admin']) ? 'N' : 'Y';
		if ($current != $previous) {
			update_option('hide_quick_mail_admin', $current);
			if ( ! $updated ) {
				echo '<div class="updated">', _e('Option Updated', 'quick-mail'), '</div>';
				$updated = true;
			}
		}

		$previous = get_option( 'editors_quick_mail_privilege', 'N' );
		$current = empty($_POST['editors_quick_mail_privilege']) ? 'N' : 'Y';
		if ($current != $previous) {
			update_option('editors_quick_mail_privilege', $current);
			if ( ! $updated ) {
				echo '<div class="updated">', _e('Option Updated', 'quick-mail'), '</div>';
			}
		}
	} // end if admin

	$user_query = new WP_User_Query( array('count_total' => true) );
	$hide_admin = get_option('hide_quick_mail_admin', 'Y');
	$total = 0; // $user_query->get_total() is wrong, if we are skipping admins
	$names = 0;
	foreach ( $user_query->results as $user ) {
		if ( 'Y' == $hide_admin && qm_is_admin( $user->ID ) ) {
			continue;
		} // end admin test

		$total++;
		$last = get_user_meta( $user->ID, 'last_name', true );
		$first = get_user_meta( $user->ID, 'first_name', true );
		if ( ! empty($first) && ! empty($last) ) {
			$names++;
		} // end if
	} // end for

	$check_all = ('A' == qm_get_option('show_quick_mail_users', 'X') ) ? 'checked="checked"' : '';
	$check_names = ('N' == qm_get_option('show_quick_mail_users', 'X') ) ? 'checked="checked"' : '';
	$check_none = ('X' == qm_get_option('show_quick_mail_users', 'X') ) ? 'checked="checked"' : '';
	$check_admin = ('Y' == get_option('hide_quick_mail_admin', 'Y') ) ? 'checked="checked"' : '';
	$check_editor = ('Y' == get_option('editors_quick_mail_privilege', 'N') ) ? 'checked="checked"' : '';
	$quick_mail_style = dirname( __FILE__ ) . '/quick-mail.css';
	if (file_exists($quick_mail_style)) {
		echo '<style>', file_get_contents($quick_mail_style), '</style>';
	}
?>
<h2 class="quick-mail-title"><?php _e( 'Quick Mail Options', 'quick-mail' ); ?></h2>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<table class="form-table">
<tr>
<tr><th class="recipients"><?php _e( 'User Display', 'quick-mail' ); ?></th>
</tr>
<tr><td><label><input name="show_quick_mail_users" type="radio" value="A" <?php echo $check_all; ?>>
<?php _e( 'Show All Users', 'quick-mail' ); echo " ({$total})"; ?>
<br><?php _e( 'Show all users sorted by nickname.', 'quick-mail' );
echo ' ', $total, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
</tr>
<tr><td><label><input name="show_quick_mail_users" type="radio" value="N" <?php echo $check_names; ?>>
<?php _e( 'Show Users with Names', 'quick-mail' ); echo " ({$names})"; ?>
<br><?php _e( 'Show users with names, sorted by last name.', 'quick-mail' );
echo ' ', $names, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
</tr>
<tr>
<td><label><input name="show_quick_mail_users" type="radio" value="X" <?php echo $check_none; ?>>
<?php _e( 'Do Not Show Users', 'quick-mail' ); ?>
<br><?php _e( 'Enter address to send mail.', 'quick-mail' );
?>
</label></td>
</tr>
<?php  if ( current_user_can('list_users') ) : ?>
<tr>
<tr><th class="recipients"><?php _e( 'Administration', 'quick-mail' ); ?></th></tr>
<tr><td><label><input name="hide_quick_mail_admin" type="checkbox" <?php echo $check_admin; ?>>
<?php _e( 'Hide Administrator profiles', 'quick-mail' ); ?>
<br><?php _e( 'User list will not include administrator profiles.', 'quick-mail' ); ?>
</label><input name="showing_quick_mail_admin" type="hidden" value="Y"></td>
</tr>
<tr><td><label><input name="editors_quick_mail_privilege" type="checkbox" <?php echo $check_editor; ?>>
<?php _e( 'Grant Editors access to user list', 'quick-mail' ); ?>
<br><?php _e( 'Modify permission to let editors see user list.', 'quick-mail' ); ?>
</label></td>
</tr>
<?php endif; ?>
<tr><td><input type="submit" name="submit" class="button button-primary" value="<?php _e( 'Save Options', 'quick-mail' ); ?>" >
</td></tr></table></form>
<?php
} // end quick_mail_options

/**
 * validate email domain
 * @param string $qm_address
 * @return boolean whether user entered a valid domain
 */
function qm_valid_email_domain( $qm_address ) {
	$a_split = explode( '@', $qm_address );
	if ( ! is_array( $a_split ) || empty( $a_split[1] ) ) {
		return false;
	} // wp checks for this, but must be certain

	$a_record = dns_get_record( $a_split[1], DNS_MX );
	if ( ! is_array( $a_record ) || ! isset( $a_record[0]['pri'] ) ) {
		return false;
	} // end if invalid domain

	$result = false;
	$j = count( $a_record );
	for ( $i = 0; ( $i < $j ) && ( $result == false ); $i++ ) {
		$result = ($a_record[$i]['pri'] > 0) || ( $a_record[$i]['host'] == $a_record[$i]['target'] );
	} // end check for a valid mail server

	return $result;
} // end qm_valid_email_domain

/**
 * get user option. return default if not found
 *
 * @param string $key Option name
 * @param string $qm_default Default value
 * @return string Option value or default
 */
function qm_get_option($key, $qm_default) {
	global $current_user;
	$value = get_user_meta($current_user->ID, $key, true);
	return ( ! empty($value) ) ? $value : $qm_default;
} // end qm_get_option

/**
 * update user option
 *
 * @param string $key
 * @param string $value
 */
function qm_update_option($key, $value) {
	global $current_user;
	update_user_meta( $current_user->ID, $key, $value );
} // end qm_update_option

/**
 * Is user an administrator?
 *
 * @param int $id User ID.
 */
function qm_is_admin($id) {
	$meta = get_user_meta($id);
	$cap = unserialize( $meta['wp_capabilities'][0] );
	return isset( $cap['administrator'] );
} // end qm_is_admin

/**
 * used with quick_mail_setup_capability filter, to let editors see user list
 *
 */
function let_editor_set_quick_mail_option($role) {
	return 'edit_others_posts';
} // end let_editor_set_quick_mail_option

/**
 * init admin page. add css.
 */
function init_quick_mail() {
	wp_register_style( 'quick-mail', plugins_url( 'quick-mail.css', __FILE__ ) );
} // end init_quick_mail

/**
 * init admin menu for appropriate users
 */
function init_quick_mail_menu() {
	$title = __( 'Quick Mail', 'quick-mail' );
	$page = add_submenu_page( 'tools.php', $title, $title,
	apply_filters( 'quick_mail_user_capability', 'publish_posts' ), 'quick_mail_form', 'quick_mail_form' );
	add_action( 'admin_print_styles-' . $page, 'init_quick_mail_style' );
	add_options_page( 'Quick Mail Options', $title, apply_filters( 'quick_mail_setup_capability', 'list_users' ), 'quick_mail_options', 'quick_mail_options' );
} // end init_quick_mail_menu

/**
 * use by admin print styles to add css to admin
 */
function init_quick_mail_style() {
	wp_enqueue_style( 'quick-mail' );
} // end init_quick_mail_style

/**
 * load translations
 */
function init_quick_mail_translation() {
	load_plugin_textdomain( 'quick-mail', '', dirname( __FILE__ ) . '/lang' );
} // end init_quick_mail_translation

add_action( 'admin_init', 'init_quick_mail' );
add_action( 'admin_menu', 'init_quick_mail_menu' );
add_action( 'plugins_loaded', 'init_quick_mail_translation' );
if ( 'Y' == get_option( 'editors_quick_mail_privilege', 'N' ) ) {
	add_filter('quick_mail_setup_capability', 'let_editor_set_quick_mail_option');
}
?>