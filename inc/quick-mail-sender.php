<?php
// quick-mail-sender.php

/**
 * Manage sending filters.
 * @version 3.4.2
 */
class QuickMailSender {

	/**
	 * name or email value for filter
	 * @var string $value
	 */
	public $value;

	/**
	 * reply to for services without reply to filter.
	 * @var string email address or empty
	 */
	public $reply_to;

	/**
	 * filter name for this instance.
	 * @var string $filter_name
	 */
	public $filter_name;

	/**
	 * priority for filter
	 * @var integer defaults to current year.
	 */
	public static $priority;

	/**
	 * SERVICES services
	 * @var string[]
	 */
	public static $SERVICES = array('sparkpost', 'mailgun', 'sendgrid');

	/**
	 * FIELDS values
	 * @var string[]
	 */
	public static $FIELDS = array('name', 'email', 'reply_to');

	public function __construct($service, $field, $value) {
		if ( !class_exists( 'QuickMail' ) ) {
			exit;
		} // exit if not called from QuickMail
		
		$invalid = false;
		self::$priority = date('Y');
		$this->reply_to = '';
		if ( empty( $service ) && $field == 'reply_to' ) {
			$this->reply_to = $value;
			return;
		}

		$lservice = strtolower($service);
		if ( !in_array( $service, self::$SERVICES ) ) {
			$invalid = true; // "{$service} is not in SERVICES.");
		}

		if (!in_array($field, self::$FIELDS) ) {
			$invalid = true; // "{$field} is not in FIELDS.");
		}

		if ( $invalid ) {
			$direction = is_rtl() ? 'rtl' : 'ltr';
			$args = array( 'response' => 200, 'back_link' => false, 'text_direction' => $direction );
			wp_die( sprintf( '<h1 role="alert">%s</h1>', __( 'Unknown Mail Service', 'quick-mail' ) , __( 'Mail Error', 'quick-mail' ), $args ) );
		} // end if invalid args

		$this->value = $value;
		if ( $lservice == 'sparkpost' ) {
			if ( 'name' == $field ) {
				$this->filter_name = 'wpsp_sender_name';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}
			if ( 'email' == $field ) {
				$this->filter_name = 'wpsp_sender_email';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' == $field ) {
				$this->filter_name = 'wpsp_reply_to';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}
		} // end sparkpost

		if ( $lservice == 'mailgun' ) {
			if ( 'name' == $field ) {
				$this->filter_name = 'wp_mail_from_name';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}

			if ( 'email' == $field ) {
				$this->filter_name = 'wp_mail_from';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' == $field ) {
				$this->reply_to = $value;
			}
		} // end mailgun

		if ( $lservice == 'sendgrid' ) {
			if ( 'name' == $field ) {
				$this->filter_name = 'wp_mail_from_name';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}

			if ( 'email' == $field ) {
				$this->filter_name = 'wp_mail_from';
				add_filter($this->filter_name, array($this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' == $field ) {
				$this->reply_to = $value;
			}
		} // end if sendgrid

	} // end constructor

	public function get_sender_value($v) {
		return $this->value;
	} // get_sender_value

	/**
	 * remove previously set mail filter
	 * @since 1.0.0
	 */
	public function remove_sender_filter() {
		if ( !empty( $this->filter_name ) ) {
			remove_filter($this->filter_name, array($this, 'get_sender_value'), self::$priority );
		} // end if not reply_to
	} // end remove_sender_filter

	/**
	 * get reply to value if it is not attached to a filter
	 * @return string reply-to address or empty if none or filtered
	 * @since 1.0.1
	 */
	public function get_reply_to() {
		if ( empty($this->filter_name) ) {
			return $this->reply_to;
		}
		return '';
	} // end get_reply_to

} // end QuickMailSender

?>