quick-mail-wp-plugin
====================

Adds "Quick Mail" to Tools menu. Send text or html email with file attachment from user's credentials. Fast, simple. Spanish translation.

### Build Status
[![Build Status](https://api.travis-ci.org/mitchelldmiller/quick-mail-wp-plugin.svg?branch=master)](https://travis-ci.org/mitchelldmiller/quick-mail-wp-plugin)

Description
-----------

>Quick Mail is the easiest way to send an email with an attachment to a WordPress user on your site.

Send a quick email from WordPress Dashboard to a WordPress user, or anyone. Adds Quick Mail to Tools menu.

Mail is sent with user's name and email. One file can be attached to message.

Sends text or html mails. Content type is determined from message.

Option to validate recipient domain before mail is sent.

Validates international domains if [idn_to_ascii](http://php.net/manual/en/function.idn-to-ascii.php) is available to convert domain to [Punycode](https://tools.ietf.org/html/rfc3492).

Saves message and subject on form to send repeat messages.

Saves last five email addresses entered on form.

User options for sending email to site users or others.

Site options for administrators to hide their profile, and limit access to user list.

### Installation ###

1. Download the plugin and unpack in your `/wp-content/plugins/` directory

1. Activate the plugin through the 'Plugins' menu in WordPress

### Frequently Asked Questions ###

__Who can send mail?__

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.

* User profile must include first name, last name, email address.

__Selecting Recipients__

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

__Limitations__

* One recipient and one attachment per email.

* Up to 5 manually entered recipients are saved in HTML Storage.

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

__More Info__

* Introduction: [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/)

* See [Quick Mail 1.3.0 Supports International Mail](http://wheredidmybraingo.com/quick-mail-1-3-0-supports-international-mail/) for update info.

__Translators and Programmers__

* A .pot file is included for translators. See [WordPress Translation Tools](https://make.wordpress.org/polyglots/handbook/tools/) for more info.

* Includes Spanish translation.
