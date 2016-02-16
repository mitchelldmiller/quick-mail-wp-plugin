=== Quick Mail ===
Contributors: brainiac
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY
Tags: email, admin, mail, attachment, Spanish
Requires at least: 4.2
Tested up to: 4.4.2
Stable tag: 1.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds "Quick Mail" to Tools. Send text or html email with a file attachment from user's credentials. Fast, simple. Spanish translation.

== Description ==

>Quick Mail is the easiest way to send an email with an attachment to a WordPress user on your site.

Send a quick email from WordPress Dashboard to a WordPress user, or anyone. Adds Quick Mail to Tools menu.

Mail is sent with user's name and email. One file can be attached to message.

Sends text or html mails. Content type is determined from message.

Recipient domain is verified before mail is sent.

Saves message and subject on form to send repeat messages.

Saves last five email addresses entered on form.

User options for sending email to site users or others.

Site options for administrators to hide their profile, and limit access to user list.

* See [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/) for an introduction.

* See [Quick Mail 1.2.5 WordPress Plugin Works on More Sites](http://wheredidmybraingo.com/quick-mail-1-2-5-wordpress-plugin/) for update info.

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

* One recipient and one attachment per email.

* Up to 5 manually entered recipients are saved in HTML Storage.

== Screenshots ==

1. Selecting users on Quick Mail data entry form

2. Selecting recent email addresses

== Changelog ==

= 1.2.5 =
 * Replaced WP REST API usage with database query.
 * Improved user list CSS.
 * Tested with WordPress 4.4.2

= 1.2.4 =
* Improved installation on sites with a single user.
* Uses WP REST API during installation, if available.
* Fixed security warning for changed files.
* Tested with WordPress 4.4.1

= 1.2.3 =
* Requires WordPress 4.2 or greater for dismissible messages.
* Improved documentation.
* Tested with WordPress 4.3.1, 4.4

= 1.2.2 =
* Status messages must be dismissed by user. Messages vanished after 4 seconds in previous versions.
* Tested with WordPress 4.2.3

= 1.2.1 =
* Maintenance release.
* Fixed error flash when mail is loaded.
* Fixed capability check for admin.
* Fixed nested quotes on form.

= 1.2.0 =
* Send HTML mail.
* Message text is not filtered.
* Manually entered email addresses are saved.
* Improved install / uninstall.
* Default options are "show admin" and "show all users." Works on sites with one user.
* Moved plugin functions into a class.
* Tested with WordPress 4.2.2

= 1.1.1 =
* temp upload dir: use [sys_get_temp_dir](http://php.net/manual/en/function.sys-get-temp-dir.php) if [upload_tmp_dir](http://php.net/manual/en/ini.core.php#ini.upload-tmp-dir) is undefined.
* uninstall deletes plugin options.
* tested with WordPress 4.1.2

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

= 1.2.5 =
* Upgrade only needed to change the user list style.
* Improved installation on single user sites.

= 1.2.4 =
* Upgrade recommended.
* Improved installation.
* Fixes security warning.

= 1.2.3 =
* Upgrade recommended.
* Complies with WordPress 4.3 plugin changes.

= 1.2.2 =
* Upgrade recommended.
* Improved status messages.

= 1.2.1 =
* Upgrade recommended.
* Fixed three bugs.

= 1.2.0 =
* Upgrade recommended.
* Send HTML mail.
* Saves five manually entered email addresses.
* Improved install / uninstall.

= 1.1.1 =
* Upgrade if you were unable to upload attachments.

= 1.1.0 =
* Upgrade recommended.
* Sends mail to users without typing addresses.
* Validates email domain on manually entered email address.

= 1.0.2 =
* Upgrade if you want to add a filter to change the minimum role.

== License ==

This plugin is free for personal or commercial use. Please encourage future development with a [donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4AAGBFXRAPFJY "Donate with PayPal").

== Translators and Programmers ==

* A .pot file is included for translators. See [WordPress Translation Tools](https://make.wordpress.org/polyglots/handbook/tools/) for more info.

* Includes Spanish translation.
