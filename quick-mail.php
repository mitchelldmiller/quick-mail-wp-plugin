<?php
/**
 * Plugin Name: Quick Mail
 * Description: Send text or html email with attachments from user's credentials. Select recipient from users or commenters.
 * Version: 4.0.6
 * Author: Mitchell D. Miller
 * Author URI: https://badmarriages.net/author/mitchell-d-miller/
 * Plugin URI: https://mitchelldmiller.github.io/quick-mail-wp-plugin/
 * GitHub Plugin URI: https://github.com/mitchelldmiller/quick-mail-wp-plugin
 * Text Domain: quick-mail
 * Domain Path: /lang
 * License: MIT
 * License URI: https://github.com/mitchelldmiller/quick-mail-wp-plugin/blob/master/LICENSE
 *
 * @package QuickMail
 */

/*
 * Quick Mail WordPress Plugin - Send mail from WordPress using Quick Mail
 *
 * Copyright (C) 2014-2021 Mitchell D. Miller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
*/

require_once 'inc/class-quickmailutil.php';
require_once 'inc/class-quickmailsender.php';

// Load our WP-CLI command, if needed.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/class-quick-mail-command.php';
}

/**
 *
 * Send email, reply to comments from WP Dashboard.
 */
class QuickMail {

	/**
	 * Our version. Used by enqueue script / style.
	 *
	 * @var string version
	 * @since 3.5.5 10-3-19
	 */
	const VERSION = '4.0.6';

	/**
	 * Current directory for Quick Mail helper plugins.
	 *
	 * Replaces QuickMail::$directory
	 *
	 * @var string current directory.
	 * @since 4.0.5 1-8-21
	 */
	const DIRECTORY = __DIR__ . '/';

	/**
	 * Content type for our instance.
	 *
	 * @since 1.2.0
	 * @var string (text|html)
	 */
	public $content_type = 'text/html';

	/**
	 * Our directory for Quick Mail helper plugins.
	 *
	 * @var string directory name
	 * @deprecated 4.0.5
	 * @see QuickMail::DIRECTORY for replacement.
	 */
	public $directory = '';

	/**
	 * Your character set for multibyte functions.
	 *
	 * @var string $charset default UTF-8
	 */
	public $charset = 'UTF-8';

	/**
	 * Local function used to filter replace_quick_mail_sender
	 *
	 * @var string filter name
	 * @since 3.3.1
	 */
	public $filter_sender = '';

	/**
	 * Local function used to filter quick mail sender
	 *
	 * @var string filter name
	 * @since 3.3.3
	 */
	public $filter_name = '';

	/**
	 * Static property for our instance.
	 *
	 * @since 1.0.0
	 * @var (boolean|object) $instance
	 */
	public static $instance = false;

	/**
	 * Our dismissed pointer name.
	 *
	 * @var string
	 * @since 1.3.0
	 */
	public static $pointer_name = 'quickmail_320';

	/**
	 * Returns an instance.
	 *
	 * If an instance exists, return it.  If not, create one and return it.
	 *
	 * @since 1.0.0
	 *
	 * @return object instance of class
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	} // end get_instance

	/**
	 * Initialize class for WordPress.
	 *
	 * @return QuickMail
	 * @since 4.0.6
	 */
	public static function init() {
		if ( ! function_exists( 'register_activation_hook' ) ) {
			exit;
		}

		$qm            = new self();
		$qm->directory = plugin_dir_path( __FILE__ );
		$qm->charset   = is_multisite() ? get_blog_option( get_current_blog_id(), 'blog_charset', 'UTF-8' ) : get_option( 'blog_charset', 'UTF-8' );
		register_activation_hook( __FILE__, array( $qm, 'check_wp_version' ) );
		add_action( 'activated_plugin', array( $qm, 'install_quick_mail' ), 10, 2 );
		add_action( 'admin_footer', array( $qm, 'qm_get_comment_script' ) );
		add_action( 'admin_footer', array( $qm, 'qm_get_title_script' ) );
		add_action( 'admin_enqueue_scripts', array( $qm, 'add_email_scripts' ), 10, 0 );
		add_action( 'admin_menu', array( $qm, 'init_quick_mail_menu' ) );
		add_action( 'deactivated_plugin', array( $qm, 'deactivate_quick_mail_plugin' ), 10, 2 );
		add_action( 'load-tools_page_quick_mail_form', array( $qm, 'add_qm_help' ), 20, 0 );
		add_action( 'plugins_loaded', array( $qm, 'init_quick_mail_translation' ) );
		add_action( 'plugins_loaded', array( $qm, 'show_qm_pointer' ), 10, 0 );
		add_action( 'wp_ajax_qm_get_comment', array( $qm, 'qm_get_comment' ) );
		add_action( 'wp_ajax_qm_get_title', array( $qm, 'qm_get_title' ) );
		add_action( 'wp_ajax_nopriv_quick_mail_banned', array( $qm, 'quick_mail_banned' ) );
		add_action( 'wp_ajax_quick_mail_banned', array( $qm, 'quick_mail_banned' ) );

		add_filter( 'comment_row_actions', array( $qm, 'qm_filter_comment_link' ), 10, 2 );
		add_filter( 'comment_notification_text', array( $qm, 'qm_comment_reply' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $qm, 'qm_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $qm, 'qm_plugin_links' ), 10, 2 );
		add_filter( 'quick_mail_setup_capability', array( $qm, 'let_editor_set_quick_mail_option' ) );
		return $qm;
	}

	/**
	 * Not used since 4.0.6.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
	} // end constructor

	/**
	 * Get info for basic help tab.
	 *
	 * @return array args for WP_Screen::add_help_tab(array $args)
	 */
	public static function get_qm_help_tab() {
		$qm_desc      = __( 'Quick Mail is the easiest way to send email with attachments to WordPress users on your site, or send private replies to comments.', 'quick-mail' );
		$want_privacy = get_user_option( 'want_quick_mail_privacy', get_current_user_id() );
		if ( 'N' !== $want_privacy ) {
			$want_privacy = 'Y';
		} // end if not set
		if ( 'Y' === $want_privacy ) {
			$qm_desc = sprintf(
				"<h3 class='wp-ui-text-highlight'>%s</h3>",
				__( 'Please grant permission to use your mail address.', 'quick-mail' )
			);
		} // end if

		$github     = __( 'Follow development on Github', 'quick-mail' );
		$glink      = "<a target='_blank' href='https://github.com/mitchelldmiller/quick-mail-wp-plugin/'>{$github}</a>.";
		$faq        = __( 'FAQ', 'quick-mail' );
		$flink      = '<a href="https://mitchelldmiller.github.io/quick-mail-wp-plugin/#frequently-asked-questions" target="_blank">' . $faq . '</a>';
		$slink      = '<a href="https://github.com/mitchelldmiller/quick-mail-wp-plugin/issues" target="_blank">' . __( 'Github Issues', 'quick-mail' ) . '</a>';
		$resources  = __( 'Resources', 'quick-mail' );
		$more_info  = __( 'has more information.', 'quick-mail' );
		$use_str    = __( 'Please use', 'quick-mail' );
		$to_ask     = __( 'to ask questions and report problems.', 'quick-mail' );
		$qm_top     = "<p>{$qm_desc}</p><h4>{$resources}</h4><ul><li>{$flink} {$more_info}</li><li>{$glink}</li><li>{$use_str} {$slink} {$to_ask}</li></ul>";
		$qm_content = $qm_top;
		return array(
			'id'      => 'qm_intro',
			'title'   => __( 'Quick Mail', 'quick-mail' ),
			'content' => $qm_content,
		);
	} // end get_qm_help_tab

	/**
	 * Get help for comment reply.
	 *
	 * @since 3.1.3
	 * @return array args for WP_Screen::add_help_tab(array $args)
	 */
	public static function get_qm_comment_help_tab() {
		$help = sprintf(
			'<dl><dt class="qm-help">%s</dt>
				<dd>%s</dd><dd>%s</dd></dl>',
			__( 'Send private replies to comments.', 'quick-mail' ),
			__( 'Select a commenter to send a message.', 'quick-mail' ),
			__( 'Subject and message are added automatically.', 'quick-mail' )
		);

		$support = sprintf(
			'<h4><a class="wp-ui-text-highlight" target="_blank"
		href="%s">%s</a></h4>',
			'https://github.com/mitchelldmiller/quick-mail-wp-plugin/issues',
			__( 'Please use Github Issues to ask questions and report problems.', 'quick-mail' )
		);

		return array(
			'id'      => 'qm_chelp',
			'title'   => __( 'Reply to Comments', 'quick-mail' ),
			'content' => $help . $support,
		);
	} // end get_qm_comment_help_tab

	/**
	 * Got Banned Domains? Used for invalid message.
	 *
	 * @return boolean
	 * @since 4.0.5
	 */
	public static function got_banned_domains() {
		$banning = '';
		if ( is_multisite() ) {
			$banning = get_blog_option( null, 'quick_mail_banned', '' );
		} else {
			$banning = get_option( 'quick_mail_banned', '' );
		}
		return ( ! empty( $banning ) );
	}

	/**
	 * Is this domain banned? Static for WP-CLI.
	 *
	 * @param string $domain domain name.
	 * @return boolean
	 * @since 4.0.5
	 */
	public static function is_banned_domain( $domain ) {
		$option = '';
		if ( is_multisite() ) {
			$option = get_blog_option( null, 'quick_mail_banned', '' );
		} else {
			$option = get_option( 'quick_mail_banned', '' );
		} // end if multisite
		$banned = explode( ' ', $option );
		foreach ( $banned as $one ) {
			if ( ! empty( $one ) && stristr( $domain, $one ) ) {
				$domain = '';
				break;
			}
		}
		if ( empty( $domain ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Validate banned domains before adding them.
	 *
	 * @param string $entry User entry.
	 * @return string Validated domain list.
	 * @since 4.0.5
	 */
	public static function validate_banned_domains( $entry ) {
		$option = '';
		if ( is_multisite() ) {
			$option = get_blog_option( null, 'quick_mail_banned', '' );
		} else {
			$option = get_option( 'quick_mail_banned', '' );
		} // end if multisite

		$previous  = explode( ' ', $option );
		$current   = array_unique( explode( ' ', $entry ) );
		$processed = '';
		foreach ( $current as $one ) {
			if ( is_string( strstr( $one, '@' ) ) ) {
				$a_split = explode( '@', $one );
				if ( 2 === count( $a_split ) ) {
					$one = $a_split[1];
				}
			}

			$a_split = explode( '.', $one );
			$j       = count( $a_split );
			if ( $j > 2 ) {
				$one = $a_split[ $j - 2 ] . '.' . $a_split[ $j - 1 ];
			} // Remove subdomains.

			if ( function_exists( 'idn_to_ascii' ) ) {
				$intl = defined( 'INTL_IDNA_VARIANT_UTS46' ) && defined( 'IDNA_NONTRANSITIONAL_TO_ASCII' ) ? idn_to_ascii( $one[1], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46 ) : idn_to_ascii( $one );
				if ( ! empty( $intl ) && 4 < strlen( $intl ) && 'xn--' === substr( $intl, 0, 4 ) ) {
					$one = $intl;
				} // end if we have punycode address.
			} // end if we have idn_to_ascii

			if ( ( ! strstr( $processed, $one ) ) && ( in_array( $one, $previous, true ) || checkdnsrr( $one, 'MX' ) ) ) {
				$processed .= "{$one} ";
			}
		}
		return trim( $processed );
	}

	/**
	 * Get count of banned domains.
	 *
	 * @return integer
	 * @since 4.0.5
	 */
	public function get_banned_count() {
		if ( is_multisite() ) {
			$banned = get_blog_option( get_current_blog_id(), 'quick_mail_banned', '' );
		} else {
			$banned = get_option( 'quick_mail_banned', '' );
		}

		$a = explode( ' ', $banned );
		return empty( $banned ) ? 0 : count( $a );
	}

	/**
	 * Processes quick_mail_banned action to reject banned domains.
	 *
	 * @since 4.0.5
	 */
	public function quick_mail_banned() {
		$domain = isset( $_POST['domain'] ) ? sanitize_text_field( $_POST['domain'] ) : '';
		$hash   = password_hash( $domain, PASSWORD_DEFAULT );
		$maybe  = password_verify( $domain, $hash );
		if ( empty( $domain ) || empty( $maybe ) ) {
			http_response_code( 204 );
			exit;
		}

		if ( $this->get_banned_count() ) {
			$retval = self::is_banned_domain( $domain ) ? '' : $domain;
		} else {
			$retval = $domain; // No banned domains.
		}
		wp_die( $retval );
	}

	/**
	 * Get user role.
	 *
	 * @return string lowercase role. author, editor, administrator, n/a
	 * @since 3.1.0
	 */
	public function qm_get_role() {
		if ( current_user_can( 'activate_plugins' ) ) {
			return 'administrator';
		} // end if administrator

		if ( current_user_can( 'delete_others_pages' ) ) {
			return 'editor';
		} // end if editor

		if ( current_user_can( 'publish_posts' ) ) {
			return 'author';
		} // end if author

		return 'n/a';
	} // end qm_get_role

	/**
	 * Does site have more than one user? Supports multisite.
	 *
	 * @param string $code 'A' (all), 'N' (users with first / last names), 'X' (no user list).
	 * @param int    $blog Blog ID or zero if not multisite.
	 * @return bool more than one user for selected option
	 *
	 * @since 1.4.0
	 */
	public function multiple_matching_users( $code, $blog ) {
		if ( 'X' === $code ) {
			return true;
		} // end if do not want user list

		if ( is_multisite() && 0 === $blog ) {
			$blog = get_current_blog_id();
		} // end if blog not set

		$you   = wp_get_current_user();
		$urole = $this->qm_get_role();
		if ( 'author' === $urole ) {
			return ( 'X' === $code );
		} // author can only reply to comments

		if ( 'editor' === $urole ) {
			$editors = '';
			if ( is_multisite() ) {
				$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
			} else {
				$editors = get_option( 'editors_quick_mail_privilege', 'N' );
			} // end if multisite

			if ( 'Y' !== $editors ) {
				return ( 'X' === $code );
			} // end if editors not allowed to see list
		} // end if editor

		$exclude    = array( $you->ID ); // Exclude current user.
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if

		if ( 'A' === $code || 'B' === $code ) {
			if ( $blog > 1 ) {
				if ( 'Y' === $hide_admin ) {
					$args = array(
						'blog_id'      => $blog,
						'role__not_in' => array( 'Administrator' ),
						'exclude'      => $exclude,
					);
				} else {
					$args = array( 'exclude' => $exclude );
				}
			} else {
				if ( 'Y' === $hide_admin ) {
					$args = array(
						'role__not_in' => array( 'Administrator' ),
						'exclude'      => $exclude,
					);
				} else {
					$args = array( 'exclude' => $exclude );
				}
			} // end if multisite

			$info = get_users( $args );
			return 1 < count( $info );
		} // end if ALL or ALL + roles.

		// Check for first and last names.
		$meta_query = array(
			'key'     => 'last_name',
			'value'   => '',
			'compare' => '>',
		);
		if ( is_multisite() ) {
			if ( 'Y' === $hide_admin ) {
				$args = array(
					'role__not_in' => array( 'Administrator' ),
					'exclude'      => $exclude,
					'blog_id'      => $blog,
					'meta_query'   => $meta_query,
					'meta_key'     => 'first_name',
					'meta_value'   => '',
					'meta_compare' => '>',
				);
			} else {
				$args = array(
					'blog_id'      => $blog,
					'meta_query'   => $meta_query,
					'exclude'      => $exclude,
					'meta_key'     => 'first_name',
					'meta_value'   => '',
					'meta_compare' => '>',
				);
			} // end if hide admin
		} else {
			if ( 'Y' === $hide_admin ) {
				$args = array(
					'role__not_in' => array( 'Administrator' ),
					'exclude'      => $exclude,
					'meta_query'   => $meta_query,
					'meta_key'     => 'first_name',
					'meta_value'   => '',
					'meta_compare' => '>',
				);
			} else {
				$args = array(
					'meta_query'   => $meta_query,
					'exclude'      => $exclude,
					'meta_key'     => 'first_name',
					'meta_value'   => '',
					'meta_compare' => '>',
				);
			} // end if
		} // end if 'N'

		$info = get_users( $args );
		return 1 < count( $info );
	} // end multiple_matching_users

	/**
	 * Content type filter for wp_mail.
	 *
	 * Filters wp_mail_content_type.
	 *
	 * @param string $type previous content type.
	 * @return string our content type
	 */
	public function get_mail_content_type( $type ) {
		return $this->content_type;
	} // end get_mail_content_type

	/**
	 * Optionally display dismissible wp_pointer with setup reminder.
	 * cannot be loaded in constructor because user info is not available until plugins_loaded.
	 *
	 * @since 1.3.0
	 */
	public function show_qm_pointer() {
		if ( is_multisite() && is_super_admin() && is_network_admin() ) {
			return;
		} // end if skipping pointer on network admin page

		$dismissed = array_filter( explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) );
		if ( ! in_array( self::$pointer_name, $dismissed, true ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'qm_pointer_setup' ) );
		} // end if pointer was not dismissed
	} // end show_qm_pointer

	/**
	 * Displays wp_mail error message.
	 *
	 * @param WP_Error $e mail error.
	 * @since 1.3.0
	 */
	public function show_mail_failure( $e ) {
		if ( is_wp_error( $e ) ) {
			$direction = is_rtl() ? 'rtl' : 'ltr';
			$args      = array(
				'response'       => 200,
				'back_link'      => true,
				'text_direction' => $direction,
			);
			wp_die( sprintf( '<h1 role="alert">%s</h1>', esc_html( $e->get_error_message() ), esc_html( __( 'Mail Error', 'quick-mail' ) ), $args ) );
		} // end if error
	} // end show_mail_failure

	/**
	 * Check for minimum WordPress version before installation.
	 * Note: `quick_mail_version` filter was removed in 1.3.0.
	 *
	 * @link http://wheredidmybraingo.com/quick-mail-1-3-0-supports-international-mail/#minversion
	 *
	 * @since 1.2.3
	 */
	public function check_wp_version() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.6', 'lt' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			echo sprintf( "<div class='notice notice-error' role='alert'>%s</div>", esc_html( __( 'Quick Mail requires WordPress 4.6 or greater.', 'quick-mail' ) ) );
			exit;
		} // end if
		if ( ! extension_loaded( 'curl' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			echo sprintf( "<div class='notice notice-error' role='alert'>%s</div>", esc_html( __( 'Quick Mail requires cURL extension.', 'quick-mail' ) ) );
			exit;
		}
	} // end check_wp_version

	/**
	 * Add options when Quick Mail is activated.
	 *
	 * Add options, do not autoload them.
	 *
	 * @param string  $plugin name of plugin.
	 * @param boolean $network if we are on a network.
	 *
	 * @since 1.2.0
	 */
	public function install_quick_mail( $plugin, $network ) {
		if ( ! strstr( $plugin, basename( __FILE__ ) ) ) {
			return;
		} // end if not Quick Mail

		$blog       = is_multisite() ? get_current_blog_id() : 0;
		$qm_options = array(
			'authors_quick_mail_privilege' => 'N',
			'editors_quick_mail_privilege' => 'N',
			'hide_quick_mail_admin'        => 'N',
			'quick_mail_cannot_reply'      => 'N',
			'quick_mail_logging'           => 'N',
			'replace_quick_mail_sender'    => 'N',
			'verify_quick_mail_addresses'  => 'N',
			'quick_mail_banned'            => '',
		);

		foreach ( $qm_options as $option => $value ) {
			if ( is_multisite() ) {
				add_blog_option( $blog, $option, $value );
			} else {
				add_option( $option, $value, '', 'no' );
			} // end if multisite
		} // end foreach

		/**
		 * Do not show users if one user. Do not apply wpautop by default.
		 */
		$code = $this->multiple_matching_users( 'A', $blog ) ? 'A' : 'X';
		$this->qm_update_option( 'show_quick_mail_users', $code );
		$this->qm_update_option( 'qm_wpautop', '0' ); // TODO this should be Y/N like others.
		$this->qm_update_option( 'show_quick_mail_commenters', 'N' );
		$this->qm_update_option( 'limit_quick_mail_commenters', '7' );
		add_user_meta( get_current_user_id(), 'want_quick_mail_privacy', 'Y' );
		add_user_meta( get_current_user_id(), 'save_quick_mail_addresses', 'N' );
	} // install_quick_mail

	/**
	 * Load Javascript to display wp_pointer after installation.
	 *
	 * @since 1.3.0
	 */
	public function quick_mail_pointer_scripts() {
		$greeting        = esc_html( __( 'Welcome to Quick Mail', 'quick-mail' ) );
		$suggestion      = esc_html( __( 'Please verify your settings before using Quick Mail.', 'quick-mail' ) );
		$pointer_content = "<h3>{$greeting}</h3><p role='alert'>{$suggestion}</p>";
		?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function() {
	jQuery('#menu-settings').pointer({
		content: "<?php echo $pointer_content; ?>",
		position:    {
		edge: 'left', // arrow direction
		align: 'center' // vertical alignment
		},
		pointerWidth: 350,
		close:	function() {
				jQuery.post( ajaxurl, {
				pointer: '<?php echo self::$pointer_name; ?>',
				action: 'dismiss-wp-pointer'	});
	}
}).pointer('open');
});
//]]>
</script>
		<?php
	}
	/**
	 * Setup wp_pointer for new installations.
	 *
	 * @since 1.3.0
	 */
	public function qm_pointer_setup() {
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		add_action( 'admin_print_footer_scripts', array( $this, 'quick_mail_pointer_scripts' ) );
	} // end qm_pointer_setup

	/**
	 * Remove Quick Mail pointer on deactivation.
	 *
	 * @since 3.2.9
	 */
	public static function remove_qm_pointer() {
		$you       = get_current_user_id();
		$key       = 'dismissed_wp_pointers';
		$dismissed = array_filter( explode( ',', (string) get_user_meta( $you, $key, true ) ) );
		if ( ! in_array( self::$pointer_name, $dismissed, true ) ) {
			return;
		} // end if not found

		$meta_value = '';
		$j          = count( $dismissed );
		for ( $i = 0; $i < $j; $i++ ) {
			if ( $dismissed[ $i ] === self::$pointer_name ) {
				continue;
			}
			$meta_value .= "{$dismissed[$i]},";
		} // end for

		$len = strlen( $meta_value );
		if ( $len && ',' === $meta_value[ $len - 1 ] ) {
			$meta_value = substr( $meta_value, 0, -1 );
		} // end if trailing comma

		update_user_meta( $you, $key, $meta_value );
	} // end remove_qm_pointer

	/**
	 * Delete options when Quick Mail is deactivated.
	 *
	 * Delete global and user options.
	 *
	 * @param string  $plugin name of plugin.
	 * @param boolean $network if we are on a network.
	 * @since 1.1.1
	 */
	public function deactivate_quick_mail_plugin( $plugin, $network ) {
		if ( ! strstr( $plugin, basename( __FILE__ ) ) ) {
			return;
		} // end if not Quick Mail

		self::remove_qm_pointer();
	} // end deactivate_quick_mail_plugin

	/**
	 * Load quick-mail.js for email select and quick-mail-addresses.js to count saved addresses.
	 *
	 * @since 1.2.0
	 */
	public function add_email_scripts() {
		if ( strstr( $_SERVER['REQUEST_URI'], 'quick_mail' ) ) {
			wp_enqueue_script(
				'qmScript',
				plugins_url( '/lib/js/quick-mail.js', __FILE__ ),
				array( 'jquery' ),
				self::VERSION,
				false
			);

			$data = array(
				'duplicate' => __( 'Duplicate', 'quick-mail' ),
					/* translators: for duplicate email addresses */
			);
			wp_localize_script( 'qmScript', 'quick_mail_words', $data );
		} // end if on quick mail form

		if ( strstr( $_SERVER['REQUEST_URI'], 'quick_mail_options' ) ) {
			wp_enqueue_script( 'qmCount', plugins_url( '/lib/js/quick-mail-addresses.js', __FILE__ ), array( 'jquery' ), self::VERSION, false );
			$data = array(
				'one'  => __( 'Clear 1 saved address', 'quick-mail' ),
				/* translators: number of saved email addresses */
				'many' => sprintf( __( 'Clear %s saved addresses', 'quick-mail' ), '{number}' ),
			);
			wp_localize_script( 'qmCount', 'quick_mail_saved', $data );
		} // end if on options page
	} // end add_email_scripts

	/**
	 * Create and display recipient input. user list or text input.
	 *
	 * @param string $to recipient email.
	 * @param int    $id user ID.
	 */
	public function quick_mail_recipient_input( $to, $id ) {
		$tlen     = wp_is_mobile() ? 28 : 75;
		$template = "<input aria-labelledby='qme_label' value='%s' id='qm-email'
			name='qm-email' type='email' required aria-required='true' tabindex='0' autofocus size='{$tlen}'>";
		$blog     = is_multisite() ? get_current_blog_id() : 0;
		$option   = $this->qm_get_display_option( $blog );
		$you      = wp_get_current_user(); // From.
		if ( 'author' === $this->qm_get_role() ) {
			$option = 'X';
		} // end if author

		if ( 'X' !== $option ) {
			$editors = '';
			if ( is_multisite() ) {
				$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
			} else {
				$editors = get_option( 'editors_quick_mail_privilege', 'N' );
			} // end if multisite
			if ( 'Y' !== $editors ) {
				if ( ! $this->qm_is_admin( $id, $blog ) ) {
					$option = 'X';
				} // end if not admin and option might have changed
			} // end if editors not allowed to see list
		} // end if wants user list

		if ( 'A' !== $option && 'B' !== $option && 'N' !== $option && 'O' !== $option ) {
			echo sprintf( $template, $to );
			return;
		} // end if invalid option.

		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if

		$args = ( 'Y' === $hide_admin )
		? array(
			'role__not_in' => 'Administrator',
			'exclude'      => array( $you->ID ),
		)
		: array( 'exclude' => array( $you->ID ) );

		$user_query = new WP_User_Query( $args );
		$users      = array();
		foreach ( $user_query->results as $user ) {
			if ( $user->user_email === $you->user_email ) {
				continue;
			} // end duplicate email test

			if ( 'A' === $option || 'B' === $option ) {
				$nickname = ucfirst( get_user_meta( $user->ID, 'nickname', true ) );
				$users[]  = "{$nickname}\t{$user->user_email}\t{$user->ID}";
			} else {
				$last  = ucfirst( get_user_meta( $user->ID, 'last_name', true ) );
				$first = ucfirst( get_user_meta( $user->ID, 'first_name', true ) );
				if ( ! empty( $first ) && ! empty( $last ) && ! empty( $user->user_email ) ) {
					$users[] = "{$last}\t{$first}\t{$user->ID}\t{$user->user_email}";
				} // end if valid name
			} // end if all users else named only
		} // end for

		$j = count( $users );
		if ( 1 > $j ) {
			echo sprintf( $template, $to );
			return;
		} // end if at least one match

		sort( $users );
		$letter = '';
		ob_start();
		echo '<select aria-labelledby="qme_label" name="qm-email" id="qm-primary" required aria-required="true" size="1" tabindex="0" autofocus><option class="qmopt" value="" selected="selected">Select</option>';
		for ( $i = 0; $i < $j; $i++ ) {
			$row = explode( "\t", $users[ $i ] );
			if ( 'A' === $option || 'B' === $option ) {
				$address = rawurlencode( "\"{$row[0]}\" <{$row[1]}>" );
			} else {
				$address = rawurlencode( "\"{$row[1]} {$row[0]}\" <{$row[3]}>" );
			} // end if

			if ( $letter !== $row[0][0] ) {
				if ( ! empty( $letter ) ) {
					echo '</optgroup>';
				} // end if not first letter group
				$letter = $row[0][0];
				echo "<optgroup class='qmog' label='{$letter}'>";
			} // end if first letter changed

			$role = '';
			if ( 'B' === $option || 'O' === $option ) {
				$user_meta = get_userdata( $row[2] );
				$urole     = empty( $user_meta->roles[0] ) ? __( 'No Role', 'quick-mail' ) : ucfirst( $user_meta->roles[0] );
				$role      = " ({$urole})";
			} // end if want role.

			if ( 'A' === $option || 'B' === $option ) {
				$selected = ( $row[1] !== $to ) ? ' ' : ' selected="selected" ';
				echo "<option{$selected}value='{$address}' class='qmopt'>{$row[0]}{$role}</option>";
			} else {
				$selected = ( $row[3] !== $to ) ? ' ' : ' selected="selected" ';
				echo "<option{$selected}value='{$address}' class='qmopt'>{$row[1]} {$row[0]}{$role}</option>";
			}
		} // end for
		echo '</optgroup></select>';
		return ob_get_clean();
	} // end quick_mail_recipient_input

	/**
	 * Get input control for cc input.
	 *
	 * @param string  $cc cc.
	 * @param integer $id user ID.
	 * @return void|string
	 */
	public function quick_mail_cc_input( $cc, $id ) {
		$tlen     = wp_is_mobile() ? '28' : '75';
		$template = "<input aria-labelledby='qmcc_label' value='%s' id='qm-cc' name='qm-cc' type='text' size='{$tlen}' tabindex='3'>";
		$blog     = is_multisite() ? get_current_blog_id() : 0;
		$option   = $this->qm_get_display_option( $blog );
		if ( ! $this->multiple_matching_users( $option, $blog ) ) {
			$option = 'X';
		} // end if since 1.4.0
		$you = wp_get_current_user();
		if ( 'author' === $this->qm_get_role() ) {
			$option = 'X';
		} // end if author

		if ( 'X' !== $option ) {
			// Check if site permissions were changed.
			$editors = '';
			if ( is_multisite() ) {
				$editors = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
			} else {
				$editors = get_option( 'editors_quick_mail_privilege', 'N' );
			} // end if multisite

			if ( 'Y' !== $editors ) {
				if ( ! $this->qm_is_admin( $id, $blog ) ) {
					$option = 'X';
				} // end if not admin
			} // end if editors not allowed to see list
		} // end if wants user list

		if ( 'A' !== $option && 'B' !== $option && 'N' !== $option && 'O' !== $option ) {
			echo sprintf( $template, $cc );
			return;
		}
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if

		$args       = ( 'Y' === $hide_admin ) ? array(
			'role__not_in' => 'Administrator',
			'exclude'      => array( $you->ID ),
		) : array( 'exclude' => array( $you->ID ) );
		$user_query = new WP_User_Query( $args );
		$users      = array();
		foreach ( $user_query->results as $user ) {
			if ( $user->user_email === $you->user_email ) {
				continue;
			} // end if duplicate email

			if ( 'A' === $option || 'B' === $option ) {
				$nickname = ucfirst( get_user_meta( $user->ID, 'nickname', true ) );
				$users[]  = "{$nickname}\t{$user->user_email}\t{$user->ID}";
			} else {
				$last  = ucfirst( get_user_meta( $user->ID, 'last_name', true ) );
				$first = ucfirst( get_user_meta( $user->ID, 'first_name', true ) );
				if ( ! empty( $first ) && ! empty( $last ) && ! empty( $user->user_email ) ) {
					$users[] = "{$last}\t{$first}\t{$user->ID}\t{$user->user_email}";
				} // end if valid name
			} // end if all users else named only
		} // end for

		$j = count( $users );
		if ( 2 > $j ) {
			echo sprintf( $template, $cc );
			return;
		} // end if one match

		sort( $users );
		$letter = '';
		ob_start();
		echo '<select aria-labelledby="qmcc_label" name="qm-cc[]" id="qm-secondary"
					multiple="multiple" size="6" tabindex="3"><option class="qmopt" value=""
					selected="selected">Select</option>';
		for ( $i = 0; $i < $j; $i++ ) {
			$row  = explode( "\t", $users[ $i ] );
			$role = '';
			if ( 'B' === $option || 'O' === $option ) {
				$user_meta = get_userdata( $row[2] );
				if ( ! empty( $user_meta->roles ) ) {
					$role = ' (' . ucfirst( $user_meta->roles[0] ) . ')';
				} else {
					$role = ' (' . __( 'No Role', 'quick-mail' ) . ')';
				} // end if found role.
			} // end if want role.

			if ( 'A' === $option || 'B' === $option ) {
				$address = rawurlencode( "\"{$row[0]}\" <{$row[1]}>" );
			} else {
				$address = rawurlencode( "\"{$row[1]} {$row[0]}\" <{$row[3]}>" );
			} // end if

			if ( $letter !== $row[0][0] ) {
				if ( ! empty( $letter ) ) {
					echo '</optgroup>';
				} // end if not first letter group
				$letter = $row[0][0];
				echo "<optgroup class='qmog' label='{$letter}'>";
			} // end if first letter changed

			if ( 'A' === $option || 'B' === $option ) {
				$selected = ( $row[1] !== $cc ) ? ' ' : ' selected="selected" ';
				echo "<option{$selected}value='{$address}' class='qmopt'>{$row[0]}{$role}</option>";
			} else {
				$selected = ( $row[3] !== $cc ) ? ' ' : ' selected="selected" ';
				echo "<option{$selected}value='{$address}' class='qmopt'>{$row[1]} {$row[0]}{$role}</option>";
			}
		} // end for

		echo '</optgroup></select>';
		return ob_get_clean();
	} // end quick_mail_cc_input.

	/**
	 * Get list of commenters from posts / pages with comments open.
	 *
	 * @return string select with commenters instead of users. WP_Error if no commenters.
	 * @since 3.0.5
	 */
	public function get_commenters() {
		$you  = wp_get_current_user();
		$days = intval( get_user_option( 'limit_quick_mail_commenters', $you->ID ) );
		if ( is_bool( $days ) && false === $days ) {
			$days = 7;
			update_user_meta( $you->ID, 'limit_quick_mail_commenters', $days );
		} // end if new value was not set 3.2.6

		$msg     = ( 998 < $days ) ? __( 'No comments for you.', 'quick-mail' ) : __( 'No recent comments for you.', 'quick-mail' );
		$problem = new WP_Error( 'no_comments', $msg, 'quick-mail' );
		$dquery  = array(
			array(
				'after'     => "{$days} days ago",
				'inclusive' => true,
				'column'    => 'post_modified',
			),
		);
		$args    = array(
			'orderby'     => 'comment_author',
			'order'       => 'ASC',
			'post_author' => get_current_user_id(),
			'post_status' => 'publish',
			'status'      => 'approve',
			'count'       => false,
			'date_query'  => $dquery,
		);
		$cquery  = get_comments( $args );
		if ( empty( $cquery ) ) {
			return $problem;
		} // end if no recent comments

		$select  = '<select aria-labelledby="qme_label" name="qm-email" id="qm-primary" required aria-required="true" size="1" tabindex="0" autofocus onchange="return qm_get_comment()"><option class="qmopt" value="" selected="selected">Select</option>';
		$matches = 0;
		foreach ( $cquery as $comment ) {
			if ( empty( $comment->comment_author ) || empty( $comment->comment_author_email ) ) {
				continue;
			}
			if ( $comment->comment_author_email === $you->data->user_email ) {
				continue;
			}

			if ( ! comments_open( $comment->comment_post_ID ) ) {
				continue;
			}

			if ( ! QuickMailUtil::qm_valid_email_domain( $comment->comment_author_email, 'Y' ) ) {
				continue;
			} // end if invalid author email

			$attributes = "data-pid={$comment->comment_post_ID} data-cid={$comment->comment_ID}";
			$title      = get_the_title( $comment->comment_post_ID );

			// Extend visible title on desktop 3.1.1.
			$maxlen = wp_is_mobile() ? 45 : 120;

			// Check for mb_ functions 3.2.4.
			$your_len = function_exists( 'mb_strlen' ) ? mb_strlen( $title, $this->charset ) : strlen( $title );
			$your_sub = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, $maxlen - 1, $this->charset ) : substr( $title, 0, $maxlen - 1 );
			if ( $maxlen < $your_len ) {
				$title = $your_sub . '&hellip;';
			} // end if long title

			$address = rawurlencode( "\"{$comment->comment_author}\" <{$comment->comment_author_email}>" );
			$select .= "\r\n<option {$attributes} value='{$address}' class='qmopt'>{$comment->comment_author} &nbsp; ({$title})</option>";
			$matches++;
		} // end foreach

		$select .= '</select>';
		return ( $matches > 0 ) ? $select : $problem;
	} // end get_commenters

	/**
	 * Get comment title from Javascript.
	 */
	public function qm_get_title() {
		check_ajax_referer( 'qm_get_title', 'security' ); // Dies on error.
		$pid   = intval( $_POST['pid'] );
		$title = get_the_title( $pid );
		echo htmlspecialchars( $title, ENT_QUOTES );
		wp_die();
	} // end qm_get_title

	/**
	 * Javascript to load comment title into subject.
	 */
	public function qm_get_title_script() {
		if ( ! strstr( $_SERVER['REQUEST_URI'], 'quick_mail_form' ) ) {
			return;
		} // end if script not needed here.

		if ( 'Y' !== get_user_option( 'show_quick_mail_commenters', get_current_user_id() ) ) {
			return;
		} // end if not replying to comments

		$ajax_nonce = wp_create_nonce( 'qm_get_title' );
		?>
		<script type="text/javascript">
		function qm_get_title() {
			var security = '<?php echo $ajax_nonce; ?>';
			var pid = jQuery('#qm-primary').find('option:selected').data('pid');
			var jqxhr = jQuery.post( ajaxurl, {
				action: 'qm_get_title', pid: pid, security: security } );
				jqxhr.always(function( response ) {
					jQuery('#qm-subject').val(response);
					return true;
			});
		}
		</script>
		<?php
	} // end qm_get_title_script

	// Four functions to load comment in textarea.
	/**
	 * Get CSS for comment in textarea. Can be filtered with quick_mail_comment_style.
	 *
	 * @return string CSS
	 * @since 3.1.1
	 */
	public function get_comment_style() {
		$direction = is_rtl() ? 'right' : 'left';
		$css       = "style='margin-bottom:2em; margin-{$direction}:2em; padding-{$direction}:2em; border-{$direction}:2px solid #999;'";
		return apply_filters( 'quick_mail_comment_style', $css );
	} // end get_comment_style

	/**
	 * Format comment reply for textarea.
	 *
	 * @param string $text comment text.
	 * @return string formatted comment
	 * @since 3.1.1
	 */
	public function get_formatted_comment( $text ) {
		if ( user_can_richedit() ) {
			$css = $this->get_comment_style();
			return "<div {$css}>{$text}</div><br>";
		} // end if rich editor
		if ( is_rtl() ) {
			return $text . ' <' . "\r\n______________\r\n";
		} else {
			return '> ' . $text . "\r\n______________\r\n";
		} // end if
	} // end get_formatted_comment

	/**
	 * Get comment text from Javascript.
	 */
	public function qm_get_comment() {
		check_ajax_referer( 'qm_get_comment', 'security' );
		$cid  = intval( $_POST['cid'] );
		$text = get_comment_text( $cid );
		echo $this->get_formatted_comment( $text );
		wp_die();
	} // end qm_get_comment

	/**
	 * Get Javascript to load comment and move cursor to end of textarea or TinyMCE.
	 */
	public function qm_get_comment_script() {
		if ( ! strstr( $_SERVER['REQUEST_URI'], 'quick_mail_form' ) ) {
			return;
		} // end if script not needed here.

		if ( 'Y' !== get_user_option( 'show_quick_mail_commenters', get_current_user_id() ) ) {
			return;
		} // end if not replying to comments

		$ajax_nonce = wp_create_nonce( 'qm_get_comment' );
		?>
		<script type="text/javascript">
		// modified: https://davidwalsh.name/caret-end
		function move_cursor_to_end_of_textarea() {
			var el = document.getElementById('quickmailmessage');
			if (typeof el.selectionStart == "number") {
				el.selectionStart = el.selectionEnd = el.value.length;
			} else if (typeof el.createTextRange != "undefined") {
				el.focus();
				var range = el.createTextRange();
				range.collapse(false);
				range.select();
			} else if (typeof el.createRange != "undefined") {
				el.focus();
				var range = el.createRange();
				range.collapse(false);
				range.select();
			} // end if
			el.focus();
			return true;
		} // end move_cursor_to_end_of_textarea

		// Find tmce from: https://gist.github.com/RadGH/523bed274f307830752c
		function tmce_set_content(content) {
			if ( jQuery('#wp-quickmailmessage-wrap').hasClass('tmce-active') ) {
				tinyMCE.get('quickmailmessage').setContent('');
				tinyMCE.get('quickmailmessage').setContent(content);
				tinyMCE.get('quickmailmessage').focus();
				// From: https://stackoverflow.com/questions/19829126/tinymce-4-how-to-put-cursor-to-end-of-the-text
				tinyMCE.activeEditor.selection.select(tinyMCE.activeEditor.getBody(), true);
				tinyMCE.activeEditor.selection.collapse(false);
			} else {
				jQuery('#quickmailmessage').val(content);
				move_cursor_to_end_of_textarea();
			} // end if tmce is active
		} // end tmce_set_content

		function qm_get_comment() {
			var security = '<?php echo $ajax_nonce; ?>';
			var cid = jQuery('#qm-primary').find('option:selected').data('cid');
			qm_get_title();
			var jqxhr = jQuery.post( ajaxurl, {
				action: 'qm_get_comment', cid: cid, security: security });
				jqxhr.always(function( response ) {
				if (response == '0') {
					console.log('qm_get_comment ACTION NOT FOUND');
					return false;
				} else if (response == '') {
					console.log('qm_get_comment COMMENT NOT FOUND');
					return false;
				} // end if invalid response

				if (response.charAt(0) != '<') {
					jQuery('#quickmailmessage').html(response);
					move_cursor_to_end_of_textarea();
				} else {
					tmce_set_content(response);
				} // end if text or HTML
				return true;
			});
		} // end qm_get_comment
		</script>
		<?php
	} // end qm_get_comment_script

	/**
	 * Display data entry form to enter recipient, cc, subject, message.
	 *
	 * Alternate form if replying to comment.
	 */
	public function quick_mail_form() {
		$commenter    = '';
		$verify       = '';
		$error        = '';
		$to           = '';
		$subject      = '';
		$message      = '';
		$raw_msg      = '';
		$want_privacy = get_user_option( 'want_quick_mail_privacy', get_current_user_id() );
		$direction    = is_rtl() ? 'rtl' : 'ltr';
		if ( 'N' !== $want_privacy ) {
			$want_privacy = 'Y';
		} // end if not set
		if ( 'Y' === $want_privacy ) {
			$args    = array(
				'response'       => 200,
				'back_link'      => false,
				'text_direction' => $direction,
			);
			$qm_link = admin_url( 'options-general.php?page=quick_mail_options' );
			wp_die( sprintf( '<span role="alert" class="quick-mail-title highlight"><a class="highlight" href="%s">%s</a></span>', $qm_link, esc_html__( 'Please grant permission to use your email address.', 'quick-mail' ) ), esc_html__( 'Privacy Error', 'quick-mail' ), $args );
		} // end if

		$save_addresses = get_user_option( 'save_quick_mail_addresses', get_current_user_id() );
		if ( empty( $save_addresses ) ) {
			$save_addresses = 'N';
		}

		// Get sender name, email, reply to.
		$blog          = is_multisite() ? get_current_blog_id() : 0;
		$you           = wp_get_current_user();
		$you_are_admin = $this->qm_is_admin( $you->ID, $blog );

		if ( is_multisite() ) {
			$verify = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify = get_option( 'verify_quick_mail_addresses', 'N' );
		}
		if ( 'Y' === $verify && 'X' !== $this->qm_get_display_option( $blog ) ) {
			$verify = 'N';
		} // end if verify disabled, because not displaying user list.

		if ( ! empty( $_REQUEST['comment_id'] ) ) {
			if ( ! $this->user_can_reply_to_comments( true ) ) {
				$args = array(
					'response'       => 200,
					'back_link'      => true,
					'text_direction' => $direction,
				);
				wp_die( sprintf( '<h1 role="alert">%s</h1>', __( 'Comments disabled by system administrator.', 'quick-mail' ) ), __( 'Mail Error', 'quick-mail' ), $args );
			} // end if check site has permission to reply

			$id      = intval( $_REQUEST['comment_id'] );
			$info    = get_comment( $id, ARRAY_A );
			$text    = $info['comment_content'];
			$raw_msg = $this->get_formatted_comment( $text );
			if ( ! empty( $info['comment_author'] ) && ! empty( $info['comment_author_email'] ) ) {
				$commenter = "\"{$info['comment_author']}\" <{$info['comment_author_email']}>";
				$to        = $info['comment_author_email'];
				$title     = get_the_title( $info['comment_post_ID'] );
				$maxlen    = wp_is_mobile() ? 45 : 120;
				$your_len  = function_exists( 'mb_strlen' ) ? mb_strlen( $title, $this->charset ) : strlen( $title );
				$your_sub  = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, $maxlen - 1, $this->charset ) : substr( $title, 0, $maxlen - 1 );
				if ( $maxlen < $your_len ) {
					$subject = $your_sub . '&hellip;';
				} else {
					$subject = $title;
				} // end if long title

				if ( user_can_richedit() ) {
					if ( '1' === get_user_meta( $you->ID, 'qm_wpautop', true ) ) {
						$raw_msg = wpautop( $raw_msg );
					} // end if add paragraphs
				} // end if rich edit allowed

				if ( ! QuickMailUtil::qm_valid_email_domain( $to, 'Y' ) ) {
					$error = __( 'Cannot reply. Invalid mail address.', 'quick-mail' ) . '<br>' . htmlspecialchars( $to );
				}
			} // end if got comment name and author
		} // end if replying to comment

		$all_cc                = array();
		$data                  = array();
		$file                  = '';
		$mcc                   = '';
		$success               = '';
		$from                  = '';
		$reply_to              = '';
		$attachments           = array();
		$headers               = array();
		$our_filters           = array();
		$your_vals             = array(
			'name'     => '',
			'email'    => '',
			'reply_to' => '',
			'defined'  => false,
		);
		$your_vals['name']     = QuickMailUtil::get_wp_user_name();
		$your_vals['email']    = QuickMailUtil::get_wp_user_email();
		$your_vals['reply_to'] = "{$your_vals['name']} <{$your_vals['email']}>";

		/**
		 * $replaced used by replace_quick_mail_sender filter, ReplaceQuickMailSender class
		 *
		 * @var array 'name' => $name, 'email' => $email, 'reply_to' => $reply_to
		 *
		 * @see https://github.com/mitchelldmiller/replace-quick-mail-sender
		 */
		$replaced   = apply_filters( 'replace_quick_mail_sender', $your_vals );
		$your_email = $replaced['email'];
		$your_name  = $replaced['name'];
		$reply_to   = ! empty( $replaced['reply_to'] ) ? $replaced['reply_to'] : "{$your_name} <{$your_email}>";

		// Are we using a replacement sender?
		$service = '';

		// If admin, use mail plugin values or replace sender plugin values.
		// If not admin, cannot change sender, if value changed by plugin.
		// SparkPost first - no defines.
		if ( QuickMailUtil::got_sparkpost_info( false ) ) {
			$service = 'sparkpost';
			$found   = QuickMailUtil::get_sparkpost_info( $replaced );
			if ( $you_are_admin ) {
				$your_name = $found['name'];
				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				} // end if might not be able to get replies
				$your_email = $found['email'];          } else {

				// User can change name, reply to.
				if ( $found['name'] !== $your_name ) {
					$our_filters[] = new QuickMailSender( $service, 'name', $your_name );
				} // end if different name

				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				} // end if need reply-to filter for SparkPost
				} // end if admin
		} // end if SparkPost

		// Mailgun next.
		if ( QuickMailUtil::got_mailgun_info( false ) ) {
			$service = 'mailgun';
			$found   = QuickMailUtil::get_mailgun_info( $replaced );
			if ( $you_are_admin ) {
				$your_name  = $found['name'];
				$your_email = $found['email'];
				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				} // end if need reply to
			} else {
				if ( $found['name'] !== $your_name ) {
					$our_filters[] = new QuickMailSender( $service, 'name', $your_name );
				} // end if different name. might have been defined as constant

				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				} // end if need reply to
			} // end if admin
		} // end if

		// SendGrid next.
		if ( QuickMailUtil::got_sendgrid_info( false ) ) {
			$service = 'sendgrid';
			$found   = QuickMailUtil::get_sendgrid_info( $replaced );
			if ( $you_are_admin ) {
				$your_name  = $found['name'];
				$your_email = $found['email'];
				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				}
			} else {
				// User can change name, reply to.
				if ( $found['name'] !== $your_name ) {
					$our_filters[] = new QuickMailSender( $service, 'name', $your_name );
				} // end if different name

				if ( $found['email'] !== $your_email ) {
					$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
				} // end if need reply to
			} // end if admin
		} // end if

		if ( empty( $service ) && ! empty( $reply_to ) ) {
			$our_filters[] = new QuickMailSender( $service, 'reply_to', $reply_to );
		}

		$from = "From: \"{$your_name}\" <{$your_email}>\r\n";
		if ( empty( $your_email ) ) {
			$error = '<a href="/wp-admin/profile.php">' . __( 'Error: Incomplete User Profile', 'quick-mail' ) . '</a>';
		} // end if missing email after replacement.

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['qm205'] ) ) {
			if ( ! wp_verify_nonce( $_POST['qm205'], 'qm205' ) ) {
				wp_die( '<h1 role="alert">' . esc_html( __( 'Login Expired. Refresh Page.', 'quick-mail' ) ) . '</h1>' );
			}
			if ( empty( $commenter ) && empty( $_POST['qm-email'] ) ) {
				$args = array(
					'response'       => 200,
					'back_link'      => true,
					'text_direction' => $direction,
				);
				wp_die( sprintf( '<h1 role="alert">%s</h1>', esc_html( __( 'Invalid mail address', 'quick-mail' ) ), esc_html( __( 'Mail Error', 'quick-mail' ) ), $args ) );
			} // end if user circumvented Javascript

			$rec_type = empty( $_POST['qm_bcc'] ) ? 'Cc' : 'Bcc';
			if ( isset( $_POST['qm-cc'] ) && is_array( $_POST['qm-cc'] ) ) {
				$e = function_exists( 'mb_strtolower' ) ? mb_strtolower( urldecode( $_POST['qm-email'] ), $this->charset ) : strtolower( urldecode( $_POST['qm-email'] ) );
				foreach ( $_POST['qm-cc'] as $c ) {
					$your_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( urldecode( $c ), $this->charset ) : strtolower( urldecode( $c ) );
					if ( $e === $your_lower ) {
						$error = __( 'Duplicate mail address', 'quick-mail' );
						break;
					} // end if
				} // end foreach
			} // end if multiple selection

			if ( empty( $to ) ) {
				$raw_email = array();
				if ( preg_match( '/<(.+@.+[.].+)>/', urldecode( $_POST['qm-email'] ), $raw_email ) ) {
					$to = trim( $raw_email[1] );
				} else {
					$to = trim( urldecode( $_POST['qm-email'] ) );
				} // end if email and name
			} // end if not comment

			if ( ! QuickMailUtil::qm_valid_email_domain( $to, $verify ) ) {
				$error = __( 'Invalid mail address', 'quick-mail' ) . '<br>' . htmlspecialchars( $to );
			}
			if ( ! empty( $_POST['qm-cc'] ) ) {
				$raw_cc = array();
				if ( ! is_array( $_POST['qm-cc'] ) ) {
					$mcc = QuickMailUtil::filter_email_input( $to, urldecode( $_POST['qm-cc'] ), $verify );
					$tab = strstr( $mcc, "\t" );
					if ( is_string( $tab ) ) {
						$mtest = explode( "\t", $mcc );
						$error = __( 'Invalid mail address', 'quick-mail' ) . '<br>' . $mtest[0];
						$mcc   = $mtest[1];
					} else {
						$data = explode( ',', $mcc );
					}
				} else {
					$data = array_map( 'urldecode', $_POST['qm-cc'] );
				} // end if not array

				$j = count( $data );
				for ( $i = 0; $i < $j && empty( $error ); $i++ ) {
					if ( preg_match( '/<(.+@.+[.].+)>/', $data[ $i ], $raw_email ) ) {
						$raw_cc[ $i ] = trim( $raw_email[1] );
					} else {
						$raw_cc[ $i ] = trim( $data[ $i ] );
					}
				} // end for

				$all_cc = array_unique( $raw_cc );
				if ( empty( $error ) && ! empty( $all_cc[0] ) && empty( $mcc ) ) {
					$mcc = implode( ',', $all_cc );
					$j   = count( $all_cc );
					for ( $i = 0; $i < $j && empty( $error ); $i++ ) {
						if ( ! QuickMailUtil::qm_valid_email_domain( $all_cc[ $i ], $verify ) ) {
							$error = 'CC ' . esc_html( __( 'Invalid mail address', 'quick-mail' ) ) . '<br>' . $all_cc[ $i ];
						} elseif ( $to === $all_cc[ $i ] ) {
							$error = 'CC ' . esc_html( __( 'Duplicate mail address', 'quick-mail' ) ) . '<br>' . $all_cc[ $i ];
						} // end if
					} // end for
				} // end if not empty
			} // end if cc

			$subject = empty( $subject ) ? htmlspecialchars_decode( urldecode( stripslashes( $_POST['qm-subject'] ) ) ) : $subject;
			$subject = sanitize_text_field( $subject );
			if ( ! preg_match( '/(\S+)/', $subject ) ) {
				$error = __( 'No subject', 'quick-mail' );
			} // end subject check

			$raw_msg  = urldecode( stripslashes( $_POST['quickmailmessage'] ) );
			$your_len = function_exists( 'mb_strlen' ) ? mb_strlen( $raw_msg, $this->charset ) : strlen( $raw_msg );
			if ( empty( $error ) && 2 > $your_len ) {
				$error = __( 'Please enter your message', 'quick-mail' );
			} else {
				$message  = do_shortcode( $raw_msg );
				$your_str = function_exists( 'mb_strstr' ) ? is_string( mb_strstr( $message, '</', false, $this->charset ) ) : is_string( strstr( $message, '</' ) );
				if ( strcmp( $raw_msg, $message ) || $your_str ) {
					$this->content_type = 'text/html';
				} else {
					$this->content_type = 'text/plain';
				} // end set content type
			} // end else got message

			if ( empty( $error ) && ! empty( $_FILES['attachment'] ) && ! empty( $_FILES['attachment']['name'][0] ) ) {
				$uploads = array_merge_recursive(
					$_FILES['attachment'],
					$_FILES['second'],
					$_FILES['third'],
					$_FILES['fourth'],
					$_FILES['fifth'],
					$_FILES['sixth']
				);
				$dup     = false;
				$j       = count( $uploads['name'] );
				for ( $i = 0; ( $i < $j ) && ( false === $dup ); $i++ ) {
					if ( empty( $uploads['name'][ $i ] ) || empty( $uploads['size'][ $i ] ) ) {
						continue;
					}
					for ( $k = $i + 1; $k < $j; $k++ ) {
						if ( ! empty( $uploads['name'][ $k ] ) && ! empty( $uploads['size'][ $k ] ) && $uploads['name'][ $k ] === $uploads['name'][ $i ] && $uploads['size'][ $k ] === $uploads['size'][ $i ] ) {
							$dup = true;
						} // end if
					} // end for
				} // end for

				if ( $dup ) {
					$error = __( 'Duplicate attachments', 'quick-mail' );
				} // end if duplicate attachments
				for ( $i = 0; ( $i < $j ) && empty( $error ); $i++ ) {
					if ( empty( $uploads['name'][ $i ] ) || empty( $uploads['size'][ $i ] ) ) {
						continue;
					}
					if ( 0 === $uploads['error'][ $i ] ) {
						$temp = QuickMailUtil::qm_get_temp_path();
						if ( ! is_dir( $temp ) || ! is_writable( $temp ) ) {
							$error = __( 'Missing temporary directory', 'quick-mail' );
						} else {
							$file = "{$temp}{$i}{$uploads['name'][$i]}";
							if ( move_uploaded_file( $uploads['tmp_name'][ $i ], $file ) ) {
								array_push( $attachments, $file );
							} else {
								$error = __( 'Error moving file to', 'quick-mail' ) . " : {$file}";
							}
						}
					} elseif ( 4 !== $uploads['error'][ $i ] ) {
						if ( 1 === $uploads['error'][ $i ] || 2 === $uploads['error'][ $i ] ) {
							$error = __( 'Uploaded file was too large', 'quick-mail' );
						} else {
							$error = __( 'File Upload Error', 'quick-mail' );
						}
					}
				} // end if has attachment
			} // end if valid email address and has attachment

			if ( empty( $error ) ) {
				$headers[] = $from;
				if ( ! empty( $mcc ) ) {
					$headers[] = "{$rec_type}: {$mcc}";
				} // end if CC

				$reply_to = '';
				foreach ( $our_filters as $f ) {
					$reply_to = $f->get_reply_to();
					if ( ! empty( $reply_to ) ) {
						$headers[] = "Reply-To: {$reply_to}\r\n";
						break;
					} // end if
				} // end if

				$recipients = QuickMailUtil::count_recipients( $headers );
				if ( 100 < $recipients ) {
					$padding = is_rtl() ? 'padding-right: 2em;' : 'padding-left: 2em;';
					$error   = sprintf(
						'%s <a class="wp-ui-text-highlight" style="%s" href="javascript:history.back()">%s</a>',
						esc_html( __( 'Cannot send mail to over 100 recipients.', 'quick-mail' ) ),
						esc_attr( $padding ),
						esc_html( __( 'Edit mail.', 'quick-mail' ) )
					);
					$args    = array(
						'response'       => 200,
						'back_link'      => false,
						'text_direction' => $direction,
					);
					wp_die( sprintf( '<span style="font-size: 1.5em;" role="alert">%s</span>', $error, esc_html( __( 'Mail Error', 'quick-mail' ) ), $args ) );
				} // end if over 100 recipients.

				if ( user_can_richedit() && 'text/html' === $this->content_type && '1' === get_user_meta( get_current_user_id(), 'qm_wpautop', true ) ) {
					$message = wpautop( $message );
				} // end if

				// Set content type and error filter before sending mail.
				add_filter( 'wp_mail_content_type', array( $this, 'get_mail_content_type' ), 99, 1 );
				add_filter( 'wp_mail_failed', array( $this, 'show_mail_failure' ), 99, 1 );
				$mg_toggle = QuickMailUtil::got_mailgun_info( true );
				if ( $mg_toggle ) {
					$this->toggle_mailgun_override();
				} // end if do not replace sender name on non-admin user
				$sp_toggle = false;
				if ( ! empty( $attachments ) && QuickMailUtil::got_sparkpost_info( true ) ) {
					$sp_toggle = QuickMailUtil::toggle_sparkpost_transactional( $attachments );
				} // end if toggle SparkPost transactional

				if ( defined( 'QUICK_MAIL_TESTING' ) && QUICK_MAIL_TESTING ) {
					$message = '';
					foreach ( $headers as $one ) {
						$message .= esc_html( $one ) . '<br>';
					} // end foreach.
					$success = sprintf( '%s : %s<br>%s', __( 'To', 'quick-mail' ), $to, $message );
				} elseif ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
					$success   = __( 'Message Sent', 'quick-mail' );
					$rec_label = ( 'Cc' === $rec_type ) ? __( 'CC', 'quick-mail' ) : __( 'BCC', 'quick-mail' );
					if ( empty( $mcc ) ) {
						$success .= sprintf( '<br>%s %s', __( 'To', 'quick-mail' ), $to );
					} else {
						$success .= sprintf( '<br>%s %s<br>%s %s', __( 'To', 'quick-mail' ), $to, $rec_label, $mcc );
					} // end if has CC
				} else {
					if ( QuickMailUtil::got_mailgun_info( false ) ) {
						$error = __( 'Mailgun Error sending mail', 'quick-mail' );
					} elseif ( QuickMailUtil::got_sparkpost_info( true ) ) {
						$error = __( 'SparkPost Error sending mail', 'quick-mail' );
					} elseif ( QuickMailUtil::got_sendgrid_info() ) {
						$rname = __( 'SendGrid', 'quick-mail' );
						$error = "{$rname} " . __( 'Error sending mail', 'quick-mail' );
					} else {
						$error = __( 'Error sending mail', 'quick-mail' );
					}
				} // end else error

				// Reset filters after send.
				if ( ! empty( $this->filter_sender ) ) {
					remove_filter( 'replace_quick_mail_sender', array( $this, $this->filter_sender, 10 ) );
				} // end if added sender filter

				remove_filter( 'wp_mail_content_type', array( $this, 'get_mail_content_type' ), 99 );
				remove_filter( 'wp_mail_failed', array( $this, 'show_mail_failure' ), 99 );
				if ( $mg_toggle ) {
					$this->toggle_mailgun_override();
				} // end if do not replace sender name on non-admin user

				if ( $sp_toggle ) {
					remove_filter( 'wpsp_transactional', '__return_zero', 2017 );
				} // end if restore SparkPost toggle

				foreach ( $our_filters as $q ) {
					$q->remove_sender_filter();
				} // end foreach remove mail filter

				if ( ! empty( $file ) ) {
					$e = '<br>' . __( 'Error Deleting Upload', 'quick-mail' );
					if ( ! unlink( $file ) ) {
						if ( empty( $error ) ) {
							$success .= $e;
						} else {
							$error .= $e;
						}
					} // end if unlink error
				} // end if file uploaded
			} // end if no error
		} // end if POST

		$no_uploads     = '';
		$commenter_list = ( empty( $commenter ) && $this->user_can_reply_to_comments( false ) ) ? $this->get_commenters() : null;
		if ( is_wp_error( $commenter_list ) ) {
			$error = $commenter_list->get_error_message();
		} elseif ( is_string( $commenter_list ) ) {
			$commenter = 'Yes';
		} // end if no comments
		if ( 'GET' === $_SERVER['REQUEST_METHOD'] && empty( $_GET['quick-mail-uploads'] ) ) {
			$can_upload = strtolower( ini_get( 'file_uploads' ) );
			$pattern    = '/(OS 5_.+like Mac OS X)/';
			if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && 1 === preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) ) {
				$no_uploads = __( 'File uploads are not available on your device', 'quick-mail' );
			} elseif ( '1' !== $can_upload && 'true' !== $can_upload && 'on' !== $can_upload ) {
				$no_uploads = __( 'File uploads were disabled by system administrator', 'quick-mail' );
			}
		} // end if uploads not allowed

		$orig_link = plugins_url( '/inc/qm-validate.php', __FILE__ );
		$site      = untrailingslashit( network_site_url( '/' ) );
		$link      = str_replace( $site, '', $orig_link );
		if ( ! $you_are_admin && 'X' !== $this->qm_get_display_option( $blog ) ) {
			$editors = '';
			if ( is_multisite() ) {
				$editors = get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' );
			} else {
				$editors = get_option( 'editors_quick_mail_privilege', 'N' );
			} // end if multisite
			if ( $this->qm_is_editor( get_current_user_id(), $blog ) && 'N' === $editors ) {
				$this->qm_update_option( 'show_quick_mail_users', 'X' );
			} // end if adjusted display
		} // end if might adjust display
		echo "<script>var qm_validate = '{$link}', val_option = '{$verify}';</script>";
		$qm_link = admin_url( 'tools.php?page=quick_mail_form' );

		// Tell user if Quick Mail is verifying or blocking addresses.
		$invalid_msg = '';
		if ( 'Y' === $verify ) {
			$invalid_msg = __( 'Cannot verify address', 'quick-mail' );
		} elseif ( self::got_banned_domains() ) {
			$invalid_msg = __( 'Invalid or blocked mail address', 'quick-mail' );
		} else {
			$invalid_msg = __( 'Invalid mail address', 'quick-mail' );
		}
		?>
		<?php if ( defined( 'QUICK_MAIL_TESTING' ) && QUICK_MAIL_TESTING ) : ?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php esc_html_e( 'Quick Mail', 'quick-mail' ); ?> <span class="wp-ui-text-highlight"><?php esc_html_e( 'TEST MODE', 'quick-mail' ); ?></span></h1>
		<?php else : ?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php esc_html_e( 'Quick Mail', 'quick-mail' ); ?></h1>
		<?php endif; ?>
		<?php if ( ! empty( $no_uploads ) ) : ?>
<div class="update-nag notice is-dismissible">
	<p role="alert"><?php esc_html_e( $no_uploads ); ?></p>
</div>
<?php elseif ( ! empty( $success ) ) : ?>
<div id="qm-success" class="updated notice is-dismissible">
	<p role="alert"><?php echo $success; ?></p>
</div>
<?php elseif ( ! empty( $error ) ) : ?>
	<?php
	$your_str = function_exists( 'mb_strstr' ) ? mb_strstr( $error, 'profile.php', false, $this->charset ) : strstr( $error, 'profile.php' );
	$ecss     = $your_str ? 'error notice' : 'error notice is-dismissible';
	?>
<div id="qm_error" class="<?php echo esc_attr( $ecss ); ?>">
	<p role="alert"><?php echo $error; ?></p>
</div>
<?php endif; ?>
<div id="qm-validate" role="alert" class="error notice is-dismissible">
	<p role="alert"><span id="qmv-message"><?php esc_html_e( $invalid_msg ); ?></span> <span id="qm-ima"> </span></p>
</div>
<div id="qm-duplicate" role="alert" class="error notice is-dismissible">
	<p role="alert"><?php esc_html_e( 'Duplicate mail address', 'quick-mail' ); ?> <span id="qm-dma"> </span></p>
</div>
<noscript><span class="quick-mail-noscript"><?php esc_html_e( 'Quick Mail requires Javascript', 'quick-mail' ); ?></span></noscript>
		<?php if ( ! empty( $you->user_email ) && ! empty( $you->display_name ) ) : ?>
<form name="Hello" id="Hello" method="post" enctype="multipart/form-data" action="<?php echo $qm_link; ?>">
		<div class="indented">
			<?php wp_nonce_field( 'qm205', 'qm205', false, true ); ?>
<input type="hidden" name="qm-invalid" id="qm-invalid" value="0">
<input type="hidden" name="qm_say_cc" id="qm_say_cc" value="<?php esc_html_e( 'CC', 'quick-mail' ); ?>">
<input type="hidden" name="qm_say_bcc" id="qm_say_bcc" value="<?php esc_html_e( 'BCC', 'quick-mail' ); ?>">
<input id="save_addresses" name="save_addresses" type="hidden" value="<?php echo $save_addresses; ?>">
			<?php if ( ! empty( $no_uploads ) || ! empty( $_POST['quick-mail-uploads'] ) ) : ?>
	<input type="hidden" name="quick-mail-uploads" value="No">
<?php endif; ?>
<fieldset>
			<?php
			$the_from = htmlspecialchars( substr( $from, 6 ), ENT_QUOTES );
			$tlen     = function_exists( 'mb_strlen' ) ? mb_strlen( $the_from, $this->charset ) + 2 : strlen( $the_from ) + 2;
			if ( 75 < $tlen ) {
				$tlen = 75;
			}
			if ( wp_is_mobile() ) {
				$tlen = 28;
			}
			$tsize            = "size='{$tlen}'";
			$to_label         = ( empty( $commenter ) || empty( $commenter_list ) ) ? __( 'To', 'quick-mail' ) : __( 'Commenters', 'quick-mail' );
			$msg_label        = ( empty( $commenter ) || empty( $commenter_list ) ) ? __( 'Message', 'quick-mail' ) : __( 'Reply', 'quick-mail' );
			$message_tabindex = ( is_string( $commenter_list ) && ! empty( $commenter_list ) ) ? 1 : 30;
			?>
<label id="tf_label" for="the_from" class="recipients"><?php esc_html_e( 'From', 'quick-mail' ); ?></label>
<p><input aria-labelledby="tf_label" <?php echo $tsize; ?> value="<?php echo $the_from; ?>" readonly aria-readonly="true" id="the_from" tabindex="5000"></p>
</fieldset>
<fieldset>
<label id="qme_label" for="qm-email" class="recipients"><?php esc_html_e( $to_label ); ?></label>
			<?php if ( empty( $commenter ) ) : ?>
<p><?php echo $this->quick_mail_recipient_input( $to, $you->ID ); ?></p>
<?php else : ?>
	<?php
	$crecipient = '';
	if ( is_string( $commenter_list ) && ! empty( $commenter_list ) ) {
		$crecipient = $commenter_list;
	} else {
		$crecipient = "<input aria-labelledby='qme_label' value='{$to}' id='qm-email' name='qm-email' type='email' required aria-required='true' tabindex='6000' readonly aria-readonly='true' {$tsize}>";
	} // end if
	?>
<p><?php echo $crecipient; ?></p>
<?php endif; ?>
</fieldset>
			<?php
			if ( empty( $commenter ) && 'X' === $this->qm_get_display_option( $blog ) ) :
				?>
<fieldset id="qm_row">
<label id="qtc_label" for="qm_to_choice" class="recipients"><?php esc_html_e( 'Recent', 'quick-mail' ); ?> <?php esc_html_e( 'To', 'quick-mail' ); ?></label>
<p id="qm_to_choice"></p>
</fieldset>
	<?php endif; ?>
			<?php if ( empty( $commenter ) ) : ?>
<fieldset>
<label id="qmcc_label" for="qm-cc" class="recipients"><?php esc_html_e( 'CC', 'quick-mail' ); ?></label>
<label id="qmbcc_label" for="qm_bcc" class="qm-label"><?php esc_html_e( 'BCC', 'quick-mail' ); ?></label>
<input tabindex="2" type="checkbox" id="qm_bcc" name="qm_bcc">
<p><?php echo $this->quick_mail_cc_input( $mcc, $you->ID ); ?></p>
</fieldset>
	<?php endif; ?>
			<?php
			if ( empty( $commenter ) && 'X' === $this->qm_get_display_option( $blog ) ) :
				?>
<fieldset id="qm_cc_row">
<label id="qcc2_label" for="qm_cc_choice" class="recipients"><?php esc_html_e( 'Recent', 'quick-mail' ); ?> <?php esc_html_e( 'CC', 'quick-mail' ); ?></label>
<p id="qm_cc_choice"></p>
</fieldset>
<?php endif; ?>
<fieldset>
<label id="qmsubject_label" for="qm-subject" class="recipients"><?php esc_html_e( 'Subject', 'quick-mail' ); ?></label>
<p><input value="<?php echo htmlspecialchars( $subject, ENT_QUOTES ); ?>" type="text"
aria-labelledby="qmsubject_label" name="qm-subject" id="qm-subject" required <?php echo $tsize; ?> aria-required="true"
autocomplete="on" tabindex="22"></p>
</fieldset>
			<?php if ( empty( $no_uploads ) && empty( $_POST['quick-mail-uploads'] ) ) : ?>
<fieldset>
<label id="qmf1" for="qm-file-first" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?></label>
<p><input aria-labelledby="qmf1" id="qm-file-first" name="attachment[]" type="file" multiple="multiple" tabindex="23"></p>
</fieldset>
<fieldset class="qm-second">
<label id="qmf2" for="qm-second-file" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-second"><input aria-labelledby="qmf2" id="qm-second-file" name="second[]" type="file" multiple="multiple" tabindex="24"></p>
</fieldset>
<fieldset class="qm-third">
<label id="qmf3" for="qm-third-file" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-third"><input aria-labelledby="qmf3" id="qm-third-file" name="third[]" type="file" multiple="multiple" tabindex="25"></p>
</fieldset>
<fieldset class="qm-fourth">
<label id="qmf4" for="qm-fourth-file" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?>:</label>
<p class="qm-row-fourth"><input aria-labelledby="qmf4" id="qm-fourth-file" name="fourth[]" type="file" multiple="multiple" tabindex="26"></p>
</fieldset>
<fieldset class="qm-fifth">
<label id="qmf5" for="qm-fifth-file" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-fifth"><input aria-labelledby="qmf5" id="qm-fifth-file" name="fifth[]" type="file" multiple="multiple" tabindex="27"></p>
</fieldset>
<fieldset class="qm-sixth">
<label id="qmf6" for="qm-sixth-file" class="recipients"><?php esc_html_e( 'Attachment', 'quick-mail' ); ?></label>
<p class="qm-row-sixth"><input aria-labelledby="qmf6" id="qm-sixth-file" name="sixth[]" type="file" multiple="multiple" tabindex="28"></p>
</fieldset>
	<?php endif; ?>
<fieldset>
<label id="qm_msg_label" for="quickmailmessage" class="recipients"><?php esc_html_e( $msg_label ); ?></label>
			<?php
			if ( ! user_can_richedit() ) {
				?>
<p><textarea id="quickmailmessage" name="quickmailmessage" autocomplete="on"
aria-labelledby="qm_msg_label" required aria-required="true" aria-multiline=”true”
rows="8" cols="<?php echo wp_is_mobile() ? '30' : '60'; ?>" tabindex="<?php echo $message_tabindex; ?>"><?php echo htmlspecialchars( $raw_msg, ENT_QUOTES ); ?></textarea></p>
				<?php
			} else {
				$settings = array(
					'textarea_rows' => 8,
					'tabindex'      => $message_tabindex,
				);
				wp_editor( $raw_msg, 'quickmailmessage', $settings );
			} // end if
			?>
</fieldset>
<p class="submit"><input disabled type="submit" id="qm-submit" name="qm-submit"
title="<?php esc_html_e( 'Send Mail', 'quick-mail' ); ?>" tabindex="99"
value="<?php esc_html_e( 'Send Mail', 'quick-mail' ); ?>"></p>
					</div> <!-- indented -->
</form>
<?php endif; ?>
		<?php
	} // end quick_mail_form

	/**
	 * Display form to edit plugin options.
	 */
	public function quick_mail_options() {
		$updated       = false;
		$blog          = is_multisite() ? get_current_blog_id() : 0;
		$you           = wp_get_current_user();
		$you_are_admin = $this->qm_is_admin( $you->ID, $blog );
		$want_privacy  = get_user_option( 'want_quick_mail_privacy', $you->ID );
		$direction     = is_rtl() ? 'rtl' : 'ltr';
		if ( empty( $want_privacy ) ) {
			$want_privacy = 'Y';
		}
		$save_addresses = get_user_option( 'save_quick_mail_addresses', $you->ID );
		if ( empty( $save_addresses ) ) {
			$save_addresses = 'N';
		}

		$previous = get_user_option( 'limit_quick_mail_commenters', $you->ID );
		if ( is_bool( $previous ) && false === $previous ) {
			update_user_meta( $you->ID, 'limit_quick_mail_commenters', 7, $previous );
		} // end if new value was not set 3.2.6

		$previous       = $this->qm_get_display_option( $blog );
		$cur_want_roles = ! empty( $_POST['show_quick_mail_roles'] );
		if ( ! empty( $_POST['show_quick_mail_users'] ) && 1 === strlen( $_POST['show_quick_mail_users'] ) ) {
			$current = $_POST['show_quick_mail_users'];
			if ( $cur_want_roles ) {
				$current++;
			} // end if user wants to see roles.

			if ( $previous !== $current ) {
				if ( $this->multiple_matching_users( $_POST['show_quick_mail_users'], $blog ) ) {
					$this->qm_update_option( 'show_quick_mail_users', $current );
					$updated = true;
				} // end if valid option, but invalid options should not be displayed
			} // end if display option changed
		} // end if received display option

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['qm205'] ) ) {
			if ( ! wp_verify_nonce( $_POST['qm205'], 'qm205' ) ) {
				wp_die( '<h1 role="alert">' . __( 'Login Expired. Refresh Page.', 'quick-mail' ) . '</h1>' );
			}

			$previous = get_user_option( 'show_quick_mail_commenters', $you->ID );
			$current  = empty( $_POST['show_quick_mail_commenters'] ) ? 'N' : $_POST['show_quick_mail_commenters'];
			if ( $current !== $previous ) {
				update_user_meta( $you->ID, 'show_quick_mail_commenters', $current, $previous );
				$updated = true;
			} // end if show_quick_mail_commenters changed

			$current = empty( $_POST['want_quick_mail_privacy'] ) ? 'Y' : 'N';
			if ( $current !== $want_privacy ) {
				update_user_meta( $you->ID, 'want_quick_mail_privacy', $current, $want_privacy );
				$updated      = true;
				$want_privacy = $current;
			} // end if show_quick_mail_commenters changed

			$current = empty( $_POST['save_quick_mail_addresses'] ) ? 'N' : 'Y';
			if ( $current !== $save_addresses ) {
				update_user_meta( $you->ID, 'save_quick_mail_addresses', $current, $save_addresses );
				$updated        = true;
				$save_addresses = $current;
			} // end if show_quick_mail_commenters changed

			$previous = get_user_option( 'limit_quick_mail_commenters', $you->ID );
			$current  = empty( $_POST['limit_quick_mail_commenters'] ) ? 0 : intval( trim( $_POST['limit_quick_mail_commenters'] ) );
			if ( -1 < $current && $current !== $previous ) {
				update_user_meta( $you->ID, 'limit_quick_mail_commenters', $current, $previous );
				$updated = true;
			} // end if limit_quick_mail_commenters changed

			$previous = get_user_meta( $you->ID, 'qm_wpautop', true );
			$current  = empty( $_POST['qm_wpautop'] ) ? '0' : $_POST['qm_wpautop'];
			if ( $current !== $previous ) {
				update_user_meta( $you->ID, 'qm_wpautop', $current, $previous );
				$updated = true;
			} // end if wpauto changed

			if ( ! empty( $_POST['showing_quick_mail_admin'] ) ) {
				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'quick_mail_banned', '' );
				} else {
					$previous = get_option( 'quick_mail_banned', '' );
				} // end if multisite

				$got     = sanitize_text_field( $_POST['quick_mail_banned'] );
				$current = self::validate_banned_domains( $got );
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'quick_mail_banned', $current );
					} else {
						update_option( 'quick_mail_banned', $current );
					} // end if multisite

					$updated = true;
				} // end if value changed

				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
				} else {
					$previous = get_option( 'hide_quick_mail_admin', 'N' );
				} // end if multisite

				$current = empty( $_POST['hide_quick_mail_admin'] ) ? 'N' : 'Y';
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'hide_quick_mail_admin', $current );
					} else {
						update_option( 'hide_quick_mail_admin', $current );
					} // end if multisite

					$updated = true;
				} // end if value changed

				$previous = ''; // Used by SendGrid.
				$current  = empty( $_POST['replace_quick_mail_sender'] ) ? 'N' : 'Y';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'replace_quick_mail_sender', 'N' );
				} else {
					$previous = get_option( 'replace_quick_mail_sender', 'N' );
				} // end if multisite

				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'replace_quick_mail_sender', $current );
					} else {
						update_option( 'replace_quick_mail_sender', $current );
					} // end if multisite

					$updated = true;
				} // end if replace_quick_mail_sender value changed.

				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
				} else {
					$previous = get_option( 'quick_mail_cannot_reply', 'N' );
				} // end if multisite

				$current = empty( $_POST['quick_mail_cannot_reply'] ) ? 'N' : 'Y';
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'quick_mail_cannot_reply', $current );
					} else {
						update_option( 'quick_mail_cannot_reply', $current );
					} // end if multisite
					if ( ! $updated ) {
						$updated = true;
					} // end if updated not displayed.
				} // end if value changed.

				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' );
				} else {
					$previous = get_option( 'authors_quick_mail_privilege', 'N' );
				} // end if multisite

				$current = empty( $_POST['authors_quick_mail_privilege'] ) ? 'N' : 'Y';
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'authors_quick_mail_privilege', $current );
					} else {
						update_option( 'authors_quick_mail_privilege', $current );
					} // end if multisite
					if ( ! $updated ) {
						$updated = true;
					} // end if updated not displayed
				} // end if value changed

				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
				} else {
					$previous = get_option( 'editors_quick_mail_privilege', 'N' );
				} // end if multisite

				$current = empty( $_POST['editors_quick_mail_privilege'] ) ? 'N' : 'Y';
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'editors_quick_mail_privilege', $current );
					} else {
						update_option( 'editors_quick_mail_privilege', $current );
					} // end if multisite
					if ( ! $updated ) {
						$updated = true;
					} // end if updated not displayed
				} // end if value changed

				$previous = '';
				if ( is_multisite() ) {
					$previous = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
				} else {
					$previous = get_option( 'verify_quick_mail_addresses', 'N' );
				} // end if multisite
				$current = empty( $_POST['verify_quick_mail_addresses'] ) ? 'N' : 'Y';
				if ( $current !== $previous ) {
					if ( is_multisite() ) {
						update_blog_option( $blog, 'verify_quick_mail_addresses', $current );
					} else {
						update_option( 'verify_quick_mail_addresses', $current );
					} // end if multisite

					if ( ! $updated ) {
						$updated = true;
					} // end if updated not displayed
				} // end if value changed
			} // end if admin
		} // end if POST
		if ( $updated ) {
			echo '<div class="updated"><p>', esc_html_e( 'Option Updated', 'quick-mail' ), '</p></div>';
		} // end if updated

		$user_query = new WP_User_Query( array( 'count_total' => true ) );
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if multisite
		$total = 0;
		$names = 0;
		foreach ( $user_query->results as $user ) {
			if ( 'Y' === $hide_admin && $this->qm_is_admin( $user->ID, $blog ) ) {
				continue;
			} // end admin test

			$total++;
			$last  = get_user_meta( $user->ID, 'last_name', true );
			$first = get_user_meta( $user->ID, 'first_name', true );
			if ( ! empty( $first ) && ! empty( $last ) ) {
				$names++;
			} // end if
		} // end for

		$display          = $this->qm_get_display_option( $blog );
		$disable_roles    = ( 'X' === $display ) ? 'disabled' : '';
		$check_roles      = ( 'B' === $display || 'O' === $display ) ? 'checked="checked"' : '';
		$check_all        = ( 'A' === $display || 'B' === $display ) ? 'checked="checked"' : '';
		$check_names      = ( 'N' === $display || 'O' === $display ) ? 'checked="checked"' : '';
		$check_none       = ( 'X' === $display ) ? 'checked="checked"' : '';
		$check_save       = ( 'Y' === $save_addresses ) ? 'checked="checked"' : '';
		$check_privacy    = ( 'N' === $want_privacy ) ? 'checked="checked"' : '';
		$check_wpautop    = ( '1' === get_user_meta( $you->ID, 'qm_wpautop', true ) ) ? 'checked="checked"' : '';
		$check_commenters = $this->user_can_reply_to_comments( false ) ? 'checked="checked"' : '';
		$limit_commenters = get_user_option( 'limit_quick_mail_commenters', $you->ID );
		$list_warning     = '';
		if ( 3 > $total && 'X' !== $this->qm_get_display_option( $blog ) ) {
			$note         = ' <strong>' . __( 'NOTE', 'quick-mail' ) . ' : </strong> ';
			$lw_msg       = __( 'Quick Mail needs three non-admin users for sender, recipient, CC to access User List.', 'quick-mail' );
			$list_warning = $note . $lw_msg;
		} // end if have total less than 3

		$admin_option    = '';
		$editor_option   = '';
		$author_option   = '';
		$verify_option   = '';
		$sendgrid_option = '';
		$banned_option   = '';
		if ( is_multisite() ) {
			$admin_option        = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
			$editor_option       = get_blog_option( $blog, 'editors_quick_mail_privilege', 'N' );
			$author_option       = get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' );
			$cannot_reply_option = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
			$verify_option       = get_blog_option( $blog, 'verify_quick_mail_addresses', 'N' );
			$sendgrid_option     = get_blog_option( $blog, 'replace_quick_mail_sender', 'N' );
			$banned_option       = get_blog_option( $blog, 'quick_mail_banned', '' );
		} else {
			$admin_option        = get_option( 'hide_quick_mail_admin', 'N' );
			$editor_option       = get_option( 'editors_quick_mail_privilege', 'N' );
			$author_option       = get_option( 'authors_quick_mail_privilege', 'N' );
			$cannot_reply_option = get_option( 'quick_mail_cannot_reply', 'N' );
			$verify_option       = get_option( 'verify_quick_mail_addresses', 'N' );
			$sendgrid_option     = get_option( 'replace_quick_mail_sender', 'N' );
			$banned_option       = get_option( 'quick_mail_banned', '' );
		} // end if multisite

		$check_admin        = ( 'Y' === $admin_option ) ? 'checked="checked"' : '';
		$check_editor       = ( 'Y' === $editor_option ) ? 'checked="checked"' : '';
		$check_author       = ( 'Y' === $author_option ) ? 'checked="checked"' : '';
		$check_verify       = ( 'Y' === $verify_option ) ? 'checked="checked"' : '';
		$check_sendgrid     = ( 'Y' === $sendgrid_option ) ? 'checked="checked"' : '';
		$check_cannot_reply = ( 'Y' === $cannot_reply_option ) ? 'checked="checked"' : '';
		$check_privacy      = ( 'N' === $want_privacy ) ? 'checked="checked"' : '';
		$check_save         = ( 'Y' === $save_addresses ) ? 'checked="checked"' : '';

		$english_dns    = __( 'http://php.net/manual/en/function.checkdnsrr.php', 'quick-mail' );
		$dnserr_link    = "<a target='_blank' href='{$english_dns}'>checkdnsrr</a>";
		$when           = __( 'when', 'quick-mail' ) . ' &ldquo;' . __( 'Do Not Show Users', 'quick-mail' ) .
		'&rdquo; ' . __( 'is selected', 'quick-mail' ) . '.';
		$verify_message = __( 'Verifies domain with', 'quick-mail' ) . ' ' . $dnserr_link . ' ' . $when;
		$verify_problem = '';
		if ( ! function_exists( 'idn_to_ascii' ) ) {
			$english_faq    = __( 'https://mitchelldmiller.github.io/quick-mail-wp-plugin/#frequently-asked-questions', 'quick-mail' );
			$faq_link       = "<a target='_blank' href='{$english_faq}'>" . __( 'FAQ', 'quick-mail' ) . '</a>';
			$english_idn    = __( 'http://php.net/manual/en/function.idn-to-ascii.php', 'quick-mail' );
			$idn_link       = "<a target='_blank' href='{$english_idn}'>idn_to_ascii</a>";
			$nf             = $idn_link . ' ' . __( 'function not found', 'quick-mail' ) . '.';
			$cannot         = __( 'Cannot verify international domains', 'quick-mail' ) . ' ' . __( 'because', 'quick-mail' ) . ' ';
			$faq            = __( 'Please read', 'quick-mail' ) . ' ' . $faq_link . '.';
			$verify_problem = '<br><br><span role="alert">' . $cannot . $nf . '<br>' . $faq . '</span>';
		} // end if idn_to_ascii is available
		$verify_note   = $verify_message . $verify_problem;
		$wam           = sprintf(
			'%s %s %s',
			__( 'Apply', 'quick-mail' ),
			'<a target="_blank" href="https://codex.wordpress.org/Function_Reference/wpautop">wpautop</a>',
			__( 'to HTML messages.', 'quick-mail' )
		);
		$space         = '';
		$comment_label = '';
		if ( QuickMailUtil::user_has_comments( $you->ID ) ) {
			if ( ! $this->multiple_matching_users( 'A', $blog ) ) {
				$space         = ' style="margin-top:2em;" ';
				$comment_label = __( 'Select recipient from commenters', 'quick-mail' );
			} else {
				$comment_label = __( 'Display Commenters instead of users', 'quick-mail' );
			} // end if no users
		} // end if user has any comments

		if ( ! $you_are_admin ) {
			$cannot_reply = '';
			if ( is_multisite() ) {
				$cannot_reply = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
			} else {
				$cannot_reply = get_option( 'quick_mail_cannot_reply', 'N' );
			} // end if multisite

			if ( 'Y' === $cannot_reply ) {
				$comment_label = '';
			} elseif ( 'author' === $this->qm_get_role() ) {
				$allowed = is_multisite() ?
				get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' ) :
				get_option( 'authors_quick_mail_privilege', 'N' );
				if ( 'Y' !== $allowed ) {
					$comment_label = '';
				} // end if not allowed to reply with Quick Mail
			} // end if author
		} // end if not admin
		$mg_label   = '';
		$mg_message = '';
		if ( $you_are_admin && QuickMailUtil::got_mailgun_info( true ) ) {
			$mg_label   = __( 'Using Mailgun credentials', 'quick-mail' );
			$mg_message = __( 'Sending mail with your Mailgun name and mail address.', 'quick-mail' );
		} elseif ( QuickMailUtil::got_mailgun_info( false ) ) {
			$mg_label = __( 'Mailgun is active', 'quick-mail' );
			if ( ! $you_are_admin ) {
				$mg_message = __( 'Administrator is using Mailgun to send mail.', 'quick-mail' );
			} else {
				$mg_message = __( 'Sending mail with Mailgun API.', 'quick-mail' );
			} // end if
		} // end if got Mailgun info

		$sp_label   = '';
		$sp_message = '';
		if ( $you_are_admin && QuickMailUtil::got_sparkpost_info( true ) ) {
			$sp_label   = __( 'Using SparkPost credentials', 'quick-mail' );
			$sp_message = __( 'Sending mail with your SparkPost name and mail address.', 'quick-mail' );
		} elseif ( QuickMailUtil::got_mailgun_info( false ) ) {
			$sp_label = __( 'Mailgun is active', 'quick-mail' );
			if ( ! $you_are_admin ) {
				$sp_message = __( 'Administrator is using Mailgun to send mail.', 'quick-mail' );
			} else {
				$sp_message = __( 'Sending mail with Mailgun API.', 'quick-mail' );
			} // end if
		} // end if got SparkPost info

		$rname         = '';
		$sendgrid_desc = '';
		if ( QuickMailUtil::got_sendgrid_info() ) {
			$rname          = __( 'SendGrid', 'quick-mail' );
			$sendgrid_label = sprintf(
				'%s %s %s',
				__( 'Use', 'quick-mail' ),
				$rname,
				__( 'credentials', 'quick-mail' )
			);
			if ( $this->user_has_replaced_sender() ) {
				$sendgrid_desc = sprintf(
					'%s %s %s %s',
					__( 'Using', 'quick-mail' ),
					$rname,
					__( 'credentials', 'quick-mail' ),
					__( 'to send mail for Administrators', 'quick-mail' )
				);
			} else {
				if ( ! $you_are_admin ) {
					$sendgrid_desc = sprintf(
						'%s %s %s.    ',
						__( 'Administrator is using', 'quick-mail' ),
						$rname,
						__( 'to send mail', 'quick-mail' )
					);
				} else {
					$sendgrid_desc = sprintf(
						'%s %s %s.    ',
						__( 'Using', 'quick-mail' ),
						$rname,
						__( 'to send mail', 'quick-mail' )
					);
				} // end if not admin
			} // end if
		} // end if got replacement API
		$banned = $this->get_banned_count();
		$css    = ( $banned > 1 ) ? 'color: #11169b; font-weight: bold;' : 'display: none;';
		$bhtml  = "<span style='{$css}' id='banned'>{$banned}</span>";

		if ( is_multisite() ) {
			$verify = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
		} else {
			$verify = get_option( 'verify_quick_mail_addresses', 'N' );
		}
		if ( 'Y' === $verify && 'X' !== $this->qm_get_display_option( $blog ) ) {
			$verify = 'N';
		} // end if verify disabled, because not displaying user list.
		// Setup uses val_option since 3.5.6.
		echo "<script>val_option = '{$verify}';</script>";
		?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php esc_html_e( 'Quick Mail Options', 'quick-mail' ); ?></h1>
<form id="quick-mail-settings" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<div class="indented">
		<?php wp_nonce_field( 'qm205', 'qm205', false, true ); ?>
<fieldset>
<legend class="recipients"><?php esc_html_e( 'Privacy', 'quick-mail' ); ?></legend>
<p><input autofocus tabindex="8" aria-describedby="qm_privacy_desc" aria-labelledby="qm_privacy_label" class="qm-input" name="want_quick_mail_privacy" type="checkbox" <?php echo $check_privacy; ?>>
<label id="qm_privacy_label" class="qm-label"><?php esc_html_e( 'Grant Quick Mail permission to use your email address.', 'quick-mail' ); ?></label>
<span id="qm_privacy_desc" class="qm-label"><?php esc_html_e( 'Permission is required to send mail.', 'quick-mail' ); ?></span></p>
<p><input tabindex="9" aria-describedby="qm_save_desc" aria-labelledby="qm_save_label" class="qm-input" name="save_quick_mail_addresses" type="checkbox" <?php echo $check_save; ?>>
<label id="qm_save_label" class="qm-label"><?php esc_html_e( 'Grant Quick Mail permission to save recipient addresses.', 'quick-mail' ); ?></label>
<span id="qm_save_desc" class="qm-label"><?php esc_html_e( 'Permission is required to save addresses. Addresses will not be shared.', 'quick-mail' ); ?></span></p>
<div id="qm_saved"></div>
<input id="save_addresses" name="save_addresses" type="hidden" value="<?php echo $save_addresses; ?>">
</fieldset>
		<?php if ( $you_are_admin ) : ?>
<fieldset>
<legend class="recipients"><?php esc_html_e( 'Administration', 'quick-mail' ); ?></legend>
			<?php if ( QuickMailUtil::got_mailgun_info( true ) ) : ?>
<p><input tabindex="20" readonly aria-readonly="true" aria-describedby="qm_mailgun_desc" aria-labelledby="qm_mailgun_label" class="qm-input" name="using_Mailgun" type="checkbox" checked="checked" onclick='return false;'>
<label id="qm_mailgun_label" class="qm-label"><?php esc_html_e( $mg_label ); ?>.</label>
<span id="qm_mailgun_desc" class="qm-label"><?php esc_html_e( $mg_message ); ?></span></p>
<?php elseif ( QuickMailUtil::got_sparkpost_info( true ) ) : ?>
<p><input tabindex="20" readonly aria-readonly="true" aria-describedby="qm_sparkpost_desc" aria-labelledby="qm_sparkpost_label" class="qm-input" name="using_sparkpost" type="checkbox" checked="checked" onclick='return false;'>
<label id="qm_sparkpost_label" class="qm-label"><?php esc_html_e( $sp_label ); ?>.</label>
<span id="qm_sparkpost_desc" class="qm-label"><?php esc_html_e( $sp_message ); ?></span></p>
<?php elseif ( QuickMailUtil::got_sendgrid_info() ) : ?>
<p><input tabindex="30" aria-describedby="qm_sendgrid_desc" aria-labelledby="qm_sendgrid_label" class="qm-input" name="replace_quick_mail_sender" type="checkbox" <?php echo $check_sendgrid; ?>>
<label id="qm_sendgrid_label" class="qm-label"><?php esc_html_e( $sendgrid_label ); ?>.</label>
<span id="qm_sendgrid_desc" class="qm-label"><?php esc_html_e( $sendgrid_desc ); ?></span></p>
<?php endif; ?>
			<?php if ( $this->multiple_matching_users( 'A', $blog ) ) : ?>
<p><input tabindex="40" aria-describedby="qm_hide_desc" aria-labelledby="qm_hide_label" class="qm-input" name="hide_quick_mail_admin" type="checkbox" <?php echo $check_admin; ?>>
<label id="qm_hide_label" class="qm-label"><?php esc_html_e( 'Hide Administrator Profiles', 'quick-mail' ); ?>.</label>
				<?php
				$admins = $this->qm_admin_count( $blog );
				/* translators: %s: number of administrator profiles */
				$profile = sprintf( _n( '%s administrator profile', '%s administrator profiles', $admins, 'quick-mail' ), $admins );
				echo sprintf( '<span id="qm_hide_desc" class="qm-label">%s %s</span>', __( 'User list will not include', 'quick-mail' ), " {$profile}." );
				?>
			<?php endif; ?>
<input name="showing_quick_mail_admin" type="hidden" value="Y"></p>
<p><input tabindex="50" aria-describedby="quick_mail_cannot_reply_desc" id="quick_mail_cannot_reply"
aria-labelledby="quick_mail_cannot_reply_label" class="qm-input"
name="quick_mail_cannot_reply" type="checkbox" <?php echo $check_cannot_reply; ?>>
<label id="quick_mail_cannot_reply_label" class="qm-label"><?php esc_html_e( 'Disable Replies to Comments', 'quick-mail' ); ?>.</label>
<span id="quick_mail_cannot_reply_desc" class="qm-label"><?php esc_html_e( 'Users will not see commenter list.', 'quick-mail' ); ?></span></p>
<p id="qm-authors"><input tabindex="60" aria-describedby="qm_author_desc" aria-labelledby="qm_author_label" class="qm-input" name="authors_quick_mail_privilege" type="checkbox" <?php echo $check_author; ?>>
<label id="qm_author_label" class="qm-label"><?php esc_html_e( 'Grant Authors permission to reply to comments', 'quick-mail' ); ?>.</label>
<span id="qm_author_desc" class="qm-label"><?php esc_html_e( 'Authors can see commenter list or user list.', 'quick-mail' ); ?></span></p>
<p><input tabindex="70" aria-describedby="qm_grant_desc" aria-labelledby="qm_grant_label" class="qm-input" name="editors_quick_mail_privilege" type="checkbox" <?php echo $check_editor; ?>>
<label id="qm_grant_label" class="qm-label"><?php esc_html_e( 'Grant Editors access to user list.', 'quick-mail' ); ?></label>
<span id="qm_grant_desc" class="qm-label"><?php esc_html_e( 'Let editors see user list.', 'quick-mail' ); ?></span></p>
<p><input tabindex="80" aria-describedby="qm_verify_desc" aria-labelledby="qm_verify_label" class="qm-input" name="verify_quick_mail_addresses" id="verify_quick_mail_addresses" type="checkbox" <?php echo $check_verify; ?>>
<label id="qm_verify_label" class="qm-label"><?php esc_html_e( 'Verify recipient email domains', 'quick-mail' ); ?>.</label>
<span id="qm_verify_desc" class="qm-label"><?php echo $verify_note; ?></span></p>
			<?php if ( 'ltr' === $direction ) : ?>
<p><label id="qm_banned_label" for="quick_mail_banned" class="qm-label" style="font-weight:bold"><?php echo $bhtml; ?> <?php esc_html_e( 'Banned Domains', 'quick-mail' ); ?></label></p>
			<?php else : ?>
<p><label id="qm_banned_label" for="quick_mail_banned" class="qm-label" style="font-weight:bold"><?php esc_html_e( 'Banned Domains', 'quick-mail' ); ?> <?php echo $bhtml; ?></label></p>
			<?php endif; ?>
<p><textarea name="quick_mail_banned" id="quick_mail_banned" cols="60" rows="3" tabindex="82" aria-describedby="qm_banned_label"><?php echo $banned_option; ?></textarea></p>
</fieldset>
<?php endif; ?>
		<?php
		if ( user_can_richedit() ) :
			?>
<fieldset>
<legend class="recipients"><?php esc_html_e( 'Add Paragraphs', 'quick-mail' ); ?></legend>
<p><input tabindex="90" aria-describedby="qm_par_desc" aria-labelledby="qm_par_label" id="qm_add_par" class="qm-input" name="qm_wpautop" type="checkbox" value="1" <?php echo $check_wpautop; ?>>
<label id="qm_par_label" for="qm_add_par" class="qm-label"><?php esc_html_e( 'Add Paragraphs to sent mail', 'quick-mail' ); ?></label></p>
<p><span id="qm_par_desc" class="qm-label"><?php echo $wam; ?></span></p>
</fieldset>
		<?php endif; ?>
<fieldset>
<legend class="recipients"><?php esc_html_e( 'User Display', 'quick-mail' ); ?></legend>
		<?php if ( empty( $comment_label ) ) : ?>
<input type="hidden" name="show_quick_mail_commenters" value="N">
<input type="hidden" name="limit_quick_mail_commenters" value="<?php echo $limit_commenters; ?>">
		<?php else : ?>
<p id="show_commenters_row"><input tabindex="100" aria-describedby="qm_commenter_desc" aria-labelledby="qm_commenter_label" id="show_quick_mail_commenters" class="qm-input" name="show_quick_mail_commenters"
type="checkbox" value="Y" <?php echo $check_commenters; ?>>
<label id="qm_commenter_label" for="show_quick_mail_commenters" class="qm-label"><?php esc_html_e( $comment_label ); ?></label>
<span id="qm_commenter_desc" class="qm-label"><?php esc_html_e( 'Send private replies to comments.', 'quick-mail' ); ?></span></p>
<div id="limit_commenters_row">
<p><label id="qm_limit_label" for="limit_quick_mail_commenters" class="qm-label"><?php esc_html_e( 'Limit comments', 'quick-mail' ); ?></label>
<input tabindex="110" type="number" min="0" max="100000" aria-labelledby="te_label" value="<?php echo $limit_commenters; ?>" name="limit_quick_mail_commenters" id="limit_quick_mail_commenters">&nbsp;<span class="mock-qm-label"><?php esc_html_e( 'days', 'quick-mail' ); ?></span><br>
<span id="qm_limit_desc" class="qm-label"><?php esc_html_e( 'Limit displayed comments to a number of days.', 'quick-mail' ); ?></span></p>
</div>
		<?php endif; ?>
		<?php if ( ! empty( $list_warning ) ) : ?>
<p role="alert" id="qm-warning"><?php esc_html_e( $list_warning ); ?></p>
		<?php endif; ?>

		<?php if ( $this->multiple_matching_users( 'A', $blog ) && $you_are_admin ) : ?>
		<p id="show_roles_row"><input tabindex="115"
		aria-describedby="qm_roles_desc" <?php echo $disable_roles; ?>
		aria-labelledby="qm_role_label" id="show_quick_mail_roles"
		class="qm-input" name="show_quick_mail_roles" <?php echo $check_roles; ?>
type="checkbox" value="Y">
<label id="qm_commenter_label" for="show_quick_mail_roles"
class="qm-label"><?php esc_html_e( 'Show user roles', 'quick-mail' ); ?></label>
<span id="qm_roles_desc" class="qm-label"><?php esc_html_e( 'Let administrators see role on user list.', 'quick-mail' ); ?></span></p>
		<?php endif; ?>
		<?php if ( $this->multiple_matching_users( 'A', $blog ) ) : ?>
<p><input tabindex="120" aria-describedby="qm_all_desc" aria-labelledby="qm_all_label" id="qm_all_users" class="qm-input" name="show_quick_mail_users" type="radio" value="A" <?php echo $check_all; ?>>
<label id="qm_all_label" for="qm_all_users" class="qm-label">
			<?php
			$css  = ( 'Y' === $hide_admin ) ? 'qm-admin' : 'qm-total';
			$info = sprintf( "<span class='%s'>{$total}</span>", $css );
			esc_html_e( 'Show All Users', 'quick-mail' );
			if ( $total > 0 ) {
				echo " ({$info})";
			} // end if
			?>
</label><span id="qm_all_desc" class="qm-label">
			<?php
			esc_html_e( 'Show all users sorted by nickname', 'quick-mail' );
			$info = sprintf( "<span class='%s'>{$total}</span>", $css );
			if ( $total > 0 ) {
				echo ' ', $info, ' ', esc_html_e( 'matching users', 'quick-mail' );
			} // end if
			?>
.</span></p>
		<?php endif; ?>
		<?php if ( $this->multiple_matching_users( 'N', $blog ) ) : ?>
<p><input tabindex="130" aria-describedby="qm_names_desc" aria-labelledby="qm_names_label" class="qm-input" id="show_quick_mail_names" name="show_quick_mail_users" type="radio" value="N" <?php echo $check_names; ?>>
<label id="qm_names_label" class="qm-label">
			<?php
			$css  = ( 'Y' === $hide_admin ) ? 'qm-admin' : 'qm-total';
			$info = sprintf( "<span class='%s'>{$names}</span>", $css );
			esc_html_e( 'Show Users with Names', 'quick-mail' );
			if ( $total > 0 ) {
				echo " ({$info})";
			} // end if
			?>
</label>
<span id="qm_names_desc" class="qm-label">
			<?php
			esc_html_e( 'Show users with names, sorted by last name', 'quick-mail' );
			$css  = ( 'Y' === $hide_admin ) ? 'qm-admin' : 'qm-total';
			$info = sprintf( "<span class='%s'>{$names}</span>", $css );
			if ( $total > 0 ) {
				echo ' ', $info, ' ', esc_html__( 'matching users', 'quick-mail' );
			} // end if
			?>
.</span></p>
		<?php endif; ?>
<p<?php echo $space; ?>><input tabindex="140" aria-describedby="qm_none_desc" aria-labelledby="qm_none_label" class="qm-input" id="do_not_show_quick_mail_users"  name="show_quick_mail_users" type="radio" value="X"
		<?php
		echo $check_none;
		if ( ! $this->multiple_matching_users( 'A', $blog ) ) {
			echo ' readonly'; }
		?>
		>
<label id="qm_none_label" class="qm-label"><?php esc_html_e( 'Do Not Show Users', 'quick-mail' ); ?></label>
		<?php
		if ( ! $this->multiple_matching_users( 'A', $blog ) ) {
			echo '<br><br><span class="qm-label" role="alert">';
			if ( $you_are_admin ) {
				esc_html_e( 'Need three users to display User List for sender, recipient, CC.', 'quick-mail' );
			} else {
				esc_html_e( 'User List was disabled by system administrator.', 'quick-mail' );
			} // end if admin
			echo '</span><br>';
		} // end if one user
		?>
<span id="qm_none_desc" class="qm-label"><?php esc_html_e( 'Enter address to send mail.', 'quick-mail' ); ?> <?php esc_html_e( 'Saves 12 addresses.', 'quick-mail' ); ?></span></p>
</fieldset>
<p class="submit"><input tabindex="150" type="submit" name="qm-submit" class="button button-primary qm-input" value="<?php esc_html_e( 'Save Options', 'quick-mail' ); ?>"></p>
</div>
</form>
		<?php
	} // end quick_mail_options

	/**
	 * Get user option. Return default if not found. Replaces qm_get_option.
	 *
	 * @param int $blog Blog ID or zero if not multisite.
	 * @return string Option value or adjusted default
	 * @since 1.4.0
	 */
	public function qm_get_display_option( $blog ) {
		global $current_user;
		if ( 'author' === $this->qm_get_role() ) {
			return 'X';
		} // end if author.
		$value  = get_user_meta( $current_user->ID, 'show_quick_mail_users', true );
		$retval = ( ! empty( $value ) ) ? $value : 'A'; // Should never be empty.
		return $this->multiple_matching_users( $retval, $blog ) ? $retval : 'X';
	} // end qm_get_display_option.

	/**
	 * Update user option.
	 *
	 * @param string $key Key to update.
	 * @param string $value Value for update.
	 */
	public function qm_update_option( $key, $value ) {
		global $current_user;
		if ( is_int( $value ) ) {
			$value = strval( $value );
		}
		update_user_meta( $current_user->ID, $key, $value );
	} // end qm_update_option

	/**
	 * Is user an administrator?
	 *
	 * @param int $id User ID.
	 * @param int $blog Blog ID or zero if not multisite.
	 * @return boolean whether user is an administrator on blog
	 */
	protected function qm_is_admin( $id, $blog ) {
		if ( 0 === $blog ) {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Administrator',
					'include'     => array( $id ),
					'count_total' => true,
				)
			);
		} else {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Administrator',
					'include'     => array( $id ),
					'count_total' => true,
					'blog_id'     => $blog,
				)
			);
		} // end if not multisite

		return ( 0 < $user_query->get_total() );
	} // end qm_is_admin

	/**
	 * Is user an editor?
	 *
	 * @param int $id User ID.
	 * @param int $blog Blog ID or zero if not multisite.
	 * @return boolean whether user is an editor on blog
	 */
	protected function qm_is_editor( $id, $blog ) {
		if ( 0 === $blog ) {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Editor',
					'include'     => array( $id ),
					'count_total' => true,
				)
			);
		} else {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Editor',
					'include'     => array( $id ),
					'count_total' => true,
					'blog_id'     => $blog,
				)
			);
		} // end if not multisite

		return ( 0 < $user_query->get_total() );
	} // end qm_is_editor

	/**
	 * Get total users with administrator role on a blog.
	 *
	 * @param int $blog Blog ID or zero if not multisite.
	 * @return int total
	 * @since 2.0.0
	 */
	protected function qm_admin_count( $blog ) {
		if ( 0 === $blog ) {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Administrator',
					'count_total' => true,
				)
			);
		} else {
			$user_query = new WP_User_Query(
				array(
					'role'        => 'Administrator',
					'count_total' => true,
					'blog_id'     => $blog,
				)
			);
		} // end if

		return $user_query->get_total();
	} // end qm_admin_count

	/**
	 * Filter comment notification to add quick mail.
	 *
	 * @param string  $text comment text.
	 * @param integer $id comment ID.
	 * @return string filtered text
	 * @since 3.1.0
	 */
	public function qm_comment_reply( $text, $id ) {
		if ( ! $this->user_can_reply_to_comments( true ) ) {
			return $text;
		} // end if comments disabled by administrator.

		$qmc        = admin_url( "tools.php?page=quick_mail_form&comment_id={$id}\r\n" );
		$title      = apply_filters( 'quick_mail_reply_title', __( 'Private Reply', 'quick-mail' ) ); // Was Reply with Quick Mail.
		$left_link  = "{$title}: {$qmc}";
		$right_link = "{$qmc} : {$title}";
		$text      .= is_rtl() ? $right_link : $left_link;
		return $text;
	} // end qm_comment_reply.

	/**
	 * Filter comment_row_actions to add Reply with Quick Mail.
	 *
	 * @param array  $actions actions to filter.
	 * @param object $comment WP_Comment.
	 * @return array string filtered comments
	 * @since 3.1.1
	 */
	public function qm_filter_comment_link( $actions, $comment ) {
		if ( '1' !== $comment->comment_approved || empty( $comment->comment_author_email ) ) {
			return $actions;
		} // end if trackback or not approved.
		if ( ! QuickMailUtil::qm_valid_email_domain( $comment->comment_author_email, 'Y' ) ) {
			return $actions;
		} // end if invalid author email.

		if ( ! $this->user_can_reply_to_comments( true ) ) {
			return $actions;
		} // end if site allows private replies to comments.

		$qm_url = admin_url( "tools.php?page=quick_mail_form&amp;comment_id={$comment->comment_ID}" );
		$reply  = apply_filters( 'quick_mail_reply_title', __( 'Private Reply', 'quick-mail' ) );  // Was Reply with Quick Mail.
		$ereply = esc_attr( $reply );
		$css    = 'style="color: #e14d43;"'; // wp-ui-text-highlight.
		$retval = array();
		foreach ( $actions as $k => $v ) {
			$retval[ $k ] = $v;
			if ( 'reply' === $k ) {
				$retval['quickmail'] = "<a {$css} href='{$qm_url}' aria-label='{$ereply}'>{$reply}</a>";
			}
		} // end foreach.

		if ( empty( $retval['quickmail'] ) ) {
			$retval['quickmail'] = "<a {$css} href='{$qm_url}' aria-label='{$ereply}'>{$reply}</a>";
		} // end if missing reply option.

		return $retval;
	} // end qm_filter_comment_link,

	/**
	 * Used with quick_mail_setup_capability filter, to let editors see user list.
	 *
	 * @param string $role user role.
	 * @return string minimum role needed.
	 */
	public function let_editor_set_quick_mail_option( $role ) {
		$editors = 'N';
		$authors = 'N';
		if ( is_multisite() ) {
			$editors = get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' );
		} else {
			$editors = get_option( 'editors_quick_mail_privilege', 'N' );
		} // end if multisite

		if ( is_multisite() ) {
			$authors = get_blog_option( get_current_blog_id(), 'authors_quick_mail_privilege', 'N' );
		} else {
			$authors = get_option( 'authors_quick_mail_privilege', 'N' );
		} // end if multisite

		if ( 'Y' === $authors ) {
			return 'publish_posts';
		} // end if author
		return ( 'Y' === $editors ) ? 'edit_others_posts' : $role;
	} // end let_editor_set_quick_mail_option

	/**
	 * Init admin menu for appropriate users.
	 */
	public function init_quick_mail_menu() {
		$title          = __( 'Quick Mail', 'quick-mail' );
		$min_permission = 'publish_posts';
		$allowed        = is_multisite() ?
		get_blog_option( get_current_blog_id(), 'authors_quick_mail_privilege', 'N' ) :
		get_option( 'authors_quick_mail_privilege', 'N' );
		if ( 'Y' !== $allowed ) {
			$min_permission = 'edit_others_posts';
		} // end if skipping authors
		$page = add_submenu_page(
			'tools.php',
			$title,
			$title,
			apply_filters( 'quick_mail_user_capability', $min_permission ),
			'quick_mail_form',
			array( $this, 'quick_mail_form' )
		);
		add_action( 'admin_print_styles-' . $page, array( $this, 'init_quick_mail_style' ) );
		$otitle = __( 'Quick Mail Options', 'quick-mail' );
		$page   = add_options_page( $otitle, $title, apply_filters( 'quick_mail_setup_capability', $min_permission ), 'quick_mail_options', array( $this, 'quick_mail_options' ) );
		if ( ! empty( $page ) ) {
			add_action( 'admin_print_styles-' . $page, array( $this, 'init_quick_mail_style' ) );
			add_action( 'load-' . $page, array( $this, 'add_qm_settings_help' ) );
		} // end if added submenu
	} // end init_quick_mail_menu

	/**
	 * Quick Mail settings help.
	 *
	 * @since 2.0.0
	 */
	public function add_qm_settings_help() {
		$blog       = is_multisite() ? get_current_blog_id() : 0;
		$my_link    = sprintf(
			'<p><a href="https://wheredidmybraingo.com/send-reliable-email-wordpress-quick-mail/">%s</a></p>',
			__( 'Send Reliable Email from WordPress with Quick Mail has additional information.', 'quick-mail' )
		);
		$screen     = get_current_screen();
		$hide_admin = '';
		if ( is_multisite() ) {
			$hide_admin = get_blog_option( $blog, 'hide_quick_mail_admin', 'N' );
		} else {
			$hide_admin = get_option( 'hide_quick_mail_admin', 'N' );
		} // end if

		$you_are_admin  = $this->qm_is_admin( get_current_user_id(), $blog );
		$is_editor_user = $this->qm_is_editor( get_current_user_id(), $blog );
		$has_all        = $this->multiple_matching_users( 'A', $blog );
		$has_names      = $this->multiple_matching_users( 'N', $blog );
		$content        = '';
		$note           = '<strong>' . __( 'NOTE', 'quick-mail' ) . ' :</strong> ';
		$people         = ' ' . __( 'Sender, recipient, CC.', 'quick-mail' );
		$editors        = 'N';
		if ( is_multisite() ) {
			if ( 'Y' === get_blog_option( get_current_blog_id(), 'editors_quick_mail_privilege', 'N' ) ) {
				$editors = 'Y';
			}
		} else {
			if ( 'Y' === get_option( 'editors_quick_mail_privilege', 'N' ) ) {
				$editors = 'Y';
			}
		} // end if multisite

			$content = '';
		if ( ( ! $you_are_admin && ! $is_editor_user ) || ( 'N' === $editors && ! $you_are_admin ) ) {
			if ( is_multisite() ) {
				$content .= '<p>' . $note . __( 'You do not have sufficient privileges to access user lists on this site.' ) . '.</p>';
			} else {
				$content .= '<p>' . $note . __( 'You do not have sufficient privileges to access user lists.' ) . '.</p>';
			}
		} elseif ( 'Y' === $hide_admin ) {
			$content = '<p>' . __( 'User totals are adjusted because administrator profiles are hidden', 'quick-mail' ) . '.</p>';
		} // end if

		if ( ! $has_all ) {
			if ( 'Y' === $hide_admin ) {
				$content .= '<p>' . $note . __( 'Three non-administrator profiles are required for user lists.', 'quick-mail' ) . $people . '.</p>';
			} else {
				$content .= '<p>' . $note . __( 'Three user profiles are required for user lists.' ) . $people . '.</p>';
			} // end if less than 3
		} // end if 'A' not possible

		$screen->add_help_tab( self::get_qm_help_tab() );
		if ( $you_are_admin ) {
			$content = '<dl>';
			// Check for SparkPost, Mailgun, SendGrid.
			if ( QuickMailUtil::got_sparkpost_info( false ) ) {
				$content .= '<dt><strong>' . __( 'SparkPost plugin is active', 'quick-mail' ) . '</strong></dt>';
				if ( QuickMailUtil::got_sparkpost_info( true ) ) {
					$content .= '<dd>' . __( 'Administrators send mail with SparkPost credentials', 'quick-mail' ) . '.</dd>';
				} else {
					$content .= '<dd>' . __( 'Sending mail with SparkPost', 'quick-mail' ) . '.</dd>';
				} // end if using Mailgun name
			} // end if Mailgun

			if ( QuickMailUtil::got_mailgun_info( false ) ) {
				$content .= '<dt><strong>' . __( 'Mailgun plugin is active', 'quick-mail' ) . '</strong></dt>';
				if ( QuickMailUtil::got_mailgun_info( true ) ) {
					$content .= '<dd>' . __( 'Administrators send mail with Mailgun credentials', 'quick-mail' ) . '.</dd>';
				} else {
					$content .= '<dd>' . __( 'Sending mail with Mailgun', 'quick-mail' ) . '.</dd>';
				} // end if using Mailgun name
			} // end if Mailgun

			if ( QuickMailUtil::got_sendgrid_info() ) {
				$sendgrid_desc  = '';
				$rname          = __( 'SendGrid', 'quick-mail' );
				$sendgrid_label = sprintf( '%s %s.', $rname, __( 'plugin is active', 'quick-mail' ) );
				$content       .= "<dt><strong>{$sendgrid_label}</strong></dt>";
				if ( $this->user_has_replaced_sender() ) {
					$sendgrid_desc = sprintf(
						'%s %s %s %s.',
						__( 'Using', 'quick-mail' ),
						$rname,
						__( 'credentials', 'quick-mail' ),
						__( 'to send mail for Administrators', 'quick-mail' )
					);
				} else {
					$sendgrid_desc = sprintf(
						'%s %s %s',
						__( 'Sending mail with', 'quick-mail' ),
						$rname,
						__( 'plugin', 'quick-mail' )
					);
				} // end if not admin
				$content .= "<dd>{$sendgrid_desc}.</dd>";
			} // end if got replacement API

			$content    .= '<dt><strong>' . __( 'Hide Administrator Profiles', 'quick-mail' ) . '</strong></dt>';
			$content    .= '<dd>' . __( 'Prevent users from sending email to administrators', 'quick-mail' ) . '.</dd>';
			$content    .= '<dt><strong>' . __( 'Grant Editors access to user list', 'quick-mail' ) . '</strong></dt>';
			$content    .= '<dd>' . __( 'Otherwise only administrators can view the user list.', 'quick-mail' ) . '</dd>';
			$content    .= '<dt><strong>' . __( 'Verify recipient email domains', 'quick-mail' ) . '</strong></dt>';
			$content    .= '<dd>' . __( 'Check if recipient domain accepts email, when user enters the address.', 'quick-mail' ) . '</dd>';
			$english_dns = __( 'http://php.net/manual/en/function.checkdnsrr.php', 'quick-mail' );
			$z           = __( 'Checks domain with', 'quick-mail' );
			$dnserr_link = "<a target='_blank' href='{$english_dns}'>checkdnsrr</a>.";
			$content    .= "<dd>{$z} {$dnserr_link}</dd>";
			$content    .= '<dd class="wp-ui-text-highlight">' . __( 'Addresses selected from user list are validated by WordPress, when user is added or updated.', 'quick-mail' ) . '</dd>';
			$content    .= '<dd class="wp-ui-text-highlight">' . __( 'Turn verification off if Quick Mail rejects a valid address.', 'quick-mail' ) . '</dd>';
			$content    .= '<dt><strong>' . __( 'Banned Domains', 'quick-mail' ) . '</strong></dt>';
			$content    .= '<dd>' . __( 'Prevent users from sending email to selected domains.', 'quick-mail' ) . '</dd>';
			$content    .= '<dd>' . __( 'Enter domains. Use a space to separate each domain.', 'quick-mail' ) . '</dd>';
			$content    .= '<dd>' . __( 'Quick Mail verifies domain before adding it.', 'quick-mail' ) . '</dd>';
			$content    .= '</dl>';
			$screen->add_help_tab(
				array(
					'id'      => 'qm_admin_display_help',
					'title'   => __(
						'Administration',
						'quick-mail'
					),
					'content' => $content,
				)
			);
		} // end if

			$slink   = '<a href="https://github.com/mitchelldmiller/quick-mail-wp-plugin/issues" target="_blank">' . __( 'Github Issues', 'quick-mail' ) . '</a>';
			$use_str = __( 'Please use', 'quick-mail' );
			$to_ask  = __( 'to ask questions and report problems', 'quick-mail' );
			$rc5     = "<dt class='qm-help'>{$use_str} {$slink} {$to_ask}.</dt>";
		if ( $this->user_can_reply_to_comments( true ) ) {
			$dc_title    = __( 'Commenters', 'quick-mail' );
			$dc_head     = $this->multiple_matching_users( 'A', $blog ) ?
			__( 'Display list of commenters, instead of users.', 'quick-mail' ) :
			__( 'Select recipient from commenters.', 'quick-mail' );
			$dc_enabled  = sprintf( '<a target="_blank" href="https://codex.wordpress.org/Comments_in_WordPress#Enabling_Comments_on_Your_Site">%s</a>', __( 'enabling comments', 'quick-mail' ) );
			$dc_settings = sprintf( '<a target="_blank" href="https://codex.wordpress.org/Settings_Discussion_Screen">%s</a>', __( 'discussion settings', 'quick-mail' ) );
			$dc_see      = __( 'See', 'quick-mail' );
			$dc_info     = __( 'for additional information.', 'quick-mail' );
			$dc_and      = __( 'and', 'quick-mail' );
			$dc1         = '<dd>' . __( 'Reply to comments on your published content.', 'quick-mail' ) . '</dd>';
			$dc3         = '<dd>' . __( 'Comments are often disabled on older content.', 'quick-mail' ) . '</dd>';
			$dc4         = '<dd>' . __( 'Comments must be enabled to reply.', 'quick-mail' ) . '</dd>';
			$dc_val      = '<dd>' . __( 'Invalid mail addresses are not displayed.', 'quick-mail' ) . '</dd>';
			$lc_head     = __( 'Limit comments', 'quick-mail' );
			$lc1         = '<dd>' . __( 'Limit displayed comments to past number of days.', 'quick-mail' ) . '</dd>';
			$lc3         = '<dd>' . __( 'Hide comments to posts modified over selected days ago.', 'quick-mail' ) . '</dd>';
			$lcontent    = "<dt class='qm-help'>{$lc_head}</dt>{$lc1}{$lc3}";
			$dc5         = "<dd>{$dc_see} {$dc_enabled} {$dc_info}</dd>";
			$dcontent    = "<dl><dt><strong>{$dc_head}</strong></dt>{$dc1}{$dc3}{$dc4}{$dc_val}{$dc5}{$lcontent}";

			if ( $you_are_admin ) {
				$dc_disable = '<strong>' . __( 'Select Disable Replies to Comments to remove this feature.', 'quick-mail' ) . '</strong>';
				$dc_grant   = __( 'Grant Authors permission to reply to comments', 'quick-mail' );
				$dc_author  = admin_url( 'options-general.php?page=quick_mail_options#qm-authors' );
				$dc_link    = "<a href='{$dc_author}'>{$dc_grant}</a>";
				$dc_use     = __( 'to let authors use this feature', 'quick-mail' );
				$note       = '<dt class="qm-help"><strong>' . __( 'Administration', 'quick-mail' ) . ' :</strong></dt>';
				$dc6        = "{$note}<dd>{$dc_disable}</dd><dd>{$dc_see} {$dc_link} {$dc_use}.</dd>";
				$dc7        = '<dd>' . __( 'Email domains are always validated.', 'quick-mail' ) . '</dd>';
				$dc5        = "{$dc6}{$dc7}<dd>{$dc_see} {$dc_enabled} {$dc_and} {$dc_settings} {$dc_info}</dl>";
				$dcontent  .= $dc5;
			} // end if admin

			$dcontent .= "{$rc5}</dl>";
			$screen->add_help_tab(
				array(
					'id'      => 'qm_commenter_help',
					'title'   => $dc_title,
					'content' => $dcontent,
				)
			);
		} // Add comment help, if user can reply to comments.

		if ( user_can_richedit() ) {
			$wpauto_link = '<a href="https://codex.wordpress.org/Function_Reference/wpautop">wpautop</a>';
			$rc1         = __( 'Add line breaks and paragraphs to HTML mail', 'quick-mail' );
			$rc2         = __( 'with', 'quick-mail' );
			$rc3         = __( 'Many plugins change the WordPress editor', 'quick-mail' );
			$rc4         = __( 'Test this option on your system to know if you need it', 'quick-mail' );
			$rcontent    = '<dl>';
			$rcontent   .= '<dt><strong>' . __( 'Add Paragraphs', 'quick-mail' ) . '</strong></dt>';
			$rcontent   .= '<dd>' . $rc1 . ' ' . $rc2 . ' ' . $wpauto_link . '.</dd>';
			$rcontent   .= '<dd>' . $rc3 . '.</dd>';
			$rcontent   .= '<dd>' . $rc4 . '.</dd></dl>';
			$screen->add_help_tab(
				array(
					'id'      => 'qm_wpautop_help',
					'title'   => __(
						'Add Paragraphs',
						'quick-mail'
					),
					'content' => $rcontent,
				)
			);
		} // end if need wpauto help

			$rcontent = '<dl>';
		if ( $has_all ) {
			if ( $you_are_admin ) {
				$rcontent .= '<dt><strong>' . __( 'Show user roles', 'quick-mail' ) . '</strong></dt>';
				$rcontent .= '<dd>' . __( 'Let administrators see role on user list.', 'quick-mail' ) . '</dd>';
			} // end if admin

			$rcontent .= '<dt><strong>' . __( 'Show All Users', 'quick-mail' ) . '</strong></dt>';
			$rcontent .= '<dd>' . __( 'Select users by WordPress nickname', 'quick-mail' ) . '.</dd>';
		}
		if ( $has_names ) {
			$rcontent .= '<dt><strong>' . __( 'Show Users with Names', 'quick-mail' ) . '</strong></dt>';
			$rcontent .= '<dd>' . __( 'Select users with first and last names', 'quick-mail' ) . '.</dd>';
		}
			$rcontent .= '<dt><strong>' . __( 'Do Not Show Users', 'quick-mail' ) . '</strong></dt>';
			$rcontent .= '<dd>' . __( 'Enter user addresses. 12 addresses are saved', 'quick-mail' ) . '.</dd></dl>';
			$screen->add_help_tab(
				array(
					'id'      => 'qm_display_help',
					'title'   => __(
						'User Display',
						'quick-mail'
					),
					'content' => $rcontent,
				)
			);

		if ( $you_are_admin && ! QuickMailUtil::got_sendgrid_info( false ) && ! QuickMailUtil::got_mailgun_info( false ) ) {
			$sp       = sprintf(
				"%s <a target='_blank' href='%s'>%s</a>",
				__( 'Several', 'quick-mail' ),
				__( 'https://wordpress.org/plugins/search/smtp/', 'quick-mail' ),
				__( 'SMTP Plugins', 'quick-mail' )
			);
			$pline    = sprintf( '%s %s.', $sp, __( 'let you send mail from a public mail account', 'quick-mail' ) );
			$supports = __( 'Quick Mail supports', 'quick-mail' );
			$mg       = sprintf(
				"<a target='_blank' href='%s'>%s</a>",
				__( 'https://www.mailgun.com/', 'quick-mail' ),
				__( 'Mailgun', 'quick-mail' )
			);
			$sg       = sprintf(
				"<a target='_blank' href='%s'>%s</a>",
				__( 'https://sendgrid.com/', 'quick-mail' ),
				__( 'SendGrid', 'quick-mail' )
			);
			$spark    = sprintf(
				"<a target='_blank' href='%s'>%s</a>",
				__( 'https://sparkpost.com/', 'quick-mail' ),
				__( 'SparkPost', 'quick-mail' )
			);

			$svces       = sprintf(
				'%s %s, %s, %s.',
				$supports,
				$mg,
				$spark,
				$sg
			);
			$content     = sprintf(
				'<h4>%s</h4>',
				__( 'How to Fix Delivery Errors', 'quick-mail' )
			);
			$mailservice = '';
			if ( QuickMailUtil::got_sparkpost_info( false ) ) {
				$mailservice = __( 'Delivering mail with SparkPost.', 'quick-mail' );
			} elseif ( QuickMailUtil::got_mailgun_info( true ) ) {
				$mailservice = __( 'Delivering mail with Mailgun.', 'quick-mail' );
			} elseif ( QuickMailUtil::got_sendgrid_info( true ) ) {
				$mailservice = __( 'Delivering mail with SendGrid.', 'quick-mail' );
			} // end if using mail service.

			if ( ! empty( $mailservice ) ) {
				$smsg     = sprintf(
					'%s %s',
					__( 'Excellent!', 'quick-mail' ),
					$mailservice
				);
				$content .= "<div class='wp-ui-text-notification'>{$smsg}</div>";
			} // end if using service to send mail

			$content .= sprintf(
				'<p>%s.</p>',
				__( 'Use these products and services with Quick Mail to fix delivery errors', 'quick-mail' )
			);
			$content .= '<dl>';
			$line     = sprintf( '<dl><dt class="qm-help">%s</dt>', __( 'Mail Delivery Service', 'quick-mail' ) );
			$content .= $line;
			$line     = sprintf(
				'<dd>%s</dd>',
				__( 'Use a mail delivery service to send reliable email anywhere.', 'quick-mail' )
			);
			$content .= $line;
			$line     = sprintf( '<dd>%s</dd>', $svces );
			$content .= $line;
			$line     = sprintf( '<dd>%s</dd>', __( 'Mailgun, SparkPost and SendGrid offer free plans with limited usage.', 'quick-mail' ) );
			$content .= $line;
			$line     = sprintf( '<dd>%s</dd>', __( 'Quick Mail is tested with Mailgun and SendGrid.', 'quick-mail' ) );
			$content .= $line;

			if ( ! empty( $pline ) ) {
				$line     = sprintf( '<dt class="qm-help">%s</dt>', __( 'SMTP Plugins', 'quick-mail' ) );
				$content .= $line;
				$content .= "<dd>{$pline}</dd>";
			} // end if
			$content .= '</dl>';
			$content .= $my_link;
			$screen->add_help_tab(
				array(
					'id'      => 'qm_delivery_help',
					'title'   => __(
						'Delivery Errors',
						'quick-mail'
					),
					'content' => $content,
				)
			);
		} // end if adding Delivery Problems

		if ( $you_are_admin ) {
			$cmd      = __( 'wp help quick-mail', 'quick-mail' );
			$content  = sprintf( '<dl><dt><strong>%s</strong></dt>', __( 'Use Quick Mail with WP-CLI', 'quick-mail' ) );
			$content .= sprintf( '<dd>%s.</dd>', __( 'Send files, documents, Web pages from the command line', 'quick-mail' ) );
			$content .= sprintf(
				'<dd>%s <code>%s</code> %s.</dd>',
				__( 'Enter', 'quick-mail' ),
				$cmd,
				__( 'to get started', 'quick-mail' )
			);
			$content .= "<dd>{$my_link}</dd></dl>";
			$screen->add_help_tab(
				array(
					'id'      => 'qm_wpcli_help',
					'title'   => __(
						'WP-CLI',
						'quick-mail'
					),
					'content' => $content,
				)
			);
		} // end if admin user.
	} // add_qm_settings_help

	/**
	 * Can user reply to comments? Checks blog option, user option.
	 *
	 * @param boolean $site want site option, instead of user's option.
	 * @return boolean if site allows comments or if user can reply to comments.
	 * @since 3.1.5
	 */
	public function user_can_reply_to_comments( $site ) {
		$blog         = is_multisite() ? get_current_blog_id() : 0;
		$cannot_reply = '';
		if ( is_multisite() ) {
			$cannot_reply = get_blog_option( $blog, 'quick_mail_cannot_reply', 'N' );
		} else {
			$cannot_reply = get_option( 'quick_mail_cannot_reply', 'N' );
		} // end if multisite

		if ( $site ) {
			return ( 'Y' !== $cannot_reply );
		} // end if want site option only, for comment list

		if ( 'Y' === $cannot_reply ) {
			return false;
		} // end if comment replies are disabled

		if ( 'author' === $this->qm_get_role() ) {
			$allowed = is_multisite() ? get_blog_option( $blog, 'authors_quick_mail_privilege', 'N' ) : get_option( 'authors_quick_mail_privilege', 'N' );
			if ( 'Y' !== $allowed ) {
				return false;
			} // end if not allowed to reply with Quick Mail
		} // end if author

		$option = get_user_option( 'show_quick_mail_commenters', get_current_user_id() );
		if ( 'Y' !== $option ) {
			return false;
		} // end if user does not want comments

		return true;
	} // end user_can_reply_to_comments

	/**
	 * Quick Mail general help.
	 *
	 * @since 2.0.0
	 */
	public function add_qm_help() {
		$blog           = is_multisite() ? get_current_blog_id() : 0;
		$screen         = get_current_screen();
		$blog           = is_multisite() ? get_current_blog_id() : 0;
		$display_option = $this->qm_get_display_option( $blog );
		$cc_title       = __( 'Adding CC', 'quick-mail' );
		$xhelp          = __( 'Enter multiple addresses by separating them with a space or comma.', 'quick-mail' );
		$mac_names      = __( 'Press Command key while clicking, to select multiple users.', 'quick-mail' );
		$win_names      = __( 'Press Control key while clicking, to select multiple users.', 'quick-mail' );
		$mob_names      = __( 'You can select multiple users', 'quick-mail' );
		$nhelp          = '';
		if ( wp_is_mobile() ) {
			$nhelp = $mob_names;
		} else {
			$b = empty( $_SERVER['HTTP_USER_AGENT'] ) ? '' : $_SERVER['HTTP_USER_AGENT'];
			if ( preg_match( '/macintosh|mac os x/i', $b ) ) {
				$nhelp = $mac_names;
			} else {
				$nhelp = $win_names;
			} // end if
		} // end if
		$cc_help          = ( 'X' === $display_option ) ? $xhelp : $nhelp;
		$attachment_title = esc_html__( 'Attachments', 'quick-mail' );
		$attachment_help  = '';
		$pattern          = '/(OS 5_.+like Mac OS X)/';
		$can_upload       = strtolower( ini_get( 'file_uploads' ) );
		if ( '1' !== $can_upload && 'true' !== $can_upload && 'on' !== $can_upload ) {
			$attachment_help = '<p>' . esc_html__( 'File uploads were disabled by system administrator', 'quick-mail' ) . '</p>';
		} elseif ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && 1 === preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) ) {
			$attachment_help = '<p>' . esc_html__( 'File uploads are not available on your device', 'quick-mail' ) . '</p>';
		} else {
			$attachment_help = '<p>' . esc_html__( 'You can attach multiple files to your message', 'quick-mail' );
			if ( ! wp_is_mobile() ) {
				$attachment_help .= ' ' . esc_html__( 'from up to six directories', 'quick-mail' );
			} // end if mobile
			$attachment_help .= '.</p>';
			$mac_files        = __( 'Press Command key while clicking, to select multiple files.', 'quick-mail' );
			$win_files        = __( 'Press Control key while clicking, to select multiple files.', 'quick-mail' );
			$mob_files        = esc_html__( 'You can select multiple files', 'quick-mail' );
			$nhelp            = '';
			if ( wp_is_mobile() ) {
				$nhelp = $mob_files;
			} else {
				$b = empty( $_SERVER['HTTP_USER_AGENT'] ) ? '' : $_SERVER['HTTP_USER_AGENT'];
				if ( preg_match( '/macintosh|mac os x/i', $b ) ) {
					$nhelp = $mac_files;
				} else {
					$nhelp = $win_files;
				} // end if
			} // end if
			$attachment_help .= "<p>{$nhelp}</p>";
		} // end if uploads
		$screen->add_help_tab( self::get_qm_help_tab() );
		if ( $this->user_can_reply_to_comments( true ) &&
			( 'Y' === get_user_option( 'show_quick_mail_commenters', get_current_user_id() ) ) ) {
			$screen->add_help_tab( self::get_qm_comment_help_tab() );
		} else {
			$screen->add_help_tab(
				array(
					'id'      => 'qm_cc_help_tab',
					'title'   => $cc_title,
					'content' => "<p>{$cc_help}</p>",
				)
			);
		} // end if replying to commenters

		$screen->add_help_tab(
			array(
				'id'      => 'qm_attach_help_tab',
				'title'   => $attachment_title,
				'content' => $attachment_help,
			)
		);
	} // end add_qm_help

	/**
	 * Use by admin print styles to add css to admin.
	 */
	public function init_quick_mail_style() {
		wp_enqueue_style( 'quick-mail', plugins_url( '/lib/css/quick-mail.css', __FILE__ ), array(), self::VERSION, 'all' );
	} // end init_quick_mail_style

	/**
	 * Load translations.
	 */
	public function init_quick_mail_translation() {
		load_plugin_textdomain( 'quick-mail', false, basename( dirname( __FILE__ ) ) . '/lang' );
	} // end init_quick_mail_translation

	/**
	 * Add helpful links to plugin description. Filters plugin_row_meta.
	 *
	 * @param array  $links plugin links.
	 * @param string $file file associated with plugin links.
	 * @return array
	 *
	 * @since 1.2.4
	 */
	public function qm_plugin_links( $links, $file ) {
		if ( $file === plugin_basename( __FILE__ ) ) {
			$links[] = '<a href="https://mitchelldmiller.github.io/quick-mail-wp-plugin/#frequently-asked-questions" target="_blank">' . __( 'FAQ', 'quick-mail' ) . '</a>';
			$links[] = '<a href="https://github.com/mitchelldmiller/quick-mail-wp-plugin/issues" target="_blank">' . __( 'Support', 'quick-mail' ) . '</a>';
		} // end if adding links
		return $links;
	} // end qm_plugin_links

	/**
	 * Add Settings to action links. Filters plugin_action_links.
	 *
	 * @param array  $links current action links.
	 * @param string $file plugin to be tested.
	 */
	public function qm_action_links( $links, $file ) {
		if ( $file === plugin_basename( __FILE__ ) ) {
			$blog    = is_multisite() ? get_current_blog_id() : null;
			$url     = get_admin_url( $blog, 'options-general.php?page=quick_mail_options' );
			$qm_link = sprintf( '<a href="%s">%s</a>', $url, __( 'Settings', 'quick-mail' ) );
			array_unshift( $links, $qm_link );
		} // end if adding links
		return $links;
	} // end qm_action_links

	/**
	 * Toggle Mailgun override from credentials.
	 *
	 * Override credentials for non-admin users.
	 *
	 * @since 3.2.0
	 */
	public function toggle_mailgun_override() {
		if ( 'administrator' === $this->qm_get_role() ) {
			return false;
		} // end if admin

		$options = array();
		$site    = false;
		if ( ! is_multisite() ) {
			$options = get_option( 'mailgun', array() );
		} else {
			$options = get_site_option( 'mailgun', array() );
			if ( empty( $options ) ) {
				$options = get_blog_option( get_current_blog_id(), 'mailgun', array() );
			} else {
				$site = true;
			} // end if no site option
		} // end if not multisite

		$override                 = $options['override-from'];
		$updated                  = ( '1' === $override ) ? '0' : '1';
		$options['override-from'] = $updated;
		if ( ! is_multisite() ) {
			update_option( 'mailgun', $options );
		} else {
			if ( $site ) {
				update_site_option( 'mailgun', $options );
			} else {
				update_blog_option( get_current_blog_id(), 'mailgun', $options );
			} // end if site option
		} // end if not multisite

		return true;
	} // end toggle_mailgun_override

	/**
	 * Check if user is admin and replaced sender.
	 *
	 * @return boolean user replaced sender
	 * @since 3.2.1
	 */
	public function user_has_replaced_sender() {
		$blog = is_multisite() ? get_current_blog_id() : 0;
		if ( $this->qm_is_admin( get_current_user_id(), $blog ) ) {
			$can_send = '';
			if ( is_multisite() ) {
				$can_send = get_blog_option( get_current_blog_id(), 'replace_quick_mail_sender', 'N' );
			} else {
				$can_send = get_option( 'replace_quick_mail_sender', 'N' );
			} // end if multisite

			return ( 'Y' === $can_send ) ? QuickMailUtil::got_sendgrid_info( true ) : false;
		} // end if admin
		return false;
	} // end user_has_replaced_sender

} // end class
QuickMail::init();
