=== Quick Mail ===
Contributors: brainiac
Tags: mail, email, comments, wp-cli, mailgun, sparkpost, attachment, sendgrid, accessibility, idn, multisite
Donate link: https://mitchelldmiller.com/donate
Requires at least: 4.6
Tested up to: 6.4
Requires PHP: 5.3
Stable tag: 4.1.9
License: MIT
License URI: https://github.com/mitchelldmiller/quick-mail-wp-plugin/blob/master/LICENSE

Send text or HTML emails with attachments and shortcodes from your WordPress dashboard or command line. Send private replies to comments. Select recipients from users or commenters. Compatible with multisite. Includes WP-CLI command.

== Description ==
>Quick Mail is the easiest way to send emails with attachments to WordPress users on your site, or to send private replies to comments.

== Features ==

* Send email with attachments and shortcodes from the WordPress dashboard or command line.

* Send private replies to comments.

* Select recipients from users or commenters.

* Compatible with multisite.

* Adds Quick Mail to the Tools menu and comment replies.

* Send a web page, file, or message from the command line with the quick-mail command for [WP-CLI](https://wp-cli.org/).

* Does not require the Gutenberg editor or REST API.

* Edit messages with [TinyMCE](https://codex.wordpress.org/TinyMCE) to add images, rich text, and shortcodes.

* Uses the sender's name and sets the reply-to address to the sender's address.

* Recognizes settings from [Mailgun](https://wordpress.org/plugins/mailgun/), [SparkPost](https://wordpress.org/plugins/sparkpost/) and [Sendgrid](https://github.com/frc/sendgrid-email-delivery-simplified/releases/latest) plugins.

* Attach multiple files from up to six directories (folders) to a message.

* Includes privacy options to help comply with [General Data Protection Regulation](https://en.wikipedia.org/wiki/General_Data_Protection_Regulation).

* [Banned domains](https://wheredidmybraingo.com/quick-mail-4-0-5-blocks-domains/): administrators can prevent users from sending mail to arbitrary domains.


= Learn More =

* [Follow development on Github](https://github.com/mitchelldmiller/quick-mail-wp-plugin/).

== Installation ==

= Automated =
* Install [Git Updater](https://github.com/afragen/git-updater) plugin to update Quick Mail from Github.

= Manual =
1. Download the plugin and unpack in your `/wp-content/plugins` directory.

2. Activate the plugin through the WordPress _Plugins_ menu.

= WP-CLI
* How to install and activate the latest version of Quick Mail with [WP-CLI](https://wp-cli.org/) :

	`wp plugin install https://github.com/mitchelldmiller/quick-mail-wp-plugin/archive/master.zip --activate`

== Configuration ==
1. Visit the settings page at `Settings -> Quick Mail` to configure the plugin for your site.

2. Optional: Install [WP-CLI](https://wp-cli.org/#installing) to send mail from the command line.

3. Optional: Install [Mailgun](https://wordpress.org/plugins/mailgun/), [SparkPost](https://wordpress.org/plugins/sparkpost/) or [Sendgrid](https://github.com/frc/sendgrid-email-delivery-simplified/releases/latest) plugin to send reliable email.

4. Optional: Install experimental [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) plugin, to change administrator's credentials.

== Translators / Translations ==

* Quick Mail is not distributed with translations.

* A .pot file is included for new translators.

== Frequently Asked Questions ==

= Who can send mail? =

* Users must be able to [publish a post](https://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email. Minimum permission can be changed with a filter.


* WP-CLI: Only administrators can send mail with the `quick-mail` WP-CLI command.


= Who can send rich text messages? =

* User must have [Visual Editor enabled](https://codex.wordpress.org/Function_Reference/user_can_richedit) on their profile, to compose messages with the Visual Editor.


* Anyone can send HTML by pasting it into a message.


= Where Do I Find Sent Emails? =

* You should be able to find sent emails in your email account's Sent Mail folder.


* Delivery services like [Mailgun](https://www.mailgun.com/), [SparkPost](https://wordpress.org/plugins/sparkpost/) and [Sendgrid](https://sendgrid.com/) also provide this information.

= Selecting Recipients =

* Options to send mail to any user, or limit to users with first and last names on their profile.


* Users need permission to [list users](https://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

= Mail Delivery Services =

* Uses [Mailgun plugin](https://wordpress.org/plugins/mailgun/) settings for Administrators, if the plugin is activated, using `Override "From" Details` and [Mailgun API](https://documentation.mailgun.com/en/latest/api_reference.html).


* Uses [SparkPost plugin](https://wordpress.org/plugins/sparkpost/) settings for Administrator name and email address, if plugin is activated and `Overrides` for name and email are set.


* Option for administrators to use [Sendgrid API](https://sendgrid.com/solutions/sendgrid-api/).

= Sending Mail from Other Addresses =

* Install the [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) plugin, to change the Quick Mail sender's name and email address.


* NOTE: Other email plugins can change these settings.

= Privacy =

* Requires permission to use your email address.


* Requires permission to save email addresses. Saved addresses are cleared if permission option is changed.

= Limitations =

* Up to 12 manually entered recipients are saved in HTML Storage.


* Additional recipients can be either `CC` or `BCC` but not both.


* Up to 99 recipients for [Gmail](https://support.google.com/a/answer/166852), others.


* Multiple files can be uploaded from up to 6 folders (directories).


* "Uploads are disabled" on some mobile devices.

Some devices cannot upload files. According to [Modernizr](https://modernizr.com/download#fileinput-inputtypes-setclasses) :
> iOS < 6 and some Android version don't support uploads.

File uploads are disabled for ancient IOS 5 devices. Please [add a support message](https://wordpress.org/support/plugin/quick-mail) if uploads are disabled on your phone or tablet, so I can remove the upload button if your device is detected.

= Address Validation =

* Check recipient domain on manually entered addresses.


* International (non-ASCII) domains must be converted to [Punycode](https://tools.ietf.org/html/rfc3492) with [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php).

  Unfortunately, `idn_to_ascii` is not available on all systems.
  
  
* "Cannot verify international domains because idn_to_ascii function not found"

  This is displayed when Quick Mail cannot verify domains containing non-ASCII characters.
  
  
* [checkdnsrr](http://php.net/manual/en/function.checkdnsrr.php) is used to check a domain for an [MX record](https://en.wikipedia.org/wiki/MX_record).

  An MX record tells senders how to send mail to the domain.
  
  *This is not always accurate. Turn verification off if Quick Mail rejects a valid address.*
  
= Mail Errors =

* Quick Mail sends email with [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/).

  `wp_mail` error messages are displayed, if there is a problem.
  
  
* You must provide at least one recipient email address.

   `wp_mail` rejected an address. Seen when Quick Mail verification is off.
   
   
* "Invalid or blocked mail address."

	You tried sending mail to a Banned Domain.


* Error: Invalid Role (WP-CLI error)

	You tried sending mail to an unknown WordPress role. Use `wp list roles` to get role names.
	
= Incompatible Plugins =

* [Stop Emails](https://wordpress.org/plugins/stop-emails/)

Stop Emails displays: 
> _To send emails, disable the plugin._

If you are using an email delivery service, you can ignore this message.

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
  Replace sender credentials. Expects associative array with values for `name` and `email`. See [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender) plugin for examples.
  
== Screenshots ==

1. Selecting users on Quick Mail data entry form.

2. Multiple attachments from different folders (directories).

3. Selecting saved recipients.

4. Quick Mail options.

5. Full screen view.

6. Reply to comment.

7. User list with roles.

== Changelog ==

= 4.1.9 =
* Updated versions, readme.
* Tested with WordPress 6.2

= 4.1.6 =
* Fixed "empty needle" after banned addresses are cleared.
* Tested with WordPress 5.8.3.

= 4.1.5 =
* Replace commas with spaces on banned domains.
* Tested with WordPress 5.8.2.

= 4.1.4 =
* Added Update URI header for WordPress 5.8.
* Tested with WordPress 5.8.1.

= 4.1.3 =
* Fixed "empty needle" after new installation.

= 4.1.1 =
* Restored directory value for helper plugins.
* Tested with WordPress 5.7.

= 4.1.0 =
* Replaced file_get_contents, file_put_contents in WP-CLI command.

= 4.0.6 =
* Use cURL to check for a banned domain.
* Fixed bug converting international domain name to Punycode.
* Updated design to improve testing.

= 4.0.5 =
* Added option to reject arbitrary domains.
* New error message: Invalid or blocked mail address.
* Deprecated: Replacing QuickMail::$directory with constant QuickMail::DIRECTORY.

= 4.0.4 =
* Fixed jQuery TypeError on cc address validation.
* Updated readmes, license.

= 4.0.2 =
* Fixed jQuery Migrate 3.0 warnings.

= Earlier versions =

Please refer to changelog.txt for changes of previous versions.

== Upgrade Notice ==

= 4.1.9 =
* Upgrade optional.

= 4.1.6 =
* Upgrade recommended.

= 4.1.5 =
* Upgrade recommended.

= 4.1.4 =
* Upgrade recommended.

= 4.1.3 =
* Upgrade recommended.

= 4.1.1 =
* Upgrade recommended.

= 4.1.0 =
* Upgrade recommended.

= 4.0.6 =
* Upgrade recommended.

= 4.0.5 =
* Upgrade recommended.

= 4.0.4 =
* Upgrade recommended.

== License ==

Quick Mail is free for personal or commercial use. Please support future development with a [donation](https://mitchelldmiller.com/donate).

== Credits ==

Banner image by [Tumisu](https://pixabay.com/en/users/Tumisu-148124/).
