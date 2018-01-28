Quick Mail WordPress Plugin
====================

Send text or html email with attachments and shortcodes. Send private replies to comments. Select recipient from users or commenters. French, Russian, Spanish translations.

### WordPress Rating
[![WordPress rating](https://img.shields.io/wordpress/plugin/r/quick-mail.svg?maxAge=2592000)]()

Description
-----------

>Quick Mail is the easiest way to send email with attachments to WordPress users on your site, or send private replies to comments. Compatible with multisite.

Send a quick email from WordPress Dashboard to WordPress users, or anyone. Adds Quick Mail to Tools menu and comment replies.

Send a Web page, file, or message from the command line with quick-mail command for [WP-CLI](https://wp-cli.org/).

Edit messages with [TinyMCE](https://codex.wordpress.org/TinyMCE) to add images, rich text and [shortcodes](https://codex.wordpress.org/Shortcode).

Uses sender's name, sets reply to sender's address. Recognizes settings from [Mailgun](https://wordpress.org/plugins/mailgun/), [SparkPost](https://wordpress.org/plugins/sparkpost/) and [Sendgrid](https://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugins.

Multiple files from up to six directories (folders) can be attached to a message.

Privacy options to comply with [General Data Protection Regulation](https://en.wikipedia.org/wiki/General_Data_Protection_Regulation).

__Features__

* Includes a [WP-CLI](https://wp-cli.org/) command to send a file, or the the contents of a Web page.

* Sends text or html mails to multiple recipients. Content type is determined from message.

* Send private replies with attachments to comments.

* Multiple recipients can be selected from users or entered manually.

* Saves message and subject on form to send repeat messages.

* Saves last 12 email addresses entered on form.

* Share a WordPress draft by copying / pasting its code into a message.

* Option to validate recipient domains with [checkdnserr](http://php.net/manual/en/function.checkdnsrr.php) before mail is sent.

* Validates international domains if [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php) is available to convert domain to [Punycode](https://tools.ietf.org/html/rfc3492).

* Site options for administrators to hide their profile, and limit access to user list.

* Option to add paragraphs and line breaks to HTML messages with [wpauto](https:/codex.wordpress.org/Function_Reference/wpautop).

* Select recipient from users or commenters.

### Learn More
* Read [How to Send Email from WordPress Admin](https://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/) for an introduction.

* Read [Quick Mail Supports SparkPost](https://wheredidmybraingo.com/quick-mail-wordpress-plugin-supports-sparkpost/) for update info.

### Installation ###

* Download the plugin and unpack in your `/wp-content/plugins` directory.

* Activate the plugin through the 'Plugins' menu in WordPress.

* Visit the settings page at `Settings -> Quick Mail` to configure the plugin for your site.

* Optional: [Install WP-CLI](https://wp-cli.org/#installing) to send mail from the command line.

* Optional: Install [Mailgun](https://wordpress.org/extend/plugins/mailgun/), [SparkPost](https://wordpress.org/plugins/sparkpost/) or [Sendgrid](https://wordpress.org/plugins/sendgrid-email-delivery-simplified/) plugin to send reliable email.

### Frequently Asked Questions ###

__Who can send mail?__

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.

* WP-CLI: Only administrators can send mail with the `quick-mail` WP-CLI command.

__Who can send rich text messages?__

* User must have [Visual Editor enabled](https://codex.wordpress.org/Function_Reference/user_can_richedit) on their profile, to compose messages with the Visual Editor.

* Anyone can send HTML by pasting it into a message.

__Where Do I Find Sent Emails?__

* You should be able to find sent emails in your email account's Sent Mail folder.

* Delivery services like [Mailgun](https://www.mailgun.com/), [SparkPost](https://wordpress.org/plugins/sparkpost/) and [Sendgrid](https://sendgrid.com/) also provide this information. 

__Selecting Recipients__

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

__Sending Mail from Other Addresses__

* Uses [Mailgun plugin](http://wordpress.org/extend/plugins/mailgun/) settings for Administrators, if the plugin is activated, using `Override "From" Details` and [Mailgun API](https://documentation.mailgun.com/en/latest/api_reference.html).

* Uses [SparkPost plugin](https://wordpress.org/plugins/sparkpost/) settings for Administrator name and email address, if plugin is activated and `Overrides` for name and email are set.

* Option for administrators to use [Sendgrid API](https://sendgrid.com/solutions/sendgrid-api/). 

* [Replace Quick Mail Sender](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) is an experimental plugin that changes the Quick Mail sender's name and email address.

* Programmers can replace their credentials by adding a filter to `replace_quick_mail_sender`. This only works if you are not using another mail plugin's credentials.

__Privacy__

* Requires permission to use your email address.

* Requires permission to save email addresses. Saved addresses are cleared if permission option is changed.

__Modifying Quick Mail__

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

__Limitations__

* Up to 12 manually entered recipients are saved in HTML Storage.

* Additional recipients can be either `CC` or `BCC` but not both.

* Multiple files can be uploaded from up to 6 folders (directories).

* "Uploads are disabled" on mobile devices.

Some devices cannot upload files. According to [Modernizr](https://modernizr.com/download#fileinput-inputtypes-setclasses) :
> iOS < 6 and some android version don't support this

File uploads are disabled for ancient IOS 5 devices. Please [add a support message](https://wordpress.org/support/plugin/quick-mail) if uploads are disabled on your phone or tablet, so I can remove the upload button if your device is detected.

__Address Validation__

* Address validation is an option to check recipient domain on manually entered addresses.

* International (non-ASCII) domains must be converted to [punycode](https://tools.ietf.org/html/rfc3492) with [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php).


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
   
__Translators and Programmers__

* A .pot file is included for translators.

* Includes French, Russian, Spanish translations.

* See [Quick Mail Translations](https://translate.wordpress.org/projects/wp-plugins/quick-mail) for more info.

__License__

This plugin is free for personal or commercial use. 
