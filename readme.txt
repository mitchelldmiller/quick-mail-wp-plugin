=== Quick Mail ===
Contributors: brainiac
Tags: mail, email, comments, attachment, wp-cli, mailgun, sendgrid, accessibility, idn, multisite
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY
Requires at least: 4.6
Tested up to: 4.9
Requires PHP: 5.3
Stable tag: 3.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send reliable text or html email with attachments and shortcodes. Send private replies to comments. Select recipient from users or commenters.

== Description ==
>Quick Mail is the easiest way to send email with attachments to WordPress users on your site, or send private replies to comments. Compatible with multisite.

Send a quick email from WordPress Dashboard to WordPress users, or anyone. Adds Quick Mail to Tools menu and comment replies.

Send a Web page, file, or message from the command line with quick-mail command for [WP-CLI](https://wp-cli.org/).

Edit messages with [TinyMCE](https://codex.wordpress.org/TinyMCE) to add images, rich text and [shortcodes](https://codex.wordpress.org/Shortcode).

User options for sending email to site users or others.

Mail is sent with user's name and email. Recognizes credentials from [Mailgun](https://wordpress.org/plugins/mailgun/) and [Sendgrid](https://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugins.

Multiple files from up to six directories (folders) can be attached to a message.

= Learn More =

* [How to Send Email from WordPress Admin](https://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/) is an introduction.

* See [Quick Mail 3.2.6 Maintenance Release](https://wheredidmybraingo.com/quick-mail-3-2-6-maintenance-release/) for update info.

* [Follow development on Github](https://github.com/mitchelldmiller/quick-mail-wp-plugin/).

== Installation ==
1. Download the plugin and unpack in your `/wp-content/plugins` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the settings page at `Settings -> Quick Mail` to configure the plugin for your site.
4. Optional: Install [WP-CLI](https://wp-cli.org/#installing) to send mail from the command line.
5. Optional: Install [Mailgun](http://wordpress.org/extend/plugins/mailgun/) or [Sendgrid](http://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugin to send reliable email.
6. Optional: Install experimental [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) plugin, to change administrator's credentials.

== Frequently Asked Questions ==

= Who can send mail? =

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email. Minimum permission can be changed with a filter.

* WP-CLI: Only administrators can send mail with the `quick-mail` WP-CLI command.

= Who can send rich text messages? =

* User must have [Visual Editor enabled](https://codex.wordpress.org/Function_Reference/user_can_richedit) on their profile, to compose messages with the Visual Editor.

* Anyone can send HTML by pasting it into a message.

= Selecting Recipients =

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

= Sending Mail from Other Addresses =

* Uses Mailgun plugin settings for Administrators, if the plugin is activated, using `Override "From" Details` and [Mailgun API](https://documentation.mailgun.com/en/latest/api_reference.html).

* Option for administrators to use [Sendgrid API](https://sendgrid.com/solutions/sendgrid-api/).

* [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) is an experimental plugin that changes the Quick Mail sender's name and email address.

* Programmers can replace their credentials by adding a filter to `replace_quick_mail_sender`. This only works if you are not using another mail plugin's credentials.

= Customizing Quick Mail =

* Add a filter to modify Quick Mail.

* Programmers can replace their credentials by adding a filter to `replace_quick_mail_sender`.

- What filters are available to modify Quick Mail?

`quick_mail_cli_attachment_message`
  Replace default CLI attachment message.
  
`quick_mail_cli_attachment_subject`
  Replace default CLI attachment subject.
  
`quick_mail_comment_style`
  Replace quick mail comment style.
  
`quick_mail_reply_title`	
  Replace title for private comment reply on comments list.

`quick_mail_user_capability`	
  Replace minimum user capability.
  
`replace_quick_mail_sender`
  Replace quick mail sender. Expects an associative array with values for `name` and `email`.
  
= Limitations =

* Up to 12 manually entered recipients are saved in HTML Storage.

* Additional recipients can be either `CC` or `BCC` but not both.

* Multiple files can be uploaded from up to 6 folders (directories).

* "Uploads are disabled" on some mobile devices.

Some devices cannot upload files. According to [Modernizr](https://modernizr.com/download#fileinput-inputtypes-setclasses) :
> iOS < 6 and some android version don't support uploads.

File uploads are disabled for ancient IOS 5 devices. Please [add a support message](https://wordpress.org/support/plugin/quick-mail) if uploads are disabled on your phone or tablet, so I can remove the upload button if your device is detected.

= Address Validation =

* Address validation is an option to check recipient domain on manually entered addresses.

* International (non-ASCII) domains must be converted to [punycode](https://tools.ietf.org/html/rfc3492) with [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php).

  Unfortunately, `idn_to_ascii` is not available on all systems.

* "Cannot verify international domains because idn_to_ascii function not found"

  This is displayed when Quick Mail cannot verify domains containing non-ASCII characters.

* [checkdnsrr](http://php.net/manual/en/function.checkdnsrr.php) is used to check a domain for an [MX record](http://www.google.com/support/enterprise/static/postini/docs/admin/en/activate/mx_faq.html).

  An MX record tells senders how to send mail to the domain.

= Mail Errors =

* Quick Mail sends email with [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/).

  `wp_mail` error messages are displayed, if there is a problem.

* You must provide at least one recipient email address.

   `wp_mail` rejected an address. Seen when Quick Mail verification is off.

== Screenshots ==

1. Selecting users on Quick Mail data entry form.

2. Multiple attachments from different folders (directories).

3. Selecting saved recipients.

4. Quick Mail options.

5. Full screen view.

6. Reply to comment.

== Changelog ==

= 3.2.6 =
* Next public release. See [development releases](https://github.com/mitchelldmiller/quick-mail-wp-plugin/releases) for more info.
* only load comment script on comment reply.
* only load delete saved address script on options page.
* added option to limit displayed comments by date.

= Earlier versions =

Please refer to the separate changelog.txt for changes of previous versions.

== Upgrade Notice ==

= 3.2.6 =
* Upgrade optional.

= 3.2.4 =
* Upgrade recommended if your language uses multibyte characters.

= 3.2.3 =
* Upgrade recommended. Added features to send reliable mail and WP-CLI command.

== License ==

Quick Mail is free for personal or commercial use. Encourage future development with a [donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY "Donate with PayPal").

== Translators and Programmers ==

* A .pot file is included for translators.

* Includes French, Russian, Spanish translations.

* Visit [Quick Mail Translations](https://translate.wordpress.org/projects/wp-plugins/quick-mail) for more info.

== Credits ==

Banner image by [Tumisu](https://pixabay.com/en/users/Tumisu-148124/).
