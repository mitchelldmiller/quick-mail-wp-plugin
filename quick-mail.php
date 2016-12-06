<?php
/*
Plugin Name: Quick Mail
Description: Adds Quick Mail to Tools menu. Send email with an attachment from dashboard, using a list of users or enter a name.
Version: 2.0.3
Author: Mitchell D. Miller
Author URI: http://wheredidmybraingo.com/
Plugin URI: http://wheredidmybraingo.com/tag/quick-mail/
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
    * @var (boolean|object) $instance
    */
   public static $instance = false;

   /**
    * Our dismissed pointer name
    * @var string
    * @since 1.3.0
    */
   public static $pointer_name = 'quickmail_131';

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
    * Get text for help tab
    * @return string[]
    */
	public static function get_qm_help_tab() {
		$qm_desc =  __( 'Quick Mail is the easiest way to send an email with attachments to WordPress users on your site.', 'quick-mail' );
		$english_faq = __('https://wordpress.org/plugins/quick-mail/faq/', 'quick-mail');
		$faq = __( 'FAQ', 'quick-mail' );
		$flink = '<a href="https://wordpress.org/plugins/quick-mail/faq/" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
		$slink = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
		$rlink = '<a href="https://wordpress.org/support/plugin/quick-mail/reviews/" target="_blank">' . __( 'Please leave a review', 'quick-mail' ) . '</a>';
		$others = __( 'to help others find Quick Mail', 'quick-mail' );
		// https://wordpress.org/support/plugin/quick-mail/reviews/
		$questions = __( 'Resources', 'quick-mail' );
		$more_info = __( 'has more information', 'quick-mail' );
		$use_str = __( 'Use', 'quick-mail' );
		$to_ask = __( 'to ask questions', 'quick-mail' );
		$help_others = __( 'Help Others', 'quick-mail' );
		$use_str = __( 'Use', 'quick-mail' );
		$everyone = __( 'Tell WordPress users that Quick Mail works with WordPress', 'quick-mail' ) . ' ' . get_bloginfo('version');
		$clink = "<a href='https://wordpress.org/plugins/quick-mail/#compatibility'>{$everyone}</a>";
		$qm_top = "<p>{$qm_desc}</p><h4>{$questions}</h4><ul><li>{$flink} {$more_info}</li><li>{$use_str} {$slink} {$to_ask}</li></ul>";
		$qm_bot = "<h4>{$help_others}</h4><ul><li>{$rlink} {$others}</li><li>{$clink}</li></ul>";
		$qm_content = $qm_top . $qm_bot; 
		return array('id' => 'qm_intro', 'title'	=> __('Quick Mail', 'quick-mail'), 'content' => $qm_content);
	} // end get_qm_help_tab

   /**
    * Does site have more than one user? Supports multisite.
    *
    * @param string $code 'A' (all), 'N' (users with first / last names), 'X' (no user list)
    * @param int $blog Blog ID or zero if not multisite
    * @return bool more than one user for selected option
    *
    * @since 1.4.0
    */
	public function multiple_matching_users($code, $blog) {
		if ( 'X' == $code ) {
			return true;
		} // end if do not want user list
		
		if ( is_multisite() && 0 == $blog ) {
			$blog = get_current_blog_id();
		} // end if blog not set

		$you = wp_get_current_user();
		$exclude = array($you->ID); // exclude current user
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if
		
		if ( 'A' == $code ) {
			if ( $blog > 1 ) {
				if ( 'Y' == $hide_admin ) {
					$args = array('blog_id' => $blog, 'role__not_in' => array('Administrator'), 'exclude' => $exclude);
				} else {
					$args = array('exclude' => $exclude);
				}
			} else {
				if ( 'Y' == $hide_admin ) {
					$args = array('role__not_in' => array('Administrator'), 'exclude' => $exclude);
				} else {
					$args = array('exclude' => $exclude);
				}
			} // end if multisite
		
			$info = get_users( $args );
			return 1 < count( $info ); // 2.0.4
		} // end if ALL
		
		// check for first and last names
		$meta_query =  array('key' => 'last_name', 'value' => '', 'compare' => '>');
		if ( is_multisite() ) {
			if ( 'Y' == $hide_admin ) {
				$args = array('role__not_in' => array('Administrator'), 'exclude' => $exclude,
						'blog_id' => $blog, 'meta_query' => $meta_query,
						'meta_key' => 'first_name', 'meta_value' => '', 'meta_compare' => '>');
			} else {
				$args = array('blog_id' => $blog, 'meta_query' => $meta_query, 'exclude' => $exclude,
						'meta_key' => 'first_name', 'meta_value' => '', 'meta_compare' => '>');
			} // end if hide admin
		} else {
			// unset($args['blog_id']);
			if ( 'Y' == $hide_admin ) {
				$args = array('role__not_in' => array('Administrator'), 'exclude' => $exclude,
						'meta_query' => $meta_query,
						'meta_key' => 'first_name', 'meta_value' => '', 'meta_compare' => '>');
			} else {
				$args = array('meta_query' => $meta_query, 'exclude' => $exclude,
						'meta_key' => 'first_name', 'meta_value' => '', 'meta_compare' => '>');
			} // end if
		} // end if 'N'
		
		$info = get_users( $args );
		return 1 < count( $info ); // 2.0.0
	} // end multiple_matching_users
	
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
      add_action( 'admin_init', array($this, 'add_email_scripts' ) );
      add_action( 'admin_menu', array($this, 'init_quick_mail_menu' ) );
      add_action( 'plugins_loaded', array($this, 'init_quick_mail_translation' ) );
      add_action( 'activated_plugin', array($this, 'install_quick_mail'), 10, 0 );
      add_action( 'deactivated_plugin', array($this, 'unload_quick_mail_plugin'), 10, 0 );
      add_filter( 'wp_mail_content_type', array($this, 'set_mail_content_type') );
      add_filter( 'plugin_row_meta', array($this, 'qm_plugin_links'), 10, 2 );
      add_filter( 'wp_mail_failed', array($this, 'show_mail_failure'), 99, 1 );
      add_filter( 'quick_mail_setup_capability', array($this, 'let_editor_set_quick_mail_option' ) );
      add_action( 'load-tools_page_quick_mail_form', array( $this, 'add_qm_help' ), 20 );
      add_action( 'plugins_loaded', array($this, 'show_qm_pointer' ) );
   } // end constructor
   
   /**
    * skip options menu when there is nothing to set
    * @return boolean do we need menu?
    * @since 2.03
    */
   public function want_options_menu() {
   	if ( is_multisite() && is_super_admin() && is_network_admin() ) {
   		return true;
   	} // end if on network admin page
   	
   	$blog = 0;
   	if ( is_multisite() && 0 == $blog ) {
   		$blog = get_current_blog_id();
   	} // end if blog not set
   	
   	$you = wp_get_current_user();
   	if ( $this->qm_is_admin( $you->ID, $blog ) ) {
   		return true;
   	} // end if always show menu to admin

   	// we've got a non-admin user. do they have any settings?
   	return $this->multiple_matching_users( 'A', $blog, $you->ID );
   } // end want_options_menu

   /**
    * optionally display dismissible wp_pointer with setup reminder.
    * cannot be loaded in constructor because user info is not available until plugins_loaded.
    *
    * @since 1.3.0
    */
	public function show_qm_pointer() {
		if ( is_multisite() && is_super_admin() && is_network_admin() ) {
	   		return;
	   	} // end if skipping pointer on network admin page
   	  	
		$dismissed = array_filter( explode( ',', (string)get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) );
		if ( ! in_array( self::$pointer_name, $dismissed ) ) {
			add_action( 'admin_enqueue_scripts', array($this, 'qm_pointer_setup') );
      	} // end if pointer was not dismissed
   } // end show_qm_pointer

   /**
    * displays wp_mail error message
    *
    * @param WP_Error $e
    * @since 1.3.0
    */
   public function show_mail_failure($e) {
      if ( is_wp_error( $e ) ) {
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
		$blog = is_multisite() ? get_current_blog_id() : 0;
		$qm_options = array('hide_quick_mail_admin', 'editors_quick_mail_privilege', 'verify_quick_mail_addresses');
		foreach ($qm_options as $option) {
			if ( is_multisite() ) {
				add_blog_option( $blog, $option, 'N' );
			} else {
				add_option( $option, 'N', '', 'no' );
			}
		} // end foreach
      /**
       * Do not show users if one user
       * since 1.2.4
       */
		$code = $this->multiple_matching_users( 'A', $blog ) ? 'A' : 'X';
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
      position:	{
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
      add_action ( 'admin_print_footer_scripts', array ($this, 'quick_mail_pointer_scripts') );
   } // end qm_pointer_setup

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
      if ( is_multisite() ) {
      	delete_blog_option( get_current_blog_id(), 'show_quick_mail_users' );
      	delete_blog_option( get_current_blog_id(), 'hide_quick_mail_admin' );
      	delete_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege' );
      	delete_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses' );
      } else {
      	delete_option( 'show_quick_mail_users' );
      	delete_option( 'hide_quick_mail_admin' );
      	delete_option( 'editors_quick_mail_privilege' );
      	delete_option( 'verify_quick_mail_addresses' );
      } // end if multisite
   } // end unload_quick_mail_plugin

   /**
    * load quick-mail.js for email select and
    * quick-mail-addresses.js to count saved addresses
    *
    * @since 1.2.0
    */
   public function add_email_scripts()
   {
      wp_enqueue_script( 'qmScript', plugins_url('/quick-mail.js', __FILE__), array('jquery'), null, false );
      wp_enqueue_script( 'qmCount', plugins_url('/quick-mail-addresses.js', __FILE__), array('jquery'), null, false );
      $data = array(
      		'one' => __( 'Clear 1 saved address', 'quick-mail' ),
      		'many' => sprintf( __( 'Clear %s saved addresses', 'quick-mail' ), '{number}' )
      );
      wp_localize_script('qmCount', 'quick_mail_saved', $data);
   } // end add_email_scripts

   /**
    * create and display recipient input. user list or text input.
    *
    * @param string $to recipient email
    * @param int $id user ID
    * @return void displays input
    */
   public function quick_mail_recipient_input( $to, $id ) {
      $template = '<input value="%s" id="qm-email" name="qm-email" type="email" required tabindex="1" autofocus size="35" placeholder="%s">';
      $blog = is_multisite() ? get_current_blog_id() : 0;
      $option = $this->qm_get_display_option( $blog );
      if ( 'X' != $option ) {
         $editors = '';
         if ( is_multisite() ) {
         	$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
         } else {
         	$editors = get_option( 'editors_quick_mail_privilege', 'N' );
         } // end if multisite
         if ( 'Y' != $editors ) {
            if ( ! $this->qm_is_admin( $id, $blog ) ) {
               $option = 'X';
            } // end if not admin and option might have changed
         } // end if editors not allowed to see list
      } // end if wants user list

      if ( 'A' != $option && 'N' != $option ) {
         echo sprintf($template, $to, __( 'Enter mail address', 'quick-mail' ) );
         return;
      }
      $you = wp_get_current_user(); // from
      $exclude = array($you->ID);
      $args = ( 'A' == $option ) ?
      array('orderby' => 'user_nicename', 'exclude' => $exclude) :
      array('exclude' => $exclude);
      $hide_admin = '';
      if ( is_multisite() ) {
      	$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
      } else {
      	$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
      } // end if

      $user_query = new \WP_User_Query( $args );
      $users = array();
      foreach ( $user_query->results as $user ) {
         if ( ( 'Y' == $hide_admin && $this->qm_is_admin( $user->ID, $blog ) ) 
         || ( $user->user_email == $you->user_email ) )
         {
			continue;
         } // end admin and sender test
         
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
      if ( 1 > $j ) {
         echo sprintf( $template, $to, __( 'Enter mail address', 'quick-mail' ) );
         return;
      } // end if at least one match

      sort( $users );
      $letter = '';
      ob_start();
      echo '<select name="qm-email" id="qm-primary" required size="1" tabindex="1" autofocus onchange="return is_qm_email_dup()"><option class="qmopt" value="" selected>Select</option>';
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
            echo "<optgroup class='qmog' label='{$letter}'>";
         } // end if first letter changed

         if ( 'A' == $option ) {
            $selected = ($row[1] != $to) ? ' ' : ' selected ';
            echo "<option{$selected}value='{$address}' class='qmopt'>{$row[0]}</option>";
         }
         else {
            $selected = ($row[3] != $to) ? ' ' : ' selected ';
            echo "<option{$selected}value='{$address}' class='qmopt'>{$row[1]} {$row[0]}</option>";
         }
      } // end for
      echo '</optgroup></select>';
      return ob_get_clean();
   } // end quick_mail_recipient_input
   
	public function quick_mail_cc_input( $to, $cc, $id ) {
	   	$template = '<input value="%s" id="qm-cc" name="qm-cc" type="text" size="35" tabindex="2" placeholder="%s">';
	   	$blog = is_multisite() ? get_current_blog_id() : 0;
	   	$option = $this->qm_get_display_option( $blog );
	   	if ( !$this->multiple_matching_users( $option, $blog ) ) {
	   		$option = 'X';
	   	} // end if since 1.4.0
	   	
	   	if ( 'X' != $option ) {
	   		// check if site permissions were changed
	   		$editors = '';
	   		if ( is_multisite() ) {
	   			$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
	   		} else {
	   			$editors = get_option( 'editors_quick_mail_privilege', 'N' );
	   		} // end if multisite
	   		 
	   		if ( 'Y' != $editors ) {
	   			if ( ! $this->qm_is_admin( $id, $blog ) ) {
	   				$option = 'X';
	   			} // end if not admin
	   		} // end if editors not allowed to see list
	   	} // end if wants user list
   
	   	if ( 'A' != $option && 'N' != $option ) {
	   		echo sprintf($template, $cc, __( 'Enter mail address', 'quick-mail' ) );
	   		return;
	   	}
	   	$you = wp_get_current_user(); // from
	   	$exclude = array($you->ID);
	   	$args = ( 'A' == $option ) ?
	   	array('orderby' => 'user_nicename', 'exclude' => $exclude) :
	   	array('exclude' => $exclude);
	   	$hide_admin = '';
	   	if ( is_multisite() ) {
	   		$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
	   	} else {
	   		$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
	   	} // end if
	   	$user_query = new \WP_User_Query( $args );
	   	$users = array();
	   	foreach ( $user_query->results as $user ) {
	   		if ( 'Y' == $hide_admin && $this->qm_is_admin( $user->ID, $blog ) ) {
	   			continue;
	   		} // end admin test
	   		
	   		if ( $user->user_email == $you->user_email ) {
	   			continue;
	   		} // end if user
	   
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
	   
	   	$j = count( $users );
	   	if ( 2 > $j ) {
	   		echo sprintf( $template, $cc, __( 'Enter mail address', 'quick-mail' ) );
	   		return;
	   	} // end if one match
	   
	   	sort( $users );
	   	$letter = '';
	   	ob_start();
	   	echo '<select name="qm-cc[]" id="qm-secondary" multiple tabindex="3" onchange="return	is_qm_email_dup()"><option class="qmopt" value="" selected>Select</option>';
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
	   			echo "<optgroup class='qmog' label='{$letter}'>";
	   		} // end if first letter changed
	   
	   		if ( 'A' == $option ) {
	   			$selected = ($row[1] != $cc) ? ' ' : ' selected ';
	   			echo "<option{$selected}value='{$address}' class='qmopt'>{$row[0]}</option>";
	   		}
	   		else {
	   			$selected = ($row[3] != $cc) ? ' ' : ' selected ';
	   			echo "<option{$selected}value='{$address}' class='qmopt'>{$row[1]} {$row[0]}</option>";
	   		}
	   	} // end for
	   	echo '</optgroup></select>';
	   	return ob_get_clean();
   } // end quick_mail_cc_input
   
   /**
    * display data entry form to enter recipient, cc, subject, message
    *
    */
	public function quick_mail_form() {
	  $all_cc = array();
      $data = array();
      $domain = '';
      $error = '';
      $file = '';
      $mcc = '';
      $message = '';
      $no_uploads = '';
      $subject = '';
      $success = '';
      $to = '';
      $verify = '';
      $blog = is_multisite() ? get_current_blog_id() : 0;
      if ( is_multisite() ) {
      	$verify = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
      } else {
      	$verify = get_option( 'verify_quick_mail_addresses', 'N' );
      }
      if ( 'Y' == $verify && 'X' != $this->qm_get_display_option( $blog ) ) {
         $verify = 'N';
     }

      $attachments = array();
      $you = wp_get_current_user();
      $from = "From: \"{$you->user_firstname} {$you->user_lastname}\" <{$you->user_email}>\r\n";
      if ( 'GET' == $_SERVER['REQUEST_METHOD'] && empty( $_GET['quick-mail-uploads'] ) ) {
         $can_upload = strtolower( ini_get( 'file_uploads' ) );
         $pattern = '/(OS 5_.+like Mac OS X)/';
         if ( !empty( $_SERVER['HTTP_USER_AGENT'] ) && 1 == preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) ) {
			$no_uploads = __( 'File uploads are not available on your device', 'quick-mail' );
         } else if ( '1' != $can_upload && 'true' != $can_upload && 'on' != $can_upload ) {
            $no_uploads = __( 'File uploads were disabled by system administrator', 'quick-mail' );
         }
         if ( !empty( $no_uploads ) ) {
         	$no_uploads .= '.';
         } // add a period
      }
      if ( empty( $you->user_firstname ) || empty( $you->user_lastname ) || empty( $you->user_email ) ) {
         $error = '<a href="/wp-admin/profile.php">' . __( 'Error: Incomplete User Profile', 'quick-mail' ) . '</a>';
      }
      elseif ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
         if ( ! wp_verify_nonce( $_POST['quick-mail'], 'quick-mail' ) || empty( $_POST['qm-email'] ) ) {
            wp_die( '<h2>' . __( 'Login Expired. Refresh Page.', 'quick-mail' ). '</h2>' );
         }
         if ( empty($_POST['qm-email'] ) ) {
         	$direction = is_rtl() ? 'rtl' : 'ltr';
         	$args = array('response' => 200, 'back_link' => true, 'text_direction' => $direction);
         	wp_die( sprintf( '<h3>%s</h3>', __( 'Invalid mail address', 'quick-mail' ) ), __( 'Mail Error', 'quick-mail' ), $args );
         } // end if user circumvented Javascript
         
         if (isset($_POST['qm-cc']) && is_array($_POST['qm-cc'])) {
         	$e = strtolower( urldecode( $_POST['qm-email'] ) );
         	foreach ($_POST['qm-cc'] as $c) {
         		if ($e == strtolower( urldecode( $c ) ) ) {
         			$error = __( 'Duplicate mail address', 'quick-mail' );
         			break;
         		} // end if
         	} // end foreach
         } // end if multiple selection

         if ( preg_match('/<(.+@.+[.].+)>/', urldecode($_POST['qm-email']), $raw_email) ) {
            $to = trim( $raw_email[1] );
         } else {
            $to = trim( urldecode( $_POST['qm-email'] ) );
         } // end if email and name

         if ( !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
            $error = __( 'Invalid mail address', 'quick-mail' ) . '<br>' . htmlspecialchars($to);
         }
         if (! empty( $_POST['qm-cc'] )) {
         	$raw_cc = array();
         	if ( !is_array( $_POST['qm-cc'] ) ) {
         		$mcc = QuickMailUtil::filter_email_input( $to, urldecode( $_POST['qm-cc'] ), $verify );
         		$tab = strstr( $mcc, "\t" );
         		if (is_string( $tab )) {
         			$mtest = explode( "\t", $mcc );
         			$error = __( 'Invalid mail address', 'quick-mail' ) . '<br>' . $mtest[0];
         			// happens if Javascript executes after submit
         			$mcc = $mtest[1];
         		} else {
	         		$data = explode( ',', $mcc );
         		}
         	} else {
         		$data = array_map( 'urldecode', $_POST['qm-cc'] );
         	} // end if not array
         	
         	$j = count( $data );
         	for ($i = 0; $i < $j && empty( $error ); $i++) {
         		if ( preg_match('/<(.+@.+[.].+)>/', $data[$i], $raw_email) ) {
         			$raw_cc[$i] = trim( $raw_email[1] );
         		} else {
         			$raw_cc[$i] = trim( $data[$i] );
         		}
         	} // end for	

         	$all_cc = array_unique( $raw_cc );
         	if ( empty( $error ) && !empty( $all_cc[0] ) && empty( $mcc )) {
	         	$mcc = implode( ',', $all_cc );
	         	$j = count( $all_cc );
	            	for ($i = 0; $i < $j && empty( $error ); $i++) {
	         		if ( !QuickMailUtil::qm_valid_email_domain( $all_cc[$i], $verify ) ) {
	         			$error = 'CC ' . __( 'Invalid mail address', 'quick-mail' ) . '<br>' . $all_cc[$i];
	        		} elseif ( $to == $all_cc[$i] ) {
	        			$error = 'CC ' . __( 'Duplicate mail address', 'quick-mail' ) . '<br>' . $all_cc[$i];
	        			} // end if
				} // end for
         	} // end if not empty
         } // end if cc

         $subject = htmlspecialchars_decode( urldecode( stripslashes( $_POST['qm-subject'] ) ) );
         $subject = sanitize_text_field( $subject );
         if (! preg_match('/(\S+)/', $subject ) ) {
            $error = __( 'No subject', 'quick-mail' );
         } // end subject check

         $message = urldecode( stripslashes( $_POST['qm-message'] ) );
         // TODO $maxlen = apply_filters( 'quick-mail-max-message', 100000 ) ;
         if ( empty( $error ) && 2 > strlen( $message ) ) {
               $error = __( 'Please enter your message', 'quick-mail' );
         }

         if ( '<' == substr( $message, 0, 1 ) ) {
            $this->content_type = 'text/html';
         } else {
            $this->content_type = 'text/plain';
         } // end set content type

         if ( empty( $error ) && !empty( $_FILES['attachment'] ) && !empty( $_FILES['attachment']['name'][0] ) ) {
			$uploads = array_merge_recursive($_FILES['attachment'], $_FILES['second'], $_FILES['third'],
											$_FILES['fourth'], $_FILES['fifth'], $_FILES['sixth'] );
			
			$dup = false;
			$j = count( $uploads['name'] );
			for ($i = 0; ($i < $j) && ($dup == false); $i++) {
				for ($k = $i + 1; $k < $j; $k++) {
					if ( !empty($uploads['name'][$k] ) && $uploads['name'][$k] == $uploads['name'][$i] ) {
						$dup = true;
					} // end if
				} // end for
			} // end for
			
            if ( $dup ) {
            		$error = __( 'Duplicate attachments', 'quick-mail' );
            } // end if duplicate attachments
			for ($i = 0; ($i < $j) && empty($error); $i++) {
				if ( empty( $uploads['name'][$i] ) ) {
					continue;
				}
				if ( ( 0 == $uploads['error'][$i] ) && ( 0 < $uploads['size'][$i] ) && ( 250 > strlen( $uploads['name'][$i] ) ) ) {
                  	$temp = $this->qm_get_temp_path(); // @since 1.1.1
                  	if ( ! is_dir( $temp ) || ! is_writable( $temp ) ) {
                     	$error = __( 'Missing temporary directory', 'quick-mail' );
                  	} else {
                     	$file = "{$temp}{$uploads['name'][$i]}";
	                     if ( move_uploaded_file( $uploads['tmp_name'][$i], $file ) ) {
	                        array_push( $attachments, $file );
	                     }
	                     else {
	                        $error = __( 'Error moving file to', 'quick-mail' ) . " : {$file}";
	                     }
	                 }
               } elseif ( 4 != $uploads['error'][$i] ) {
					if ( 1 == $uploads['error'][$i] || 2 == $uploads['error'][$i] ) {
                     	$error = __( 'Uploaded file was too large', 'quick-mail' );
                  	} else {
                     	$error = __( 'File Upload Error', 'quick-mail' );
                  	}
               }
            } // end if has attachment
         } // end if valid email address and has attachment

         if ( empty( $error ) ) {
         	$headers = array( $from );
         	if ( !empty( $mcc ) ) {
         		$headers[] = "Cc: {$mcc}";
         	} // end if CC

            if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
	            	$success = __( 'Message Sent', 'quick-mail' );
	    			if (empty( $mcc ) ) {
					$success .= sprintf("<br>%s %s", __( 'To', 'quick-mail' ), $to);            				
				} else {
					$success .= sprintf("<br>%s %s<br>%s %s", __( 'To', 'quick-mail' ), $to, __( 'CC', 'quick-mail' ), $mcc);
				} // end if has CC
            } else {
             	$error = __( 'Error sending mail', 'quick-mail' ); // else  error
         	} // end else error
            
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
      $orig_link = plugins_url( '/qm_validate.php', __FILE__ );
      $site = untrailingslashit( network_site_url( '/' ) );
      $link = str_replace( $site, '', $orig_link );
      if ( !$this->qm_is_admin( get_current_user_id(), $blog ) && 'X' != $this->qm_get_display_option( $blog ) ) {
      	$editors = '';
      	if ( is_multisite() ) {
      		$editors = get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' );
      	} else {
      		$editors = get_option( 'editors_quick_mail_privilege', 'N' );
      	} // end if multisite
      	if ( $this->qm_is_editor( get_current_user_id(), $blog ) && 'N' == $editors ) {
      		$this->qm_update_option( 'show_quick_mail_users', 'X' );
      	} // end if adjusted display
      } // end if might adjust display
      echo "<script>var qm_validate = '{$link}', val_option = '{$verify}';</script>";
?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Quick Mail', 'quick-mail' ); ?></h1>
<?php if ( ! empty( $no_uploads ) ) : ?>
<div class="update-nag notice is-dismissible">
   <p><?php echo $no_uploads; ?></p>
</div>
<?php elseif ( ! empty( $success ) ) : ?>
<div id="qm-success" class="updated notice is-dismissible">
   <p><?php echo $success; ?></p>
</div>
<?php elseif ( ! empty( $error ) ) : ?>
<?php $ecss = ( strstr( $error, 'profile.php' ) ) ? 'error notice': 'error notice is-dismissible'; ?>
<div id="qm_error" class="<?php echo $ecss; ?>">
   <p><?php echo $error; ?></p>
</div>
<?php endif; ?>
<div id="qm-validate" class="error notice is-dismissible">
   <p><?php _e( 'Invalid mail address', 'quick-mail' ); ?><span id="qm-ima"> </span></p>
</div>
<div id="qm-duplicate" class="error notice is-dismissible">
   <p><?php _e( 'Duplicate mail address', 'quick-mail' ); ?> <span id="qm-dma"> </span></p>
</div>
<noscript><span class="quick-mail-noscript"><?php _e( 'Quick Mail requires Javascript', 'quick-mail' ); ?></span></noscript>
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
         <td class="quick-mail"><input type="hidden" name="qm-invalid" id="qm-invalid" value="0"><?php _e( 'From', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td><?php echo htmlspecialchars( substr( $from, 6 ), ENT_QUOTES ); ?></td>
      </tr>
      <tr>
         <td class="quick-mail"><?php _e( 'To', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td><?php echo $this->quick_mail_recipient_input( $to, $you->ID ); ?></td>
      </tr>
      <?php
      if ( 'X' == $this->qm_get_display_option( $blog ) ) : ?>
      <tr id="qm_row">
         <td class="quick-mail qm-vtop"><?php _e( 'Recent', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td id="qm_to_choice"></td>
      </tr>
      <?php endif; ?>
      <tr>
         <td class="quick-mail qm-vtop"><span id="qm_cc_changed"><?php _e( 'Updated', 'quick-mail' ); ?> </span><?php _e( 'CC', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td><?php echo $this->quick_mail_cc_input( $to, $mcc, $you->ID ); ?></td>
      </tr>
      <?php
      if ( 'X' == $this->qm_get_display_option( $blog ) ) : ?>
      <tr id="qm_cc_row">
         <td class="quick-mail qm-vtop" ><?php _e( 'Recent', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td id="qm_cc_choice"></td>
      </tr>
      <?php endif; ?>
      <tr>
         <td class="quick-mail"><?php _e( 'Subject', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td><input
            value="<?php echo htmlspecialchars( $subject, ENT_QUOTES ); ?>"
            name="qm-subject" id="qm-subject" type="text" required size="35"
            placeholder="<?php _e( 'Subject', 'quick-mail' ); ?>" tabindex="22"></td>
      </tr>
      <?php if ( empty( $no_uploads ) && empty( $_POST['quick-mail-uploads'] ) ) : ?>
      <tr>
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td><input id="qm-file-first" name="attachment[]" type="file" multiple="multiple" tabindex="23"></td>
      </tr>
	  <tr class="qm-second">
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
	  <tr class="qm-row-second">
         <td><input id="qm-second-file" name="second[]" type="file" multiple="multiple" tabindex="24"></td>
      </tr>
      <tr class="qm-third">
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
      <tr class="qm-row-third">
         <td><input id="qm-third-file" name="third[]" type="file" multiple="multiple" tabindex="25"></td>
      </tr>
      <tr class="qm-fourth">
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
      <tr class="qm-row-fourth">
         <td><input id="qm-fourth-file" name="fourth[]" type="file" multiple="multiple" tabindex="26"></td>
      </tr>
      <tr class="qm-fifth">
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
      <tr class="qm-row-fifth">
         <td><input id="qm-fifth-file" name="fifth[]" type="file" multiple="multiple" tabindex="27"></td>
      </tr>
      <tr class="qm-sixth">
         <td class="quick-mail"><?php _e( 'Attachment', 'quick-mail' ); ?>:</td>
      </tr>
      <tr class="qm-row-sixth">
         <td><input id="qm-sixth-file" name="sixth[]" type="file" multiple="multiple" tabindex="28"></td>
      </tr>
      <?php endif; ?>
      <tr>
         <td class="quick-mail qm-vtop"><?php _e( 'Message', 'quick-mail' ); ?>:</td>
      </tr>
      <tr>
         <td class="qm-vtop"><textarea id="qm-message" name="qm-message"
               placeholder="<?php _e( 'Enter your message', 'quick-mail' ); ?>"
               required rows="4" cols="35" tabindex="50"><?php echo htmlspecialchars( $message, ENT_QUOTES ); ?></textarea></td>
      </tr>
      <tr>
         <td class="submit"><input disabled type="submit" id="qm-submit" name="qm-submit"
            title="<?php _e( 'Send Mail', 'quick-mail' ); ?>" tabindex="99"
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
    */
   public function quick_mail_options() {
      $updated = false;
      $blog = is_multisite() ? get_current_blog_id() : 0;
      if ( ! empty( $_POST['show_quick_mail_users'] ) && 1 == strlen( $_POST['show_quick_mail_users'] ) ) {
         $previous = $this->qm_get_display_option( $blog );
         if ( $previous != $_POST['show_quick_mail_users'] ) {
         	if ( $this->multiple_matching_users( $_POST['show_quick_mail_users'], $blog ) ) {
	            $this->qm_update_option( 'show_quick_mail_users', $_POST['show_quick_mail_users'] );
    		        echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
        		    $updated = true;
         	} // end if valid option, but invalid options should not be displayed
         } // end if display option changed
      } // end if received display option
      
      if ( ! empty($_POST['showing_quick_mail_admin']) ) {
         $previous = '';
         if ( is_multisite() ) {
         	$previous = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
         } else {
         	$previous = get_option( 'hide_quick_mail_admin', 'N' );
         } // end if multisite
         
         $current = empty( $_POST['hide_quick_mail_admin'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
         	if ( is_multisite() ) {
         		update_blog_option( $blog, 'hide_quick_mail_admin', $current );
         	} else {
         		update_option( 'hide_quick_mail_admin', $current );         		
         	} // end if multisite

            if ( ! $updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
               $updated = true;
            } // end if updated not displayed
         } // end if value changed

         $previous = '';
         if ( is_multisite() ) {
         	$previous = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
         } else {
         	$previous = get_option( 'editors_quick_mail_privilege', 'N' );
         } // end if multisite
         
         $current = empty( $_POST['editors_quick_mail_privilege'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
         	if ( is_multisite() ) {
         		update_blog_option( $blog, 'editors_quick_mail_privilege', $current );
         	} else {
         		update_option( 'editors_quick_mail_privilege', $current );
         	} // end if multisite
            if ( ! $updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
               $updated = true;
            } // end if updated not displayed
         } // end if value changed

         $previous = '';
         if ( is_multisite() ) {
         	$previous = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
         } else {
         	$previous = get_option( 'verify_quick_mail_addresses', 'N' );
         } // end if multisite
         $current = empty( $_POST['verify_quick_mail_addresses'] ) ? 'N' : 'Y';
         if ( $current != $previous ) {
         	if ( is_multisite() ) {
         		update_blog_option( $blog, 'verify_quick_mail_addresses', $current );
         	} else {
         		update_option( 'verify_quick_mail_addresses', $current );
         	} // end if multisite
            
            if ( !$updated ) {
               echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
               $updated = true;
            } // end if updated not displayed
         } // end if value changed
      } // end if admin

      $user_query = new \WP_User_Query( array('count_total' => true) );
      $hide_admin = '';
      if ( is_multisite() ) {
      	$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
      } else {
      	$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
      } // end if multisite
      $total = 0;
      $names = 0;
      foreach ( $user_query->results as $user ) {
         if ( 'Y' == $hide_admin && $this->qm_is_admin( $user->ID, $blog ) ) {
            continue;
         } // end admin test

         $total++;
         $last = get_user_meta( $user->ID, 'last_name', true );
         $first = get_user_meta( $user->ID, 'first_name', true );
         if ( ! empty($first) && ! empty($last) ) {
            $names++;
         } // end if
      } // end for

      $check_all    = ( 'A' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $check_names  = ( 'N' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $check_none   = ( 'X' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $admin_option = '';
      $editor_option = '';
      $verify_option = '';
      if ( is_multisite() ) {
      	$admin_option = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
      	$editor_option = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
      	$verify_option = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
      } else {
      	$admin_option = get_option( 'hide_quick_mail_admin', 'N' );
      	$editor_option = get_option( 'editors_quick_mail_privilege', 'N' );
      	$verify_option = get_option( 'verify_quick_mail_addresses', 'N' );
      } // end if multisite
      $check_admin  = ( 'Y' == $admin_option ) ? 'checked="checked"' : '';
      $check_editor = ( 'Y' == $editor_option ) ? 'checked="checked"' : '';
      $check_verify = ( 'Y' == $verify_option ) ? 'checked="checked"' : '';
      $list_warning = '';
	  if ( 2 == $total && 'Y' == $admin_option && 'X' != $this->qm_get_display_option( $blog ) ) {
	  	$note = ' <strong>' . __( 'NOTE', 'quick-mail' ) . ' :</strong> ';
	  	$lw_top = __( 'Only administrators will see user list.', 'quick-mail' );
	  	$lw_bot = __( 'Editors need three non-admin users for sender, recipient, CC to access User List.', 'quick-mail' );
	  	$list_warning = $note . $lw_top . '<br>' . $lw_bot; // FIXME
      } // end if have list warning
      
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
      <tr><td id="qm_saved"></td></tr>
      <tr>
         <th id="recipients" class="recipients"><?php _e( 'User Display', 'quick-mail' ); ?></th>
      </tr>
      <?php if (!empty($list_warning)) : ?>
      <tr>
         <th id="qm-warning"><?php echo $list_warning; ?></th>
      </tr>
      <?php endif; ?>
      <?php if ( $this->multiple_matching_users( 'A', $blog ) ) : ?>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="A"
               <?php echo $check_all; ?>>
<?php 
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$total}</span>", $css);
_e( 'Show All Users', 'quick-mail' ); echo " ({$info})"; ?>
<br><?php _e( 'Show all users sorted by nickname.', 'quick-mail' );
$info = sprintf("<span class='%s'>{$total}</span>", $css);
echo ' ', $info, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
      </tr>
      <?php endif; ?>
	  <?php if ( $this->multiple_matching_users( 'N', $blog ) ) : ?>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="N"
               <?php echo $check_names; ?>>
<?php
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$names}</span>", $css);
_e( 'Show Users with Names', 'quick-mail' ); echo " ({$info})"; ?>
<br><?php _e( 'Show users with names, sorted by last name.', 'quick-mail' );
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$names}</span>", $css);
echo ' ', $info, ' ', __( 'matching users', 'quick-mail' );
?>
.</label></td>
      </tr>
      <?php endif; ?>
      <tr>
         <td><label><input name="show_quick_mail_users" type="radio" value="X"
               <?php echo $check_none; 
               if (! $this->multiple_matching_users( 'A', $blog ) ) {
               	echo ' onclick="return false" '; }
               ?>>
               <?php _e( 'Do Not Show Users', 'quick-mail' ); ?>
<?php 
if (! $this->multiple_matching_users( 'A', $blog ) ) {
	echo '<br><br>';
	if ( $this->qm_is_admin( get_current_user_id(), $blog ) ) {
		_e( 'Need three users to display User List for sender, recipient, CC.', 'quick-mail' );
	} else {
		_e( 'User List was disabled by system administrator.', 'quick-mail' );
	} // end if admin
	echo '<br>';
} // end if one user
?>
<br><?php _e( 'Enter address to send mail.', 'quick-mail' ); ?> <?php _e( 'Saves 12 addresses.', 'quick-mail' ); ?></label></td>
      </tr>
<?php  
if ( $this->qm_is_admin( get_current_user_id(), $blog ) ) : ?> 
   <tr>
      <th class="recipients"><?php _e( 'Administration', 'quick-mail' ); ?></th>
   </tr>
   <tr>
      <td><label><input name="hide_quick_mail_admin" type="checkbox" <?php echo $check_admin; ?>>
   <?php _e( 'Hide Administrator Profiles', 'quick-mail' ); ?>
   <br><?php
   $admins = $this->qm_admin_count( $blog );
   $profile = sprintf( _n( '%s administrator profile', '%s administrator profiles', $admins, 'quick-mail' ), $admins );
   echo sprintf('%s %s', __( 'User list will not include', 'quick-mail' ), " {$profile}.");
?>
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
<?php _e( 'Verify recipient email domains', 'quick-mail' ); ?>
<br><?php
echo $verify_note;
?>
</label></td>
      </tr>
<?php endif; ?>
      <tr>
         <td><input type="submit" name="qm-submit" class="button button-primary"
            value="<?php _e( 'Save Options', 'quick-mail' ); ?>"></td>
      </tr>
   </table>
</form>
<?php
   } // end quick_mail_options

   /**
    * get user option. return default if not found. replaces qm_get_option
    *
	* @param int $blog Blog ID or zero if not multisite
    * @return string Option value or adjusted default
    * @since 1.4.0
    */
   public function qm_get_display_option( $blog ) {
      global $current_user;
      $value = get_user_meta( $current_user->ID, 'show_quick_mail_users', true );
      $retval = ( ! empty($value) ) ? $value : 'A'; // should never be empty
      return $this->multiple_matching_users( $retval, $blog ) ? $retval : 'X';
   } // end qm_get_display_option

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
    * @param int $id User ID
    * @param int $blog Blog ID or zero if not multisite
    * @return boolean whether user is an administrator on blog
    */
	protected function qm_is_admin( $id, $blog ) {
		if ($blog == 0) {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'include' => array($id), 'count_total' => true ) );
		} else {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'include' => array($id), 'count_total' => true, 'blog_id' => $blog ) );
		} // end if not multisite

		return (0 < $user_query->get_total());		
	} // end qm_is_admin

	/**
	 * Is user an administrator?
	 *
	 * @param int $id User ID
	 * @param int $blog Blog ID or zero if not multisite
	 * @return boolean whether user is an editor on blog
	 */
	protected function qm_is_editor( $id, $blog ) {
		if ($blog == 0) {
			$user_query = new WP_User_Query( array( 'role' => 'Editor',
					'include' => array($id), 'count_total' => true ) );
		} else {
			$user_query = new WP_User_Query( array( 'role' => 'Editor',
					'include' => array($id), 'count_total' => true, 'blog_id' => $blog ) );
		} // end if not multisite
	
		return (0 < $user_query->get_total());
	} // end qm_is_admin
	
	/**
	 * get total users with administrator role on a blog
	 * 
	 * @param int $blog Blog ID or zero if not multisite
	 * @return int total
	 * @since 2.0.0
	 */
	protected function qm_admin_count( $blog ) {
		if ($blog == 0) {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'count_total' => true ) );
		} else {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'count_total' => true, 'blog_id' => $blog ) );
		} // end if
	
		return $user_query->get_total();
	} // end qm_admin_count

   /**
    * used with quick_mail_setup_capability filter, to let editors see user list
    *
    */
	public function let_editor_set_quick_mail_option( $role ) {
		$editors = 'N';
		if ( is_multisite() ) {
			$editors = get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' );
		} else {
			$editors = get_option( 'editors_quick_mail_privilege', 'N' );
		} // end if multisite
		return ('Y' == $editors) ? 'edit_others_posts' : $role;
   } // end let_editor_set_quick_mail_option

	/**
    * init admin menu for appropriate users
    */
	public function init_quick_mail_menu() {
		$title = __( 'Quick Mail', 'quick-mail' );
		$page = add_submenu_page( 'tools.php', $title, $title,
		apply_filters( 'quick_mail_user_capability', 'publish_posts' ), 'quick_mail_form', array($this, 'quick_mail_form') );
		add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
		if ( $this->want_options_menu() ) {
			$page = add_options_page( 'Quick Mail Options', $title, apply_filters( 'quick_mail_setup_capability', 'list_users' ), 'quick_mail_options', array($this, 'quick_mail_options') );
			add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
			add_action('load-' . $page, array($this, 'add_qm_settings_help'));
		} // end if displaying options menu
   } // end init_quick_mail_menu

	/**
    * Quick Mail settings help
    * @since 2.0.0
    */
	public function add_qm_settings_help() {
   		$blog = is_multisite() ? get_current_blog_id() : 0;
		$screen = get_current_screen();
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if
		
		$is_admin_user = $this->qm_is_admin( get_current_user_id(), $blog );
		$is_editor_user = $this->qm_is_editor( get_current_user_id(), $blog );
		$user_query = new \WP_User_Query( array('count_total' => true) );
    		$users = $user_query->get_total();
    		$has_all = ( 'A' == $this->multiple_matching_users( 'A', $blog ) );
    		$has_names = ( 'N' == $this->multiple_matching_users( 'N', $blog ) );
    		$content = '';
    		$note = '<strong>' . __( 'NOTE', 'quick-mail' ) . ' :</strong> ';
    		$people = ' ' . __( 'Sender, recipient, CC.', 'quick-mail' );
    		$editors = 'N';
    		if ( is_multisite() ) {
    			if ( 'Y' == get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' ) ) {
    				$editors = 'Y';
    			}
    		} else {
    			if ( 'Y' == get_option( 'editors_quick_mail_privilege', 'N' ) ) {
    				$editors = 'Y';
    			}
    		} // end if multisite
 
    		$content = '';
    		$not_editor_or_admin = !$is_admin_user && !$is_editor_user;
    		if ( ( !$is_admin_user && !$is_editor_user ) || ( 'N' == $editors && !$is_admin_user ) ) {
    			if ( is_multisite() ) {
    				$content .= '<p>' . $note . __( 'You do not have sufficient privileges to access user lists on this site.' ) . '.</p>';
    			} else {
    				$content .= '<p>' . $note . __( 'You do not have sufficient privileges to access user lists.' ) . '.</p>';
    			}
    		} else if ( 'Y' == $hide_admin ) {
    			$content = '<p>' . __( 'User totals are adjusted because administrator profiles are hidden', 'quick-mail' ) . '.</p>';    			
    		} // end if
    		
    		if ( !$has_all ) {
    			if ( 'Y' == $hide_admin ) {
    				$content .= '<p>' . $note . __( 'Three non-administrator profiles are required for user lists.', 'quick-mail' ) . $people . '.</p>';
    			} else {
    				$content .= '<p>' . $note . __( 'Three user profiles are required for user lists.' ) . $people . '.</p>';
    			} // end if less than 3
    		} // end if 'A' not possible
    		
    		$screen->add_help_tab( self::get_qm_help_tab() );
    		$content .= '<dl>';
    		if ( $has_all ) {
    			$content .= '<dt><strong>' . __( 'Show All Users', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Select users by WordPress nickname', 'quick-mail' ) . '.</dd>';
    		}
    		if ( $has_names ) {
    			$content .= '<dt><strong>' . __( 'Show Users with Names', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Select users with first and last names', 'quick-mail' ) . '.</dd>';
    		}
    		$content .= '<dt><strong>' . __( 'Do Not Show Users', 'quick-mail' ) . '</strong></dt>';
    		$content .= '<dd>' . __( 'Enter user addresses. 12 addresses are saved', 'quick-mail' ) . '.</dd>';
    		$content .= '</dl>';
    		
    $screen->add_help_tab( array('id' => 'qm_display_help',
        'title'	=> __('User Display', 'quick-mail'), 'content' => $content) );
    
    		if ( $is_admin_user ) {
    			$title =  __('Administration', 'quick-mail');
    			$content = '<dl><dt><strong>' . __( 'Hide Administrator Profiles', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Prevent users from sending email to administrators', 'quick-mail' ) . '.</dd>';
    			$content .= '<dt><strong>' . __( 'Grant Editors access to user list', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __(  'Otherwise only administrators can view the user list', 'quick-mail' ) . '</dd>';
    			$content .= '<dt><strong>' . __( 'Verify recipient email domains', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Check if recipient domain accepts email. Detects typos.', 'quick-mail' ) . '.</dd></dl>';
    			$screen->add_help_tab( array('id'	=> 'qm_admin_display_help',
    					'title'	=> __('Administration', 'quick-mail'), 'content' => $content) );
    		} // end if
	} // add_qm_settings_help

	/**
	 * Quick Mail general help
	 * @since 2.0.0
	 */
	public function add_qm_help() {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		$screen = get_current_screen();
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if
		$blog = is_multisite() ? get_current_blog_id() : 0;
		$display_option = $this->qm_get_display_option( $blog );
		$cc_title = __( 'Adding CC', 'quick-mail' );
		$xhelp = __( 'Enter multiple addresses by separating them with a space or comma.', 'quick-mail' );
		$mac_names = $nhelp = __( 'Press &lt;Command&gt; while clicking, to select multiple users.', 'quick-mail' );
		$win_names = __( 'Press &lt;Control&gt; while clicking, to select multiple users.', 'quick-mail' );
		$mob_names = __( 'You can select multiple users', 'quick-mail' );
		$nhelp ='';
		if (wp_is_mobile()) {
			$nhelp = $mob_names;
		} else {
			$b = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
			if ( preg_match( '/macintosh|mac os x/i', $b ) ) {
				$nhelp = $mac_names;
			} else {
				$nhelp = $win_names;
			} // end if
		} // end if
		$cc_help = ($display_option == 'X') ? $xhelp : $nhelp;
		$attachment_title = __( 'Attachments', 'quick-mail' );
		$attachment_help = '';
		$pattern = '/(OS 5_.+like Mac OS X)/';
		$can_upload = strtolower( ini_get( 'file_uploads' ) );
		if ( '1' != $can_upload && 'true' != $can_upload && 'on' != $can_upload ) {
			$attachment_help = __( 'File uploads were disabled by system administrator', 'quick-mail' );
		} else if ( !empty( $_SERVER['HTTP_USER_AGENT'] ) && 1 == preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) ) {
			$attachment_help = __( 'File uploads are not available on your device', 'quick-mail' );
		} else {
			$attachment_help = __( 'You can attach multiple files to your message', 'quick-mail' );
			if ( !wp_is_mobile() ) {
				$attachment_help .= ' ' . __( 'from up to six directories', 'quick-mail' );
			} // end if mobile
			$attachment_help .= '.';
		} // end if uploads
		$screen->add_help_tab( self::get_qm_help_tab() );
		$screen->add_help_tab( array(
				'id'	=> 'qm_cc_help_tab',
				'title'	=> $cc_title,
				'content'	=> "<p>{$cc_help}</p>"));
		$screen->add_help_tab( array('id' => 'qm_attach_help_tab',
				'title'	=> $attachment_title,
				'content'	=> "<p>{$attachment_help}</p>") );
	} // add_qm_help
	
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
   	  load_plugin_textdomain( 'quick-mail', false, basename( dirname( __FILE__ ) ) . '/lang' );
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
			if ( !is_multisite() && $this->want_options_menu() ) {
				$links[] = '<a href="options-general.php?page=quick_mail_options">' . __( 'Settings', 'quick-mail' ) . '</a>';
			} // get settings from menu on multisite or hide if N/A
         $links[] = '<a href="https://wordpress.org/plugins/quick-mail/faq/" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
         $links[] = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
      } // end if adding links
      return $links;
   } // end qm_plugin_links
   
} // end class
$quick_mail_plugin = QuickMail::get_instance();
