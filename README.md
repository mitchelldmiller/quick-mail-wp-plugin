quick-mail-wp-plugin
====================

Quick Mail WordPress plugin sends email with attachment from WordPress admin.

Description
-----------

Send a quick email from WordPress Admin. Adds Quick Mail to WordPress Tools menu.

No options. Mail is sent with user's name and email. One file can be attached to message.

Saves message and subject on form to send repeat messages.

### Installation ###

1. Download the plugin and unpack in your /wp-content/plugins/ directory

1. Activate the plugin through the 'Plugins' menu in WordPress

### Frequently Asked Questions ###

__Who can send mail?__

* Users with [manage_options](http://codex.wordpress.org/Roles_and_Capabilities#manage_options) capability.

* User profile must include first name, last name, email address.

__Limitations__

* HTML and multiple line breaks are removed from message by [sanitize_text_field](http://codex.wordpress.org/Function_Reference/sanitize_text_field).

* Email addresses cannot be selected or saved. Message and sender are saved for reuse until window is closed.
