<?php
/**
 * Mail a Web page or file with quick-mail.
 * @version 3.4.2
 */
class Quick_Mail_Command extends WP_CLI_Command {

	public $from = '', $name = '', $content_type = 'text/html';

	public static $charset = '';

	/**
	 * supported mail services.
	 * @var string
	 * @since 3.4.1
	 * @todo needs translation
	 */
	public static $services = array(	'Mailgun', 'SendGrid', 'SparkPost');

	/**
	 * valid MIME types for messages.
	 * @var array
	 */
	public static $VALID_MIME = array('text/plain', 'text/html'); // 'text/x-php'

	/**
	 * Mail the contents of a URL or file.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : Sender. Must be Administrator. Enter WordPress user ID or email address.
	 *
	 * <to>
	 * : Mail recipient. Enter WordPress user ID or email address.
	 *
	 * <url or filename>
	 * : Url or file to send.
	 * HTML or Text file is sent as a message. Other content is sent as an attachment.
	 *
	 * [<subject>]
	 * : Optional subject.
	 * Default subject for Url is html document's title.
	 * Default subject for text is "For Your Eyes Only."
	 *
	 * [<message attachment file>]
	 * : Optional file to replace default message, when sending attachment.
	 * Contents of this file will replace default message: "Please see attachment."
	 *
	 * ## EXAMPLES
	 *
	 *     * wp quick-mail fred@example.com mary@example.com https://example.com "Hello Mary"
	 *
	 *     Send https://example.com from fred@example.com to mary@example.com
	 *     with "Hello Mary" subject
	 *
	 *     If content is not text/plain or text/html, link will be sent as an attachment.
	 *     If subject is omitted and link content is HTML, page title is used for subject.
	 *
	 *     * wp quick-mail fred@example.com mary@example.com image.png "Beautiful Image"
	 *
	 *     Send image.png to mary@example.com as attachment with subject "Beautiful Image"
	 *     Default attachment message is "Please see attachment : filename."
	 *
	 *     * wp quick-mail fred@example.com mary@example.com resume.doc Application cover.txt
	 *
	 *     Send resume.doc to mary@example.com with "Application" subject.
	 *     Message will be the contents of cover.txt.
	 *
	 * @synopsis <from> <to> <url|filename> [<subject>] [<message_attachment_file>]
	 */
	public function __invoke( $args, $assoc_args ) {
		require_once plugin_dir_path( __FILE__ ) . 'qm_util.php';
		self::$charset = get_bloginfo( 'charset' );
		$temp_msg = '';
		$active = '';
		foreach ( self::$services as $k ) {
			$s = strtolower( $k );
			$func = array('QuickMailUtil', "got_{$s}_info");
			$replaced_credentials = call_user_func( $func, true );
			if ( $replaced_credentials ) {
				$active = $k;
				$temp_msg = sprintf( "%s %s %s", 	__( 'Using', 'quick-mail' ),
						$k, 	__( 'credentials', 'quick-mail' ) );
				break;
			} // end if
		} // end foreach

		if ( !empty( $temp_msg) ) {
			WP_CLI::warning( $temp_msg );
		} // end if using service

		$verify_domain = '';
		if ( is_multisite() ) {
			$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
		} // end if multisite

		$this->from = $this->verify_email_or_id( $args[0], true ); // admin only
		$temp_msg = '';
		if ( empty( $this->from ) ) {
			$temp_msg = __( 'Only administrators can send mail with WP-CLI.', 'quick-mail' );
		} else if ( !QuickMailUtil::qm_valid_email_domain( $this->from, $verify_domain ) ) {
			$temp_msg = __( 'Invalid Sender Address', 'quick-mail' );
		} // end if invalid user or address

		if ( !empty( $temp_msg ) ) {
			WP_CLI::error( $temp_msg ); // exit
		} // end if we have an error message

		$to = $this->verify_email_or_id( $args[1], false );
		if ( empty( $to ) || !QuickMailUtil::qm_valid_email_domain( $to, $verify_domain ) ) {
			$temp_msg = __( 'Invalid Recipient Address', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // exit
		} // end if invalid recipient

		$url = '';
		$subject = '';
		$domain = '';
		$sending_file = false;
		$file = '';
		if ( 'http' == substr( $args[2], 0, 4) ) {
			$url = str_replace( '&#038;', '&', esc_url( $args[2] ) );
			if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$temp_msg = __( 'Invalid URL', 'quick-mail' );
				$hurl = htmlspecialchars( $url, ENT_QUOTES, self::$charset, false );
				WP_CLI::error( "$temp_msg: {$hurl}" ); // exit
			} // end if invalid URL

			$domain = parse_url( $url, PHP_URL_HOST );
		} else {
			if ( !file_exists( $args[2] ) ) {
				$temp_msg = __( 'File not found', 'quick-mail' );
				WP_CLI::error( $temp_msg ); // exit
			} // end if file not found

			if ( empty( filesize ( $args[2] ) ) ) {
				$temp_msg = __( 'Empty file', 'quick-mail' );
				$html = htmlspecialchars( $args[2], ENT_QUOTES, self::$charset, false );
				WP_CLI::error( "$temp_msg: {$html}" ); // exit
			} // end if empty file

			$url = $args[2];
			$sending_file = true;
		} // end if URL

		$subject = isset( $args[3] ) ? html_entity_decode( $args[3], ENT_QUOTES, self::$charset ) : '';

		// get sender info
		$query_args = array('search' => $this->from, 'search_columns' => array('user_email'), 'role' => 'Administrator');
		$user_query = new WP_User_Query( $query_args );
		if ( 1 > count( $user_query->results ) ) {
			$temp_msg = __( 'Invalid user', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // exit
		} // end if email not found

		$user = null;
		foreach ( $user_query->results as $u ) {
			if ( $u->user_email == $this->from ) {
				$user = $u;
				break;
			} // end if user
		} // end foreach
		if ( empty( $user ) || $user->user_email != $this->from ) {
			$temp_msg = __( 'Invalid user', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // exit
		} // end if unknown email

		if ( empty( $user->user_firstname ) || empty( $user->user_lastname ) ) {
			$this->name = $user->display_name;
		} else {
			$this->name = "\"{$user->user_firstname} {$user->user_lastname}\"";
		} // end if missing first or last name

		$message = '';
		$mime_type = '';

		$attachments = array();
		if ( !$sending_file ) {
			$data = $this->get_wp_site_data( $url );
			if ( is_wp_error( $data ) ) {
				$temp_msg = preg_replace( '/curl error .+: /i', '',  WP_CLI::error_to_string( $data ) );
				WP_CLI::error( $temp_msg );
			} // end if error

			$message = wp_remote_retrieve_body( $data );
			if ( empty( $message ) ) {
				$temp_msg = __( 'No content', 'quick-mail' );
				WP_CLI::error( $temp_msg );
			} // end if no content

			$finfo = new finfo( FILEINFO_MIME );
			$fdata = explode( ';', $finfo->buffer( $message ) );
			$fmime = is_array( $fdata ) ? $fdata[0] : '';
			if ( !in_array( $fmime, self::$VALID_MIME) ) {
				$ext = str_replace( '+', '_', explode( '/', $fmime ) ); // no + in file name
				$fext = ( !is_array( $ext ) || empty( $ext[1] ) ) ? __( 'unknown', 'quick-mail' ) : $ext[1];
				$temp = QuickMailUtil::qm_get_temp_path();
				$fname = $temp . 'qm' . strval( time() ) . ".{$fext}"; // temp file name
				if ( empty( file_put_contents( $fname, $message ) ) ) {
					$temp_msg = __( 'Error saving content', 'quick-mail' ) . ' : ' . $fmime;
					WP_CLI::error( $temp_msg );
				} // end if cannot save temp file
				$sending_file = true;
				$url = $fname;
			} // end if remote link cannot be sent as a mail message

			if ( !$sending_file && empty( $subject ) ) {
				$pattern = "/title>(.+)<\/title>/";
				preg_match( $pattern, $message, $found );
				if ( !empty( $found ) && !empty( $found[1] ) ) {
					$subject = html_entity_decode( $found[1], ENT_QUOTES, self::$charset );
				} else {
					$subject = $domain;
				}
			} // end if need subject
		} // end if getting Web page

		if ( $sending_file ) {
			$mime_type = mime_content_type( $url );
			// TODO only checking for replaced attachment title, if sending file.
			if ( !in_array( $mime_type, self::$VALID_MIME) ) {
				$attachments = array($url);
				$file = isset( $args[4] ) ? $args[4] : ''; // removed sanitize_file_name()
				if ( !empty( $file ) && !$this->valid_attachment_message( $file ) 	) {
					$file = '';
				} // end if file OK

				if ( !empty( $file ) ) {
					add_filter( 'quick_mail_cli_attachment_message', array($this, 'quick_mail_cli_attachment_message'), 1, 1 );
					$message = apply_filters( 'quick_mail_cli_attachment_message', $file );
					$temp_msg = __( 'Replaced attachment message.', 'quick-mail' );
					WP_CLI::log( $temp_msg );
				} else {
					$amsg = sprintf( '%s : %s', __( 'Please see attachment', 'quick-mail' ), basename( $url ) );
					$message = apply_filters( 'quick_mail_cli_attachment_message', $amsg );
				} // end if got separate attachment for message
			} else {
				$message = file_get_contents( $url );
				$this->content_type = ( 'text/html' == $mime_type ) ? $mime_type : 'text/plain';
			} // end if not text file

			if ( empty( $subject ) ) {
				$smsg = __( 'For Your Eyes Only', 'quick-mail' );
				$subject = apply_filters( 'quick_mail_cli_attachment_subject', $smsg );
			} // end if no subject
		} elseif ( isset( $args[4] ) && !empty( $args[4])) {
			$temp_msg = __( 'Not sending file. Attachment message ignored.', 'quick-mail' );
			WP_CLI::warning( $temp_msg ); // extra arg
		} // end if sending file

		// set filters and send
		add_filter( 'wp_mail_content_type', array($this, 'type_filter'), 2500, 2500 );
		// if not replaced, set name and from
		if ( empty( $active ) ) {
			add_filter( 'wp_mail_from', array($this, 'from_filter'), 2500, 2500 );
			add_filter( 'wp_mail_from_name', array($this, 'name_filter'), 2500, 2500 );
		} // end if

		// need from to avoid missing SERVER_NAME in wp_mail
		$headers = array("From: \"{$this->name}\" <{$this->from}>\r\n");
		// add reply to
		if ( 'SparkPost' == $active ) {
			add_filter( 'wpsp_transactional', '__return_false', 2500 );
			add_filter( 'wpsp_reply_to', array($this, 'get_sender_value' ), 2500 );
			WP_CLI::log( 'Filtering SparkPost reply-to' );
		} else {
			$headers[] = "Reply-To: {$this->from}\r\n";
		}

		if ( ! wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
			$this->remove_qm_filters( $file, $active );
			$temp_msg = __( 'Error sending mail', 'quick-mail' );
			WP_CLI::error( $temp_msg );
		} // end if error sending mail

		$this->remove_qm_filters( $file, $active );
		if ( $sending_file ) {
			$temp_msg = sprintf( '%s %s %s %s', __( 'Sent', 'quick-mail' ),
					basename( $url ), __( 'to', 'quick-mail' ), $to );
		} else {
			$temp_msg = sprintf( '%s %s', __( 'Sent email to', 'quick-mail' ), $to );
		} // end if sending file
		WP_CLI::success( $temp_msg );
		exit;
	} // end _invoke

	/**
	 * get send value for reply-to filters
	 * @param string $old_value
	 * @since 3.4.1
	 */
	public function get_sender_value( $old_value ) {
		return $this->from;
	} // end get_sender_value

	/**
	 * convenience function to remove filters.
	 *
	 * @param string $file if not empty, also remove attachment message filter.
	 * @param string $active name of active service
	 */
	public function remove_qm_filters( $file, $active ) {
		if ( !empty( $file ) ) {
			remove_filter( 'quick_mail_cli_attachment_message', array($this, 'quick_mail_cli_attachment_message'), 1 );
		} // end if attached message

		if ( 'SparkPost' == $active ) {
			remove_filter( 'wpsp_reply_to', array($this, 'get_sender_value' ), 2500 );
			remove_filter( 'wpsp_transactional', '__return_false', 2500 );
		} // end if SparkPost

		remove_filter( 'wp_mail_content_type', array($this, 'type_filter'), 2500 );
		if ( empty( $active ) ) {
			remove_filter( 'wp_mail_from', array($this, 'from_filter'), 2500 );
			remove_filter( 'wp_mail_from_name', array($this, 'name_filter'), 2500 );
		} // end if
	} // end remove_qm_filters

	/**
	 * is the attachment message valid? does it exist and is text or HTML?
	 *
	 * displays warnings for invalid type and not found.
	 *
	 * @param string $filename
	 * @return boolean if file is valid
	 */
	public function valid_attachment_message( $filename ) {
		if ( file_exists( $filename ) && !empty( filesize ( $filename ) ) ) {
			$data = file_get_contents( $filename );
			$finfo = new finfo( FILEINFO_MIME );
			$fdata = explode( ';', $finfo->buffer( $data ) );
			$fmime = is_array( $fdata ) ? $fdata[0] : '';
			$ok = in_array( $fmime, self::$VALID_MIME );
			if ( !$ok ) {
				$temp_msg = __( 'Invalid message type', 'quick-mail' ) . ' : ' . $fmime;
				WP_CLI::warning( $temp_msg );
			}
			return $ok;
		} // end if file exists
		$temp_msg = __( 'File not found', 'quick-mail' ) . ' : ' . $filename;
		WP_CLI::warning( $temp_msg );
		return false;
	} // end valid_attachment_message

	public function quick_mail_cli_attachment_message( $orig_msg ) {
		$message = __( 'You have an attachment.', 'quick-mail' );
		if ( file_exists( $orig_msg ) ) {
			$data = file_get_contents( $orig_msg );
			$finfo = new finfo( FILEINFO_MIME );
			$fdata = explode( ';', $finfo->buffer( $data ) );
			$fmime = is_array( $fdata ) ? $fdata[0] : '';
			if ( !in_array( $fmime, self::$VALID_MIME ) ) {
				return $message;
			} else {
				return $data;
			} // end if invalid attachment
		} // end if
		return empty( $orig_msg ) ? $message : $orig_msg;
	} // end quick_mail_cli_attachment_message

	/**
	 * filter for wp_mail_content_type.
	 * @param string $type MIME type
	 * @return string text/html
	 */
	public function type_filter( $type ) {
		return $this->content_type;
	} // end type_filter

	/**
	 * filter for wp_mail_from.
	 * @param string $f from address: ignored.
	 * @return string sender email address
	 */
	public function from_filter( $f ) {
		return $this->from;
	} // end from_filter

	/**
	 * filter for wp_mail_from_name.
	 * @param string $n name: ignored.
	 * @return string sender name
	 */
	public function name_filter( $n ) {
		return $this->name;
	} // end from_filter

	/**
	 * Connect to remote site as Chrome browser. Return error string or array with data.
	 *
	 * @param string $site
	 * @return string|array
	 */
	private function get_wp_site_data( $site ) {
		$chrome = 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1062.0 Safari/536.3';
		$args = array('user-agent' => $chrome);
		$data = wp_remote_get( $site, $args );
		if ( is_wp_error( $data ) ) {
			return $data;
		} // end if WP Error

		$code = empty( $data['response']['code'] ) ? 500 : $data['response']['code'] ;
		if ( 200 != $code ) {
			if ( 404 == $code ) {
				$title = __( 'Not found', 'quick-mail' );
				$temp_msg = sprintf( "%s %s", $title, $site );
				return new WP_Error( '404', $temp_msg );
			} else {
				$temp_msg = sprintf( "(%d) %s %s", $code, __( 'Cannot connect to', 'quick-mail' ), $site );
				$title = __( 'Error', 'quick-mail' );
				return new WP_Error( $title, $temp_msg );
			} // end if 404
		}
		return $data;
	} // end get_wp_site_data

	/**
	 * Return email address from user ID, with optional check for Administrator.
	 *
	 * @param mixed $from ID number or email address.
	 * @param boolean $admin_only limit search to Administrators.
	 */
	private function verify_email_or_id( $from, $admin_only ) {
		if ( !is_numeric( $from ) && !$admin_only ) {
			return sanitize_email( $from );
		} // end if not numeric or admin only

		$args = array();
		if ( is_numeric( $from ) ) {
			if ( is_multisite() ) {
				if ( $admin_only ) {
					$args = array( 'blog_id' => get_current_blog_id(), 'include' =>  array($from), 'role' => 'Administrator' );
				} else {
					$args = array( 'blog_id' => get_current_blog_id(), 'include' =>  array($from) );
				} // end if admin
			} else {
				if ( $admin_only ) {
					$args = array( 'include' =>  array($from), 'role' => 'Administrator' );
				} else {
					$args = array( 'include' =>  array($from) );
				} // end if admin
			} // end if
		} else {
			$from = sanitize_email( $from );
			if ( is_multisite() ) {
				if ( $admin_only ) {
					$args = array( 'blog_id' => get_current_blog_id(), 'user_email' => $from , 'role' => 'Administrator' );
				} else {
					$args = array( 'blog_id' => get_current_blog_id(), 'user_email' => $from  );
				} // end if admin
			} else {
				if ( $admin_only ) {
					$args = array('search' => $from, 'search_columns' => array('user_email'), 'role' => 'Administrator');
				} else {
					$args = array('search' => $from, 'search_columns' => array('user_email'));
				} // end if admin
			} // end if
		} // end if numeric

		$user_query = new WP_User_Query( $args );
		return empty( $user_query->results ) ? '' : $user_query->results[0]->data->user_email;
	} // end verify_email_or_id
} // end Quick_Mail_Command

WP_CLI::add_command( 'quick-mail', 'Quick_Mail_Command' );
