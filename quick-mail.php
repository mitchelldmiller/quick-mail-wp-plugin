<?php
/*
Plugin Name: Quick Mail
Description: Adds Quick Mail to Tools menu. Send email with an attachment using a list of users or enter a name.
Version: 1.3.0
Author: Mitchell D. Miller
Author URI: http://mitchelldmiller.com/
Plugin URI: http://wheredidmybraingo.com/quick-mail-1-3-0-supports-international-mail/
Text Domain: quick-mail
Domain Path: /lang
*/

require_once 'qm_util.php';

class QuickMail {

   /**
    * Content type for our instance.
    *
    * @since 1.2.0
    * @var string (text|html)
    */
   public $content_type = 'text/html';

   /**
    * Static property for our instance.
    *
    * @since 1.0.0
    * @var (boolean|object) $instance Description.
    */
   public static $instance = false;

   /**
    * Our dismissed pointer name
    * @var string
    * @since 1.3.0
    */
   public static $pointer_name = 'quickmail_130';

   /**
    * Returns an instance.
    *
    * If an instance exists, return it.  If not, create one and return it.
    *
    * @since 1.0.0
    *
    * @return object instance of class
    */
   public static function get_instance()
   {
      if ( ! self::$instance )
      {
         self::$instance = new self;
      }
      return self::$instance;
   } // end get_instance

   /**
    * Does site have more than one user? Replaces get_user_count()
    *
    * @return bool more than one user
    *
    * @since 1.2.5
    */
   public function multiple_users()
   {
      global $wpdb;
      $sql = "select count(ID) as USERS from {$wpdb->users} where user_email != ''";
      return 1 < intval( $wpdb->get_var( $sql ) );
   } // end multiple_users

   /**
    * content type filter for wp_mail
    *
    * filters wp_mail_content_type
    *
    * @see wp_mail
    * @param string $type
    * @return string
    */
   public function set_mail_content_type($type)
   {
      return $this->content_type;
   } // end set_mail_content_type

   /**
    * create object. add actions.
    *
    * @since 1.2.0
    */
   public function __construct() {
      /**
       * if not called by WordPress, exit without error message
       * @since 1.2.5
       */
      if ( ! function_exists( 'register_activation_hook' ) ) {
         exit;
      }
      register_activation_hook( __FILE__, array($this, 'check_wp_version') );
      add_action( 'admin_init', array($this, 'add_email_script' ) );
      add_action( 'admin_menu', array($this, 'init_quick_mail_menu' ) );
      add_action( 'plugins_loaded', array($this, 'init_quick_mail_translation' ) );
      add_action( 'activated_plugin', array($this, 'install_quick_mail'), 10, 0 );
      add_action( 'deactivated_plugin', array($this, 'unload_quick_mail_plugin'), 10, 0 );
      add_filter( 'wp_mail_content_type', array($this, 'set_mail_content_type') );
      add_filter( 'plugin_row_meta', array($this, 'qm_plugin_links'), 10, 2 );
      add_filter( 'wp_mail_failed', array($this, 'show_mail_failure'), 99, 1 );
      if ( 'Y' == get_option( 'editors_quick_mail_privilege', 'N' ) ) {
         add_filter('quick_mail_setup_capability', array($this, 'let_editor_set_quick_mail_option') );
      }

      add_action( 'plugins_loaded', array($this, 'show_qm_pointer') );
   } // end constructor

   /**
    * optionally display dismissible wp_pointer with setup reminder.
    * cannot be loaded in constructor because user info is not available until plugins_loaded.
    *
    * @since 1.3.0
    */
   public function show_qm_pointer() {
      $dismissed = array_filter( explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) );
      if ( ! in_array( self::$pointer_name, $dismissed ) ) {
         add_action( 'admin_enqueue_scripts', array($this, 'qm_pointer_setup') );
      }
   }

   /**
    * displays wp_mail error message
    *
    * @param WP_Error $e
    * @since 1.3.0
    */
   public function show_mail_failure($e) {
      if (is_wp_error($e)) {
         $direction = is_rtl() ? 'rtl' : 'ltr';
         $args = array( 'response' => 200, 'back_link' => true, 'text_direction' => $direction );
         wp_die( sprintf( '<h3>%s</h3>', $e->get_error_message() ), __( 'Mail Error', 'quick-mail' ), $args );
      }
   }

   /**
    * Check for minimum WordPress version before installation.
    * Note: `quick_mail_version` filter was removed in 1.3.0
    *
    * @link http://wheredidmybraingo.com/quick-mail-1-3-0-supports-international-mail/#minversion
    *
    * @since 1.2.3
    */
   public function check_wp_version()
   {
      global $wp_version;
      if ( version_compare( $wp_version, '4.4', 'lt' ) )
      {
         deactivate_plugins( basename( __FILE__ ) );
         wp_die('<span style="font-size:120%">'.
               __( 'Quick Mail requires WordPress 4.4 or greater.', 'quick-mail' ) . '</span><br><br>' . __('Please upgrade WordPress to use Quick Mail.', 'quick-mail'), __('Quick Mail Cannot Be Installed', 'quick-mail' ),  array( 'response' => 200, 'back_link' => true ) );
      } // end if
   } // end check_wp_version

   /**
    * add options when Quick Mail is activated
    *
    * add options, do not autoload them.
    *
    * @since 1.2.0
    */
   public function install_quick_mail() {
      add_option( 'hide_quick_mail_admin', 'N', '', 'no' );
      add_option( 'editors_quick_mail_privilege', 'N', '', 'no'  );
      add_option( 'verify_quick_mail_addresses', 'N', '', 'no'  );
      /**
       * Do not show users if one user
       * since 1.2.4
       */
      $code = $this->multiple_users() ? 'A' : 'X';
      $this->qm_update_option( 'show_quick_mail_users', $code );
   } // install_quick_mail

   /**
    * load scripts to display wp_pointer after installation
    *
    * @since 1.3.0
    */
   public function quick_mail_pointer_scripts() {
      $greeting = __( 'Welcome to Quick Mail', 'quick-mail' );
      $suggestion = __( 'Please verify your settings before using Quick Mail.', 'quick-mail' );
     $pointer_content = "<h3>{$greeting}</h3><p>{$suggestion}</p>";
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function() {
    jQuery('#menu-settings').pointer({
      content: '<?php echo $pointer_content; ?>',
      position:		{
         edge: 'left', // arrow direction
         align: 'center' // vertical alignment
      },
     pointerWidth: 350,
     close:	 function() {
         jQuery.post( ajaxurl, {
               pointer: '<?php echo self::$pointer_name; ?>',
               action: 'dismiss-wp-pointer'
         });
      }
}).pointer('open');
});
//]]>
</script>
<?php
}
   /**
    * setup wp_pointer for new installations
    *
    * @since 1.3.0
    */
   public function qm_pointer_setup() {
      wp_enqueue_style ( 'wp-pointer' );
      wp_enqueue_script ( 'wp-pointer' );
      add_action ( 'admin_print_footer_scripts', array (
            $this,
            'quick_mail_pointer_scripts'
      ) );
   }

   /**
    * delete options when Quick Mail is deactivated
    *
    * delete global and user options
    *
    * @global object $wpdb delete user options
    *
    * @since 1.1.1
    */
   public function unload_quick_mail_plugin() {
      global $wpdb;
      $sql = "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'show_quick_mail_users'";
      $wpdb->query( $sql );
      delete_option( 'show_quick_mail_users' );
      delete_option( 'hide_quick_mail_admin' );
      delete_option( 'editors_quick_mail_privilege' );
      delete_option( 'verify_quick_mail_addresses' );
   } // end unload_quick_mail_plugin

   /**
    * load quick-mail.js for email select
    *
    * quick-mail.js displays a select with up to
    * five recent email addresses, from local storage
    *
    * @since 1.2.0
    */
   public function add_email_script()
   {
      wp_enqueue_script( 'qmScript', plugins_url('/quick-mail.js', __FILE__), array('jquery'), null, false );
   } // end add_email_script

   /**
    * create and display recipient input. user list or text input.
    *
    * @param string $to recipient email
    * @param int $id user ID
    * @return void displays input
    */
   public function quick_mail_recipient_input( $to, $id ) {
      $template = '<input value="%s" id="email" name="email" type="email" required size="35" placeholder="%s" tabindex="1" autofocus>';
      $option = $this->qm_get_option( 'show_quick_mail_users', 'A' );
      if ( 'X' != $option ) {
         // check if site permissions were changed
         if ( 'Y' != get_option( 'editors_quick_mail_privilege', 'N' ) ) {
            // only admins can see list
            if ( ! $this->qm_is_admin( $id ) ) {
               $option = 'X';
            }
         } // end admin check
      } // end if wants user list

      if ( 'A' != $option && 'N' != $option ) {
         echo sprintf($template, $to, __( 'Enter mail address', 'quick-mail' ) );
         return;
      }
      $args = ( 'A' == $option ) ?
      array('orderby' => 'user_nicename', 'count_total' => true) :
      array('count_total' => true);
      $hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
      $user_query = new \WP_User_Query( $args );
      $users = array();
      foreach ( $user_query->results as $user ) {
         if ( 'Y' == $hide_admin && $this->qm_is_admin( $user->ID ) ) {
               continue;
         } // end admin test

         if ( 'A' == $option ) {
            $users[] = ucfirst( "{$user->user_nicename}\t{$user->user_email}" );
         } // end if all users
         else {
            $last = ucfirst( get_user_meta( $user->ID, 'last_name', true ) );
            $first = get_user_meta( $user->ID, 'first_name', true );
            if ( ! empty( $first ) && ! empty( $last ) && ! empty( $user->user_email ) ) {
               $users[] = "{$last}\t{$first}\t{$user->ID}\t{$user->user_email}";
            } // end if valid name
         } // end else named only
      } // end for

      $j = count($users);
      if (1 > $j) {
         echo sprintf( $template, $to, __( 'Enter mail address', 'quick-mail' ) );
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
   public function quick_mail_form() {
      $success = '';
      $error = '';
      $to = '';
      $subject = '';
      $message = '';
      $no_uploads = '';
      $file = '';
      $domain = '';
      $verify = get_option( 'verify_quick_mail_addresses', 'N' );
      if ( 'Y' == $verify && 'X' != $this->qm_get_option( 'show_quick_mail_users', 'A' ) ) {
         $verify = 'N';
     }

      $attachments = array();
      $you = wp_get_current_user();
      $from = "From: \"{$you->user_firstname} {$you->user_lastname}\" <{$you->user_email}>\r\n";
      if ( 'GET' == $_SERVER['REQUEST_METHOD'] && empty( $_GET['quick-mail-uploads'] ) ) {
         $can_upload = strtolower( ini_get( 'file_uploads' ) );
         if ( '1' != $can_upload && 'true' != $can_upload && 'on' != $can_upload ) {
            $no_uploads = __( 'Uploads are disabled', 'quick-mail' );
         }
      }
      if ( empty( $you->user_firstname ) || empty( $you->user_lastname ) || empty( $you->user_email ) ) {
         $error = '<a href="/wp-admin/profile.php">' . __( 'Error: Incomplete User Profile', 'quick-mail' ) . '</a>';
      }
      elseif ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
         if ( ! wp_verify_nonce( $_POST['quick-mail'], 'quick-mail' ) || empty( $_POST['email'] ) ) {
            wp_die( '<h2>' . __( 'Login Expired. Refresh Page.', 'quick-mail' ). '</h2>' );
         }

         if ( preg_match('/<(.+@.+[.].+)>/', urldecode($_POST['email']), $raw_email) ) {
            $to = trim( $raw_email[1] );
         } else {
            $to = trim( urldecode( $_POST['email'] ) );
         }

         if ( !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
            $error = __( 'Invalid mail address', 'quick-mail' );
         }

         $subject = htmlspecialchars_decode( urldecode( stripslashes( $_POST['subject'] ) ) );
         $subject = sanitize_text_field( $subject );
         if (! preg_match('/(\S+)/', $subject ) ) {
            $error = __( 'No subject', 'quick-mail' );
         } elseif ( 'Y' == $verify ) {
            if ( ! checkdnsrr( $domain, 'MX' ) ) {
               $error = __( 'Invalid mail address', 'quick-mail' );
            }
         } // end subject check

         $message = urldecode( stripslashes( $_POST['message'] ) );
         // TODO $maxlen = apply_filters( 'quick-mail-max-message', 100000 ) ;
         if ( empty( $error ) && 2 > strlen( $message ) ) {
               $error = __( 'Please enter your message', 'quick-mail' );
         }

         if ( '<' == substr( $message, 0, 1 ) ) {
            $this->content_type = 'text/html';
         } else {
            $this->content_type = 'text/plain';
         } // end set content type

         if ( empty( $error ) ) {
            if ( ! empty( $_FILES['attachment'] ) ) {
               if ( ( 0 == $_FILES['attachment']['error'] ) && ( 0 < $_FILES['attachment']['size'] ) && ( 250 > strlen( $_FILES['attachment']['name'] ) ) ) {
                  $temp = $this->qm_get_temp_path(); // @since 1.1.1
                  if ( ! is_dir( $temp ) || ! is_writable( $temp ) ) {
                     $error = __( 'Missing temporary directory', 'quick-mail' );
                  } else {
                     $file = "{$temp}{$_FILES['attachment']['name']}";
                     if ( move_uploaded_file( $_FILES['attachment']['tmp_name'], $file ) ) {
                        array_push( $attachments, $file );
                     }
                     else {
                        $error = __( 'Error moving file to', 'quick-mail' ) . " : {$file}";
                     }
                  }
               } elseif ( 4 != $_FILES['attachment']['error'] ) {
                  if ( 1 == $_FILES['attachment']['error'] || 2 == $_FILES['attachment']['error'] ) {
                     $error = __( 'Uploaded file was too large', 'quick-mail' );
                  } else {
                  $error = __( 'File Upload Error', 'quick-mail' );
                  }
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
      } // end if POST
      ob_start();
      $link = plugins_url( '/qm_validate.php', __FILE__ );
      echo "<script>var qm_validate = '{$link}', val_option = '{$verify}';</script>";
   ?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Quick Mail', 'quick-mail' ); ?></h1>
<?php if ( ! empty( $no_uploads ) ) : ?>
<div class="update-nag notice is-dismissible">
   <p><?php echo $no_uploads; ?></p>
</div>
<?php elseif ( ! empty( $success ) ) : ?>
<div id="success" class="updated notice is-dismissible">
   <p><?php echo $success; ?></p>
</div>
<?php elseif ( ! empty( $error ) ) : ?>
            <?php $ecss = (strstr($error, 'profile.php')) ? 'error notice': 'error notice is-dismissible'; ?>
<div id="qm_error" class="<?php echo $ecss; ?>">
   <p><?php echo $error; ?></p>
</div>
<?php endif; ?>
<div id="qm-validate" class="error notice is-dismissible">
   <p><?php _e( 'Invalid mail address', 'quick-mail' ); ?></p>
</div>
<?php if ( ! empty( $you->user_firstname ) && ! empty( $you->user_lastname ) && ! empty( $you->user_email ) ) : ?>
<form name="Hello" id="Hello" method="post"
   enctype="multipart/form-data"
   action="<?php echo $_SERVER['REQUEST_URI']; ?>">
               <?php wp_nonce_field( 'quick-mail', 'quick-mail', false, true ); ?>
               <?php if ( ! empty( $no_uploads ) || ! empty( $_POST['quick-mail-uploads'] ) ) : ?>
               <input type="hidden" name="quick-mail-uploads" value="No">
               <?php endif; ?>
               <input type="hidden" name="quick-mail-verify"
      value="<?php echo $verify; ?>">
   <table id="quick-mail" class="form-table">
      <tr>
         <td class="quick-mail"><?php _e( 'From', 'quick-mail' ); ?>:</td>
         <td><?php echo htmlspecialchars( substr( $from, 6 ), ENT_QUOTES ); ?></td>
      </tr>
      <tr>
         <td class="quick-mail"><?php _e( 'To', 'quick-mail' ); ?>:</td>
         <td><?php echo $this->quick_mail_recipient_input( $to, $you->ID ); ?></td>
      </tr>
                  <?php
                  if ( 'X' == $this->qm_get_option( 'show_quick_mail_users', 'A' ) ) : ?>
      <tr id="qm_row">
         <td class="quick-mail"><?php _e( 'Recent', 'quick-mail' ); ?>:</td>
         <td id="qm_choice"></td>
      </tr>
                  <?php endif; ?>
      <tr>
         <td class="quick-mail"><?php _e( 'Subject', 'quick-mail' ); ?>:</td>
         <td><input
            value="<?php echo htmlspecialchars( $subject, ENT_QUOTES ); ?>"
            name="subject" id="subject" type="text" required size="35"
            placeholder="<?php _e( 'Subject', 'quick-mail' ); ?>" tabindex="2"></td>
      </tr>
                  <?php if ( empty( $no_uploads ) && empty( $_POST['quick-mail-uploads'] ) ) : ?>
      <tr>
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
         <td><input id="attachment" name="attachment" type="file" tabindex="3"></td>
      </tr>
                  <?php endif; ?>
      <tr>
         <td class="quick-mail-message"><?php _e( 'Message', 'quick-mail' ); ?>:</td>
         <td><textarea id="message" name="message"
               placeholder="<?php _e( 'Enter your message', 'quick-mail' ); ?>"
               required rows="4" cols="35" tabindex="4"><?php echo htmlspecialchars( $message, ENT_QUOTES ); ?></textarea></td>
      </tr>
      <tr>
         <td>&nbsp;</td>
         <td class="submit"><input type="submit" id="submit" name="submit"
            title="<?php _e( 'Send Mail', 'quick-mail' ); ?>" tabindex="5"
            value="<?php _e( 'Send Mail', 'quick-mail' ); ?>"></td>
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
   public function quick_mail_options() {
      global $wpdb;
      $updated = false;
      if ( ! empty( $_POST['show_quick_mail_users'] ) && 1 == strlen( $_POST['show_quick_mail_users'] ) ) {
         $previous = $this->qm_get_option( 'show_quick_mail_users', 'A' );
         if ( $previous != $_POST['show_quick_mail_users'] ) {
            $this->qm_update_option( 'show_quick_mail_users', $_POST['show_quick_mail_users'] );
            echo '<div class="updated">', _e('Option Updated', 'quick-mail'), '</div>';
            $updated = true;
         }
      }
      if ( ! empty($_POST['showing_quick_mail_admin']) ) {
         $previous = get_option( 'hide_quick_mail_admin', 'N' );
         $current = empty( $_POST['hide_quick_mail_admin'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
            update_option( 'hide_quick_mail_admin', $current );
            if ( ! $updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
               $updated = true;
            }
         }

         $previous = get_option( 'editors_quick_mail_privilege', 'N' );
         $current = empty( $_POST['editors_quick_mail_privilege'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
            update_option( 'editors_quick_mail_privilege', $current );
            if ( ! $updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
            }
         }

         $previous = get_option( 'verify_quick_mail_addresses', 'N' );
         $current = empty( $_POST['verify_quick_mail_addresses'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
            update_option( 'verify_quick_mail_addresses', $current );
            if ( !$updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
            }
         }
      } // end if admin

      $user_query = new \WP_User_Query( array('count_total' => true) );
      $hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
      $total = 0; // $user_query->get_total() is wrong, if we are skipping admins
      $names = 0;
      foreach ( $user_query->results as $user ) {
         if ( 'Y' == $hide_admin && $this->qm_is_admin( $user->ID ) ) {
            continue;
         } // end admin test

         $total++;
         $last = get_user_meta( $user->ID, 'last_name', true );
         $first = get_user_meta( $user->ID, 'first_name', true );
         if ( ! empty($first) && ! empty($last) ) {
            $names++;
         } // end if
      } // end for

      $check_all    = ( 'A' == $this->qm_get_option( 'show_quick_mail_users', 'A' ) ) ? 'checked="checked"' : '';
      $check_names  = ( 'N' == $this->qm_get_option( 'show_quick_mail_users', 'A' ) ) ? 'checked="checked"' : '';
      $check_none   = ( 'X' == $this->qm_get_option( 'show_quick_mail_users', 'A' ) ) ? 'checked="checked"' : '';
      $check_admin  = ( 'Y' == get_option( 'hide_quick_mail_admin', 'N' ) ) ? 'checked="checked"' : '';
      $check_editor = ( 'Y' == get_option( 'editors_quick_mail_privilege', 'N' ) ) ? 'checked="checked"' : '';
      $check_verify = ( 'Y' == get_option( 'verify_quick_mail_addresses', 'N' ) ) ? 'checked="checked"' : '';

      $english_dns = __('http://php.net/manual/en/function.checkdnsrr.php', 'quick-mail');
      $z = __( 'Checks domain with', 'quick-mail' );
      $dnserr_link = "<a target='_blank' href='{$english_dns}'>checkdnsrr</a>";
      $when = __( 'when', 'quick-mail') . ' &ldquo;' . __( 'Do Not Show Users', 'quick-mail' ) .
      '&rdquo; ' . __( 'is selected', 'quick-mail') . '.';
      $verify_message = __( 'Verifies domain with', 'quick-mail' ) . ' ' . $dnserr_link . ' ' . $when;
      $verify_problem = '';
      if ( !function_exists( 'idn_to_ascii' ) ) {
         $english_faq = __('https://wordpress.org/plugins/quick-mail/faq/', 'quick-mail');
         $faq_link = "<a target='_blank' href='{$english_faq}'>" . __( 'FAQ', 'quick-mail') . '</a>';
         $english_idn = __('http://php.net/manual/en/function.idn-to-ascii.php', 'quick-mail');
         $idn_link = "<a target='_blank' href='{$english_idn}'>idn_to_ascii</a>";
         $nf = $idn_link . ' ' . __( 'function not found', 'quick-mail') . '.';
         $cannot = __( 'Cannot verify international domains', 'quick-mail' ) . ' ' . __( 'because', 'quick-mail' ) . ' ';
         $faq = __( 'Please read', 'quick-mail' ) . ' ' . $faq_link . '.';
         $verify_problem = '<br><br>' . $cannot . $nf . '<br>' . $faq;
      } // end if idn_to_ascii is available
      $verify_note = $verify_message . $verify_problem;
?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Quick Mail Options', 'quick-mail' ); ?></h1>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
   <table class="form-table">
      <tr>
         <th id="recipients" class="recipients"><?php _e( 'User Display', 'quick-mail' ); ?></th>
      </tr>
      <tr>
         <td id="qm_saved"></td>
      </tr>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="A"
               <?php echo $check_all; ?>>
<?php _e( 'Show All Users', 'quick-mail' ); echo " ({$total})"; ?>
<br><?php _e( 'Show all users sorted by nickname.', 'quick-mail' );
echo ' ', $total, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
      </tr>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="N"
               <?php echo $check_names; ?>>
<?php _e( 'Show Users with Names', 'quick-mail' ); echo " ({$names})"; ?>
<br><?php _e( 'Show users with names, sorted by last name.', 'quick-mail' );
echo ' ', $names, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
      </tr>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="X"
               <?php echo $check_none; ?>>
<?php _e( 'Do Not Show Users', 'quick-mail' ); ?>
<br><?php _e( 'Enter address to send mail.', 'quick-mail' ); ?> <?php _e( 'Saves last five addresses.', 'quick-mail' ); ?></label></td>
      </tr>
<?php  if ( current_user_can('list_users') ) : ?>
   <tr>
      <th class="recipients"><?php _e( 'Administration', 'quick-mail' ); ?></th>
   </tr>
   <tr>
      <td><label><input name="hide_quick_mail_admin" type="checkbox" <?php echo $check_admin; ?>>
   <?php _e( 'Hide Administrator profiles', 'quick-mail' ); ?>
   <br><?php _e( 'User list will not include administrator profiles.', 'quick-mail' ); ?>
   </label><input name="showing_quick_mail_admin" type="hidden" value="Y"></td>
   </tr>
      <tr>
         <td><label><input name="editors_quick_mail_privilege" type="checkbox"
               <?php echo $check_editor; ?>>
<?php _e( 'Grant Editors access to user list', 'quick-mail' ); ?>
<br><?php _e( 'Modify permission to let editors see user list.', 'quick-mail' ); ?>
</label></td>
      </tr>
      <tr>
         <td><label><input name="verify_quick_mail_addresses" type="checkbox" <?php echo $check_verify; ?>>
<?php _e( 'Verify recipient email domains.', 'quick-mail' ); ?>
<br><?php
echo $verify_note;
?>
</label></td>
      </tr>
<?php endif; ?>
      <tr>
         <td><input type="submit" name="submit" class="button button-primary"
            value="<?php _e( 'Save Options', 'quick-mail' ); ?>"></td>
      </tr>
   </table>
</form>
<?php
   } // end quick_mail_options

   /**
    * get user option. return default if not found
    *
    * @param string $key Option name
    * @param string $qm_default Default value
    * @return string Option value or default
    */
   public function qm_get_option( $key, $qm_default ) {
      global $current_user;
      $value = get_user_meta( $current_user->ID, $key, true );
      return ( ! empty($value) ) ? $value : $qm_default;
   } // end qm_get_option

   /**
    * update user option
    *
    * @param string $key
    * @param string $value
    */
   public function qm_update_option( $key, $value ) {
      global $current_user;
      update_user_meta( $current_user->ID, $key, $value );
   } // end qm_update_option

   /**
    * Is user an administrator?
    *
    * @param int $id User ID.
    */
   protected function qm_is_admin( $id ) {
      global $table_prefix;
      $meta = get_user_meta( $id );
      $field = $table_prefix . 'capabilities';
      $cap = unserialize( $meta[$field][0] );
      return isset( $cap['administrator'] );
   } // end qm_is_admin

   /**
    * used with quick_mail_setup_capability filter, to let editors see user list
    *
    */
   public function let_editor_set_quick_mail_option( $role ) {
      return 'edit_others_posts';
   } // end let_editor_set_quick_mail_option

   /**
    * init admin menu for appropriate users
    */
   public function init_quick_mail_menu() {
      $title = __( 'Quick Mail', 'quick-mail' );
      $page = add_submenu_page( 'tools.php', $title, $title,
      apply_filters( 'quick_mail_user_capability', 'publish_posts' ), 'quick_mail_form', array($this, 'quick_mail_form') );
      add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
      $page = add_options_page( 'Quick Mail Options', $title, apply_filters( 'quick_mail_setup_capability', 'list_users' ), 'quick_mail_options', array($this, 'quick_mail_options') );
      add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
   } // end init_quick_mail_menu

   /**
    * use by admin print styles to add css to admin
    */
   public function init_quick_mail_style() {
      wp_enqueue_style( 'quick-mail', 	plugins_url( '/quick-mail.css', __FILE__) , array(), null, 'all' );
   } // end init_quick_mail_style

   /**
    * load translations
    */
   public function init_quick_mail_translation() {
      load_plugin_textdomain( 'quick-mail', '', dirname( __FILE__ ) . '/lang' );
   } // end init_quick_mail_translation

   /**
    *	find system temp path
    *
    *	test order: upload_tmp_dir, sys_get_temp_dir()
    *
    *	@since 1.1.1
    *
    *	@return string path or empty string if not found
    */
   public function qm_get_temp_path()
   {
      $path = ini_get( 'upload_tmp_dir' );
      if ( ! empty( $path ) ) {
         return trailingslashit( $path );
      }
      return trailingslashit( sys_get_temp_dir() );
   } // end qm_get_temp_path

   /**
    * add helpful links to plugin description. filters plugin_row_meta.
    *
    * @param array $links
    * @param string $file
    * @return array
    *
    * @since 1.2.4
    */
   public function qm_plugin_links( $links, $file ) {
      $base = plugin_basename( __FILE__ );
      if ( $file == $base ) {
         $links[] = '<a href="options-general.php?page=quick_mail_options">' . __( 'Settings', 'quick-mail' ) . '</a>';
         $links[] = '<a href="https://wordpress.org/plugins/quick-mail/faq/" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
         $links[] = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
      }
      return $links;
   } // end qm_plugin_links

} // end class
$quick_mail_plugin = QuickMail::get_instance();
