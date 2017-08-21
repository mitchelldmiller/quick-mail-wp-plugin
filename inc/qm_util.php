<?php
/**
 * Quick Mail validation functions for Javascript and quick-mail-cli.php
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
	   		if ( 2 > strlen( $name ) ) {
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
	   	if (!empty($invalid)) {
	   		if (empty($duplicate)) {
	   			return "{$invalid}\t" . $saved;
	   		}
	   		return "{$invalid}<br><br>Duplicate:<br>{$duplicate}\t" . $saved;
	   	}

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

		if ( function_exists( 'idn_to_ascii' ) ) {
			$intl = idn_to_ascii( $a_split[1] );
			if ( !empty( $intl ) ) {
				$a_split[1] = $intl;
				$qm_address = "{$a_split[0]}@{$a_split[1]}";
			}
		} // end if we have idn_to_ascii

		if ( !filter_var( $qm_address, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		} // end if PHP rejects address

       return ( 'N' == $validate_option ) ? true : checkdnsrr( $a_split[1], 'MX' );
   } // end qm_valid_email_domain

	/**
	 * check if plugin is active.
	 *
	 * does not require exact name like WordPress is_plugin_active()
	 *
	 * @param string $pname
	 *        	plugin name
	 * @return boolean is this plugin active?
	 */
	public static function qm_is_plugin_active( $pname ) {
		$result = false;
		$your_plugins = is_multisite() ? get_blog_option( get_current_blog_id(), 'active_plugins', array() ) : get_option( 'active_plugins', array() );
		if ( empty( $your_plugins ) || ! is_array( $your_plugins ) || 1 > count( $your_plugins ) ) {
			return $result;
		} // end if no plugins

		foreach ( $your_plugins as $p ) {
			if ($result = stristr( $p, $pname ) ) {
				break;
			} // end if match
		} // end foreach

		return $result;
	} // end qm_is_plugin_active


} // end class
