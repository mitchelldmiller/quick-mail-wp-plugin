Quick Mail WordPress Plugin
====================
Quick Mail makes it easy to send email with attachments and shortcodes from your WP dashboard or command line. Reply privately to comments. Choose recipients from users or commenters. Multisite compatible. Includes powerful WP-CLI command.

* Requires: [WordPress 4.6](https://wordpress.org/support/wordpress-version/version-4-6/)
* Tested up to: [WordPress 6.8](https://wordpress.org/news/2025/04/cecil/)
* Stable version: [4.1.10](https://github.com/mitchelldmiller/quick-mail-wp-plugin/releases/latest)

Description
-----------

>Quick Mail is the easiest way to send emails with attachments to WordPress users on your site, or to send private replies to comments.

__Features__

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

### Installation ###


#### Legacy ####
1. Download the [latest release](https://github.com/mitchelldmiller/quick-mail-wp-plugin/releases/latest) and unpack in your `/wp-content/plugins` directory.


2. Activate the plugin through the WordPress _Plugins_ menu.

#### WP-CLI ####
* How to install and activate the latest version of Quick Mail with [WP-CLI](https://wp-cli.org/) :

	`wp plugin install https://github.com/mitchelldmiller/quick-mail-wp-plugin/archive/master.zip --activate`

### Configuration ###
* Visit the settings page at `Settings -> Quick Mail` to configure the plugin for your site.


* Optional: Install [WP-CLI](https://wp-cli.org/#installing) to send mail from the command line.


* Optional: Install [Mailgun](https://wordpress.org/extend/plugins/mailgun/), [SparkPost](https://wordpress.org/plugins/sparkpost/) or [Sendgrid](https://github.com/frc/sendgrid-email-delivery-simplified/releases/latest) plugin to send reliable email.


* Optional: Install experimental [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) plugin, to change administrator's credentials.

#### Updates ####

* Install [Git Updater](https://github.com/afragen/git-updater) plugin to update Quick Mail from Github.


### Translators / Translations ###

* Quick Mail is not distributed with translations.


* A .pot file is included for new translators.

### Frequently Asked Questions ###

__Who can send mail?__

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.


* WP-CLI: By default, only administrators can send mail with the `quick-mail` WP-CLI command. Use 
the [quick_mail_cli_admin_only](https://wheredidmybraingo.com/whats-new-in-quick-mail-4-0-1) filter to let other users send mail.

__Who can send rich text messages?__

* User must have [Visual Editor enabled](https://codex.wordpress.org/Function_Reference/user_can_richedit) on their profile, to compose messages with the Visual Editor.


* Anyone can send HTML by pasting it into a message.

__Where Do I Find Sent Emails?__

* You should be able to find sent emails in your email account's Sent Mail folder.


* Delivery services like [Mailgun](https://www.mailgun.com/), [SparkPost](https://wordpress.org/plugins/sparkpost/) and [Sendgrid](https://sendgrid.com/) also provide this information. 

__Selecting Recipients__

* Options to send mail to any user, or limit to users with first and last names on their profile.


* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

__Mail Delivery Services__

* Uses [Mailgun plugin](https://wordpress.org/plugins/mailgun/) settings for Administrators, if the plugin is activated, using `Override "From" Details` and [Mailgun API](https://documentation.mailgun.com/en/latest/api_reference.html).


* Uses [SparkPost plugin](https://wordpress.org/plugins/sparkpost/) settings for Administrator name and email address, if plugin is activated and `Overrides` for name and email are set.


* Option for administrators to use [Sendgrid API](https://sendgrid.com/solutions/sendgrid-api/). 


* [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) is an experimental plugin that changes the Quick Mail sender's name and email address.



* Programmers can replace their credentials by adding a filter to `replace_quick_mail_sender`. This only works if you are not using another mail plugin's credentials.


__Sending Mail from Other Addresses__

* Install the [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/
releases/latest) plugin, to change the Quick Mail sender's name and email address.


* Programmers can replace their credentials by adding a filter to `replace_quick_mail_sender`.


* NOTE: Other email plugins can change these settings.


__Privacy__

* Requires permission to use your email address.


* Requires permission to save email addresses. Saved addresses are cleared if permission option is changed.


__Limitations__

* Up to 12 manually entered recipients are saved in HTML Storage.


* Additional recipients can be either `CC` or `BCC` but not both.


* Up to 99 recipients for [Gmail](https://support.google.com/a/answer/166852), others.


* Multiple files can be uploaded from up to 6 folders (directories).


* "Uploads are disabled" on mobile devices.

Some devices cannot upload files. According to [Modernizr](https://modernizr.com/download#fileinput-inputtypes-setclasses) :
> iOS < 6 and some android version don't support this


* File uploads are disabled for ancient IOS 5 devices. Please [add a support message](https://github.com/mitchelldmiller/quick-mail-wp-plugin/issues) if uploads are disabled on your phone or tablet.


__Address Validation__

* Check recipient domain on manually entered addresses.


* International (non-ASCII) domains must be converted to [Punycode](https://tools.ietf.org/html/rfc3492) with [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php).


  Unfortunately, `idn_to_ascii` is not available on all systems.

* "Cannot verify international domains because idn_to_ascii function not found"


  This is displayed when Quick Mail cannot verify domains containing non-ASCII characters.

* [checkdnsrr](http://php.net/manual/en/function.checkdnsrr.php) is used to check a domain for an [MX record](https://en.wikipedia.org/wiki/MX_record).


  An MX record tells senders how to send mail to the domain.

  *This is not always accurate. Turn verification off if Quick Mail rejects a valid address.*

__Mail Errors__

* Quick Mail sends email with [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/).

	`wp_mail` error messages are displayed, if there is a problem.


* "You must provide at least one recipient email address."

	`wp_mail` rejected an address. Seen when Quick Mail verification is off.


* "Invalid or blocked mail address."

	You tried sending mail to a Banned Domain.


* Error: Invalid Role (WP-CLI error)

	You tried sending mail to an unknown WordPress role. Use `wp list roles` to get role names.


__Incompatible Plugins__

* [Stop Emails](https://wordpress.org/plugins/stop-emails/)


Stop Emails displays: 

> _To send emails, disable the plugin._

If you are using an email delivery service, you can ignore this message.


__Customizing Quick Mail__

* Add a filter to modify Quick Mail.


- What filters are available to modify Quick Mail?

`replace_quick_mail_sender`

  Replace sender credentials. Expects associative array with values for `name` and `email`.

  See [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender) plugin for examples.

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

`quick_mail_cli_admin_only`

  Allow non-admin users to send mail with WP-CLI.

__Additional Information__

* [42 articles about Quick Mail](https://wheredidmybraingo.com/tag/quick-mail/)


__Translators and Programmers__

* A .pot file is included for translators.


__License__

Quick Mail is licensed under the MIT License. Encourage future development with a [donation](https://mitchelldmiller.com/donate).

__Credits__

Banner image by [Tumisu](https://pixabay.com/en/users/Tumisu-148124/).

