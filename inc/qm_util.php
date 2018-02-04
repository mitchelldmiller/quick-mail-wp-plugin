<?php
/**
 * Quick Mail utility functions for Javascript and quick-mail-cli.php
 * @package QuickMail
 * @version 3.4.1
 */
class QuickMailUtil {

   public function __construct() { }

	/**
	 * find system temp path.
	 * test upload_tmp_dir, sys_get_temp_dir().
	 *
	 * @return string path
	 */
	public static function qm_get_temp_path() {
		$path = ini_get( 'upload_tmp_dir' );
		if ( !empty( $path ) ) {
			return trailingslashit( $path );
		}
		return trailingslashit( sys_get_temp_dir() );
	} // end qm_get_temp_path

	/**
	 * count input characters without spaces.
	 *
	 * @param string $text original text
	 * @param integer $min_len minimum acceptable length
	 * @param integer $max_len maximum acceptable length
	 * @return boolean input length
	 */
	public static function check_char_count($text, $min_len = 1, $max_len = 80) {
		$charset = is_multisite() ? get_blog_option( get_current_blog_id(), 'blog_charset', 'UTF-8' ) : get_option( 'blog_charset', 'UTF-8' );

		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $text, $charset ) : strlen( $text );
		if ( $len < $min_len || $len > $max_len ) {
			return false;
		} // end if original string is too short or long

		$content = preg_replace( '/\s/', '', $text );
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $content, $charset ) : strlen( $content );
		return ( $len >= $min_len );
	} // end check_char_count

   /**
    * find duplicates in array. does not check case.
    * @see QuickMailUtil::filter_email_input() for use
    * @param array $orig_names
    * @return string duplicates
    * @since 1.4.0
    */
	public static function qm_find_dups($orig_names) {
		$retval = '';
		$all_dups = array();
		$j = count($orig_names);
	   	for ($i = 0; $i < $j; $i++) {
	   		$name = $orig_names[$i];
	   		$your_len = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	   		if ( 2 > $your_len ) {
	   			continue;
	   		}
	   		$orig_names[$i] = '';
	   		if ( in_array( $name, $orig_names ) ) {
	   			$all_dups[] = $name;
	   		}
	   	} // end foreach
	   	$dups = array_unique($all_dups);
	   	foreach ($dups as $name) {
	   		$hsave = htmlspecialchars($name, ENT_HTML5);
	   		$retval .= "{$hsave}<br>";
	   	}

	   	return $retval;
   } // end qm_find_dups

   /**
    * remove invalid email addresses from user input.
    *
    * addresses can be delimited with comma or space.
    *
    * @param string $to recipient
    * @param string $original user input
    * @return string filtered string
    *
    * @since 1.4.0
    */
	public static function filter_email_input($to, $original, $validate_option) {
		$search = array('%40', '%20', '+', ' ', '%2C');
		$replace = array('@', ',', ',', ',', ',');
	   	$commas = strtolower( str_ireplace( $search, $replace, trim( $original ) ) );
		$raw_dup = strtolower( str_ireplace( $search, $replace, trim( $to ) ) );
	   	$exploded_names = explode( ',', $commas );
	   	$all_names = array_unique( $exploded_names );
	   	$j = count( $all_names );
	   	// check for duplicate recipients
	   	$duplicate = self::qm_find_dups( $exploded_names );
	   	$invalid = '';
	   	$retval = array();
	   	foreach ($all_names as $name) {
	   		if ( empty( $name ) ) {
	   			continue;
	   		} // end if empty

	   		$hname = htmlspecialchars( html_entity_decode(strip_tags($name)), ENT_QUOTES );
	   		if ( !self::qm_valid_email_domain( $name, $validate_option ) ) {
	   			$invalid .= "{$hname}<br>";
	   			$name = '';
	   			continue;
	   		} // end if invalid name

	   		if ( $name == $raw_dup ) {
	   			if ( !strstr( $duplicate, $name ) ) {
	   				$duplicate .= "{$hname}<br>";
	   			}
	   			$name = '';
	   			continue;
	   		} // end if sender is a recipient

	   		$retval[] = $name;
	   	} // end for

	   	$saved = empty( $retval[0] ) ? '' : implode( ',', $retval );
	   	if ( !empty( $invalid ) ) {
	   		if ( empty( $duplicate ) ) {
	   			return "{$invalid}\t" . $saved;
	   		} // end if not duplicate

	   		$word = __( 'Duplicate', 'quick-mail' );
	   		return "{$invalid}<br><br>{$word}:<br>{$duplicate}\t" . $saved;
	   	} // end if invalid

	   	if ( !empty( $duplicate ) ) {
	   		return " {$duplicate}\t" . $saved;
	   	} // end if duplicates

   		return $saved;
   } // end filter_email_input

   /**
    * remove invalid email addresses from user input.
    *
    * addresses can be delimited with comma or space.
    *
    * @param string $original
    * @return array filtered input
    * @since 1.4.0
    */
	public static function filter_user_emails($original) {
	   	$commas = str_replace( ' ', ',', $original );
	   	$data = explode( ',', strtolower( $commas ) );
	   	$retval = array();
	   	foreach ( $data as $qm_address ) {
	   		if ( self::qm_valid_email_domain( $qm_address ) ) {
		   		$retval[] = $qm_address;
	   		} // end if valid address
	   	} // end foreach

		return array_unique( $retval );
	} // end filter_user_emails

   /**
    * validate email domain with DNS record.
    * translate domain if validation on and idn_to_ascii is available.
    * rejects length greater than 255 or less than 5
    * returns value of string tests or value of checkdnsrr
    *
    * @since 1.0.0
    * @param string $qm_address email address
    * @param string $validate_option Y or N
    * @return bool valid email address, or valid address and valid domain
    */
	public static function qm_valid_email_domain( $qm_address, $validate_option = 'N' ) {
		$length = strlen( $qm_address );
   		if ( 5 > $length || 255 < $length ) {
   			return false;
   		} // end if invalid length

		$a_split = explode( '@', trim( $qm_address ) );
		if ( ! is_array( $a_split ) || 2 != count( $a_split ) || empty( $a_split[0] ) || empty( $a_split[1] ) ) {
			return false;
		} // return false if missing amphora

		if ( !filter_var( $qm_address, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		} // end if PHP rejects address

		$is_ip = is_string( filter_var( $a_split[1], FILTER_VALIDATE_IP ) ); // IP address

		if ( ! strpos( $a_split[1], '.' ) ) {
			return false;
		} // end if no dots - localhost?

		if ( false == filter_var( $a_split[1], FILTER_VALIDATE_IP )  ) {
			if ( function_exists( 'idn_to_ascii' ) ) {
				$intl = idn_to_ascii( $a_split[1] );
				if ( !empty( $intl ) ) {
					$a_split[1] = $intl;
					$qm_address = "{$a_split[0]}@{$a_split[1]}";
				}
			} // end if we have idn_to_ascii

			$dots = explode( '.', $a_split[1] );
			$j = count( $dots );
			$domain = ( $j > 2 ) ?  "{$dots[$j - 2]}.{$dots[$j - 1]}" : $a_split[1];
		} // end if not IP address

       return ( 'N' == $validate_option ) ? true : checkdnsrr( $domain, 'MX' );
   } // end qm_valid_email_domain

	/**
	 * check if plugin is active.
	 *
	 * does not require exact name like WordPress is_plugin_active()
	 *
	 * @param string $pname 	plugin name, or unique portion of name.
	 * @return boolean is this plugin active?
	 */
   public static function qm_is_plugin_active( $pname ) {
      $result = false;
      $your_plugins = is_multisite () ?
      get_blog_option ( get_current_blog_id (), 'active_plugins', array () ) :
      get_option ( 'active_plugins', array () );

      if ( !is_multisite() && ( !is_array ( $your_plugins ) || 1 > count ( $your_plugins ) ) ) {
         return $result;
      } // end if not multisite and no plugins

      $all_plugins = array();
      if ( is_multisite() ) {
         $more_plugins = get_site_option ( 'active_sitewide_plugins' );
         $all_plugins = array_unique( array_merge( $your_plugins, array_keys( $more_plugins ) ) );
      } else {
         $all_plugins = $your_plugins;
      } // end if multisite
      foreach ( $all_plugins as $one ) {
         if ($result = stristr ( $one, $pname )) {
            break;
         } // end if match
      } // end foreach

      return $result;
   } // end qm_is_plugin_active

	/**
	 * get default sender name from WP
	 * @param string $old_name for filter
	 * @return string sender name
	 * @since 3.3.1
	 */
	public static function get_wp_user_name($old_name = '') {
   		$you = wp_get_current_user();
   		$name = '';
	   	if ( !empty( $you->user_firstname ) && !empty( $you->user_lastname ) ) {
	   		$name = "{$you->user_firstname} {$you->user_lastname}";
	   	} else {
	   		$name = $you->display_name;
	   	} // end if user has first and last names

	   	if (empty($name) ) {
	   		$title = __( 'Mail Error', 'quick-mail' );
	   		$message = __( 'Error: Incomplete User Profile', 'quick-mail' );
	   		$link = "<a href='/wp-admin/profile.php'>{$title}</a>";
	   		$direction = is_rtl() ? 'rtl' : 'ltr';
	   		$args = array( 'response' => 200, 'back_link' => true, 'text_direction' => $direction );
	   		wp_die( sprintf( '<h1 role="alert">%s</h1>', $title, $message, $args ) );
	   	} // end if no name

	   	return $name;
   } // end get_wp_user_name

   /**
    * get default user email from WP
    * @param string email arg for filter (not used)
    * @return string email address
    * @since 3.3.1
    */
	public static function get_wp_user_email($email = '') {
		$you = wp_get_current_user();
		if ( empty( $you->user_email ) ) {
			$title = __( 'Mail Error', 'quick-mail' );
			$message = __( 'Error: Incomplete User Profile', 'quick-mail' );
			$link = "<a href='/wp-admin/profile.php'>{$title}</a>";
			$direction = is_rtl() ? 'rtl' : 'ltr';
			$args = array( 'response' => 200, 'back_link' => true, 'text_direction' => $direction );
			wp_die( sprintf( '<h1 role="alert">%s</h1>', $title, $message, $args ) );
		} // end if no email

		return $you->user_email;
   } // end get_wp_user_name

   /**
    * do we have SparkPost plugin and credentials?
    *
    * @param $check_from boolean should we check if SparkPost name, email are set?
    * @return boolean got SparkPost info
    * @since 3.3.1
    */
	public static function got_sparkpost_info( $check_from ) {
	   	if ( !self::qm_is_plugin_active( 'sparkpost' ) ) {
	   		return false;
	   	} // end if not active

	   	if ( !class_exists( 'WPSparkPost\SparkPost' ) ) {
	   		return false;
	   	} // end if cannot find class

	   	$active = WPSparkPost\SparkPost::get_setting( 'enable_sparkpost' );
	   	if ( empty( $active ) ) {
	   		return false;
	   	}

	   	$pw = WPSparkPost\SparkPost::get_setting( 'password' );
	   	if ( empty( $pw ) ) {
	   		return false;
	   	} // end if no password

	   	if ( false == $check_from ) {
	   		return true;
	   	} // end if checking install

	   	// this should be enough
	   	$from_name = WPSparkPost\SparkPost::get_setting( 'from_name' );
	   	$from_name = apply_filters( 'wpsp_sender_name', $from_name );

	   	$from_email = WPSparkPost\SparkPost::get_setting( 'from_email' );
	   	$from_email = apply_filters( 'wpsp_sender_email', $from_email );

	   	if ( empty( $from_email) && empty( $from_name) ) {
	   		return false;
	   	} // end if no name or email

	   	// we have name or email
	   	// not validating email. QuickMailUtil::qm_valid_email_domain( $from_email, 'Y' )
		return true;
	} // end got_sparkpost_info

   /**
    * get SparkPost credentials.
    *
    * @param array $wp_info 'name' => $name, 'email' => $email, 'reply_to' => $reply_to
    * @return string[] original or updated array
    * @since 3.3.1
    */
   public static function get_sparkpost_info( $wp_info ) {
   	if ( !self::got_sparkpost_info( true ) ) {
   		return $wp_info;
   	} // end if sparkpost is not active

   	// SparkPost does not use definitions
   	$sp_email = WPSparkPost\SparkPost::get_setting( 'from_email' );
   	if ( empty( $sp_email ) ) {
   		$sp_email = apply_filters( 'wpsp_sender_email', $wp_info['email'] );
   	} // end if
   	$email = empty( $sp_email ) ? $wp_info['email'] : $sp_email;

   	$sp_name = WPSparkPost\SparkPost::get_setting( 'from_name' );
   	if ( empty( $sp_name ) ) {
   		$sp_name = apply_filters( 'wpsp_sender_name', $wp_info['name'] );
   	}
   	$name = empty( $sp_name ) ? $wp_info['name'] : $sp_name;
   	$reply_to = apply_filters( 'wpsp_reply_to', $wp_info['reply_to'] );
   	return array('name' => $name, 'email' => $email, 'reply_to' => $reply_to);
   } // end get_sparkpost_info

   /**
    * do we have Mailgun plugin and credentials?
    *
    * @param $check_from boolean should we check if Mailgun override-from is set?
    * @return boolean got Mailgun info
    * @since 3.2.0
    */
	public static function got_mailgun_info( $check_from ) {
	   	if ( !self::qm_is_plugin_active( 'mailgun' ) ) {
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

	   	// from Mailgun plugin
	   	$apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options['apiKey'];
	   	$domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options['domain'];
	   	if ( empty( $domain ) || empty( $apiKey ) ) {
	   		return false;
	   	} // end if not using API or missing key / domain

	   	if ( false == $check_from ) {
	   		return true;
	   	} // end if not checking name, email

	   	if ( $check_from && empty( $options['override-from'] ) ) {
	   		return false;
	   	} // end if do not replace sender credentials

		return !empty( $options['from-address'] );
   } // end got_mailgun_info

   /**
    * get Mailgun credentials.
    *
    * @param array $wp_info 'name' => $name, 'email' => $email, 'reply_to' => $reply_to
    * @return string[] original or updated array
    * @since 3.2.0
    */
   public static function get_mailgun_info( $wp_info ) {
   	if ( !self::qm_is_plugin_active( 'mailgun' ) ) {
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
   	if (  empty( $domain ) || empty( $apiKey ) ) {
   		return $wp_info;
   	} // end if not using API or missing key / domain

   	$email = $wp_info['email'];
   	/* from Mailgun:
   	 * 1. From address given by headers - {@param $from_addr_header}
   	 *  2. From address set in Mailgun settings
   	 *  3. From `MAILGUN_FROM_ADDRESS` constant
   	 *  4. From address constructed as `wordpress@<your_site_domain>`
   	 *  sender domain should match Mailgun domain
   	 */

   	$mg_email = '';
   	if ( !empty( $options['from-address'] ) ) {
   		$mg_email = $options['from-address'];
   	} else if ( defined('MAILGUN_FROM_ADDRESS') ) {
   		$mg_email = MAILGUN_FROM_ADDRESS;
   	}

   	if ( !empty( $mg_email ) ) {
   		if ( !self::matches_email_domain( $email, $mg_email ) ) {
   			$email = $mg_email;
   		} // end if
   	} // end if got Mailgun value

   	$name = $wp_info['name'];
   	if ( !empty( $options['from-name'] ) ) {
   		$name = $options['from-name'];
   	} else if ( defined('MAILGUN_FROM_NAME') ) {
   		$name = MAILGUN_FROM_NAME;
   	}  // end if have sender name

   	// Mailgun plugin does not have reply-to setting.
   	return array('name' => $name, 'email' => $email, 'reply_to' => $wp_info['reply_to']);
   } // end get_mailgun_info

   /**
    * is site using SendGrid? Check for active plugin with SendGrid in name.
    *
    * @param $check_from boolean default false. check if Sengrid from mail is set?
    * @return boolean replace is active and optionally if config has an email address.
    * @since 3.1.9
    */
   	public static function got_sendgrid_info( $check_from = false ) {
	   	if ( self::qm_is_plugin_active( 'mailgun' ) ) {
	   		return false;
	   	} // end if Mailgun is active. cannot use Sengrid with Mailgun.

	   	if ( class_exists( 'WPSparkPost\SparkPost' ) ) {
	   		$active = WPSparkPost\SparkPost::get_setting( 'enable_sparkpost' );
	   		if ( !empty( $active ) ) {
	   			return false;
	   		} // end if SparkPost is active
	   	} // end if have SparkPost

	   	if ( !self::qm_is_plugin_active( 'sendgrid' ) ) {
	   		return false;
	   	} // end if sendgrid is not active

	   	if ( false == $check_from ) {
	   		return true;
	   	} // end if not checking name, email

	   	// check for SendGrid email
	   	if ( defined( 'SENDGRID_FROM_EMAIL') && SENDGRID_FROM_EMAIL ) {
	   		return true;
	   	}
	   	$sg_email = '';
	   	if ( is_multisite() ) {
	   		$sg_email = get_site_option( 'sendgrid_from_email', '');
	   		if ( empty( $sg_email ) ) {
	   			$sg_email = get_blog_option( get_current_blog_id(), 'sendgrid_from_email', 'N' );
	   		} // end if
	   	} else {
	   		$sg_email = get_option( 'sendgrid_from_email', '' );
	   	} // end if multisite

	   	return !empty( $sg_email );
	} // end got_sendgrid_info



   /**
    * get SendGrid user info, if available
    *
    * @param array $wp_info 'name' => $name, 'email' => $email
    * @return array updated array
    * @since 3.1.9
    */
   public static function get_sendgrid_info( $wp_info ) {
   	if ( ! self::got_sendgrid_info() ) {
   		return $wp_info;
   	}

   	// Sendgrid_Tools::get_from_name, get_from_email, get_reply_to test for define first.
   	$sg_name = defined( 'SENDGRID_FROM_NAME' ) ? SENDGRID_FROM_NAME : '';
   	$sg_email = defined( 'SENDGRID_FROM_EMAIL' ) ? SENDGRID_FROM_EMAIL : '';
   	$sg_reply_to = defined( 'SENDGRID_REPLY_TO' ) ? SENDGRID_REPLY_TO : '';

   	if ( is_multisite() ) {
   		$sg_name = empty( $sg_name ) ? get_site_option( 'sendgrid_from_name', '') : get_blog_option( get_current_blog_id(), 'sendgrid_from_name', '' );
   		$sg_email = empty( $sg_email ) ? get_site_option( 'sendgrid_from_email', '') : get_blog_option( get_current_blog_id(), 'sendgrid_from_email', '' );
   	} else {
   		if ( empty( $sg_name) ) {
   			$sg_name = get_option( 'sendgrid_from_name' );
   		}
   		if ( empty( $sg_email) ) {
   			$sg_email = get_option( 'sendgrid_from_email' );
   		}
   	} // end if multisite

   	$email = empty( $sg_email ) ? $wp_info['email'] : $sg_email;
   	$name = empty( $sg_name ) ? $wp_info['name'] : $sg_name;
   	$reply_to = '';
   	if ( !empty( $sg_reply_to ) ) {
   		$reply_to = $sg_reply_to;
   	} else if ( empty( $wp_info['reply_to'] ) ) {
   		$reply_to = $wp_info['reply_to'];
   	} else {
   		$reply_to = $email;
   	} // end if

   	return array('name' => $name, 'email' => $email, 'reply_to' => $reply_to);
   } // end get_sendgrid_info


	/**
	 * is user using the same domain as SparkPost?
	 * @return boolean if user and SparkPost are using the same domain.
	 * @since 3.3.3
	 */
	public static function using_sparkpost_domain() {
		$wp_domain = '';
		$spark_domain = '';
   		$wpmail = self::get_wp_user_email();
   		$sparkmail  = WPSparkPost\SparkPost::get_setting( 'from_email' );
   		$qsplit = explode( '@', $sparkmail );
   		if ( is_array( $qsplit ) && !empty( $qsplit[1] ) ) {
   			$spark_domain = $qsplit[1];
   		} // end if

   		$qsplit = explode( '@', $wpmail );
   		if ( is_array( $qsplit ) && !empty( $qsplit[1] ) ) {
   			$wp_domain = $qsplit[1];
   		} // end if

   		return ( !empty( $spark_domain ) && $spark_domain == $wp_domain );
	} // end using sparkpost domain

	/**
	 * should SparkPost transaction be toggled for attachments?
	 *
	 * @param array $attachments
	 * @since 3.3.1
	 */
	public static function toggle_sparkpost_transactional( $attachments ) {
   		$retval = false;
   		$trans = WPSparkPost\SparkPost::get_setting( 'transactional' );
   		if ( !empty( $trans ) && is_array( $attachments ) && !empty( $attachments ) ) {
   			$retval = true;
   			add_filter( 'wpsp_transactional', '__return_zero', 2017, 0 );
   		} // end if
   		return $retval;
	} // end toggle_sparkpost_transactional

	/**
	 * get email domain to check for matching service sender domain
	 * @param string $email email address
	 * @return string domain or empty if missing amphora
	 * @since 3.3.5
	 */
	public static function get_email_domain( $email ) {
		$a_split = explode( '@', trim( $email ) );
		if ( ! is_array( $a_split ) || 2 != count( $a_split ) || empty( $a_split[0] ) || empty( $a_split[1] ) ) {
			return '';
		} // return false if missing amphora

		return $a_split[1];
	} // end get_email_domain

	/**
	 * check if both email addresses use the same top level domain.
	 * @param string $e1 email 1
	 * @param string $e2 email 2
	 * @return boolean do addresses use the same domain?
	 */
	public static function matches_email_domain( $e1, $e2 ) {
		$d1 = self::get_email_domain( $e1 );
		$d2 = self::get_email_domain( $e2 );
		$len1 = strlen( $d1 );
		$len2 = strlen( $d2 );
		$result = ( $len1 > $len2 ) ? strstr( $d1, $d2 ) : strstr( $d2, $d1 );
		return is_string( $result );
	} // end matches_email_domain

} // end class
