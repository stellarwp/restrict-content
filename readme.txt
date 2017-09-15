=== Restrict Content ===
Author URI: http://pippinsplugins.com
Author: Pippin Williamson
Contributors: mordauk, mindctrl, nosegraze
Donate link: http://pippinsplugins.com/support-the-site
Tags: Restrict content, member, members, membership, memberships, member only, registered, registration form, login form, logged in, restricted access, restrict access, limiit access, read-only, read only, access control
Requires at least: 3.9
Tested up to: 4.5
Stable tag: 2.1.3

Restrict Content to registered users only. This is a simple membership plugin that will allow you to easily restrict complete posts / pages to logged in users only. Levels of restriction may also be set. For example, you can restrict content to only Administrators, Editors, Authors, Contributors, and Subscribers.

== Description ==

Restrict Content to registered users only. This is a simple membership plugin that will allow you to easily restrict complete posts / pages to logged in users only. Levels of restriction may also be set.
For example, you can restrict content to only Administrators, Editors, Authors, Contributors, and Subscribers.

Content restriction works both with shortcodes and drop-down menu of access levels for post, pages, and most custom post types.

Also includes frontend forms for user registration, login, and password reset so your members can do all these actions on the front end of your site without going to the wp-admin or wp-login.php pages.

= Pro version available! =

The [Pro version of Restrict Content](https://restrictcontentpro.com/?utm_campaign=restrict-content&utm_medium=readme&utm_source=description&utm_content=first-mention) provides a significant additional feature set, including:

* Payments - including one-time payments and recurring subscriptions.
* Integration with popular payment systems, including Stripe, PayPal Standard, PayPal Express, PayPal Pro, Authorize.net, 2Checkout, and Braintree.
* Discount codes
* Printable HTML invoices
* Complete member management
* Prevent account sharing
* WooCommerce integration
* And much more. See the [Features](https://restrictcontentpro.com/tour/features/?utm_campaign=restrict-content&utm_medium=readme&utm_source=description&utm_content=features-link) page for additional details.

== Installation ==

1. Upload restrict-content to wp-content/plugins
2. Click "Activate" in the WordPress plugins menu
3. Go to Settings > Restrict Content and customize the Message settings
4. Follow instructions below to restrict content

To restrict an entire post or page, simply select the user level you’d like to restrict the post or page to from the drop down menu added just below the post/page editor.

To restrict just a section of content within a post or page, you may use shortcodes like this:

[restrict userlevel="editor"] . . . your restricted content goes here . . . [/restrict]

Accepted userlevel values are:
 * admin
 * editor
 * author
 * subscriber
 * contributor

There is also a short code for showing content only to users that are not logged in.

[not_logged_in]This content is only shown to non-logged-in users.[/not_logged_in]

== Frequently Asked Questions ==

= Does this plugin include frontend log in or registration forms? =

Yes! Frontend forms are provided for user registration, login, and password reset.

= Does this plugin support custom user roles? =

No. If you need custom role support, check out [Restrict Content Pro](https://restrictcontentpro.com/?utm_campaign=restrict-content&utm_medium=readme&utm_source=faq&utm_content=custom-roles)

== Screenshots ==

Go to the demo page to see examples:

http://pippinsplugins.com/restricted-content-plugin-free/

== Changelog ==

= 2.2 =
* New: Login form shortcode - [login_form]
* New: Password reset form - part of the [login_form] shortcode
* New: User registration form - [register_form] shortcode
* New: Improved compatibility with Restrict Content Pro, allowing for seamless upgrades
* Fix: Undefined index PHP notice
* Tweak: General code cleanup and improvements

= 2.1.3 =

* Fix: Undefined nonce index when saving some post types
* Fix: Removed restrict metabox from post types that it does not apply to
* Fix: Made restricted message shown in feeds translatable
* Fix: Some text strings not translatable
* Tweak: Added new rcp_metabox_excluded_post_types filter

= 2.1.2 =

* Removed incorrect contextual help tab

= 2.1.1 =

* Some general code cleanup and compatibility checks for WordPress 4.1+

= 2.1 =

* Improved settings page to match core WordPress UI
* Fixed problem with unescaped HTML in restricted messages options
* Added complete internationalization on default language files

= 2.0.4 =

* Added do_shortcode() to the not logged in short code

= 2.0.3 =

* Fixed a problem with the not logged in short code.

= 2.0.2 =

* Added new [not_logged_in] short code.

= 2.0 =

* Added settings page with options to configure each of the messages displayed to users who do not have permission to view a page.
* Improved the performance of several functions.
* Better organization of the plugin files and improved infrastructure for soon-to-come new features.

== Upgrade Notice ==

= 2.1 =

* Improved settings page to match core WordPress UI
* Fixed problem with unescaped HTML in restricted messages options
* Added complete internationalization on default language files

= 2.0.4 =

* Added do_shortcode() to the not logged in short code

= 2.0.3 =

* Fixed a problem with the not logged in short code.

= 2.0.2 =

* Added new [not_logged_in] short code.

= 2.0 =

Added message configuration, custom post type support, and improved plugin infrastructure.
