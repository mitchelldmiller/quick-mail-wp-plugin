=== Quick Mail ===
Contributors: braniac
Tags: email, admin, mail
Requires at least: 2.9.0
Tested up to: 4.0
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

* Users with [manage_options](http://codex.wordpress.org/Roles_and_Capabilities#manage_options) capability.

* User profile must include first name, last name, email address.

= Limitations =

* HTML and multiple line breaks are removed from message by [sanitize_text_field](http://codex.wordpress.org/Function_Reference/sanitize_text_field).

* Email addresses cannot be selected or saved. Message and sender are saved for reuse until window is closed.

== Screenshots ==

1. Data entry form

2. **Quick Mail** on Tools

== Changelog ==

= 1.0 =
* First version

== Upgrade Notice ==

= 1.0 =
* No upgrades are available

== Translators and Programmers ==

* A .pot file is included for translators.

* A Spanish translation is included