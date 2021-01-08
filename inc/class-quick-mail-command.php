<?php
/**
 * Mail a Web page or file with quick-mail and WP-CLI.
 *
 * @package QuickMail
 */
class Quick_Mail_Command extends WP_CLI_Command {

	/**
	 * Sender email address.
	 *
	 * @var string
	 */
	public $from = '';

	/**
	 * Sender name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Message content type.
	 *
	 * @var string default: text/html
	 */
	public $content_type = 'text/html';

	/**
	 * Users selected for role emails.
	 *
	 * @var string
	 * @since 3.5.1
	 */
	public $roles = '';

	/**
	 * Sender's character set.
	 *
	 * @var string
	 */
	public static $charset = '';

	/**
	 * Supported mail services.
	 *
	 * @var string
	 * @since 3.4.1
	 */
	public static $services = array( 'Mailgun', 'SendGrid', 'SparkPost' );

	/**
	 * Valid MIME types for messages.
	 *
	 * @var array
	 */
	public static $valid_mime = array( 'text/plain', 'text/html' ); // 'text/x-php'

	/**
	 * Mail the contents of a URL or file.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : Sender. Must be Administrator. Enter WordPress user ID or email address.
	 * Filter `quick_mail_cli_admin_only` to allow non-admin to send mail.
	 *
	 * <to>
	 * : Mail recipient. Enter WordPress user ID, WP role or email address.
	 * "all" sends mail to all users.
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
	 *     * wp quick-mail 5 editor notice.pdf Notice
	 *
	 *     Send notice.pdf with Notice subject from user 5 to all users with `editor` role.
	 *     Editor addresses are hidden with `Bcc`.
	 *
	 *     * wp quick-mail 5 all notice.pdf Notice
	 *
	 *     Send notice.pdf with Notice subject from user 5 to all users.
	 *     Recipient addresses are hidden with `Bcc`.
	 *
	 * @synopsis <from> <to> <url|filename> [<subject>] [<message_attachment_file>]
	 */
	public function __invoke( $args, $assoc_args ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-quickmailutil.php';
		self::$charset = get_bloginfo( 'charset' );
		$temp_msg      = '';
		$active        = '';
		foreach ( self::$services as $k ) {
			$s                    = strtolower( $k );
			$func                 = array( 'QuickMailUtil', "got_{$s}_info" );
			$replaced_credentials = call_user_func( $func, true );
			if ( $replaced_credentials ) {
				$active   = $k;
				$temp_msg = sprintf(
					'%s %s %s',
					__( 'Using', 'quick-mail' ),
					$k,
					__( 'credentials', 'quick-mail' )
				);
				break;
			} // end if
		} // end foreach

		if ( ! empty( $temp_msg ) ) {
			WP_CLI::warning( $temp_msg );
		} // end if using service

		$verify_domain = '';
		if ( is_multisite() ) {
			$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
		} // end if multisite

		$only_admin = apply_filters( 'quick_mail_cli_admin_only', true );
		$data       = $this->verify_email_or_id( $args[0], $only_admin ); // Admin only.
		if ( is_array( $data ) && ! empty( $data[0] ) && ! empty( $data[1] ) ) {
			$this->from = $data[0];
			$sender_id  = $data[1];
		} // end if got results

		$temp_msg = '';
		if ( empty( $this->from ) ) {
			$temp_msg = __( 'Only administrators can send mail with WP-CLI.', 'quick-mail' );
		} elseif ( ! QuickMailUtil::qm_valid_email_domain( $this->from, $verify_domain ) ) {
			$temp_msg = __( 'Invalid Sender Address.', 'quick-mail' );
		} // end if invalid user or address.

		if ( ! empty( $temp_msg ) ) {
			WP_CLI::error( $temp_msg ); // Exit.
		} // end if we have an error message.

		$to = '';
		if ( ! is_numeric( $args[1] ) && ! strstr( $args[1], '@' ) ) {
			$all_roles = $this->qm_get_roles();
			if ( ! in_array( $args[1], $all_roles, true ) && 'all' !== $args[1] ) {
				$temp_msg = sprintf(
					'%s: %s',
					__( 'Invalid Role', 'quick-mail' ),
					$args[1]
				);
				WP_CLI::error( $temp_msg ); // Exit.
			} // end if invalid role.

			$this->roles = $this->qm_get_role_email( $args[1], $sender_id );
			if ( empty( $this->roles ) ) {
				if ( 'administrator' === $args[1] ) {
					$to       = $this->from;
					$temp_msg = __( 'You are the only administrator.', 'quick-mail' );
					WP_CLI::warning( $temp_msg ); // Warning.
				} else {
					if ( 'all' === $args[1] ) {
						$temp_msg = __( 'Cannot send to all. You are the only user.', 'quick-mail' );
					} else {
						$temp_msg = sprintf(
							'%s: %s',
							__( 'No users for role', 'quick-mail' ),
							$args[1]
						);
					} // Else not all.
					WP_CLI::error( $temp_msg ); // Exit.
				}
			}
		} else {
			$data = $this->verify_email_or_id( $args[1], false ); // Not Admin only.
			if ( is_array( $data ) && ! empty( $data[0] ) && ! empty( $data[1] ) ) {
				$to = $data[0];
			} // end if got results

			$temp_msg = '';
			$a_split  = explode( '@', $to );
			$j        = count( $a_split );
			if ( 2 !== $j ) {
				$to = '';
			} elseif ( QuickMail::is_banned_domain( $a_split[1] ) ) {
				$to       = '';
				$temp_msg = sprintf( '%s : %s', __( 'Recipient address is blocked', 'quick-mail' ), $args[1] );
			}

			if ( empty( $to ) || ! QuickMailUtil::qm_valid_email_domain( $to, $verify_domain ) ) {
				if ( empty( $temp_msg ) ) {
					$temp_msg = sprintf( '%s : %s', __( 'Invalid Recipient Address', 'quick-mail' ), $args[1] );
				}
				WP_CLI::error( $temp_msg ); // Exit.
			} // end if invalid recipient.
		} // end if recipient is a role.

		$url          = '';
		$subject      = '';
		$domain       = '';
		$sending_file = false;
		$file         = '';
		if ( 'http' === substr( $args[2], 0, 4 ) ) {
			$url = str_replace( '&#038;', '&', esc_url( $args[2] ) );
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$temp_msg = __( 'Invalid URL', 'quick-mail' );
				$hurl     = htmlspecialchars( $url, ENT_QUOTES, self::$charset, false );
				WP_CLI::error( "{$temp_msg}: {$hurl}" ); // Exit.
			} // end if invalid URL.

			$domain = wp_parse_url( $url, PHP_URL_HOST );
		} else {
			if ( ! file_exists( $args[2] ) ) {
				$temp_msg = __( 'File not found', 'quick-mail' );
				WP_CLI::error( $temp_msg ); // Exit.
			} // end if file not found

			if ( empty( filesize( $args[2] ) ) ) {
				$temp_msg = __( 'Empty file', 'quick-mail' );
				$html     = htmlspecialchars( $args[2], ENT_QUOTES, self::$charset, false );
				WP_CLI::error( "$temp_msg: {$html}" ); // Exit.
			} // end if empty file.

			$url          = $args[2];
			$sending_file = true;
		} // end if URL.

		$subject = isset( $args[3] ) ? html_entity_decode( $args[3], ENT_QUOTES, self::$charset ) : '';

		// Get sender info.
		$query_args = array();
		if ( $only_admin ) {
			$query_args = array(
				'search'         => $this->from,
				'search_columns' => array( 'user_email' ),
				'role'           => 'Administrator',
			);
		} else {
			$query_args = array(
				'search'         => $this->from,
				'search_columns' => array( 'user_email' ),
			);
		}
		$user_query = new WP_User_Query( $query_args );
		if ( 1 > count( $user_query->results ) ) {
			$temp_msg = __( 'Invalid user', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // Exit.
		} // end if email not found.

		$user = null;
		foreach ( $user_query->results as $u ) {
			if ( $u->user_email === $this->from ) {
				$user = $u;
				break;
			} // end if user
		} // end foreach
		if ( empty( $user ) || $user->user_email !== $this->from ) {
			$temp_msg = __( 'Invalid user', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // Exit.
		} // end if unknown email.

		if ( empty( $user->user_firstname ) || empty( $user->user_lastname ) ) {
			$this->name = $user->display_name;
		} else {
			$this->name = "{$user->user_firstname} {$user->user_lastname}";
		} // end if missing first or last name

		$message   = '';
		$mime_type = '';

		$attachments = array();
		if ( ! $sending_file ) {
			$data = $this->get_wp_site_data( $url );
			if ( is_wp_error( $data ) ) {
				$temp_msg = preg_replace( '/curl error .+: /i', '', WP_CLI::error_to_string( $data ) );
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
			if ( ! in_array( $fmime, self::$valid_mime, true ) ) {
				$ext   = str_replace( '+', '_', explode( '/', $fmime ) ); // No + allowed in file name.
				$fext  = ( ! is_array( $ext ) || empty( $ext[1] ) ) ? __( 'unknown', 'quick-mail' ) : $ext[1];
				$temp  = QuickMailUtil::qm_get_temp_path();
				$fname = $temp . 'qm' . strval( time() ) . ".{$fext}"; // Temp file name.
				if ( empty( file_put_contents( $fname, $message ) ) ) {
					$temp_msg = __( 'Error saving content', 'quick-mail' ) . ' : ' . $fmime;
					WP_CLI::error( $temp_msg );
				} // end if cannot save temp file
				$sending_file = true;
				$url          = $fname;
			} // end if remote link cannot be sent as a mail message

			if ( ! $sending_file && empty( $subject ) ) {
				$pattern = '/title>(.+)<\/title>/';
				$found   = array();
				preg_match( $pattern, $message, $found );
				if ( ! empty( $found ) && ! empty( $found[1] ) ) {
					$subject = html_entity_decode( $found[1], ENT_QUOTES, self::$charset );
				} else {
					$subject = $domain;
				}
			} // end if need subject
		} // end if getting Web page

		if ( $sending_file ) {
			$mime_type = mime_content_type( $url );
			// Note: only checking for replaced attachment title, if sending file.
			if ( ! in_array( $mime_type, self::$valid_mime, true ) ) {
				$attachments = array( $url );
				$file        = isset( $args[4] ) ? $args[4] : ''; // Removed sanitize_file_name().
				if ( ! empty( $file ) && ! $this->valid_attachment_message( $file ) ) {
					$file = '';
				} // end if file OK.

				if ( ! empty( $file ) ) {
					add_filter( 'quick_mail_cli_attachment_message', array( $this, 'quick_mail_cli_attachment_message' ), 1, 1 );
					$message  = apply_filters( 'quick_mail_cli_attachment_message', $file );
					$temp_msg = __( 'Replaced attachment message.', 'quick-mail' );
					WP_CLI::log( $temp_msg );
				} else {
					$amsg    = sprintf( '%s : %s', __( 'Please see attachment', 'quick-mail' ), basename( $url ) );
					$message = apply_filters( 'quick_mail_cli_attachment_message', $amsg );
				} // end if got separate attachment for message
			} else {
				$message            = file_get_contents( $url );
				$this->content_type = ( 'text/html' === $mime_type ) ? $mime_type : 'text/plain';
			} // end if not text file

			if ( empty( $subject ) ) {
				$smsg    = __( 'For Your Eyes Only', 'quick-mail' );
				$subject = apply_filters( 'quick_mail_cli_attachment_subject', $smsg );
			} // end if no subject.
		} elseif ( isset( $args[4] ) && ! empty( $args[4] ) ) {
			$temp_msg = __( 'Not sending file. Attachment message ignored.', 'quick-mail' );
			WP_CLI::warning( $temp_msg ); // Extra arg.
		} // end if sending file.

		// Set filters and send.
		add_filter( 'wp_mail_content_type', array( $this, 'type_filter' ), 2500, 2500 );

		// If not replaced, set name and from.
		if ( empty( $active ) ) {
			add_filter( 'wp_mail_from', array( $this, 'from_filter' ), 2500, 2500 );
			add_filter( 'wp_mail_from_name', array( $this, 'name_filter' ), 2500, 2500 );
		} // end if

		$headers = array( "From: \"{$this->name}\" <{$this->from}>\r\n" );
		if ( 'SparkPost' === $active ) {
			add_filter( 'wpsp_transactional', '__return_false', 2500 );
			add_filter( 'wpsp_reply_to', array( $this, 'get_sender_value' ), 2500 );
			WP_CLI::log( 'Filtering SparkPost reply-to' );
		} else {
			$headers[] = "Reply-To: {$this->from}\r\n";
		} // end if Sparkpost.

		if ( ! empty( $this->roles ) ) {
			if ( strstr( $this->roles, ',' ) ) {
				$to        = $this->from;
				$headers[] = "Bcc: {$this->roles}";
			} else {
				$to = $this->roles;
			}
		} // end if sending to roles.

		$recipients = QuickMailUtil::count_recipients( $headers );
		if ( 100 < $recipients ) {
			$temp_msg = __( 'Cannot send mail to over 100 recipients.', 'quick-mail' );
			WP_CLI::error( $temp_msg );
		} // end if too many recipients.

		if ( defined( 'QUICK_MAIL_TESTING' ) && QUICK_MAIL_TESTING ) {
			$bottom = '';
			foreach ( $headers as $one ) {
				$bottom .= "{$one}\r\n";
			} // end foreach.

			$top      = sprintf( "%s\r\n%s : %s", __( 'TEST MODE', 'quick-mail' ), __( 'To', 'quick-mail' ), $to );
			$temp_msg = "{$top}\r\n{$bottom}";
			WP_CLI::log( $temp_msg );
			exit;
		} elseif ( ! wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
			$this->remove_qm_filters( $file, $active );
			$temp_msg = __( 'Error sending mail', 'quick-mail' );
			WP_CLI::error( $temp_msg );
		} // end if error sending mail

		$this->remove_qm_filters( $file, $active );
		if ( ! empty( $this->roles ) ) {
			if ( strstr( $this->roles, ',' ) ) {
				$this->roles = str_replace( ',', ', ', $this->roles );
				$this->roles = wordwrap( $this->roles, 65 );
			}
			$to = "{$args[1]} : {$this->roles}";
		} // end if using roles.

		if ( $sending_file ) {
			$temp_msg = sprintf(
				'%s %s %s %s',
				__( 'Sent', 'quick-mail' ),
				basename( $url ),
				__( 'to', 'quick-mail' ),
				$to
			);
		} else {
			$temp_msg = sprintf( '%s %s', __( 'Sent email to', 'quick-mail' ), $to );
		} // end if sending file
		WP_CLI::success( $temp_msg );
		exit;
	} // end _invoke

	/**
	 * Get send value for reply-to filters
	 *
	 * @param string $old_value ignored: value to filter.
	 * @since 3.4.1
	 */
	public function get_sender_value( $old_value ) {
		return $this->from;
	} // end get_sender_value

	/**
	 * Convenience function to remove filters.
	 *
	 * @param string $file if not empty, also remove attachment message filter.
	 * @param string $active name of active service.
	 */
	public function remove_qm_filters( $file, $active ) {
		if ( ! empty( $file ) ) {
			remove_filter( 'quick_mail_cli_attachment_message', array( $this, 'quick_mail_cli_attachment_message' ), 1 );
		} // end if attached message

		if ( 'SparkPost' === $active ) {
			remove_filter( 'wpsp_reply_to', array( $this, 'get_sender_value' ), 2500 );
			remove_filter( 'wpsp_transactional', '__return_false', 2500 );
		} // end if SparkPost

		remove_filter( 'wp_mail_content_type', array( $this, 'type_filter' ), 2500 );
		if ( empty( $active ) ) {
			remove_filter( 'wp_mail_from', array( $this, 'from_filter' ), 2500 );
			remove_filter( 'wp_mail_from_name', array( $this, 'name_filter' ), 2500 );
		} // end if
	} // end remove_qm_filters

	/**
	 * Is the attachment message valid? does it exist and is text or HTML?
	 *
	 * Displays warnings for invalid type and not found.
	 *
	 * @param string $filename file name for attachment message.
	 * @return boolean if file is valid
	 */
	public function valid_attachment_message( $filename ) {
		if ( file_exists( $filename ) && ! empty( filesize( $filename ) ) ) {
			$data  = file_get_contents( $filename );
			$finfo = new finfo( FILEINFO_MIME );
			$fdata = explode( ';', $finfo->buffer( $data ) );
			$fmime = is_array( $fdata ) ? $fdata[0] : '';
			$ok    = in_array( $fmime, self::$valid_mime, true );
			if ( ! $ok ) {
				$temp_msg = __( 'Invalid message type', 'quick-mail' ) . ' : ' . $fmime;
				WP_CLI::error( $temp_msg );
			}
			return $ok;
		} // end if file exists
		$temp_msg = __( 'File not found', 'quick-mail' ) . ' : ' . $filename;
		WP_CLI::warning( $temp_msg );
		return false;
	} // end valid_attachment_message

	/**
	 * Try to read attachment description from an external text file.
	 *
	 * @param string $orig_msg user text, might be valid file name.
	 * @return string file contents or default message.
	 */
	public function quick_mail_cli_attachment_message( $orig_msg ) {
		$message = __( 'You have an attachment.', 'quick-mail' );
		if ( file_exists( $orig_msg ) ) {
			$data  = file_get_contents( $orig_msg );
			$finfo = new finfo( FILEINFO_MIME );
			$fdata = explode( ';', $finfo->buffer( $data ) );
			$fmime = is_array( $fdata ) ? $fdata[0] : '';
			if ( ! in_array( $fmime, self::$valid_mime, true ) ) {
				return $message;
			} else {
				return $data;
			} // end if invalid attachment
		} // end if
		return empty( $orig_msg ) ? $message : $orig_msg;
	} // end quick_mail_cli_attachment_message

	/**
	 * Filter for wp_mail_content_type.
	 *
	 * @param string $type MIME type.
	 * @return string text/html
	 */
	public function type_filter( $type ) {
		return $this->content_type;
	} // end type_filter

	/**
	 * Filter for wp_mail_from.
	 *
	 * @param string $f from address: ignored.
	 * @return string sender email address
	 */
	public function from_filter( $f ) {
		return $this->from;
	} // end from_filter

	/**
	 * Filter for wp_mail_from_name.
	 *
	 * @param string $n name: ignored.
	 * @return string sender name
	 */
	public function name_filter( $n ) {
		return $this->name;
	} // end from_filter

	/**
	 * Get an array of site's roles, to validate input.
	 *
	 * @return array role keys
	 * @since 3.5.1
	 */
	private function qm_get_roles() {
		global $wp_roles;
		$data = array();
		foreach ( $wp_roles->roles as $key => $value ) {
			$data[] = $key;
		} // end foreach
		sort( $data );
		return $data;
	} // end qm_get_roles

	/**
	 * Get string of role email addresses, separated by commas.
	 *
	 * @param string  $input user input.
	 * @param integer $sender_id user ID of sender, for exclude.
	 * @return string role email addresses, separated by commas
	 * @since 3.5.1
	 */
	private function qm_get_role_email( $input, $sender_id ) {
		$args = array();
		if ( 'all' === $input ) {
			$args = array(
				'exclude' => $sender_id,
				'fields'  => array( 'user_email' ),
			);
		} else {
			$args = array(
				'exclude' => $sender_id,
				'role'    => $input,
				'fields'  => array( 'user_email' ),
			);
		} // end if want all users or role.
		$user_query = new WP_User_Query( $args );
		$j          = count( $user_query->results );
		if ( empty( $j ) ) {
			return '';
		} // end if no match.

		$got = '';
		for ( $i = 0; $i < $j; $i++ ) {
			$got .= $user_query->results[ $i ]->user_email . ',';
		} // end for
		return substr( $got, 0, -1 );
	} // end qm_get_role_email.

	/**
	 * Connect to remote site as Chrome browser. Return error string or array with data.
	 *
	 * @param string $site url of site.
	 * @return mixed WP_Error or array with site data.
	 */
	private function get_wp_site_data( $site ) {
		$chrome = 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1062.0 Safari/536.3';
		$args   = array( 'user-agent' => $chrome );
		$data   = wp_remote_get( $site, $args );
		if ( is_wp_error( $data ) ) {
			return $data;
		} // end if WP Error

		$code = empty( $data['response']['code'] ) ? 500 : $data['response']['code'];
		if ( 200 !== $code ) {
			if ( 404 === $code ) {
				$title    = __( 'Not found', 'quick-mail' );
				$temp_msg = sprintf( '%s %s', $title, $site );
				return new WP_Error( '404', $temp_msg );
			} else {
				$temp_msg = sprintf( '(%d) %s %s', $code, __( 'Cannot connect to', 'quick-mail' ), $site );
				$title    = __( 'Error', 'quick-mail' );
				return new WP_Error( $title, $temp_msg );
			} // end if 404
		}
		return $data;
	} // end get_wp_site_data

	/**
	 * Return email address from user ID, with optional check for Administrator.
	 *
	 * @param mixed   $from ID number or email address.
	 * @param boolean $admin_only limit search to Administrators.
	 * @return array email address, user ID
	 */
	private function verify_email_or_id( $from, $admin_only ) {
		if ( ! is_numeric( $from ) && ! $admin_only ) {
			return array( $from, 1 );
		} // end if not numeric or admin only

		$args = array();
		if ( is_numeric( $from ) ) {
			if ( is_multisite() ) {
				if ( $admin_only ) {
					$args = array(
						'blog_id' => get_current_blog_id(),
						'include' => array( $from ),
						'role'    => 'Administrator',
					);
				} else {
					$args = array(
						'blog_id' => get_current_blog_id(),
						'include' => array( $from ),
					);
				} // end if admin
			} else {
				if ( $admin_only ) {
					$args = array(
						'include' => array( $from ),
						'role'    => 'Administrator',
					);
				} else {
					$args = array( 'include' => array( $from ) );
				} // end if admin
			} // end if
		} else {
			$from = sanitize_email( $from );
			if ( is_multisite() ) {
				if ( $admin_only ) {
					$args = array(
						'blog_id'    => get_current_blog_id(),
						'user_email' => $from,
						'role'       => 'Administrator',
					);
				} else {
					$args = array(
						'blog_id'    => get_current_blog_id(),
						'user_email' => $from,
					);
				} // end if admin
			} else {
				if ( $admin_only ) {
					$args = array(
						'search'         => $from,
						'search_columns' => array( 'user_email' ),
						'role'           => 'Administrator',
					);
				} else {
					$args = array(
						'search'         => $from,
						'search_columns' => array( 'user_email' ),
					);
				} // end if admin
			} // end if
		} // end if numeric

		$user_query = new WP_User_Query( $args );
		return empty( $user_query->results ) ? '' : array( $user_query->results[0]->data->user_email, $user_query->results[0]->data->ID );
	} // end verify_email_or_id
} // end Quick_Mail_Command

WP_CLI::add_command( 'quick-mail', 'Quick_Mail_Command' );
