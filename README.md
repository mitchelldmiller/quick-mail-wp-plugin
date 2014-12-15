quick-mail-wp-plugin
====================

Quick Mail WordPress plugin sends email with attachment from WordPress admin.

Description
-----------

Send a quick email from WordPress Admin. Adds Quick Mail to WordPress Tools menu.

Mail is sent with user's name and email. One file can be attached to message.

Saves message and subject on form to send repeat messages.

User options for sending email to site users or others.

Site options for administrators to hide their profile, and limit access to user list.

### Installation ###

1. Download the plugin and unpack in your /wp-content/plugins/ directory

1. Activate the plugin through the 'Plugins' menu in WordPress

### Frequently Asked Questions ###

__Who can send mail?__

* Users must be able to [publish a post](http://codex.wordpress.org/Roles_and_Capabilities#publish_posts) to send an email.

* Minimum role can changed by adding a filter. Props: [@lumpysimon](https://github.com/lumpysimon/)

* User profile must include first name, last name, email address.

* See [Quick Mail WordPress Plugin Update: Send Email to Site Users] (http://wheredidmybraingo.com/quick-mail-wordpress-plugin-update-send-email-to-site-users/) for update info.

* See [How to Send Email from WordPress Admin](http://wheredidmybraingo.com/how-to-send-email-from-wordpress-admin/) for introduction.

__Selecting Recipients__

* Options to send mail to any user, or limit to users with first and last names on their profile.

* Users need permission to [list users](http://codex.wordpress.org/Roles_and_Capabilities#list_users), to view user list or change options. Minimum permission can be changed with an option or filter.

__Limitations__

* HTML and multiple line breaks are removed from message by [sanitize_text_field](http://codex.wordpress.org/Function_Reference/sanitize_text_field).

* Email addresses cannot be selected or saved. Message and sender are saved for reuse until window is closed.

__Translators and Programmers__

* A .pot file is included for translators. See [WordPress Translation Tools](https://make.wordpress.org/polyglots/handbook/tools/) for more info.

* Spanish translation is included