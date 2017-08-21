<?php
/**
 * Mail a Web page or file with quick-mail.
 *
 * @author mitchelldmiller
 *
 */
class Quick_Mail_Command extends WP_CLI_Command {

	public $from = '', $name = '', $content_type = 'text/html';
	public static $charset = '';

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
	 * Url or Text file is sent as a message. Binary file is sent as an attachment.
	 *
	 * [<subject>]
	 * : Optional subject.
	 * Default subject for Url is html document's title.
	 * Default subject for text is "For Your Eyes Only."
	 *
	 * ## EXAMPLES
	 *
	 *     * wp quick-mail fred@example.com mary@example.com https://example.com "Hello Mary"
	 *
	 *     Sends https://example.com from fred@example.com to mary@example.com
	 *     with "Hello Mary" subject
	 *
	 *     Link's HTML page title will be used if optional subject is omitted.
	 *
	 *     * wp quick-mail fred@example.com mary@example.com image.png "Beautiful Image"
	 *
	 *     Sends image.png to mary@example.com as attachment with subject "Beautiful Image"
	 *     Default subject is "For Your Eyes Only."
	 *     Default attachment message is "Please see attachment : filename."
	 *
	 * @synopsis <from> <to> <url|filename> [<subject>]
	 */
	public function __invoke( $args, $assoc_args ) {
		require_once plugin_dir_path( __FILE__ ) . 'qm_util.php';
		self::$charset = get_bloginfo( 'charset' );
		$verify_domain = '';
		if ( is_multisite() ) {
			$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
		} // end if multisite

		$this->from = $this->verify_email_or_id( $args[0], true ); // admin only
		$temp_msg = '';
		if ( empty( $this->from ) ) {
			$temp_msg = __( 'Only administrators can send mail.', 'quick-mail' );
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
		if ( 'http' == substr( $args[2], 0, 4) ) {
			$url = str_replace('&#038;', '&', esc_url( $args[2] ) );
			if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$temp_msg = __( 'Invalid URL', 'quick-mail' );
				$hurl = htmlspecialchars($url, ENT_QUOTES, self::$charset, false);
				WP_CLI::error( "$temp_msg: {$hurl}"); // exit
			} // end if invalid URL

			$domain = parse_url( $url, PHP_URL_HOST );
		} else {
			if ( !file_exists( $args[2] ) ) {
				$temp_msg = __( 'File not found', 'quick-mail' );
				WP_CLI::error( $temp_msg ); // exit
			} // end if file not found

			if ( empty( filesize ( $args[2] ) ) ) {
				$temp_msg = __( 'Empty file', 'quick-mail' );
				$html = htmlspecialchars($args[2], ENT_QUOTES, self::$charset, false);
				WP_CLI::error( "$temp_msg: {$html}"); // exit
			} // end if empty file

			$url = $args[2];
			$sending_file = true;
		} // end if URL

		$subject = isset( $args[3] ) ? html_entity_decode( $args[3], ENT_QUOTES, self::$charset ) : '';

		// get sender info
		$args = array('search' => $this->from, 'search_columns' => array('user_email'), 'role' => 'Administrator');
		$user_query = new WP_User_Query( $args );
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
		if ( empty($user) || $user->user_email != $this->from ) {
			$temp_msg = __( 'Invalid user', 'quick-mail' );
			WP_CLI::error( $temp_msg ); // exit
		} // end if unknown email

		if ( empty( $user->user_firstname ) || empty( $user->user_lastname ) ) {
			$this->name = $user->display_name;
		} else {
			$this->name = "\"{$user->user_firstname} {$user->user_lastname}\"";
		} // end if missing first or last name

		$message = '';
		$attachments = array();
		if ( $sending_file ) {
			$mime_type = mime_content_type( $url );
			if ( 'text/' != substr( $mime_type, 0, 5) ) {
				$message = sprintf('%s : %s', __( 'Please see attachment', 'quick-mail' ), basename( $url ) );
				$attachments = array($url);
			} else {
				$message = file_get_contents( $url );
				$this->content_type = ( 'text/html' == $mime_type ) ? $mime_type : 'text/plain';
			} // end if not text file

			if (empty($subject)) {
				$subject = __( 'For Your Eyes Only', 'quick-mail' );
			} // end if no subject

			$temp_msg = sprintf( '%s %s %s %s', __( 'Sending file', 'quick-mail' ),
					basename( $url ), __( 'to', 'quick-mail' ), $to );
			WP_CLI::line( $temp_msg );
		} // end if sending file
		else {
			$temp_msg = sprintf( '%s %s %s %s', __( 'Sending', 'quick-mail' ),
					$domain, __( 'to', 'quick-mail' ), $to );
			WP_CLI::line( $temp_msg );
			$data = $this->get_wp_site_data( $url );
			if ( is_wp_error( $data ) ) {
				$temp_msg = preg_replace( '/curl error .+: /i', '',  WP_CLI::error_to_string( $data ) );
				WP_CLI::error( $temp_msg );
			} // end if error

			$message = wp_remote_retrieve_body( $data );
			if ( empty( $message ) ) {
				$temp_msg = __( 'No content', 'quick-mail' );
				WP_CLI::error( $temp_msg );
			}

			if ( empty( $subject ) ) {
				$pattern = "/title>(.+)<\/title>/";
				preg_match( $pattern, $message, $found );
				if ( !empty( $found ) && !empty( $found[1] ) ) {
					$subject = html_entity_decode( $found[1], ENT_QUOTES, self::$charset );
				} else {
					$subject = $this->get_wp_site_title( $domain );
				}
			} // end if need subject
		} // end else sending Web page

		// set filters and send
		add_filter( 'wp_mail_content_type', array($this, 'type_filter'), 1, 1 );
		add_filter( 'wp_mail_from', array($this, 'from_filter'), 1, 1 );
		add_filter( 'wp_mail_from_name', array($this, 'name_filter'), 1, 1 );

		if ( ! wp_mail( $to, $subject, $message, '', $attachments ) ) {
			$this->remove_qm_filters();
			$temp_msg = __( 'Error sending mail', 'quick-mail' );
			WP_CLI::error( $temp_msg );
		} // end if error

		$this->remove_qm_filters();
		if ( $sending_file ) {
			$temp_msg = sprintf('%s %s %s %s', __( 'Sent', 'quick-mail' ),
					basename( $url ), __( 'to', 'quick-mail' ), $to );
		} else {
			$temp_msg = sprintf( '%s %s', __( 'Sent email to', 'quick-mail' ), $to );
		} // end if sending file
		WP_CLI::success( $temp_msg );
		exit;
	} // end _invoke

	/**
	 * convenience function to remove filters.
	 */
	public function remove_qm_filters() {
		remove_filter( 'wp_mail_content_type', array($this, 'type_filter'), 1 );
		remove_filter( 'wp_mail_from', array($this, 'from_filter'), 1 );
		remove_filter( 'wp_mail_from_name', array($this, 'name_filter'), 1 );
	} // end remove_qm_filters

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
	* Read the title of a URL. Return this site's name if URL is empty.
	*
	* @param string $site
	* @return string title|error
	*/
	private function get_wp_site_title( $site ) {
		$data = $this->get_wp_site_data( $site );
		if ( is_string( $data ) ) {
			return $data;
		} // end if error

		$html = wp_remote_retrieve_body( $data );
		$pattern = "/title>(.+)<\/title>/";
		preg_match( $pattern, $html, $found );
		if ( empty( $found ) || empty( $found[1] ) )
		{
			return $site;
		} // end if no title

		return trim( $found[1] );
	} // end get_wp_site_title

	/**
	 * Connect to remote site as Chrome browser. Return error string or array with data.
	 *
	 * @param string $site
	 * @return string|array
	 */
	private function get_wp_site_data($site) {
		$chrome = 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1062.0 Safari/536.3';
		$args = array('user-agent' => $chrome);
		$data = wp_remote_get($site, $args);
		if ( is_wp_error( $data ) ) {
			return $data;
		} // end if WP Error

		$code = empty($data['response']['code']) ? 500 : $data['response']['code'] ;
		if ( 200 != $code ) {
			if ( 404 == $code ) {
				$title = __( 'Not found', 'quick-mail' );
				$temp_msg = sprintf("%s %s", $title, $site);
				return new WP_Error('404', $temp_msg);
			} else {
				$temp_msg = sprintf("(%d) %s %s", $code, __( 'Cannot connect to', 'quick-mail' ), $site);
				$title = __( 'Error', 'quick-mail' );
				return new WP_Error($title, $temp_msg);
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
				if ($admin_only) {
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
