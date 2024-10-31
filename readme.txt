=== Scaleable Contact Form ===
Contributors: ukautz
Donate link:
Tags: contact, form, mail, captcha
Requires at least: 2.7.0
Tested up to: 3.0.1
Stable tag: 0.8.1

Another contact form with very scalable multi-type Fields. Uses Captcha, no Akismet. Can use external SMTP via wp_mail() or other Plugins. AJAX Support. Confirmation mail optional.

== Description ==

Scaleable Contact Form Plugin

= A Plugin for .. =

.. a very customizable contact form on your wordpress blog.

= Features: =

* Uses Captcha but no Akismet (yet).
* Required, non required Fields can be configured.
* Five different Field Types: Textfield, Textarea, Select, Radios and Checkboxes.
* All labels and buttons can be modified.
* You can choose either a regular send formular or AJAX driven
* Optional confirmation mail to ser

= Usage: =

Either put `[scaleable-contact-form]` somewhere in a post or site or call `echo scf_print_contact_form()` directly from a template.

== Configuration ==

In the WP Admin interface, you'll find a submenu entry "S.C.Form" under "Settings". There you can add new fields, edit labels, error messages, behavior and so on..

= Usage for AJAX Form =

Either put `[scaleable-contact-form-ajax]` somewhere in a post or site or call `echo scf_print_contact_form_ajax()` directly from a template.

== Installation ==

1. Use the WP Pluginstaller OR download .zip file and extract into your "wp-content/plugins" directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Either put [scaleable-contact-form] somewhere in a post or page (in the text-editor, it will be replaced by the generated contact form) or call `scf_print_contact_form()` directly from a template.


== Frequently Asked Questions ==

= Does it work with WP Super Cache (or other caches for that matter) =

No, but you can simply add the URL of the page where you use the form to the ignore list (at least with WP Super Cache, tested with version 0.9.9.6):
1. Go to WP Super Cache configurations in WP Admin.
2. Go to the advanced tab.
3. Scroll down to where it says "Add here strings (not a filename) that forces a page not to be cached. [...]"
4. Add the path of your page/site.. eg if it is in http://yourdomain.tld/contact/ then "/contact/.*" will fit).

Example:
I use the SCForm here: http://blog.foaa.de/about/, so i added "/about/.*" to the list, cleared the cache once and it worked like a charm.

= How many fields i can use in my contact form ? =

As much as you want and common sense dictates .. surely i didnt test with more then a "reasonable" amount..

= How can i use SMTP for sending the contact form mails ? =

This plugin uses `wp_mail()` instead of php's `mail()` so you need (another) plugin which modifies this method. I used WP-Mail-SMTP ( http://www.callum-macdonald.com/code/wp-mail-smtp/ )

== Change Notes ==

= 0.8.2, 2011-05-03 =

* Fix: Session unset after comment

= 0.8.1, 2010-10-25 =

* Urgent fix: removed debugging "display_errors"

= 0.8.0, 2010-10-25 =

* Adjusting PHP code to be compatible with php 6.
* Message is now optional.
* Added new error, which will be displayed if the user hit's (accidentally? spam?) reload (could not use redirect due to session_start for the captcha..).
* New optional customizable confirmation mail to user.
* Fixed non removal of the used captcha code.

= 0.7.1, 2010-01-24 =

* Fix for 2.9.1

= 0.7, 2009-09-23 =

* fixed the missing closing div

= 0.6, 2009-09-23 =

* .. and replaced bloginfo with get_bloginfo to surpress the site title

= 0.5, 2009-09-23 =

* Replaced lot of the patches relating captcha with old working version

= 0.4, 2009-09-16 =

* Patch from Jonathan Rogers: Assure JQuery is included, differentiate between checkboxes and radios better, using fieldset for checkboxes and radios
* remove the "formular always displayed on top of page" bug (the [scaleable-contact-form] tag could be placed now anywhere)

= 0.3, 2009-07-08 =

* fix for PHP4 (still?)

= 0.2, 2009-04-10 =

* minor security issue
* better Simple CAPTCHA recognition
* integration of ajax formular

