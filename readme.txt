=== Quick Mail ===
Contributors: brainiac
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY
Tags: email, admin, mail, attachment, Spanish
Requires at least: 2.9.0
Tested up to: 4.0.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds "Quick Mail" to Tools. Send email with file attachment using logged-in user's credentials. Fast, simple. Spanish translation.

== Description ==

Send a quick email from WordPress Admin. Adds Quick Mail to Tools menu.

Mail is sent with user's name and email. One file can be attached to message.

Saves message and subject on form to send repeat messages.

User options for sending email to site users or others.

Site options for administrators to hide their profile, and limit access to user list.

== Installation ==

1. Download the plugin and unpack in your /wp-content/plugins directory

1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Who can send mail? =

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email. Minimum permission can be changed with a filter.

* User profile must include first name, last name, email address.

= Selecting Recipients =

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

= Limitations =

* HTML and multiple line breaks are removed from message by [sanitize_text_field](http://codex.wordpress.org/Function_Reference/sanitize_text_field).

* One recipient and one attachment per email.

* Message and sender are saved for reuse until window is closed.

* See [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/how-to-send-email-from-wordpress-admin/) for more info.

== Screenshots ==

1. Selecting users on Quick Mail data entry form

== Changelog ==

= 1.1.0 =
* Send email to site users, without typing address.
* Limit access to user list and administrator profile.
* Verify recipient domain on manual entry.
* Removed form background.

= 1.0.2 =
* Minimum role can changed by adding a filter. Props: [@lumpysimon](https://github.com/lumpysimon/)
* Fixed typos.

= 1.0.1 =
* First version on WordPress Plugin Repository

== Upgrade Notice ==

= 1.1.0 =
* Upgrade recommended.
* Sends mail to users without typing addresses.
* Validates email domain on manually entered email address.

= 1.0.2 =
* Upgrade if you want to add a filter to change the minimum role.

== License ==

This plugin is free for personal or commercial use. If you like it, you can thank me and support future development with a [small donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY "Donate with PayPal"). Thank you :)

== Translators and Programmers ==

* A .pot file is included for translators. See [WordPress Translation Tools](https://make.wordpress.org/polyglots/handbook/tools/) for more info.

* Includes Spanish translation.