<?php
// qm_util.php 1.00 2-28-15
// shared class / static function

class QuickMailUtil {

	public function __construct() { }

	/**
	 * validate email domain with DNS record.
	 *
	 * @since 1.0.0
	 * @param string $qm_address
	 * @return boolean valid domain
	 */
	public static function qm_valid_email_domain( $qm_address ) {
		$result = false;
		$a_split = explode( '@', $qm_address );
		if ( ! is_array( $a_split ) || empty( $a_split[1] ) ) {
			return $result;
		} // sanity check

		$a_record = dns_get_record( $a_split[1], DNS_MX );
		if ( ! is_array( $a_record ) || ! isset( $a_record[0]['pri'] ) ) {
			return $result;
		} // end if invalid domain

		$j = count( $a_record );
		for ( $i = 0; ( $i < $j ) && ( $result == false ); $i++ ) {
			$result = ($a_record[$i]['pri'] > 0) || ( $a_record[$i]['host'] == $a_record[$i]['target'] );
		} // end check for a valid mail server

		return $result;
	} // end qm_valid_email_domain
} // end class