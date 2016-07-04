<?php
/**
 * @package QuickMail
 */

/**
 * validation functions for Javascript
 */
class QuickMailUtil {

   public function __construct() { }

   /**
    * validate email domain with DNS record.
    * translate domain if validation on and idn_to_ascii is available.
    * returns value of string tests or value of checkdnsrr
    *
    * @since 1.0.0
    * @param string $qm_address email address
    * @param string $validate_option Y or N
    * @return bool valid email address, or valid address and valid domain
    */
   public static function qm_valid_email_domain( $qm_address, $validate_option ) {
      $a_split = explode( '@', $qm_address );
      if ( ! is_array( $a_split ) || 2 < count( $a_split ) || empty( $a_split[1] ) ) {
         return false;
      } // return false if missing amphora

      $dots = strtolower( $a_split[1] );
      $test = explode( '.', $dots );
      if ( 'N' == $validate_option ) {
         return ( 2 == count($test) );
        }

      if ( function_exists( 'idn_to_ascii' ) ) {
         $intl = idn_to_ascii( $a_split[1] );
         if ( !empty( $intl ) ) {
            $a_split[1] = $intl;
         }
      } // end if we have idn_to_ascii

      return checkdnsrr( $a_split[1], 'MX' );
   } // end qm_valid_email_domain
} // end class
