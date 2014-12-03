=== Quick Mail ===
Contributors: brainiac
Tags: email, admin, mail, attachment, Spanish
Requires at least: 2.9.0
Tested up to: 4.0.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds "Quick Mail" to Tools. Sends email with optional file attachment from logged-in user's credentials. Fast, simple. Spanish translation.

== Description ==

Send a quick email from WordPress Admin. Adds Quick Mail to Tools menu.

No options. Mail is sent with user's name and email. One file can be attached to message.

Saves message and subject on form to send repeat messages.

== Installation ==

1. Download the plugin and unpack in your /wp-content/plugins/ directory

1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Who can send mail? =

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.

* User profile must include first name, last name, email address.

= Limitations =

* HTML and multiple line breaks are removed from message by [sanitize_text_field](http://codex.wordpress.org/Function_Reference/sanitize_text_field).

* Email addresses cannot be selected or saved. Message and sender are saved for reuse until window is closed.

* See [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/how-to-send-email-from-wordpress-admin/) for more info.

== Screenshots ==

1. Data entry form

2. **Quick Mail** on Tools

== Changelog ==

= 1.0.0 =
* First version on GitHub

= 1.0.1
* Updated Readme and minor edit

== Upgrade Notice ==

= 1.0.1 =
* Updated Readme and minor edit

= 1.0.2 =
* add filter to change minimum Role. Props: [@lumpysimon](https://github.com/lumpysimon/)

== License ==

This plugin is free for personal or commercial use. If you like it, you can thank me and support future development with a [small donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY "Donate with PayPal"). Thank you :)

== Translators and Programmers ==

* A .pot file is included for translators. See [WordPress Translation Tools](https://make.wordpress.org/polyglots/handbook/tools/) for more info.

* Spanish translation included