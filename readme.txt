=== Quick Mail ===
Contributors: brainiac
Tags: mail, email, comments, mailgun, sendgrid, attachment, accessibility, comment, idn, multisite, rich text, tinymce
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 3.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send reliable text or html email with attachments and shortcodes. Send private replies to comments. Select recipient from users or commenters.

== Description ==
>Quick Mail is the easiest way to send email with attachments to WordPress users on your site, or send private replies to comments. Compatible with multisite.

Send a quick email from WordPress Dashboard to WordPress users, or anyone. Adds Quick Mail to Tools menu and comment replies.

Edit messages with [TinyMCE](https://codex.wordpress.org/TinyMCE) to add images, rich text and [shortcodes](https://codex.wordpress.org/Shortcode).

User options for sending email to site users or others.

Mail is sent with user's name and email. Recognizes credentials from [Mailgun](https://wordpress.org/plugins/mailgun/) and [Sendgrid](https://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugins.

Multiple files from up to six directories (folders) can be attached to a message.

== Installation ==
1. Download the plugin and unpack in your `/wp-content/plugins` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the settings page at `Settings -> Quick Mail` to configure the plugin for your site.

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

* Programmers can replace their credentials by adding a filter to replace_quick_mail_sender. This only works, if you are not using another mail plugin's credentials.

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

* "You must provide at least one recipient email address."

   `wp_mail` rejected an address. Seen when Quick Mail verification is off.


== Screenshots ==

1. Selecting users on Quick Mail data entry form.
2. Multiple attachments from different folders (directories).
3. Selecting saved recipients.

== Changelog ==

= 3.2.3 =
* Next public release. See [development releases](https://github.com/mitchelldmiller/quick-mail-wp-plugin/releases) for more info.
* Send URL or file contents with quick-mail WP CLI command.
* Uses Mailgun credentials, if the plugin is active and set to use API.
* Option to use Sendgrid credentials, if the plugin is active.
* Organized plugin files into multiple directories.
* Fixed "Login Expired" error when form was requested with POST from another plugin.

= 3.1.8 =
* Fixed error that presented user list to administrator when there are less than 3 users on site.

= 3.1.7 =
* Next public release. See [development releases](https://github.com/mitchelldmiller/quick-mail-wp-plugin/releases) for more info.
* Many changes to improve private comment replies.

= 3.1.1 =
* reply to comments with Quick Mail from Comments list. 
* fix error displaying multibyte post titles.

= 3.1.0 =
* reply to comments.
* improved performance.

= 3.0.5 =
* added Russian translation. Props @orlov562

= 3.0.4 =
* fixed reset email content type.
* preserves shortcodes in messages.

= 3.0.3 =
* fixed email content type compatibility error.
* fixed settings display error.

= 3.0.2 =
* added wpauto option for HTML messages.
* display user nickname instead of `user_nicename`.

= 3.0.1 =
* added Blind Carbon Copy (BCC).
* improved HTML messages.

= 3.0.0 =
* improved data entry form accessibility and design.
* added visual editor.

== Upgrade Notice ==

= 3.2.3 =

* Upgrade recommended. Added features to send reliable mail and WP-CLI command.

== License ==

Quick Mail is free for personal or commercial use. Encourage future development with a [donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY "Donate with PayPal").

== Translators and Programmers ==

* A .pot file is included for translators.

* Includes French, Russian, Spanish translations.

* Visit [Quick Mail Translations](https://translate.wordpress.org/projects/wp-plugins/quick-mail) for more info.

== Credits ==

Banner image by [27707](https://pixabay.com/en/users/27707-27707/).
