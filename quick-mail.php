<?php
/*
Plugin Name: Quick Mail
Description: Send text or html email with attachments from user's credentials. Select recipient from users or commenters.
Version: 3.2.1
Author: Mitchell D. Miller
Author URI: https://wheredidmybraingo.com/
Plugin URI: https://wheredidmybraingo.com/how-to-send-private-comment-replies-with-wordpress/
Text Domain: quick-mail
Domain Path: /lang
*/

require_once 'inc/qm_util.php';

// Load our WP-CLI command, if available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/quick-mail-cli.php';
}

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
    * Our dismissed pointer name.
    * @var string
    * @since 1.3.0
    */
   public static $pointer_name = 'quickmail_320';

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
    * create object. add actions.
    *
    * @since 1.2.0
    */
	public function __construct() {
	   	/**
	   	 * if not called by WordPress, exit without error message.
	   	 * @since 1.2.5
	   	 */
		if ( ! function_exists( 'register_activation_hook' ) ) {
	   		exit;
	   	}
	   	register_activation_hook( __FILE__, array($this, 'check_wp_version') );
	   	add_action( 'admin_init', array($this, 'add_email_scripts') );
	   	add_action( 'admin_menu', array($this, 'init_quick_mail_menu') );
	   	add_action( 'plugins_loaded', array($this, 'init_quick_mail_translation') );
	   	add_filter( 'comment_notification_text', array($this, 'qm_comment_reply'), 10, 2 );
	   	add_filter( 'comment_row_actions', array($this, 'qm_filter_comment_link'), 10, 2 );
	   	add_action( 'activated_plugin', array($this, 'install_quick_mail'), 10, 0);
	   	add_action( 'deactivated_plugin', array($this, 'unload_quick_mail_plugin'), 10, 0 );
	   	add_action( 'wp_ajax_qm_get_comment', array($this, 'qm_get_comment') );
	   	add_action( 'admin_footer', array($this, 'qm_get_comment_script') );
	   	add_action( 'wp_ajax_qm_get_title', array($this, 'qm_get_title') );
	   	add_action( 'admin_footer', array($this, 'qm_get_title_script') );
	   	add_filter( 'plugin_row_meta', array($this, 'qm_plugin_links'), 10, 2 );
	   	add_filter( 'plugin_action_links', array($this, 'qm_action_links'), 10, 2 );
	   	add_filter( 'quick_mail_setup_capability', array($this, 'let_editor_set_quick_mail_option') );

	   	// let_user_replace_sender
	   	add_action( 'init', array($this, 'let_user_replace_sender'), 10, 0 );
	   	add_action( 'load-tools_page_quick_mail_form', array( $this, 'add_qm_help' ), 20, 0 );
	   	add_action( 'plugins_loaded', array($this, 'show_qm_pointer' ), 10, 0 );
   } // end constructor

   /**
    * Get info for basic help tab.
    	* @return array args for WP_Screen::add_help_tab(array $args)
    */
	public static function get_qm_help_tab() {
		$qm_desc =  __( 'Quick Mail is the easiest way to send email with attachments to WordPress users on your site, or send private replies to comments.', 'quick-mail' );
		$english_faq = __('https://wordpress.org/plugins/quick-mail/faq/', 'quick-mail');
		$faq = __( 'FAQ', 'quick-mail' );
		$flink = '<a href="https://wordpress.org/plugins/quick-mail/faq/" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
		$slink = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
		$rlink = '<a href="https://wordpress.org/support/plugin/quick-mail/reviews/" target="_blank">' . __( 'Please leave a review', 'quick-mail' ) . '</a>';
		$others = __( 'to help others find Quick Mail', 'quick-mail' );
		$questions = __( 'Resources', 'quick-mail' );
		$more_info = __( 'has more information', 'quick-mail' );
		$use_str = __( 'Please use', 'quick-mail' );
		$to_ask = __( 'to ask questions and report problems', 'quick-mail' );
		$help_others = __( 'Help Others', 'quick-mail' );
		$qm_top = "<p>{$qm_desc}</p><h4>{$questions}</h4><ul><li>{$flink} {$more_info}</li><li>{$use_str} {$slink} {$to_ask}</li></ul>";
		$qm_bot = "<h4>{$help_others}</h4><ul><li>{$rlink} {$others}</li></ul>";
		$qm_content = $qm_top . $qm_bot;
		return array('id' => 'qm_intro', 'title'	=> __('Quick Mail', 'quick-mail'), 'content' => $qm_content);
	} // end get_qm_help_tab

	/**
	 * get help for comment reply.
	 * @since 3.1.3
	 * @return array args for WP_Screen::add_help_tab(array $args)
	 */
	public static function get_qm_comment_help_tab() {
		$qm_desc =  __( 'Send private replies to comments.', 'quick-mail' );
		$qm_how =  __( 'Select a commenter to send a message.', 'quick-mail' );
		$qm_info = __( 'Subject and message are automatically added.', 'quick-mail' );
		$slink = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
		$use_str = __( 'Please use', 'quick-mail' );
		$to_ask = __( 'to ask questions and report problems', 'quick-mail' );
		$rc5 = "<dd style='font-weight:bold; margin-top:2em;'>{$use_str} {$slink} {$to_ask}.</dd>";
		$qm_content = "<dl><dt style='font-weight:bold; margin-bottom:1em;'>{$qm_desc}</dt><dd>{$qm_how}</dd><dd>{$qm_info}</dd>{$rc5}</dl>";
		return array('id' => 'qm_chelp', 'title'	=> __('Reply to Comments', 'quick-mail'), 'content' => $qm_content);
	} // end get_qm_comment_help_tab

	/**
	 * get user role.
	 * @return string lowercase role. author, editor, administrator, n/a
	 * @since 3.1.0
	 */
	public function qm_get_role() {
		if ( current_user_can( 'activate_plugins' ) ) {
			return 'administrator';
		}

		if ( current_user_can( 'delete_others_pages' ) ) {
			return 'editor';
		}

		if ( current_user_can( 'publish_posts' ) ) {
			return 'author';
		}

		return 'n/a';
	} // end qm_get_role

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
		$urole = $this->qm_get_role();
		if ( 'author' == $urole ) {
			return ( 'X' == $code );
		} // author can only reply to comments

		if ( 'editor' == $urole ) {
			$editors = '';
			if ( is_multisite() ) {
				$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
			} else {
				$editors = get_option( 'editors_quick_mail_privilege', 'N' );
			} // end if multisite

			if ( 'Y' != $editors ) {
				return ( 'X' == $code );
			} // end if editors not allowed to see list
		} // end if editor

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
    * content type filter for wp_mail.
    *
    * filters wp_mail_content_type.
    *
    * @see wp_mail
    * @param string $type
    * @return string
    */
   public function get_mail_content_type($type)
   {
      return $this->content_type;
   } // end get_mail_content_type

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
    * displays wp_mail error message.
    *
    * @param WP_Error $e
    * @since 1.3.0
    */
   public function show_mail_failure($e) {
      if ( is_wp_error( $e ) ) {
         $direction = is_rtl() ? 'rtl' : 'ltr';
         $args = array( 'response' => 200, 'back_link' => true, 'text_direction' => $direction );
         wp_die( sprintf( '<h1 role="alert">%s</h1>', $e->get_error_message() ), __( 'Mail Error', 'quick-mail' ), $args );
      } // end if error
   } // end show_mail_failure

   /**
    * Check for minimum WordPress version before installation.
    * Note: `quick_mail_version` filter was removed in 1.3.0.
    *
    * @link http://wheredidmybraingo.com/quick-mail-1-3-0-supports-international-mail/#minversion
    *
    * @since 1.2.3
    */
   public function check_wp_version()
   {
      global $wp_version;
      if ( version_compare( $wp_version, '4.6', 'lt' ) )
      {
         deactivate_plugins( basename( __FILE__ ) );
         echo sprintf("<div class='notice notice-error' role='alert'>%s</div>", __( 'Quick Mail requires WordPress 4.6 or greater.', 'quick-mail' ) );
         exit;
      } // end if
   } // end check_wp_version

   /**
    * add options when Quick Mail is activated.
    *
    * add options, do not autoload them.
    *
    * @since 1.2.0
    */
	public function install_quick_mail() {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		$qm_options = array('replace_quick_mail_sender', 'hide_quick_mail_admin', 'quick_mail_cannot_reply', 'authors_quick_mail_privilege', 'editors_quick_mail_privilege', 'verify_quick_mail_addresses');
		foreach ($qm_options as $option) {
			if ( is_multisite() ) {
				add_blog_option( $blog, $option, 'N' );
			} else {
				add_option( $option, 'N', '', 'no' );
			} // end if multisite
		} // end foreach

      /**
       * Do not show users if one user. Do not apply wpautop by default.
       */
		$code = $this->multiple_matching_users( 'A', $blog ) ? 'A' : 'X';
      	$this->qm_update_option( 'show_quick_mail_users', $code );
      	$this->qm_update_option( 'qm_wpautop', '0' ); // TODO this should be Y/N like others
      	$this->qm_update_option( 'show_quick_mail_commenters', 'N');
   } // install_quick_mail

   /**
    * load Javascript to display wp_pointer after installation.
    *
    * @since 1.3.0
    */
   public function quick_mail_pointer_scripts() {
      $greeting = __( 'Welcome to Quick Mail', 'quick-mail' );
      $suggestion = __( 'Please verify your settings before using Quick Mail.', 'quick-mail' );
	  $pointer_content = "<h3>{$greeting}</h3><p role='alert'>{$suggestion}</p>";
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function() {
    jQuery('#menu-settings').pointer({
      content: "<?php echo $pointer_content; ?>",
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
    * setup wp_pointer for new installations.
    *
    * @since 1.3.0
    */
   public function qm_pointer_setup() {
      wp_enqueue_style ( 'wp-pointer' );
      wp_enqueue_script ( 'wp-pointer' );
      add_action ( 'admin_print_footer_scripts', array ($this, 'quick_mail_pointer_scripts') );
   } // end qm_pointer_setup

   /**
    * delete options when Quick Mail is deactivated.
    *
    * delete global and user options.
    *
    * @since 1.1.1
    */
	public function unload_quick_mail_plugin() {
		delete_metadata( 'user', 1, 'show_quick_mail_users', '', true );
		delete_metadata( 'user', 1, 'show_quick_mail_commenters', '', true );
		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ($sites as $site) {
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
	} // end unload_quick_mail_plugin

   /**
    * load quick-mail.js for email select and quick-mail-addresses.js to count saved addresses.
    *
    * @since 1.2.0
    */
   public function add_email_scripts()
   {
      wp_enqueue_script( 'qmScript', plugins_url('/lib/js/quick-mail.js', __FILE__), array('jquery'), null, false );
      wp_enqueue_script( 'qmCount', plugins_url('/lib/js/quick-mail-addresses.js', __FILE__), array('jquery'), null, false );
      $data = array(
      		'one' => __( 'Clear 1 saved address', 'quick-mail' ),
      		'many' => sprintf( __( 'Clear %s saved addresses', 'quick-mail' ), '{number}' )
      );
      wp_localize_script( 'qmCount', 'quick_mail_saved', $data );
   } // end add_email_scripts

   /**
    * create and display recipient input. user list or text input.
    *
    * @param string $to recipient email
    * @param int $id user ID
    */
	public function quick_mail_recipient_input( $to, $id ) {
      $template = '<input aria-labelledby="qme_label" value="%s" id="qm-email" name="qm-email" type="email" required aria-required="true" tabindex="0" autofocus size="35" placeholder="%s">';
      $blog = is_multisite() ? get_current_blog_id() : 0;
      $option = $this->qm_get_display_option( $blog );
      $you = wp_get_current_user(); // from
      if ( 'author' == $this->qm_get_role() ) {
      	$option = 'X';
      } // end if author

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
         echo sprintf( $template, $to, __( 'Enter mail address', 'quick-mail' ) );
         return;
      }
      $hide_admin = '';
      if ( is_multisite() ) {
      	$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
      } else {
      	$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
      } // end if

      $args = ( 'Y' == $hide_admin )
      ? array('role__not_in' => 'Administrator', 'exclude' => array($you->ID))
      : array('exclude' => array($you->ID));

      $user_query = new \WP_User_Query( $args );
      $users = array();
      foreach ( $user_query->results as $user ) {
         if ( $user->user_email == $you->user_email ) {
			continue;
         } // end duplicate email test

         if ( 'A' == $option ) {
         	$nickname = ucfirst( get_user_meta( $user->ID, 'nickname', true ) );
         	$users[] = "{$nickname}\t{$user->user_email}";
         } // end if all users
         else {
            $last = ucfirst( get_user_meta( $user->ID, 'last_name', true ) );
            $first = ucfirst( get_user_meta( $user->ID, 'first_name', true ) );
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
      echo '<select aria-labelledby="qme_label" name="qm-email" id="qm-primary" required aria-required="true" size="1" tabindex="0" autofocus onchange="return is_qm_email_dup()"><option class="qmopt" value="" selected>Select</option>';
      for ( $i = 0; $i < $j; $i++ ) {
         $row = explode( "\t", $users[$i] );
         if ($option == 'A') {
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

   /**
    * get input control for cc input.
    * @param string $to recipient
    * @param string $cc cc
    * @param integer $id user ID
    * @return void|string
    */
	public function quick_mail_cc_input( $to, $cc, $id ) {
	   	$template = '<input aria-labelledby="qmcc_label" value="%s" id="qm-cc" name="qm-cc" type="text" size="35" tabindex="3" placeholder="%s">';
	   	$blog = is_multisite() ? get_current_blog_id() : 0;
	   	$option = $this->qm_get_display_option( $blog );
	   	if ( !$this->multiple_matching_users( $option, $blog ) ) {
	   		$option = 'X';
	   	} // end if since 1.4.0
	   	$you = wp_get_current_user();
	   	if ( 'author' == $this->qm_get_role() ) {
	   		$option = 'X';
	   	} // end if author

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
	   	$hide_admin = '';
	   	if ( is_multisite() ) {
	   		$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
	   	} else {
	   		$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
	   	} // end if

	   	$args = ('Y' == $hide_admin)
	   	? array('role__not_in' => 'Administrator', 'exclude' => array($you->ID))
	   	: array('exclude' => array($you->ID));

	   	$user_query = new \WP_User_Query( $args );
	   	$users = array();
	   	foreach ( $user_query->results as $user ) {
	   		if ( $user->user_email == $you->user_email ) {
	   			continue;
	   		} // end if duplicate email

	   		if ( 'A' == $option ) {
	   			$nickname = ucfirst( get_user_meta( $user->ID, 'nickname', true ) );
	   			$users[] = "{$nickname}\t{$user->user_email}";
	   		} // end if all users
	   		else {
	   			$last = ucfirst( get_user_meta( $user->ID, 'last_name', true ) );
	   			$first = ucfirst( get_user_meta( $user->ID, 'first_name', true ) );
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
	   	echo '<select aria-labelledby="qmcc_label" name="qm-cc[]" id="qm-secondary" multiple size="6" tabindex="3" onchange="return is_qm_email_dup()"><option class="qmopt" value="" selected>Select</option>';
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
    * get list of commenters from posts / pages with comments open.
    * @return string select with commenters instead of users. WP_Error if no commenters.
    * @since 3.0.5
    */
	public function get_commenters() {
		$problem = new WP_Error( 'no_comments', __( 'No comments for you.', 'quick-mail' ) );
		$you = wp_get_current_user();
		// TODO 'date_query' => $dquery
		$args = array('orderby' => 'comment_author', 'order' => 'ASC', 'post_author' => get_current_user_id(),
				'post_status' => 'publish', 'status' => 'approve', 'count' => false);
	   	$cquery = get_comments( $args );
	   	if (empty( $cquery ) ) {
	   		return $problem;
	   	} // end if no recent comments

	   	$select = '<select aria-labelledby="qme_label" name="qm-email" id="qm-primary" required aria-required="true" size="1" tabindex="0" autofocus onchange="return qm_get_comment()"><option class="qmopt" value="" selected>Select</option>';
	   	$matches = 0;
	   	foreach ( $cquery as $comment ) {
	   		if ( empty( $comment->comment_author ) || empty( $comment->comment_author_email ) ) {
	   			continue;
	   		}
	   		if ( $comment->comment_author_email == $you->data->user_email ) {
	   			continue;
	   		}

	   		if ( !comments_open( $comment->comment_post_ID ) ) {
	   			continue;
	   		}

	   		if ( !QuickMailUtil::qm_valid_email_domain( $comment->comment_author_email, 'Y' ) ) {
	   			continue;
	   		} // end if invalid author email

	   		$attributes = "data-pid={$comment->comment_post_ID} data-cid={$comment->comment_ID}";
	   		$title = get_the_title( $comment->comment_post_ID );

	   		// extend visible title on desktop 3.1.1
	   		$maxlen = wp_is_mobile() ? 45 : 120;
	   		if ( $maxlen < mb_strlen( $title, 'UTF-8' ) ) {
	   			$title = mb_substr( $title, 0, $maxlen -1, 'UTF-8' ) . '&hellip;';
	   		} // end if long title
	   		$address = urlencode( "\"{$comment->comment_author}\" <{$comment->comment_author_email}>" );
	   		$select .= "\r\n<option {$attributes} value='{$address}' class='qmopt'>{$comment->comment_author} &nbsp; ({$title})</option>";
	   		$matches++;
	   	} // end foreach
	   	$select .= '</select>';
	   	return ($matches > 0) ? $select : $problem;
   } // end get_commenters

   /**
    * get comment title from Javascript.
    */
	public function qm_get_title() {
		check_ajax_referer( 'qm_get_title', 'security' );
   		$pid = intval( $_POST['pid'] );
   		$title = get_the_title( $pid );
   		echo htmlspecialchars( $title, ENT_QUOTES );
   		wp_die();
	} // end qm_get_title

	/**
	 * Javascript to load comment title into subject.
	 */
	public function qm_get_title_script() {
		$ajax_nonce = wp_create_nonce( 'qm_get_title' );
		?>
		<script type="text/javascript">
		function qm_get_title() {
			var security = '<?php echo $ajax_nonce; ?>';
			var pid = jQuery('#qm-primary').find('option:selected').data('pid');
			var jqxhr = jQuery.post( ajaxurl, {
				action: 'qm_get_title', pid: pid, security: security } );
				jqxhr.always(function( response ) {
					jQuery('#qm-subject').val(response);
					return true;
			  	});
		}
		</script>
		<?php
	} // end qm_get_title_script

   	// four functions to load comment in textarea
	/**
	 * get CSS for comment in textarea. can be filtered with quick_mail_comment_style.
	 * @return string CSS
	 * @since 3.1.1
	 */
   	public function get_comment_style() {
   		$direction = is_rtl() ? 'right' : 'left';
   		$css = "style='margin-bottom:2em; margin-{$direction}:2em; padding-{$direction}:2em; border-{$direction}:2px solid #999;'";
   		return apply_filters( 'quick_mail_comment_style', $css );
   	} // end get_comment_style

   	/**
   	 * format comment reply for textarea.
   	 * @param string $text comment text
   	 * @return string formatted comment
   	 * @since 3.1.1
   	 */
	public function get_formatted_comment( $text ) {
		if ( user_can_richedit() ) {
			$direction = is_rtl() ? 'right' : 'left';
			$css = $this->get_comment_style();
			return "<div {$css}>{$text}</div><br>";
		} // end if rich editor
		if ( is_rtl() ) {
			return $text . ' <' . "\r\n______________\r\n";
		} else {
			return '> ' . $text . "\r\n______________\r\n";
		} // end if
	} // end get_formatted_comment

	/**
	 * get comment text from Javascript.
	 */
   	public function qm_get_comment() {
   		check_ajax_referer( 'qm_get_comment', 'security' );
   		$cid = intval( $_POST['cid'] );
   		$text = get_comment_text( $cid );
   		echo $this->get_formatted_comment( $text );
		wp_die();
   	} // end qm_get_comment

   	/**
   	 * get Javascript to load comment and move cursor to end of textarea or TinyMCE.
   	 */
	public function qm_get_comment_script() {
		$ajax_nonce = wp_create_nonce( 'qm_get_comment' );
	?>
		<script type="text/javascript">
		// modified: https://davidwalsh.name/caret-end
		function move_cursor_to_end_of_textarea() {
			var el = document.getElementById('quickmailmessage');
		    if (typeof el.selectionStart == "number") {
		        el.selectionStart = el.selectionEnd = el.value.length;
		    } else if (typeof el.createTextRange != "undefined") {
		        el.focus();
		        var range = el.createTextRange();
		        range.collapse(false);
		        range.select();
		    } else if (typeof el.createRange != "undefined") {
		        el.focus();
		        var range = el.createRange();
		        range.collapse(false);
		        range.select();
		    } // end if
		    el.focus();
		    return true;
		} // end move_cursor_to_end_of_textarea

		// find tmce from: https://gist.github.com/RadGH/523bed274f307830752c
		function tmce_set_content(content) {
			if ( jQuery('#wp-quickmailmessage-wrap').hasClass('tmce-active') ) {
				tinyMCE.get('quickmailmessage').setContent('');
			    tinyMCE.get('quickmailmessage').setContent(content);
			    tinyMCE.get('quickmailmessage').focus();
			    // from: https://stackoverflow.com/questions/19829126/tinymce-4-how-to-put-cursor-to-end-of-the-text
			    tinyMCE.activeEditor.selection.select(tinyMCE.activeEditor.getBody(), true);
			    tinyMCE.activeEditor.selection.collapse(false);
			  } else {
			    jQuery('#quickmailmessage').val(content);
			    move_cursor_to_end_of_textarea();
			  } // end if tmce is active
			} // end tmce_set_content

		function qm_get_comment() {
			var security = '<?php echo $ajax_nonce; ?>';
			var cid = jQuery('#qm-primary').find('option:selected').data('cid');
			qm_get_title();
			var jqxhr = jQuery.post( ajaxurl, {
				action: 'qm_get_comment', cid: cid, security: security });
				jqxhr.always(function( response ) {
				if (response == '0') {
					console.log('qm_get_comment ACTION NOT FOUND');
					return false;
				} else if (response == '') {
					console.log('qm_get_comment COMMENT NOT FOUND');
					return false;
				} // end if invalid response

				if (response.charAt(0) != '<') {
					jQuery('#quickmailmessage').html(response);
					move_cursor_to_end_of_textarea();
				} else {
					tmce_set_content(response);
				} // end if text or HTML
				return true;
			  });
		} // end qm_get_comment
		</script>
<?php
   	} // end qm_get_comment_script

   /**
    * display data entry form to enter recipient, cc, subject, message.
    *
    * alternate form if replying to comment.
    *
    */
	public function quick_mail_form() {
		$commenter = '';
		$verify = '';
		$error = '';
		$to = '';
		$subject = '';
		$message = '';
		$raw_msg = '';

		$blog = is_multisite() ? get_current_blog_id() : 0;
		if ( is_multisite() ) {
			$verify = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify = get_option( 'verify_quick_mail_addresses', 'N' );
		}
		if ( 'Y' == $verify && 'X' != $this->qm_get_display_option( $blog ) ) {
			$verify = 'N';
		} // end if verify disabled, because not displaying user list.

		$you = wp_get_current_user();
		if ( !empty( $_REQUEST['comment_id'] ) ) {
			if ( !$this->user_can_reply_to_comments( true ) ) {
				$direction = is_rtl() ? 'rtl' : 'ltr';
				$args = array('response' => 200, 'back_link' => true, 'text_direction' => $direction);
				wp_die( sprintf( '<h1 role="alert">%s</h1>', __( 'Comments disabled by system administrator.', 'quick-mail' ) ), __( 'Mail Error', 'quick-mail' ), $args );
			} // end if check site has permission to reply

			$id = intval( $_REQUEST['comment_id'] );
			$info = get_comment( $id, ARRAY_A );
			$name = empty($info['comment_author']) ? __( 'You', 'quick-mail' ) : 	$info['comment_author'];
			$text = $info['comment_content'];
			$said = __( 'said', 'quick-mail' );
			$raw_msg = $this->get_formatted_comment( $text );
			$tmessage = "{$name} {$said}: {$text}\r\n\r\n";
			if ( !empty( $info['comment_author'] ) && !empty( $info['comment_author_email'] ) ) {
				$commenter = "\"{$info['comment_author']}\" <{$info['comment_author_email']}>";
				$to = $info['comment_author_email'];
   				$title = get_the_title( $info['comment_post_ID'] );
   				$maxlen = wp_is_mobile() ? 45 : 120;
   				if ( $maxlen < mb_strlen( $title, 'UTF-8' ) ) {
   					$subject = mb_substr( $title, 0, $maxlen -1, 'UTF-8' ) . '&hellip;';
   				} else {
   					$subject = $title;
   				} // end if long title
				if ( user_can_richedit() ) {
					if ( '1' == get_user_meta( $you->ID, 'qm_wpautop', true ) ) {
						$raw_msg = wpautop( $raw_msg );
					} // end if add paragraphs
				} // end if rich edit allowed
				if ( !QuickMailUtil::qm_valid_email_domain( $to, 'Y' ) ) {
					$error = __( 'Cannot reply. Invalid mail address.', 'quick-mail' ) . '<br>' . htmlspecialchars( $to );
				}
			} // end if got comment name and author
		} // end if replying to comment

	  $all_cc = array();
      $data = array();
      $domain = '';
      $file = '';
      $mcc = '';
      $no_uploads = '';
      $success = '';
      $from = '';
      $attachments = array();
      $commenter_list = (empty( $commenter ) && $this->user_can_reply_to_comments( false ) ) ? $this->get_commenters() : null;
      if ( is_wp_error( $commenter_list ) ) {
      	$error = $commenter_list->get_error_message();
      } elseif ( is_string( $commenter_list ) ) {
      	$commenter = 'Yes';
      } // end if no comments
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
      } // end if uploads not allowed

    $your_vals = array('name' => '', 'email' => $you->user_email);
    if ( !empty( $you->user_firstname ) && !empty( $you->user_lastname ) ) {
      	$your_vals['name'] = "{$you->user_firstname} {$you->user_lastname}";
    } else {
      	$your_vals['name'] = $you->display_name;
    } // end if user has first and last names

    $replaced = apply_filters( 'replace_quick_mail_sender', $your_vals );
    $your_email = $replaced['email'];
    $your_name = $replaced['name'];
    $from = "From: \"{$your_name}\" <{$your_email}>\r\n";
    if ( empty( $your_email ) ) {
       $error = '<a href="/wp-admin/profile.php">' . __( 'Error: Incomplete User Profile', 'quick-mail' ) . '</a>';
    }

      if ( 'POST' == $_SERVER['REQUEST_METHOD']  && !empty( $_POST['qm205'] ) ) {
         if ( ! wp_verify_nonce( $_POST['qm205'], 'qm205' ) ) {
            wp_die( '<h1 role="alert">' . __( 'Login Expired. Refresh Page.', 'quick-mail' ). '</h1>' );
         }
         if ( empty( $commenter ) && empty( $_POST['qm-email'] ) ) {
         	$direction = is_rtl() ? 'rtl' : 'ltr';
         	$args = array('response' => 200, 'back_link' => true, 'text_direction' => $direction);
         	wp_die( sprintf( '<h1 role="alert">%s</h1>', __( 'Invalid mail address', 'quick-mail' ) ), __( 'Mail Error', 'quick-mail' ), $args );
         } // end if user circumvented Javascript

         $rec_type = empty($_POST['qm_bcc']) ? 'Cc' : 'Bcc';
         if (isset($_POST['qm-cc']) && is_array($_POST['qm-cc'])) {
         	$e = mb_strtolower( urldecode( $_POST['qm-email'] ), 'UTF-8' );
         	foreach ($_POST['qm-cc'] as $c) {
         		if ( $e == mb_strtolower( urldecode( $c ),'UTF-8' ) ) {
         			$error = __( 'Duplicate mail address', 'quick-mail' );
         			break;
         		} // end if
         	} // end foreach
         } // end if multiple selection

         if ( empty( $to ) ) {
	         if ( preg_match('/<(.+@.+[.].+)>/', urldecode($_POST['qm-email']), $raw_email) ) {
	            $to = trim( $raw_email[1] );
	         } else {
	            $to = trim( urldecode( $_POST['qm-email'] ) );
	         } // end if email and name
         } // end if not comment

         if ( !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
            $error = __( 'Invalid mail address', 'quick-mail' ) . '<br>' . htmlspecialchars( $to );
         }
         if (! empty( $_POST['qm-cc'] )) {
         	$raw_cc = array();
         	if ( !is_array( $_POST['qm-cc'] ) ) {
         		$mcc = QuickMailUtil::filter_email_input( $to, urldecode( $_POST['qm-cc'] ), $verify );
         		$tab = strstr( $mcc, "\t" );
         		if ( is_string( $tab ) ) {
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
         	for ( $i = 0; $i < $j && empty( $error ); $i++ ) {
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
	            	for ( $i = 0; $i < $j && empty( $error ); $i++ ) {
	         		if ( !QuickMailUtil::qm_valid_email_domain( $all_cc[$i], $verify ) ) {
	         			$error = 'CC ' . __( 'Invalid mail address', 'quick-mail' ) . '<br>' . $all_cc[$i];
	        		} elseif ( $to == $all_cc[$i] ) {
	        			$error = 'CC ' . __( 'Duplicate mail address', 'quick-mail' ) . '<br>' . $all_cc[$i];
	        			} // end if
				} // end for
         	} // end if not empty
         } // end if cc

         $subject = empty( $subject ) ? htmlspecialchars_decode( urldecode( stripslashes( $_POST['qm-subject'] ) ) ) : $subject;
         $subject = sanitize_text_field( $subject );
         if (! preg_match('/(\S+)/', $subject ) ) {
            $error = __( 'No subject', 'quick-mail' );
         } // end subject check

         $raw_msg = urldecode( stripslashes( $_POST['quickmailmessage'] ) );
         if ( empty( $error ) && 2 > mb_strlen( $raw_msg, 'UTF-8' ) ) {
         	$error = __( 'Please enter your message', 'quick-mail' );
         } else {
	         $message = do_shortcode( $raw_msg );
	         if ( strcmp( $raw_msg, $message ) || is_string( mb_strstr( $message, '</', false, 'UTF-8' ) ) ) {
				$this->content_type = 'text/html';
	         } else {
	         	$this->content_type = 'text/plain';
	         } // end set content type
         } // end else got message

         if ( empty( $error ) && !empty( $_FILES['attachment'] ) && !empty( $_FILES['attachment']['name'][0] ) ) {
			$uploads = array_merge_recursive($_FILES['attachment'], $_FILES['second'], $_FILES['third'],
											$_FILES['fourth'], $_FILES['fifth'], $_FILES['sixth'] );
			$dup = false;
			$j = count( $uploads['name'] );
			for ( $i = 0; ($i < $j) && ($dup == false); $i++ ) {
				if ( empty( $uploads['name'][$i] ) || empty( $uploads['size'][$i] ) ) {
					continue;
				}
				for ( $k = $i + 1; $k < $j; $k++ ) {
					if ( !empty( $uploads['name'][$k] ) && !empty( $uploads['size'][$k] ) && $uploads['name'][$k] == $uploads['name'][$i] && $uploads['size'][$k] == $uploads['size'][$i] ) {
						$dup = true;
					} // end if
				} // end for
			} // end for

            if ( $dup ) {
            		$error = __( 'Duplicate attachments', 'quick-mail' );
            } // end if duplicate attachments
			for ( $i = 0; ($i < $j) && empty( $error ); $i++ ) {
				if ( empty( $uploads['name'][$i] ) || empty( $uploads['size'][$i] ) ) {
					continue;
				}
				if ( 0 == $uploads['error'][$i] ) {
                  	$temp = $this->qm_get_temp_path(); // @since 1.1.1
                  	if ( ! is_dir( $temp ) || ! is_writable( $temp ) ) {
                     	$error = __( 'Missing temporary directory', 'quick-mail' );
                  	} else {
                     	$file = "{$temp}{$i}{$uploads['name'][$i]}";
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
         		$headers[] = "{$rec_type}: {$mcc}";
         	} // end if CC

         	if ( user_can_richedit() && 'text/html' == $this->content_type && '1' == get_user_meta( get_current_user_id(), 'qm_wpautop', true ) ) {
         		$message = wpautop( $message );
         	} // end if

         	// set content type and redirect error before sending mail.
         	add_filter( 'wp_mail_content_type', array($this, 'get_mail_content_type'), 99, 1 );
         	add_filter( 'wp_mail_failed', array($this, 'show_mail_failure'), 99, 1 );
         	// do not use Mailgun credentials for non-admin users
         	$mg_toggle = $this->got_mailgun_info( true );
			if ( $mg_toggle ) {
				$this->toggle_mailgun_override();
			} // end if do not replace sender name on non-admin user

            if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
	            	$success = __( 'Message Sent', 'quick-mail' );
	            	$rec_label = ($rec_type == 'Cc') ? __( 'CC', 'quick-mail' ) : __( 'BCC', 'quick-mail' );
	    			if (empty( $mcc ) ) {
					$success .= sprintf("<br>%s %s", __( 'To', 'quick-mail' ), $to);
				} else {
					$success .= sprintf("<br>%s %s<br>%s %s", __( 'To', 'quick-mail' ), $to, $rec_label, $mcc);
				} // end if has CC
            } else {
            		if ( $this->got_mailgun_info( false ) ) {
            			$error = __( 'Mailgrid Error sending mail', 'quick-mail' );
            		} elseif ( $this->got_replacement_info() ) {
            			$rname = $this->get_replacement_name();
            			$error = "{$rname} " . __( 'Error sending mail', 'quick-mail' );
            		} else {
	             	$error = __( 'Error sending mail', 'quick-mail' );
            		}
         	} // end else error

         	// reset filters after send
         	remove_filter( 'wp_mail_content_type', array($this, 'get_mail_content_type'), 99 );
         	remove_filter( 'wp_mail_failed', array($this, 'show_mail_failure'), 99 );
         	if ( $mg_toggle ) {
         		$this->toggle_mailgun_override();
         	} // end if do not replace sender name on non-admin user

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

      $orig_link = plugins_url( '/inc/qm_validate.php', __FILE__ );
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
      $qm_link = admin_url( 'tools.php?page=quick_mail_form' );
?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Quick Mail', 'quick-mail' ); ?></h1>
<?php if ( !empty( $no_uploads ) ) : ?>
<div class="update-nag notice is-dismissible">
   <p role="alert"><?php echo $no_uploads; ?></p>
</div>
<?php elseif ( !empty( $success ) ) : ?>
<div id="qm-success" class="updated notice is-dismissible">
   <p><?php echo $success; ?></p>
</div>
<?php elseif ( !empty( $error ) ) : ?>
<?php $ecss = ( mb_strstr( $error, 'profile.php', false, 'UTF-8' ) ) ? 'error notice': 'error notice is-dismissible'; ?>
<div id="qm_error" class="<?php echo $ecss; ?>">
   <p role="alert"><?php echo $error; ?></p>
</div>
<?php endif; ?>
<div id="qm-validate" role="alert" class="error notice is-dismissible">
   <p role="alert"><?php _e( 'Invalid mail address', 'quick-mail' ); ?><span id="qm-ima"> </span></p>
</div>
<div id="qm-duplicate" role="alert" class="error notice is-dismissible">
   <p role="alert"><?php _e( 'Duplicate mail address', 'quick-mail' ); ?> <span id="qm-dma"> </span></p>
</div>
<noscript><span class="quick-mail-noscript"><?php _e( 'Quick Mail requires Javascript', 'quick-mail' ); ?></span></noscript>
<?php if ( !empty( $you->user_email ) && !empty( $you->display_name ) ) : ?>
<form name="Hello" id="Hello" method="post" enctype="multipart/form-data" action="<?php echo $qm_link; ?>">
		<div class="indented">
<?php wp_nonce_field( 'qm205', 'qm205', false, true ); ?>
<input type="hidden" name="qm-invalid" id="qm-invalid" value="0">
<?php if ( ! empty( $no_uploads ) || ! empty( $_POST['quick-mail-uploads'] ) ) : ?>
	<input type="hidden" name="quick-mail-uploads" value="No">
<?php endif; ?>
<fieldset>
<?php
$the_from = htmlspecialchars( substr( $from, 6 ), ENT_QUOTES );
$tlen = mb_strlen( $the_from, 'UTF-8' ) + 2;
if ( 75 < $tlen ) {
	$tlen = 75;
}
$tsize = "size='{$tlen}'";
$to_label = ( empty( $commenter ) || empty( $commenter_list ) ) ? __( 'To', 'quick-mail' ) : __( 'Commenters', 'quick-mail' );
$msg_label =  ( empty( $commenter ) || empty( $commenter_list ) ) ? __( 'Message', 'quick-mail' ) : __( 'Reply', 'quick-mail' );
$message_tabindex = (is_string($commenter_list) && empty($commenter_list) ) ? 50 : 1;
?>
<label id="tf_label" for="the_from" class="recipients"><?php _e( 'From', 'quick-mail' ); ?></label>
<p><input aria-labelledby="tf_label" <?php echo $tsize; ?> value="<?php echo $the_from; ?>" readonly aria-readonly="true" id="the_from" tabindex="5000"></p>
</fieldset>
<fieldset>
<label id="qme_label" for="qm-email" class="recipients"><?php echo $to_label; ?></label>
<?php if ( empty( $commenter ) ) : ?>
<p><?php echo $this->quick_mail_recipient_input( $to, $you->ID ); ?></p>
<?php else : ?>
<?php
$crecipient = '';
if (is_string($commenter_list) && !empty($commenter_list) ) {
	$crecipient = $commenter_list;
} else {
	$crecipient = "<input aria-labelledby='qme_label' value='{$to}'
	id='qm-email' name='qm-email' type='email' required aria-required='true' tabindex='6000'
	readonly aria-readonly='true' size='35'>";
} // end if
?>
<p><?php echo $crecipient; ?></p>
<?php endif; ?>
</fieldset>
<?php
if ( empty( $commenter ) && 'X' == $this->qm_get_display_option( $blog ) ) : ?>
<fieldset id="qm_row">
<label id="qtc_label" for="qm_to_choice" class="recipients"><?php _e( 'Recent', 'quick-mail' ); ?> <?php _e( 'To', 'quick-mail' ); ?></label>
<p id="qm_to_choice"></p>
</fieldset>
<?php endif; ?>
<?php if ( empty( $commenter ) ) : ?>
<fieldset>
<label id="qmcc_label" for="qm-cc" class="recipients"><?php _e( 'CC', 'quick-mail' ); ?></label>
<label id="qmbcc_label" for="qm_bcc" class="qm-label"><?php _e( 'BCC', 'quick-mail' ); ?></label>
<input tabindex="2" type="checkbox" id="qm_bcc" name="qm_bcc" onchange="if (jQuery('#qm_bcc').is(':checked')) { jQuery('#qmcc_label').text('<?php _e( 'BCC', 'quick-mail' ); ?>'); } else { jQuery('#qmcc_label').text('<?php _e( 'CC', 'quick-mail' ); ?>') }">
<p><?php echo $this->quick_mail_cc_input( $to, $mcc, $you->ID ); ?></p>
</fieldset>
<?php endif; ?>
<?php
if ( empty( $commenter ) && 'X' == $this->qm_get_display_option( $blog ) ) : ?>
<fieldset id="qm_cc_row">
<label id="qcc2_label" for="qm_cc_choice" class="recipients"><?php _e( 'Recent', 'quick-mail' ); ?> <?php _e( 'CC', 'quick-mail' ); ?></label>
<p id="qm_cc_choice"></p>
</fieldset>
<?php endif; ?>
<fieldset>
<label id="qmsubject_label" for="qm-subject" class="recipients"><?php _e( 'Subject', 'quick-mail' ); ?></label>
<p><input value="<?php echo htmlspecialchars( $subject, ENT_QUOTES ); ?>" type="text"
aria-labelledby="qmsubject_label" name="qm-subject" id="qm-subject" required size="40" aria-required="true"
placeholder="<?php _e( 'Subject', 'quick-mail' ); ?>" tabindex="22"></p>
</fieldset>
<?php if ( empty( $no_uploads ) && empty( $_POST['quick-mail-uploads'] ) ) : ?>
<fieldset>
<label id="qmf1" for="qm-file-first" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?></label>
<p><input aria-labelledby="qmf1" id="qm-file-first" name="attachment[]" type="file" multiple="multiple" tabindex="23"></p>
</fieldset>
<fieldset class="qm-second">
<label id="qmf2" for="qm-second-file" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-second"><input aria-labelledby="qmf2" id="qm-second-file" name="second[]" type="file" multiple="multiple" tabindex="24"></p>
</fieldset>
<fieldset class="qm-third">
<label id="qmf3" for="qm-third-file" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-third"><input aria-labelledby="qmf3" id="qm-third-file" name="third[]" type="file" multiple="multiple" tabindex="25"></p>
</fieldset>
<fieldset class="qm-fourth">
<label id="qmf4" for="qm-fourth-file" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?>:</label>
<p class="qm-row-fourth"><input aria-labelledby="qmf4" id="qm-fourth-file" name="fourth[]" type="file" multiple="multiple" tabindex="26"></p>
</fieldset>
<fieldset class="qm-fifth">
<label id="qmf5" for="qm-fifth-file" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-fifth"><input aria-labelledby="qmf5" id="qm-fifth-file" name="fifth[]" type="file" multiple="multiple" tabindex="27"></p>
</fieldset>
<fieldset class="qm-sixth">
<label id="qmf6" for="qm-sixth-file" class="recipients"><?php _e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-sixth"><input aria-labelledby="qmf6" id="qm-sixth-file" name="sixth[]" type="file" multiple="multiple" tabindex="28"></p>
</fieldset>
<?php endif; ?>
<fieldset>
<label id="qm_msg_label" for="quickmailmessage" class="recipients"><?php echo $msg_label; ?></label>
<?php if ( !user_can_richedit() ) {
?>
<p><textarea id="quickmailmessage" name="quickmailmessage"
placeholder="<?php _e( 'Enter your message', 'quick-mail' ); ?>"
aria-labelledby="qm_msg_label" required aria-required="true" aria-multiline=”true”
rows="8" cols="60" tabindex="<?php echo $message_tabindex; ?>"><?php echo htmlspecialchars( $raw_msg, ENT_QUOTES ); ?></textarea></p>
<?php
} else {
$settings = array('textarea_rows' => 8, 'tabindex' => $message_tabindex );
if (is_string($commenter_list) && !empty($commenter_list) ) {
	$settings['tabindex'] = 1;
} // end if replying to comment
wp_editor( $raw_msg, 'quickmailmessage', $settings);
} // end if
?>
</fieldset>
<p class="submit"><input disabled type="submit" id="qm-submit" name="qm-submit"
title="<?php _e( 'Send Mail', 'quick-mail' ); ?>" tabindex="99"
value="<?php _e( 'Send Mail', 'quick-mail' ); ?>"></p>
					</div> <!-- indented -->
</form>
<?php endif; ?>
<?php
   } // end quick_mail_form

   /**
    * display form to edit plugin options.
    */
   public function quick_mail_options() {
      $updated = false;
      $blog = is_multisite() ? get_current_blog_id() : 0;
      $you = wp_get_current_user();
      // show_quick_mail_commenters
      if ( ! empty( $_POST['show_quick_mail_users'] ) && 1 == strlen( $_POST['show_quick_mail_users'] ) ) {
         $previous = $this->qm_get_display_option( $blog );
         if ( $previous != $_POST['show_quick_mail_users'] ) {
         	if ( $this->multiple_matching_users( $_POST['show_quick_mail_users'], $blog ) ) {
	            $this->qm_update_option( 'show_quick_mail_users', $_POST['show_quick_mail_users'] );
        		    $updated = true;
         	} // end if valid option, but invalid options should not be displayed
         } // end if display option changed
      } // end if received display option

	  if ( 'POST' == $_SERVER['REQUEST_METHOD']) {
	  	$previous = get_user_option( 'show_quick_mail_commenters', $you->ID );
	  	$current = empty($_POST['show_quick_mail_commenters']) ? 'N' : $_POST['show_quick_mail_commenters'];
	  	if ( $current != $previous ) {
	  		update_user_meta( $you->ID, 'show_quick_mail_commenters', $current, $previous );
	  		$updated = true;
	  	} // end if show_quick_mail_commenters changed

	      $previous = get_user_meta( $you->ID, 'qm_wpautop', true );
	      $current = empty($_POST['qm_wpautop']) ? '0' : $_POST['qm_wpautop'];
	      if ( $current != $previous ) {
	      	update_user_meta( $you->ID, 'qm_wpautop', $current, $previous );
	      	$updated = true;
	      } // end if wpauto changed

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

      			$updated = true;
	      	} // end if value changed

		      $previous = '';
		      $current = empty( $_POST['replace_quick_mail_sender'] ) ? 'N' : 'Y';

		      if ( is_multisite() ) {
		      	$previous = get_blog_option( $blog, 'replace_quick_mail_sender', 'N' );
		      } else {
		      	$previous = get_option( 'replace_quick_mail_sender', 'N' );
		      } // end if multisite

		      if ( $current != $previous ) {
		      	if ( is_multisite() ) {
		      		update_blog_option( $blog, 'replace_quick_mail_sender', $current );
		      	} else {
		      		update_option( 'replace_quick_mail_sender', $current );
		      	} // end if multisite

		      	$updated = true;
		      } // end if replace_quick_mail_sender value changed

	         $previous = '';
	         if ( is_multisite() ) {
	         	$previous = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
	         } else {
	         	$previous = get_option( 'quick_mail_cannot_reply', 'N' );
	         } // end if multisite

	         $current = empty( $_POST['quick_mail_cannot_reply'] ) ? 'N' : 'Y';
	         if ( $current != $previous ) {
	         	if ( is_multisite() ) {
	         		update_blog_option( $blog, 'quick_mail_cannot_reply', $current );
	         	} else {
	         		update_option( 'quick_mail_cannot_reply', $current );
	         	} // end if multisite
	         	if ( !$updated ) {
	         		$updated = true;
	         	} // end if updated not displayed
	         } // end if value changed

	         $previous = '';
	         if ( is_multisite() ) {
	         	$previous = get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' );
	         } else {
	         	$previous = get_option( 'authors_quick_mail_privilege', 'N' );
	         } // end if multisite

	         $current = empty( $_POST['authors_quick_mail_privilege'] ) ? 'N' : 'Y';
	         if ( $current != $previous ) {
	         	if ( is_multisite() ) {
	         		update_blog_option( $blog, 'authors_quick_mail_privilege', $current );
	         	} else {
	         		update_option( 'authors_quick_mail_privilege', $current );
	         	} // end if multisite
	         	if ( !$updated ) {
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
	            if ( !$updated ) {
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
	               $updated = true;
	            } // end if updated not displayed
	         } // end if value changed
	      } // end if admin
      } // end if POST
      if ( $updated ) {
      	echo '<div class="updated">', _e( 'Option Updated', 'quick-mail' ), '</div>';
      } // end if updated

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

      $check_wpautop = ( '1' == get_user_meta( $you->ID, 'qm_wpautop', true ) ) ? 'checked="checked"' : '';
      $check_commenters = $this->user_can_reply_to_comments( false ) ? 'checked="checked"' : '';
      $check_all    = ( 'A' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $check_names  = ( 'N' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $check_none   = ( 'X' == $this->qm_get_display_option( $blog ) ) ? 'checked="checked"' : '';
      $list_warning = '';
      if ( 3 > $total && 'X' != $this->qm_get_display_option( $blog ) ) {
	      	$note = ' <strong>' . __( 'NOTE', 'quick-mail' ) . ' : </strong> ';
	      	$lw_msg = __( 'Quick Mail needs three non-admin users for sender, recipient, CC to access User List.', 'quick-mail' );
	      	$list_warning = $note . $lw_msg;
      } // end if have total less than 3

      $admin_option = '';
      $editor_option = '';
      $author_option = '';
      $verify_option = '';
      $sendgrid_option = '';
      if ( is_multisite() ) {
      	$admin_option = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
      	$editor_option = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
      	$author_option = get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' );
      	$cannot_reply_option = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
      	$verify_option = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
      	$sendgrid_option = get_blog_option( $blog, 'replace_quick_mail_sender', 'N' );
      	// authors_quick_mail_privilege replace_quick_mail_sender
      } else {
      	$admin_option = get_option( 'hide_quick_mail_admin', 'N' );
      	$editor_option = get_option( 'editors_quick_mail_privilege', 'N' );
      	$author_option = get_option( 'authors_quick_mail_privilege', 'N' );
      	$cannot_reply_option = get_option( 'quick_mail_cannot_reply', 'N' );
      	$verify_option = get_option( 'verify_quick_mail_addresses', 'N' );
      	$sendgrid_option = get_option( 'replace_quick_mail_sender', 'N' );
      } // end if multisite

      $check_admin  = ( 'Y' == $admin_option ) ? 'checked="checked"' : '';
      $check_editor = ( 'Y' == $editor_option ) ? 'checked="checked"' : '';
      $check_author = ( 'Y' == $author_option ) ? 'checked="checked"' : '';
      $check_verify = ( 'Y' == $verify_option ) ? 'checked="checked"' : '';
      $check_sendgrid = ( 'Y' == $sendgrid_option ) ? 'checked="checked"' : '';
      $check_cannot_reply = ( 'Y' == $cannot_reply_option ) ? 'checked="checked"' : '';

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
         $verify_problem = '<br><br><span role="alert">' . $cannot . $nf . '<br>' . $faq . '</span>';
      } // end if idn_to_ascii is available
      $verify_note = $verify_message . $verify_problem;
      $wam = sprintf("%s %s %s",	__( 'Apply', 'quick-mail'),
      		'<a target="_blank" href="https://codex.wordpress.org/Function_Reference/wpautop">wpautop</a>',
      		__( 'to HTML messages', 'quick-mail'));
	$space = '';
	$comment_label = '';
	if ( ! $this->multiple_matching_users( 'A', $blog ) ) {
		$space = ' style="margin-top:2em;" ';
		$comment_label = __( 'Select recipient from commenters', 'quick-mail' );
	} else {
		$comment_label = __( 'Display Commenters instead of users', 'quick-mail' );
	} // end if no users

      if ( !$this->qm_is_admin( get_current_user_id(), $blog ) ) {
      	$cannot_reply = '';
      	if ( is_multisite() ) {
      		$cannot_reply = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
      	} else {
      		$cannot_reply = get_option( 'quick_mail_cannot_reply', 'N' );
      	} // end if multisite

      	if ( 'Y' == $cannot_reply ) {
      		$comment_label = '';
      	} elseif ( 'author' == $this->qm_get_role() ) {
	      	$allowed = is_multisite() ?
	      	get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' ) :
	      	get_option( 'authors_quick_mail_privilege', 'N' );
	      	if ( 'Y' != $allowed ) {
	      		$comment_label = '';
	      	} // end if not allowed to reply with Quick Mail
	      } // end if author
      } // end if not admin
	$mg_label = '';
	$mg_message = '';
	if ( $this->qm_is_admin( $you->ID, $blog ) && $this->got_mailgun_info( true ) ) {
		$mg_label = __( 'Using Mailgun credentials', 'quick-mail' );
		$mg_message = __( 'Sending mail with your Mailgun name and mail address.', 'quick-mail' );
	} elseif ( $this->got_mailgun_info( false ) ) {
		$mg_label = __( 'Mailgun is active', 'quick-mail' );
		if ( !$this->qm_is_admin( $you->ID, $blog ) ) {
			$mg_message = __( 'Administrator is using Mailgun to send mail.', 'quick-mail' );
		} else {
			$mg_message = __( 'Sending mail with Mailgun API.', 'quick-mail' );
		} // end if
	} // end if got mailgun info

	$rname = '';
	$rlabel = '';
	$replacement_label = '';
	$replacement_desc = '';
	if ( $this->got_replacement_info() ) {
		$rname = $this->get_replacement_name();
		$replacement_label = sprintf('%s %s %s', __( 'Use', 'quick-mail' ),
				$rname, 	__( 'credentials', 'quick-mail' ) );
		if ($this->user_has_replaced_sender()) {
			$replacement_desc = sprintf('%s %s %s %s', __( 'Using', 'quick-mail' ),
				$rname, 	__( 'credentials', 'quick-mail' ), __( 'to send mail for Administrators', 'quick-mail' ) );
		} else {
			if ( !$this->qm_is_admin( $you->ID, $blog ) ) {
				$replacement_desc = sprintf('%s %s %s.	', __( 'Administrator is using', 'quick-mail' ),
						$rname, 	__( 'to send mail', 'quick-mail' ) );
			} else {
				$replacement_desc = sprintf('%s %s %s.	', __( 'Using', 'quick-mail' ),
						$rname, 	__( 'to send mail', 'quick-mail' ) );
			} // end if not admin
		} // end if
	} // end if got replacement API
?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Quick Mail Options', 'quick-mail' ); ?></h1>
<form id="quick-mail-settings" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<div class="indented">
<div id="qm_saved"></div>
<?php if ( defined('NOT_NOW') && !$this->qm_is_admin( $you->ID, $blog ) && $this->got_mailgun_info( false ) ) : ?>
<fieldset>
<legend class="recipients"><?php _e( 'Administration', 'quick-mail' ); ?></legend>
<p><input readonly aria-readonly="true" aria-describedby="qm_mailgun_desc" aria-labelledby="qm_mailgun_label" class="qm-input" name="using_Mailgun" type="checkbox" checked="checked" onclick='return false;'>
<label id="qm_mailgun_label" class="qm-label"><?php echo $mg_label; ?>.</label>
<span id="qm_mailgun_desc" class="qm-label"><?php echo $mg_message; ?></span></p>
</fieldset>
<?php endif; ?>
<?php if ( $this->qm_is_admin( $you->ID, $blog ) ) : ?>
<fieldset>
<legend class="recipients"><?php _e( 'Administration', 'quick-mail' ); ?></legend>
<?php if ( $this->got_mailgun_info(false) ) : ?>
<p><input readonly aria-readonly="true" aria-describedby="qm_mailgun_desc" aria-labelledby="qm_mailgun_label" class="qm-input" name="using_Mailgun" type="checkbox" checked="checked" onclick='return false;'>
<label id="qm_mailgun_label" class="qm-label"><?php echo $mg_label; ?>.</label>
<span id="qm_mailgun_desc" class="qm-label"><?php echo $mg_message; ?></span></p>
<?php elseif ( $this->got_replacement_info() ) : ?>
<p><input aria-describedby="qm_sendgrid_desc" aria-labelledby="qm_sendgrid_label" class="qm-input" name="replace_quick_mail_sender" type="checkbox" <?php echo $check_sendgrid; ?>>
<label id="qm_sendgrid_label" class="qm-label"><?php echo $replacement_label; ?>.</label>
<span id="qm_sendgrid_desc" class="qm-label"><?php echo $replacement_desc; ?></span></p>
<?php endif; ?>
<?php if ( $this->multiple_matching_users( 'A', $blog ) ) : ?>
<p><input aria-describedby="qm_hide_desc" aria-labelledby="qm_hide_label" class="qm-input" name="hide_quick_mail_admin" type="checkbox" <?php echo $check_admin; ?>>
<label id="qm_hide_label" class="qm-label"><?php _e( 'Hide Administrator Profiles', 'quick-mail' ); ?>.</label>
<?php
$admins = $this->qm_admin_count( $blog );
$profile = sprintf( _n( '%s administrator profile', '%s administrator profiles', $admins, 'quick-mail' ), $admins );
echo sprintf('<span id="qm_hide_desc" class="qm-label">%s %s</span>', __( 'User list will not include', 'quick-mail' ), " {$profile}.");
?>
<?php endif; ?>
<input name="showing_quick_mail_admin" type="hidden" value="Y"></p>
<p><input aria-describedby="quick_mail_cannot_reply_desc" id="quick_mail_cannot_reply"
aria-labelledby="quick_mail_cannot_reply_label" class="qm-input"
name="quick_mail_cannot_reply" type="checkbox" <?php echo $check_cannot_reply; ?>>
<label id="quick_mail_cannot_reply_label" class="qm-label"><?php _e( 'Disable Replies to Comments', 'quick-mail' ); ?>.</label>
<span id="quick_mail_cannot_reply_desc" class="qm-label"><?php _e( 'Users will not see commenter list.', 'quick-mail' ); ?></span></p>
<p id="qm-authors"><input aria-describedby="qm_author_desc" aria-labelledby="qm_author_label" class="qm-input" name="authors_quick_mail_privilege" type="checkbox" <?php echo $check_author; ?>>
<label id="qm_author_label" class="qm-label"><?php _e( 'Grant Authors permission to reply to comments', 'quick-mail' ); ?>.</label>
<span id="qm_author_desc" class="qm-label"><?php _e( 'Authors will not have access to user list.', 'quick-mail' ); ?></span></p>
<p><input aria-describedby="qm_grant_desc" aria-labelledby="qm_grant_label" class="qm-input" name="editors_quick_mail_privilege" type="checkbox" <?php echo $check_editor; ?>>
<label id="qm_grant_label" class="qm-label"><?php _e( 'Grant Editors access to user list.', 'quick-mail' ); ?></label>
<span id="qm_grant_desc" class="qm-label"><?php _e( 'Let editors see user list.', 'quick-mail' ); ?></span></p>
<p><input aria-describedby="qm_verify_desc" aria-labelledby="qm_verify_label" class="qm-input" name="verify_quick_mail_addresses" type="checkbox" <?php echo $check_verify; ?>>
<label id="qm_verify_label" class="qm-label"><?php _e( 'Verify recipient email domains', 'quick-mail' ); ?>.</label>
<span id="qm_verify_desc" class="qm-label"><?php echo $verify_note; ?></span></p>
</fieldset>
<?php endif; ?>
<?php
if ( user_can_richedit() ) : ?>
<fieldset>
<legend class="recipients"><?php _e( 'Add Paragraphs', 'quick-mail' ); ?></legend>
<p><input aria-describedby="qm_par_desc" aria-labelledby="qm_par_label" id="qm_add_par" class="qm-input" name="qm_wpautop" type="checkbox" value="1" <?php echo $check_wpautop; ?>>
<label id="qm_par_label" for="qm_add_par" class="qm-label"><?php _e( 'Add Paragraphs to sent mail', 'quick-mail' ); ?></label></p>
<p><span id="qm_par_desc" class="qm-label"><?php echo $wam; ?></span></p>
</fieldset>
<?php endif; ?>
<fieldset>
<legend class="recipients"><?php _e( 'User Display', 'quick-mail' ); ?></legend>
<?php if ( empty( $comment_label ) ) : ?>
<input type="hidden" name="show_quick_mail_commenters" value="N">
<?php else : ?>
      <p id="show_commenters_row"><input aria-describedby="qm_commenter_desc" aria-labelledby="qm_commenter_label" id="show_quick_mail_commenters" class="qm-input" name="show_quick_mail_commenters"
      type="checkbox" value="Y" <?php echo $check_commenters; ?>>
      <label id="qm_commenter_label" for="show_quick_mail_commenters" class="qm-label"><?php echo $comment_label; ?></label>
      <span id="qm_commenter_desc" class="qm-label"><?php _e( 'Send private replies to comments.', 'quick-mail' ); ?></span></p>
<?php endif; ?>
      <?php if (!empty($list_warning)) : ?>
      <p role="alert" id="qm-warning"><?php echo $list_warning; ?></p>
      <?php endif; ?>
      <?php if ( $this->multiple_matching_users( 'A', $blog ) ) : ?>
      <p><input aria-describedby="qm_all_desc" aria-labelledby="qm_all_label" id="qm_all_users" class="qm-input" name="show_quick_mail_users" type="radio" value="A" <?php echo $check_all; ?>>
      <label id="qm_all_label" for="qm_all_users" class="qm-label">
<?php
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$total}</span>", $css);
_e( 'Show All Users', 'quick-mail' );
if ($total > 0) {
	echo " ({$info})";
} // end if
?>
</label><span id="qm_all_desc" class="qm-label"><?php _e( 'Show all users sorted by nickname', 'quick-mail' );
$info = sprintf("<span class='%s'>{$total}</span>", $css);
if ($total > 0) {
	echo ' ', $info, ' ', __( 'matching users', 'quick-mail' );
} // end if
?>
.</span></p>
     <?php endif; ?>
	  <?php if ( $this->multiple_matching_users( 'N', $blog ) ) : ?>
      <p><input aria-describedby="qm_names_desc" aria-labelledby="qm_names_label" class="qm-input" name="show_quick_mail_users" type="radio" value="N" <?php echo $check_names; ?>>
      <label id="qm_names_label" class="qm-label">
<?php
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$names}</span>", $css);
_e( 'Show Users with Names', 'quick-mail' );
if ($total > 0) {
	echo " ({$info})";
} // end if
?></label>
<span id="qm_names_desc" class="qm-label"><?php _e( 'Show users with names, sorted by last name', 'quick-mail' );
$css = ('Y' == $hide_admin) ? 'qm-admin' : 'qm-total';
$info = sprintf("<span class='%s'>{$names}</span>", $css);
if ($total > 0) {
	echo ' ', $info, ' ', __( 'matching users', 'quick-mail' );
} // end if
?>
.</span></p>
      <?php endif; ?>
<p<?php echo $space; ?>><input aria-describedby="qm_none_desc" aria-labelledby="qm_none_label" class="qm-input" name="show_quick_mail_users" type="radio" value="X"
<?php
echo $check_none;
if (! $this->multiple_matching_users( 'A', $blog ) ) {
	echo ' readonly'; }
?>>
<label id="qm_none_label" class="qm-label"><?php _e( 'Do Not Show Users', 'quick-mail' ); ?></label>
<?php
if ( !$this->multiple_matching_users( 'A', $blog ) ) {
	echo '<br><br><span class="qm-label" role="alert">';
	if ( $this->qm_is_admin( $you->ID, $blog ) ) {
		_e( 'Need three users to display User List for sender, recipient, CC.', 'quick-mail' );
	} else {
		_e( 'User List was disabled by system administrator.', 'quick-mail' );
	} // end if admin
	echo '</span><br>';
} // end if one user
?>
<span id="qm_none_desc" class="qm-label"><?php _e( 'Enter address to send mail.', 'quick-mail' ); ?> <?php _e( 'Saves 12 addresses.', 'quick-mail' ); ?></span></p>
</fieldset>
<p class="submit"><input type="submit" name="qm-submit" class="button button-primary qm-input" value="<?php _e( 'Save Options', 'quick-mail' ); ?>"></p>
</div>
</form>
<?php
   } // end quick_mail_options

   /**
    * get user option. return default if not found. replaces qm_get_option.
    *
	* @param int $blog Blog ID or zero if not multisite
    * @return string Option value or adjusted default
    * @since 1.4.0
    */
   public function qm_get_display_option( $blog ) {
      global $current_user;
      if ( 'author' == $this->qm_get_role() ) {
      	return 'X';
      } // end if author
      $value = get_user_meta( $current_user->ID, 'show_quick_mail_users', true );
      $retval = ( ! empty( $value ) ) ? $value : 'A'; // should never be empty
      return $this->multiple_matching_users( $retval, $blog ) ? $retval : 'X';
   } // end qm_get_display_option

   /**
    * update user option.
    *
    * @param string $key
    * @param string $value
    */
   public function qm_update_option( $key, $value ) {
      global $current_user;
      if ( is_int( $value ) ) {
      	$value = strval( $value );
      }
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
		if ( $blog == 0 ) {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'include' => array($id), 'count_total' => true ) );
		} else {
			$user_query = new WP_User_Query( array( 'role' => 'Administrator',
					'include' => array($id), 'count_total' => true, 'blog_id' => $blog ) );
		} // end if not multisite

		return ( 0 < $user_query->get_total() );
	} // end qm_is_admin

	/**
	 * Is user an editor?
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
	} // end qm_is_editor

	/**
	 * get total users with administrator role on a blog.
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
	 * filter comment notification to add quick mail.
	 * @param string $text
	 * @param integer $id comment ID
	 * @return string filtered text
	 * @since 3.1.0
	 */
	public function qm_comment_reply($text, $id) {
		if ( !$this->user_can_reply_to_comments( true ) ) {
			return $text;
		} // end if comments disabled by administrator

		$qm = admin_url( "tools.php?page=quick_mail_form&comment_id={$id}\r\n" );
		$title = apply_filters( 'quick-mail-reply-title',  __( 'Private Reply', 'quick-mail' ) ); // was Reply with Quick Mail
		$left_link = "{$title}: {$qm}";
		$right_link = "{$qm} : {$title}";
		$text .= is_rtl() ? $right_link: $left_link;
		return $text;
	} // end qm_comment_reply

	/**
	 * filter comment_row_actions to add Reply with Quick Mail.
	 * @param array $actions actions to filter
	 * @param object $comment WP_Comment
	 * @return array string filtered comments
	 * @since 3.1.1
	 */
	public function qm_filter_comment_link($actions, $comment) {
		if ( '1' != $comment->comment_approved || empty( $comment->comment_author_email ) ) {
			return $actions;
		} // end if trackback or not approved
		if ( !QuickMailUtil::qm_valid_email_domain( $comment->comment_author_email, 'Y' ) ) {
			return $actions;
		} // end if invalid author email

		if ( !$this->user_can_reply_to_comments( true ) ) {
			return $actions;
		} // end if site allows private replies to comments

		$qm_url = admin_url( "tools.php?page=quick_mail_form&comment_id={$comment->comment_ID}");
		$reply = apply_filters( 'quick-mail-reply-title',  __( 'Private Reply', 'quick-mail' ) );  // was Reply with Quick Mail
		$ereply = esc_attr( $reply );
		$css = 'style="color: #e14d43;"'; // wp-ui-text-highlight
		$retval = array();
		foreach ($actions as $k => $v) {
			$retval[$k] = $v;
			if ('reply' == $k) {
				$retval['quickmail'] = "<a {$css} href='{$qm_url}' aria-label='{$ereply}'>{$reply}</a>";
			}
		} // end foreach

		if (empty($retval['quickmail'])) {
			$retval['quickmail'] = "<a {$css} href='{$qm_url}' aria-label='{$ereply}'>{$reply}</a>";
		} // end if missing reply option

		return $retval;
	} // end qm_filter_comment_link

   /**
    * used with quick_mail_setup_capability filter, to let editors see user list.
    *
    */
	public function let_editor_set_quick_mail_option( $role ) {
		$editors = 'N';
		$authors = 'N';
		if ( is_multisite() ) {
			$editors = get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' );
		} else {
			$editors = get_option( 'editors_quick_mail_privilege', 'N' );
		} // end if multisite

		if ( is_multisite() ) {
			$authors = get_blog_option( get_current_blog_id(), 'authors_quick_mail_privilege', 'N' );
		} else {
			$authors = get_option( 'authors_quick_mail_privilege', 'N' );
		} // end if multisite

		if ( 'Y' == $authors ) {
			return 'publish_posts';
		} // end if author
		return ( 'Y' == $editors ) ? 'edit_others_posts' : $role;
   } // end let_editor_set_quick_mail_option

	/**
    * init admin menu for appropriate users.
    */
	public function init_quick_mail_menu() {
		$title = __( 'Quick Mail', 'quick-mail' );
		$min_permission = 'publish_posts';
		$allowed = is_multisite() ?
		get_blog_option( get_current_blog_id(), 'authors_quick_mail_privilege', 'N' ) :
		get_option( 'authors_quick_mail_privilege', 'N' );
		if ( 'Y' != $allowed ) {
			$min_permission = 'edit_others_posts';
		} // end if skipping authors
		$page = add_submenu_page( 'tools.php', $title, $title,
		apply_filters( 'quick_mail_user_capability', $min_permission ), 'quick_mail_form', array($this, 'quick_mail_form') );
		add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
		$page = add_options_page( 'Quick Mail Options', $title, apply_filters( 'quick_mail_setup_capability', $min_permission ), 'quick_mail_options', array($this, 'quick_mail_options') );
		if ( !empty( $page ) ) {
			add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
			add_action('load-' . $page, array($this, 'add_qm_settings_help'));
		} // end if added submenu
   } // end init_quick_mail_menu

	/**
    * Quick Mail settings help.
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
    		if ( $is_admin_user ) {
    			$content = '<dl>';
    			// check for Mailgun, Sendgrid
			if ($this->got_mailgun_info( false ) ) {
				$content .= '<dt><strong>' . __( 'Mailgun is active', 'quick-mail' ) . '</strong></dt>';
				if ( $this->got_mailgun_info( true ) ) {
					$content .= '<dd>' . __( 'Administrators send mail with Mailgun credentials', 'quick-mail' ) . '.</dd>';
				} else {
					$content .= '<dd>' . __( 'Sending mail with Mailgun API', 'quick-mail' ) . '.</dd>';
				} // end if using Mailgun name
			} // end if Mailgun

			if ( $this->got_replacement_info() ) {
				$content .= '<dt><strong>' . __( 'Sendgrid is active', 'quick-mail' ) . '</strong></dt>';

			} // end if

    			$content .= '<dt><strong>' . __( 'Hide Administrator Profiles', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Prevent users from sending email to administrators', 'quick-mail' ) . '.</dd>';
    			$content .= '<dt><strong>' . __( 'Grant Editors access to user list', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __(  'Otherwise only administrators can view the user list', 'quick-mail' ) . '</dd>';
    			$content .= '<dt><strong>' . __( 'Verify recipient email domains', 'quick-mail' ) . '</strong></dt>';
    			$content .= '<dd>' . __( 'Check if recipient domain accepts email. Detects typos.', 'quick-mail' ) . '.</dd></dl>';
    			$screen->add_help_tab( array('id'	=> 'qm_admin_display_help',
    					'title'	=> __('Administration', 'quick-mail'), 'content' => $content) );
    		} // end if

    		$slink = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
    		$use_str = __( 'Please use', 'quick-mail' );
    		$to_ask = __( 'to ask questions and report problems', 'quick-mail' );
    		$rc5 = "<dd style='font-weight:bold; margin-top:2em;'>{$use_str} {$slink} {$to_ask}.</dd>";

   		$dc_title = __( 'Commenters', 'quick-mail' );
   		$dc_head = $this->multiple_matching_users( 'A', $blog ) ?
   		__( 'Display list of commenters, instead of users.', 'quick-mail' ) :
   		__( 'Select recipient from commenters.', 'quick-mail' );
   		// instead
    		$dc_enabled = sprintf('<a target="_blank" href="https://codex.wordpress.org/Comments_in_WordPress#Enabling_Comments_on_Your_Site">%s</a>', __( 'enabling comments', 'quick-mail' ) );
    		$dc_settings = sprintf('<a target="_blank" href="https://codex.wordpress.org/Settings_Discussion_Screen">%s</a>', __( 'discussion settings', 'quick-mail' ) );
    		$dc_see = __( 'See', 'quick-mail' );
    		$dc_info = __( 'for additional information.', 'quick-mail' );
    		$dc_and = __( 'and', 'quick-mail' );
    		$dc1 = '<dd>' . __( 'Reply to comments on your published content.', 'quick-mail' ) . '</dd>';
    		// $dc2 = '<dd>' . __( 'Display list of commenters, instead of users.', 'quick-mail' ) . '</dd>';
    		$dc3 = '<dd>' . __( 'Comments are often disabled on older content.', 'quick-mail' ) . '</dd>';
    		$dc4 = '<dd>' . __( 'Comments must be enabled to reply.', 'quick-mail' ) . '</dd>';
    		$dc_val = '<dd>' . __( 'Invalid mail addresses are not displayed.', 'quick-mail' ) . '</dd>';
    		if ( $is_admin_user ) {
    			$dc_disable = '<strong>' . __('Select Disable Replies to Comments to remove this feature.', 'quick-mail') . '</strong>';
    			$dc_grant = __('Grant Authors permission to reply to comments', 'quick-mail');
    			$dc_author = admin_url('options-general.php?page=quick_mail_options#qm-authors');
    			$dc_link = "<a href='{$dc_author}'>{$dc_grant}</a>";
    			$dc_use = __('to let authors use this feature', 'quick-mail');
    			$note = '<strong>' . __( 'Administration', 'quick-mail' ) . ' :</strong>';
    			$dc6 = "<dl><dt style='margin-top:2em;'>{$note}</dt><dd>{$dc_disable}</dd><dd>{$dc_see} {$dc_link} {$dc_use}.</dd>";
    			$dc7 = "<dd>Email domains are always validated.</dd>";
    			$dc5 = "<dd>{$dc_see} {$dc_enabled} {$dc_and} {$dc_settings} {$dc_info}{$dc6}{$dc7}{$rc5}</dl></dd>";
    		} else {
    			$dc5 = "<dd>{$dc_see} {$dc_enabled} {$dc_info}</dd>{$rc5}";
    		} // end if admin
    		$dcontent = "<dl><dt><strong>{$dc_head}</strong></dt>{$dc1}{$dc3}{$dc4}{$dc_val}{$dc5}</dl>";
		if ( $this->user_can_reply_to_comments( false ) ) {
	    		$screen->add_help_tab( array('id' => 'qm_commenter_help', 'title'	=> $dc_title, 'content' => $dcontent) );
		} // add comment help, if user can reply to comments

    		if ( user_can_richedit() ) {
    			$wpauto_link = '<a href="https://codex.wordpress.org/Function_Reference/wpautop">wpautop</a>';
    			$rc1 = __( 'Add line breaks and paragraphs to HTML mail', 'quick-mail' );
    			$rc2 = __( 'with', 'quick-mail' );
    			$rc3 = __( 'Many plugins change the WordPress editor', 'quick-mail' );
    			$rc4 = __( 'Test this option on your system to know if you need it', 'quick-mail' );
    			$rcontent = '<dl>';
    			$rcontent .= '<dt><strong>' . __( 'Add Paragraphs', 'quick-mail' ) . '</strong></dt>';
    			$rcontent .= '<dd>' . $rc1 . ' ' . $rc2 . ' ' . $wpauto_link . '.</dd>';
    			$rcontent .= '<dd>' . $rc3 . '.</dd>';
    			$rcontent .= '<dd>' . $rc4 . '.</dd>';
    			$rcontent .= $rc5 . '</dl>';
    			$screen->add_help_tab( array('id' => 'qm_wpautop_help',
    				'title'	=> __( 'Add Paragraphs', 'quick-mail' ), 'content' => $rcontent) );
    		} // end if need wpauto help

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
    		$content .= $rc5 . '</dl>';
    		$screen->add_help_tab( array('id' => 'qm_display_help',
        		'title'	=> __('User Display', 'quick-mail'), 'content' => $content) );

	} // add_qm_settings_help

	/**
	 * can user reply to comments? checks blog option, user option.
	 * @param boolean $site want site option, instead of user's option
	 * @return boolean if site allows comments or if user can reply to comments.
	 * @since 3.1.5
	 */
	function user_can_reply_to_comments( $site ) {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		$cannot_reply = '';
		if ( is_multisite() ) {
			$cannot_reply = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
		} else {
			$cannot_reply = get_option( 'quick_mail_cannot_reply', 'N' );
		} // end if multisite

		if ( $site ) {
			return ( 'Y' != $cannot_reply );
		} // end if want site option only, for comment list

		if ( 'Y' == $cannot_reply ) {
			return false;
		} // end if comment replies are disabled

		if ( 'author' == $this->qm_get_role () ) {
			$allowed = is_multisite () ? get_blog_option ( $blog, 'authors_quick_mail_privilege', 'N' ) : get_option ( 'authors_quick_mail_privilege', 'N' );
			if ('Y' != $allowed) {
				return false;
			} // end if not allowed to reply with Quick Mail
		} // end if author

		$option = get_user_option( 'show_quick_mail_commenters', get_current_user_id() );
		if ( 'Y' != $option ) {
			return false;
		} // end if user does not want comments

		return true;
	} // end user_can_reply_to_comments

	/**
	 * Quick Mail general help.
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
		$mac_names = __( 'Press &lt;Command&gt; while clicking, to select multiple users.', 'quick-mail' );
		$win_names = __( 'Press &lt;Control&gt; while clicking, to select multiple users.', 'quick-mail' );
		$mob_names = __( 'You can select multiple users', 'quick-mail' );
		$nhelp = '';
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
			$attachment_help = '<p>' . __( 'File uploads were disabled by system administrator', 'quick-mail' ) . '</p>';
		} else if ( !empty( $_SERVER['HTTP_USER_AGENT'] ) && 1 == preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) ) {
			$attachment_help = '<p>' . __( 'File uploads are not available on your device', 'quick-mail' ) . '</p>';
		} else {
			$attachment_help = '<p>' . __( 'You can attach multiple files to your message', 'quick-mail' );
			if ( !wp_is_mobile() ) {
				$attachment_help .= ' ' . __( 'from up to six directories', 'quick-mail' );
			} // end if mobile
			$attachment_help .= '.</p>';
			$mac_files = __( 'Press &lt;Command&gt; while clicking, to select multiple files.', 'quick-mail' );
			$win_files = __( 'Press &lt;Control&gt; while clicking, to select multiple files.', 'quick-mail' );
			$mob_files = __( 'You can select multiple files', 'quick-mail' );
			$nhelp = '';
			if (wp_is_mobile()) {
				$nhelp = $mob_files;
			} else {
				$b = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
				if ( preg_match( '/macintosh|mac os x/i', $b ) ) {
					$nhelp = $mac_files;
				} else {
					$nhelp = $win_files;
				} // end if
			} // end if
			$attachment_help .= "<p>{$nhelp}</p>";
		} // end if uploads
		$screen->add_help_tab( self::get_qm_help_tab() );
		$you = wp_get_current_user();
		if ( 'Y' == get_user_option( 'show_quick_mail_commenters', $you->ID ) ) {
			$screen->add_help_tab( self::get_qm_comment_help_tab() );
		} else {
			$screen->add_help_tab( array(
					'id'	=> 'qm_cc_help_tab',
					'title'	=> $cc_title,
					'content'	=> "<p>{$cc_help}</p>"));
		} // end if replying to commenters

		$screen->add_help_tab( array('id' => 'qm_attach_help_tab',
				'title'	=> $attachment_title,
				'content'	=> $attachment_help) );
	} // end add_qm_help

   /**
    * use by admin print styles to add css to admin.
    */
   public function init_quick_mail_style() {
      wp_enqueue_style( 'quick-mail', plugins_url( '/lib/css/quick-mail.css', __FILE__) , array(), null, 'all' );
   } // end init_quick_mail_style

   /**
    * load translations.
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
         	$links[] = '<a href="https://wordpress.org/plugins/quick-mail/faq/" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
         	$links[] = '<a href="https://wordpress.org/support/plugin/quick-mail" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
      } // end if adding links
      return $links;
   } // end qm_plugin_links

   /**
    * add Settings to action links. filters plugin_action_links
    * @param array $links current action links
    * @param string $file plugin to be tested
    */
	public function qm_action_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$blog = is_multisite() ? get_current_blog_id() : null;
			$url = get_admin_url( $blog, 'options-general.php?page=quick_mail_options' );
			$qm_link = sprintf('<a href="%s">%s</a>', $url, __( 'Settings', 'quick-mail' ) );
			array_unshift( $links, $qm_link );
	   	} // end if adding links
   		return $links;
	} // end qm_action_links

	/**
	 * check if plugin is active.
	 *
	 * does not require exact name like is_plugin_active()
	 *
	 * @param string $pname plugin name
	 * @return boolean is this plugin active?
	 * @since 3.2.0
	 */
	public function qm_is_plugin_active( $pname ) {
		$result = false;
		$your_plugins = is_multisite() ?
		get_blog_option( get_current_blog_id(), 'active_plugins', array() ) :
		get_option( 'active_plugins', array() );
		if ( empty( $your_plugins ) || !is_array( $your_plugins ) || 1 > count( $your_plugins ) ) {
			return $result;
		} // end if no plugins

		foreach ( $your_plugins as $p ) {
			if ( $result = stristr( $p, $pname ) ) {
				break;
			} // end if match
		} // end foreach

		return $result;
	} // end qm_is_plugin_active

	/**
	 * is site using Replacement? Check for active plugin with Sendgrid in name.
	 *
	 * @param $check_from boolean default false. check if Sengrid from mail is set?
	 * @return boolean replace is active and optionally if config has an email address.
	 * @since 3.1.9
	 */
	public function got_replacement_info( $check_from = false ) {
		if ( $this->qm_is_plugin_active( 'mailgun' ) ) {
			error_log('2300 mailgun active?'); // TODO
			return false;
		} // end if Mailgun is active. cannot use Sengrid with Mailgun.

		if ( !$this->qm_is_plugin_active( 'sendgrid' ) ) {
			error_log('2305 SENDGRID not active?'); // TODO
			return false;
		} // end if sendgrid is not active

		// check for Sendgrid email
		if ( $check_from ) {
			$sg_email = '';
			if ( is_multisite() ) {
				$sg_email = get_site_option( 'sendgrid_from_email', '');
				if ( empty( $sg_email ) ) {
					$sg_email = get_blog_option( get_current_blog_id(), 'sendgrid_from_email', 'N' );
				} // end if
			} else {
				$sg_email = get_option( 'sendgrid_from_email', '' );
			} // end if multisite

			return !empty( $sg_email ); 	// TODO is this a valid email address?
		} else {
			return true;
		} // end if want to check for Sendgrid from address
	} // end got_replacement_info

	/**
	 * get a name for text label of the replacement service settings and messages.
	 *
	 * @return string name of replacement service
	 * @todo add a setting, or add option to get it from credentials function.
	 * @since 3.2.1
	 */
	public function get_replacement_name() {
		return $this->got_replacement_info() ? __( 'Sendgrid', 'quick-mail' ) : '';
	} // end get_replacement_name TODO add setting

	/**
	 * get Sendgrid user info, if available
	 *
	 * @param array $wp_info 'name' => $name, 'email' => $email
	 * @return array updated array
	 * @since 3.1.9
	 */
	public function get_replacement_credentials( $wp_info ) {
		if ( ! $this->got_replacement_info() ) {
			return $wp_info;
		}
		$sg_name = '';
		$sg_email = '';
		if ( is_multisite() ) {
			$sg_name = get_site_option( 'sendgrid_from_name', '');
			if ( empty( $sg_name ) ) {
			$sg_name = get_blog_option( get_current_blog_id(), 'sendgrid_from_name', 'N' );
			} // end if
			$sg_email = get_site_option( 'sendgrid_from_email', '');
			if ( empty( $sg_email ) ) {
				$sg_email = get_blog_option( get_current_blog_id(), 'sendgrid_from_email', 'N' );
			} // end if
		} else {
			$sg_name = get_option( 'sendgrid_from_name' );
			$sg_email = get_option( 'sendgrid_from_email' );
		} // end if multisite

		if ( empty( $sg_email ) ) {
			return $wp_info;
		} // end if no Sendgrid email
		if ( empty( $sg_name ) ) {
			$sg_name = $sg_email;
		} // end if no Sendgrid name
		return array('name' => $sg_name, 'email' => $sg_email);
	} // end get_replacement_credentials

	/**
	 * do we have Mailgun plugin and credentials?
	 *
	 * @param $check_from boolean should we check if Mailgrid override-from is set?
	 * @return boolean got mailgun info
	 * @since 3.2.0
	 */
	public function got_mailgun_info( $check_from ) {
		if ( !$this->qm_is_plugin_active( 'mailgun' ) ) {
			return false;
		} // end if not active

		$options = array();
		if ( !is_multisite() ) {
			$options = get_option( 'mailgun', array() );
		} else {
			$options = get_site_option( 'mailgun', array() );
			if ( empty($options) ) {
				$options = get_blog_option( get_current_blog_id(), 'mailgun', array() );
			} // end if no site option
		} // end if not multisite

		if ( $check_from && empty( $options['override-from'] ) ) {
			return false;
		} // end if do not replace sender credentials

		// from Mailgun plugin
		$apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options['apiKey'];
		$domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options['domain'];
		$useapi = (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI) ? MAILGUN_USEAPI : $options['useAPI'];
		if ( empty($useapi) || empty($domain) || empty($apiKey) ) {
			return false;
		} // end if not using API or missing key / domain

		$email = empty($options['from-address']) ? '' : $options['from-address'];
		if (empty($email) ) {
			return false;
		} // end if missing email address

		return true;
	} // end got_mailgun_info

	/**
	 * get Mailgun credentials.
	 *
	 * @param array $wp_info 'name' => $name, 'email' => $email
	 * @return string[] original or updated array
	 * @since 3.2.0
	 */
	function get_mailgun_info( $wp_info ) {
		if ( !$this->qm_is_plugin_active( 'mailgun' ) ) {
			return $wp_info;
		} // end if Mailgun is not active

		$options = array();
		if ( !is_multisite() ) {
			$options = get_option( 'mailgun', array() );
		} else {
			$options = get_site_option( 'mailgun', array() );
			if ( empty($options) ) {
				$options = get_blog_option( get_current_blog_id(), 'mailgun', array() );
			} // end if no site option
		} // end if not multisite

		if ( empty( $options['override-from'] ) || empty( $options['useAPI'] ) ) {
			return $wp_info;
		} // end if not using API or override from not set

		$apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options['apiKey'];
		// from mailgun.php
		$domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options['domain'];
		$useapi = (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI) ? MAILGUN_USEAPI : $options['useAPI'];
		if ( empty( $useapi ) || empty( $domain ) || empty( $apiKey ) ) {
			return $wp_info;
		} // end if not using API or missing key / domain

		$email = empty($options['from-address']) ? '' : $options['from-address'];
		if (empty($email) ) {
			return $wp_info;
		} // end if missing email address

		$name = '';
		if ( !empty( $options['from-name'] ) ) {
			$name = $options['from-name'];
		} else {
			$split = explode( '@', $email );
			if ( is_array( $split ) ) {
				$name = $split[0];
			} // end if found amphora
		} // end if missing sender name

		return array('name' => $name, 'email' => $email);
	} // end get_mailgun_info

	/**
	 * toggle mailgun override from credentials.
	 *
	 * override credentials for non-admin users.
	 *
	 * @since 3.2.0
	 */
	public function toggle_mailgun_override() {
		if ( 'administrator' == $this->qm_get_role() ) {
			return false;
		} // end if admin

		$options = array();
		$site = false;
		if ( !is_multisite() ) {
			$options = get_option( 'mailgun', array() );
		} else {
			$options = get_site_option( 'mailgun', array() );
			if ( empty($options) ) {
				$options = get_blog_option( get_current_blog_id(), 'mailgun', array() );
			} else {
				$site = true;
			} // end if no site option
		} // end if not multisite

		$override = $options['override-from'];
		$updated = ($override == '1') ? '0' : '1';
		$options['override-from'] = $updated;
		if ( !is_multisite() ) {
			update_option( 'mailgun', $options );
		} else {
			if ( $site ) {
				update_site_option( 'mailgun', $options );
			} else {
				update_blog_option( get_current_blog_id(), 'mailgun', $options );
			} // end if site option
		} // end if not multisite

		return true;
	} // end toggle_mailgun_override

	/**
	 * check if user is admin and replaced sender.
	 * @return boolean user replaced sender
	 * @since 3.2.1
	 */
	public function user_has_replaced_sender() {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		if ( $this->qm_is_admin( get_current_user_id(), $blog ) ) {
			$can_send = '';
			if ( is_multisite() ) {
				$can_send = get_blog_option( get_current_blog_id(), 'replace_quick_mail_sender', 'N' );
			} else {
				$can_send = get_option( 'replace_quick_mail_sender', 'N' );
			} // end if multisite

			return ('Y' == $can_send) ? $this->got_replacement_info( true ) : false;
		} // end if admin
		return false;
	} // end user_has_replaced_sender

	/**
	 * is user allowed to replace sender?
	 *
	 * Use Mailgun or Sengrid credentials. Mailgun has priority, it replaces wp_mail()
	 *
	 * @return boolean if user can replace sender
	 * @since 3.1.9
	 * @see Mailgun
	 */
	public function let_user_replace_sender() {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		if ( $this->qm_is_admin( get_current_user_id(), $blog ) ) {
			$can_send = '';
			if ( is_multisite() ) {
				$can_send = get_blog_option( get_current_blog_id(), 'replace_quick_mail_sender', 'N' );
			} else {
				$can_send = get_option( 'replace_quick_mail_sender', 'N' );
			} // end if multisite
			if ( 'Y' == $can_send || $this->got_mailgun_info( true ) ) {
				if ( $this->got_mailgun_info( true )  ) {
					add_filter('replace_quick_mail_sender', array($this, 'get_mailgun_info'), 10, 1);
				} else if ( $this->got_replacement_info( true ) ) {
					add_filter('replace_quick_mail_sender', array($this, 'get_replacement_credentials'), 10, 1);
				} // end if got Mailgun
			} // end if allowed to replace sender by option
		} // end if admin
	} // end let_user_replace_sender
} // end class
$quick_mail_plugin = QuickMail::get_instance();
