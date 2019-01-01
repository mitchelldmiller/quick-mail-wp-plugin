<?php
/**
 * Welcome to class-quickmailsender.php Tue Nov 20 2018 21:24
 *
 * @package QuickMail
 */

/**
 * Manage sending filters.
 *
 * @version 3.4.2
 */
class QuickMailSender {

	/**
	 * Name or email value for filter.
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * Reply to for services without reply to filter.
	 *
	 * @var string email address or empty
	 */
	public $reply_to;

	/**
	 * Filter name for this instance.
	 *
	 * @var string $filter_name
	 */
	public $filter_name;

	/**
	 * Priority for filter.
	 *
	 * @var integer defaults to current year.
	 */
	public static $priority;

	/**
	 * Supported services.
	 *
	 * @var string[]
	 */
	public static $services = array( 'sparkpost', 'mailgun', 'sendgrid' );

	/**
	 * Field values to filter.
	 *
	 * @var string[]
	 */
	public static $fields = array( 'name', 'email', 'reply_to' );

	/**
	 * Create class. Validate args. Die if invalid service.
	 *
	 * @param string $service mail service name.
	 * @param string $field name, email or reply_to.
	 * @param string $value replacement value for field.
	 */
	public function __construct( $service, $field, $value ) {
		if ( ! class_exists( 'QuickMail' ) ) {
			exit;
		} // exit if not called from QuickMail

		$invalid        = false;
		self::$priority = date( 'Y' );
		$this->reply_to = '';
		if ( empty( $service ) && 'reply_to' === $field ) {
			$this->reply_to = $value;
			return;
		}

		$lservice = strtolower( $service );
		if ( ! in_array( $service, self::$services, true ) ) {
			$invalid = true;
		}

		if ( ! in_array( $field, self::$fields, true ) ) {
			$invalid = true;
		}

		if ( $invalid ) {
			$direction = is_rtl() ? 'rtl' : 'ltr';
			$args      = array(
				'response'       => 200,
				'back_link'      => false,
				'text_direction' => $direction,
			);
			wp_die( sprintf( '<h1 role="alert">%s</h1>', esc_html( __( 'Unknown Mail Service', 'quick-mail' ) ), esc_html( __( 'Mail Error', 'quick-mail' ) ) ), $args );
		} // end if invalid args

		$this->value = $value;
		if ( 'sparkpost' === $lservice ) {
			if ( 'name' === $field ) {
				$this->filter_name = 'wpsp_sender_name';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}
			if ( 'email' === $field ) {
				$this->filter_name = 'wpsp_sender_email';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' === $field ) {
				$this->filter_name = 'wpsp_reply_to';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}
		} // end sparkpost

		if ( 'mailgun' === $lservice ) {
			if ( 'name' === $field ) {
				$this->filter_name = 'wp_mail_from_name';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}

			if ( 'email' === $field ) {
				$this->filter_name = 'wp_mail_from';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' === $field ) {
				$this->reply_to = $value;
			}
		} // end mailgun

		if ( 'sendgrid' === $lservice ) {
			if ( 'name' === $field ) {
				$this->filter_name = 'wp_mail_from_name';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}

			if ( 'email' === $field ) {
				$this->filter_name = 'wp_mail_from';
				add_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
			}

			if ( 'reply_to' === $field ) {
				$this->reply_to = $value;
			}
		} // end if sendgrid

	} // end constructor

	/**
	 * Get value of current filter.
	 *
	 * @param string $v ignored. Needed by filter.
	 */
	public function get_sender_value( $v ) {
		return $this->value;
	} // get_sender_value

	/**
	 * Remove previously set mail filter.
	 *
	 * @since 1.0.0
	 */
	public function remove_sender_filter() {
		if ( ! empty( $this->filter_name ) ) {
			remove_filter( $this->filter_name, array( $this, 'get_sender_value' ), self::$priority );
		} // end if not reply_to
	} // end remove_sender_filter

	/**
	 * Get reply to value if it is not attached to a filter.
	 *
	 * @return string reply-to address or empty if none or filtered
	 * @since 1.0.1
	 */
	public function get_reply_to() {
		if ( empty( $this->filter_name ) ) {
			return $this->reply_to;
		}
		return '';
	} // end get_reply_to

} // end QuickMailSender

