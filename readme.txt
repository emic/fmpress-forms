=== FMPress Forms ===
Contributors: emiccorp, matsuoatsushi
Tags: claris, filemaker, database, contact form 7, form
Requires at least: 5.7
Tested up to: 6.0
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FMPress Forms can save form data to Claris FileMaker Server.

== Description ==

FMPress Forms is a message storage plugin for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/).
This plugin can save form data to Claris FileMaker Server.

= What is Claris FileMaker? =

Claris FileMaker is an application development platform to build custom apps that solve your business problems, 
provided by Claris International Inc.

* Information in Japanese : [https://www.claris.com/ja/filemaker/](https://www.claris.com/ja/filemaker/)
* Information in English: [https://www.claris.com/filemaker/](https://www.claris.com/filemaker/)

== Requirements ==

To run FMPress Forms we recommend your host supports:
- Contact Form 7 version 5.5 or greater.
- PHP version 7.4 or greater.
- Claris FileMaker Server version 19.1 or greater.
- HTTPS support

== Installation ==

1. Upload the entire `fmpress-forms` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

After activation of the plugin, you'll find *FMPress* on the WordPress admin screen menu, 
and *FMPress* tab appears on the "Contact Form 7" setting screen.

== Frequently Asked Questions ==

= How to activate FMPress Forms plugin? =

After activating the plugin, you will see a note at the top of the Administration Screen asking you to add two constants, copy the values and paste the copied content into wp-config.php. Reactivate the plugin after editing wp-config.php.

Example:
`
define( 'FMPRESS_CONNECT_ENCRYPT_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
define( 'FMPRESS_CONNECT_ENCRYPT_IV', 'xxxxxxxxxxxxxxxxxxxxxxxx' );`

= How to assign fields in the *FMPress* tab panel? =

You must add *fm_field-* as a prefix to the beginning of the form-tag name in the *Form* tab panel. (e.g. fm_field-company_name)

== Screenshots ==

1. Edit Datasource on the WordPress admin screen
2. "FMPress" tab on the Contact Form 7's Admin Screen

== Changelog ==

= 1.2.0 =
(In development)

* Add support for executing a FileMaker script
* Add support for [_remote_ip] and [_user_agent] of special mail-tags
* Add compatibility with FMPress CloudAuth
* Fix compatibility with Contact Form 7 5.6 in some cases
* Fix saving an encrypted password when the value of FMPRESS_CONNECT_ENCRYPT_KEY or FMPRESS_CONNECT_ENCRYPT_IV is invalid

= 1.1.0 =
Release Date: May 26, 2022

* Add support for uploading a file into a container field
* Fix loading translation files
* Suppress warning messages when Contact Form 7 is disabled

= 1.0.2 =
Release Date: February 21, 2022

* Support demo_mode and skip_mail for Additional Settings of Contact Form 7
* Rename translation files to modify the text domain
* Improve compatibility with FMPress Pro

= 1.0.1 =
Release Date: December 6, 2021

* Support Contact Form 7 5.5.3

= 1.0.0 =
* First version (Unreleased)
