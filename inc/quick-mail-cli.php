<?php
/**
 * Mail a Web page with quick-mail.
 *
 */
class Quick_Mail_Command extends WP_CLI_Command {

	public $from = '', $name = '', $content_type = 'text/html';

	/**
	 * Mail the contents of a URL or file.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : Mail sender.
	 *
	 * <to>
	 * : Mail recipient.
	 *
	 * <url or filename>
	 * : Url or file to send.
	 *
	 * [<subject>]
	 * : Optional subject.
	 *
	 * ## EXAMPLES
	 *
	 *     quick-mail fred@example.com mary@example.com https://example.com "Hello Mary"
	 *
	 *     Sends https://example.com from fred@example.com to mary@example.com
	 *     with "Hello Mary" subject
	 *
	 *     Web page title will be used if optional subject is omitted.
	 *
	 * @synopsis <from> <to> <url|filename> [<subject>]
	 */
	public function __invoke( $args, $assoc_args ) {
		require_once plugin_dir_path( __FILE__ ) . 'qm_util.php';
		$this->from = sanitize_email( $args[0] );
		$to = sanitize_email( $args[1] );
		$url = '';
		$subject = '';
		$domain = '';
		$sending_file = false;
		if ( 'http' == substr( $args[2], 0, 4) ) {
			$url = str_replace('&#038;', '&', esc_url( $args[2] ) );
			if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$temp_msg = __( 'Invalid URL', 'quick-mail' );
				$hurl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false);
				WP_CLI::error( "$temp_msg: {$hurl}"); // exit
			} // end if invalid URL

			$domain = parse_url( $url, PHP_URL_HOST ); // TODO
			if ( !QuickMailUtil::valid_web_domain( $domain ) ) {
				$temp_msg = __( 'Invalid domain', 'quick-mail' );
				WP_CLI::error( "{$temp_msg} : {$domain}" );
			}
		} else {
			if ( !file_exists( $args[2] ) ) {
				$temp_msg = __( 'File not found', 'quick-mail' );
				WP_CLI::error( $temp_msg ); // exit
			}
			// MIME type TODO
			$url = $args[2];
			$sending_file = true;
		} // end if URL

		$subject = '';
		if ( isset( $args[3] ) ) {
			$subject = html_entity_decode( $args[3], ENT_QUOTES, 'UTF-8' );
		} // end if got subject

		$verify = '';
		if ( is_multisite() ) {
			$verify = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify = get_option( 'verify_quick_mail_addresses', 'N' );
		}

		if ( !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
			$temp_msg = __( 'Invalid Recipient Address', 'quick-mail' );
			WP_CLI::error("{$temp_msg} : {$to}"); // exit
		} // end if invalid recipient

		if ( !QuickMailUtil::qm_valid_email_domain( $this->from, $verify ) ) {
			$temp_msg = __( 'Invalid Sender Address', 'quick-mail' );
			WP_CLI::error("{$temp_msg} : {$this->from}"); // exit
		} // end if invalid sender

		// get user info
		$args = array( 'user_email' => $this->from );
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
			WP_CLI::error('User query error'); // exit
		} // end if unknown email

		if (empty($user->caps['administrator'])) {
			WP_CLI::error('Sorry. Only administrators can send mail.'); // exit
		} // end if not administrator

		if ( empty( $user->user_firstname ) || empty( $user->user_lastname ) ) {
			$this->name = $user->display_name;
		} else {
			$this->name = "\"{$user->user_firstname} {$user->user_lastname}\"";
		} // end if missing first or last name

		$message = '';
		$attachments = array();
		if ( $sending_file ) {
			if ( 'text/' != substr( mime_content_type($url), 0, 5) ) {
				$message = sprintf('%s : %s', __( 'Please see attachment', 'quick-mail' ), basename( $url ) );
				$attachments = array($url); // ignored $this->content_type = 'multipart/form-data';
			} else {
				$message = file_get_contents( $url );
			} // end if not text file

			if ( empty( $message ) ) {
				$temp_msg = __( 'Empty file', 'quick-mail' );
				$hurl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false);
				WP_CLI::error( "$temp_msg: {$hurl}"); // exit
			} // end if empty file

			if ( empty( mb_strstr( $message, '</', false, 'UTF-8' ) ) ) {
				 $this->content_type = 'text/plain';
			} // end if plain text

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
					$subject = html_entity_decode( $found[1], ENT_QUOTES, 'UTF-8' );
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
	* Read the title of a URL. Return this site's name if URL is empty
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
	 * Connect to remote site as Chrome browser. Return error string or array with data
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
} // end Quick_Mail_Command

WP_CLI::add_command( 'quick-mail', 'Quick_Mail_Command' );
