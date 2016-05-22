<?php
/**
 * @package QuickMail
 * @version 1.3.0
 */

/**
 * validation functions for Javascript
 */
class QuickMailUtil {

	public function __construct() { }

	/**
	 * validate email domain with DNS record. translate domain if idn_to_ascii
	 * is available. returns translated domain, empty string on error, or
	 * true if PHP validation failed and idn_to_ascii is not available.
	 *
	 * @since 1.0.0
	 * @param string $qm_address
	 * @return bool valid email address, optionally valid domain
	 */
	public static function qm_valid_email_domain( $qm_address, $validate_option ) {
		$a_split = explode( '@', $qm_address );
		if ( ! is_array( $a_split ) || 2 < count( $a_split ) || empty( $a_split[1] ) ) {
			return false;
		} // return false if missing ampersand

		$dots = strtolower( $a_split[1] );
		$test = explode( '.', $dots );
		if ( !is_array( $test ) || 2 < count( $test ) ) {
			return false;
		} // return false if no dots

		if ( 'N' == $validate_option ||
		( ( false == filter_var( $qm_address, FILTER_VALIDATE_EMAIL ) &&
		!function_exists( 'idn_to_ascii' ) ) ) ) {
			return true;
		} // return address if not validating, or validation failed and no idn_to_ascii

		if ( function_exists( 'idn_to_ascii' ) ) {
			$intl = idn_to_ascii( $a_split[1] );
			if ( !empty( $intl ) ) {
				$a_split[1] = $intl;
			}
		} // end if we have idn_to_ascii

		return checkdnsrr( $a_split[1], 'MX' );
	} // end qm_valid_email_domain
} // end class
