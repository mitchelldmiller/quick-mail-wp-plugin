quick-mail-wp-plugin
====================

Adds "Quick Mail" to Tools. Send text or html email with file attachments from user's credentials. French and Spanish translations.

### WordPress Rating
[![WordPress rating](https://img.shields.io/wordpress/plugin/r/quick-mail.svg?maxAge=2592000)]()

Description
-----------

>Quick Mail is the easiest way to send an email with attachments to a WordPress user on your site.

Send a quick email from WordPress Dashboard to WordPress users, or anyone. Adds Quick Mail to Tools menu.

Edit messages with [TinyMCE](https://codex.wordpress.org/TinyMCE) to add images, rich text and [shortcodes](https://codex.wordpress.org/Shortcode).

User options for sending email to site users or others. Mail is sent with user's name and email. Multiple files from up to six directories (folders) can be attached to a message.

__Features__

* Sends text or html mails to multiple recipients. Content type is determined from message.

* Multiple recipients can be selected from users or entered manually.

* Saves message and subject on form to send repeat messages.

* Saves last 12 email addresses entered on form.

* Share a WordPress draft by copying / pasting its code into a message.

* Option to validate recipient domains with [checkdnserr](http://php.net/manual/en/function.checkdnsrr.php) before mail is sent.

* Validates international domains if [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php) is available to convert domain to [Punycode](https://tools.ietf.org/html/rfc3492).

* Site options for administrators to hide their profile, and limit access to user list.

### Learn More
* See [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/) for an introduction.

* See [Quick Mail Adds BCC, Improves HTML Messages](https://wheredidmybraingo.com/quick-mail-adds-bcc-improves-html-messages/) for update info.

### Installation ###

1. Download the plugin and unpack in your `/wp-content/plugins/` directory.

1. Activate the plugin through the 'Plugins' menu in WordPress.

### Frequently Asked Questions ###

__Who can send mail?__

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.

* User profile must include first name, last name, email address.

= Who can send rich text messages? =

* User must have [Visual Editor enabled](https://codex.wordpress.org/Function_Reference/user_can_richedit) on their profile, to compose messages with the Visual Editor.

* Anyone can send HTML by pasting it into a message.

__Selecting Recipients__

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

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

* [checkdnsrr](http://php.net/manual/en/function.checkdnsrr.php) is used to check a domain for an [MX record](http://www.google.com/support/enterprise/static/postini/docs/admin/en/activate/mx_faq.html).


  An MX record tells senders how to send mail to the domain.

__Mail Errors__

* Quick Mail sends email with [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/).


  `wp_mail` error messages are displayed, if there is a problem.

* "You must provide at least one recipient email address."


   `wp_mail` rejected an address. Seen when Quick Mail verification is off.

__Translators and Programmers__

* A .pot file is included for translators.

* Includes French and Spanish translations.

* See [Quick Mail Translations](https://translate.wordpress.org/projects/wp-plugins/quick-mail) for more info.

__License__

This plugin is free for personal or commercial use. 


