<?php
/**
 * Mail a Web page with quick-mail.
 *
 */
class Quick_Mail_Command extends WP_CLI_Command {
	/**
	 * Mail the contents of a URL.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : Mail sender.
	 *
	 * <to>
	 * : Mail recipient.
	 *
	 * <url>
	 * : Url to send.
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
	 * @synopsis <from> <to> <url> [<subject>]
	 */
	public function __invoke( $args, $assoc_args ) {
		require_once plugin_dir_path( __FILE__ ) . 'qm_util.php';
		$from = isset( $args[0] ) ? sanitize_email( $args[0] ) : '';
		$to = isset( $args[1] ) ? sanitize_email( $args[1] ) : '';
		$url = isset( $args[2] ) ? str_replace('&#038;', '&', esc_url( $args[2] ) ) : '';
		$subject = '';
		if ( isset( $args[3] ) ) {
			$subject = html_entity_decode( $args[3], ENT_QUOTES, 'UTF-8' );
		} // end if got subject

		$usage = 'Usage: quick-mail <from> <to> https://example.com [subject]';
		if ( empty( $from ) || empty( $to ) || empty( $url ) ) {
			WP_CLI::warning( $usage );
			exit;
		}
		$verify = '';
		if ( is_multisite() ) {
			$verify = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify = get_option( 'verify_quick_mail_addresses', 'N' );
		}

		if ( !QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
			$msg = __( 'Invalid Recipient Email', 'quick-mail' );
			WP_CLI::error("{$msg} : {$to}"); // exit
		} // end if invalid recipient

		if ( !QuickMailUtil::qm_valid_email_domain( $from, $verify ) ) {
			$msg = __( 'Invalid Sender Email', 'quick-mail' );
			WP_CLI::error("{$msg} : {$from}"); // exit
		} // end if invalid sender

		if ( !empty($url) && !filter_var( $url, FILTER_VALIDATE_URL ) ) {
			WP_CLI::error('Invalid URL'); // exit
		} // end if URL was entered

		// get user info
		$args = array( 'user_email' => $from );
		$user_query = new WP_User_Query( $args );
		if ( 1 > count( $user_query->results ) ) {
			WP_CLI::error('Invalid user'); // exit
		}

		$user = null;
		foreach ($user_query->results as $u) {
			if ( $u->user_email != $from ) {
				continue;
			} else {
				$user = $u;
			}
		} // end foreach

		if ( empty($user) || $user->user_email != $from ) {
			WP_CLI::error("User query error"); // exit
		}

		if (empty($user->caps['administrator'])) {
			WP_CLI::error("Sorry. Only administrators can send mail."); // exit
		}

		$name = '';
		if ( empty( $user->user_firstname ) || empty( $user->user_lastname ) ) {
			$name = $user->display_name;
		} else {
			$name = "\"{$user->user_firstname} {$user->user_lastname}\"";
		} // end if missing first or last name

		$domain = parse_url( $url, PHP_URL_HOST );
		WP_CLI::line( "Sending {$domain} to {$to}" );
		$data = $this->get_wp_site_data( $url );
		if ( is_wp_error( $data ) ) {
			$msg = preg_replace('/curl error .+: /i', '',  WP_CLI::error_to_string( $data ) );
			WP_CLI::error( $msg );
		} // end if error

		$message = wp_remote_retrieve_body($data);
		if ( empty( $message ) ) {
			WP_CLI::error( 'No content' );
		}

		if ( empty( $subject ) ) {
			$pattern = "/title>(.+)<\/title>/";
			preg_match( $pattern, $message, $found );
			if ( !empty( $found ) && !empty( $found[1] ) ) {
				$subject = html_entity_decode( $found[1], ENT_QUOTES, 'UTF-8' );
			} else {
				$subject = $this->get_wp_site_title($domain);
			}
		} // end if need subject

		// set filters and send
		add_filter( 'wp_mail_content_type', function ( $e ) { return 'text/html'; }, 1, 1 );
		add_filter( 'wp_mail_from', function( $e ) use( $from ) { return $from; }, 1, 1);
		add_filter( 'wp_mail_from_name', function( $e ) use( $name ) { return $name; }, 1, 1);
		if ( ! wp_mail( $to, $subject, $message ) ) {
			WP_CLI::error( 'Error sending mail' );
		} // end if error

		$msg = sprintf( '%s %s', __( 'Sent email to', 'quick-mail' ), $to );
		WP_CLI::success( $msg );
		exit;
	} // end _invoke

	/**
	* Read the title of a URL. Return this site's name if URL is empty
	*
	* @param string $site
	* @return string title|error
	*/
	private function get_wp_site_title( $site ) {
		if ( empty( $site ) ) {
			return get_bloginfo( 'name' );
		}

		$data = $this->get_wp_site_data( $site );
		if ( is_string( $data ) ) {
			return $data;
		}

		$html = wp_remote_retrieve_body( $data );
		$pattern = "/title>(.+)<\/title>/";
		preg_match($pattern, $html, $found);
		if ( empty( $found ) || empty( $found[1] ) )
		{
			return $site;
		} // end if no title

		return trim($found[1]);
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
		if ($data['response']['code'] != 200) {
			$code = empty($data['response']['code']) ? 500 : $data['response']['code'] ;
			if ( 404 == $code ) {
				$msg = sprintf("%s %s", __( 'Not found', 'quick-mail' ), $site);
				return new WP_Error('404', $msg);
			} else {
				$msg = sprintf("(%d) %s %s", $code, __( 'Cannot connect to', 'quick-mail' ), $site);
				return new WP_Error('error', $msg);
			} // end if 404
		}
		return $data;
	} // end get_wp_site_data
} // end Quick_Mail_Command

WP_CLI::add_command( 'quick-mail', 'Quick_Mail_Command' );
