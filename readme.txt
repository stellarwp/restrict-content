=== Restrict Content ===
Author URI: https://restrictcontentpro.com
Author: Pippin Williamson
Contributors: mordauk, mindctrl, nosegraze
Tags: Restrict content, member, members, membership, memberships, member only, registration form, login form, restricted access, limit access, read only, access control
Requires at least: 3.9
Tested up to: 4.9.2
Stable tag: 2.2.2

Run your own membership website using the Restrict Content membership plugin for WordPress.

== Description ==

Restrict Content is a simple membership plugin that enables you to easily restrict access to your content to logged-in users only.

Content restriction works both with partial content restrictions using shortcodes, as well as full page restrictions for post, pages, and most custom post types.

Restrict Content also includes frontend forms for user registration, login, and password reset so your members can do all these actions on the front end of your site without going to the wp-admin or wp-login.php pages. Display these with the [login_form] and [register_form] shortcodes.

= Features of the plugin =

* Limit access to content with a shortcode, i.e. `[restrict]This content is limited to logged in users.[/restrict]`.
* Limit access to full content via a simple interface on the edit post, page, and custom post type screens.
* Display a registration form for new accounts on any page of your website with `[register_form]`.
* Display a log in form for existing users on any page of your website with `[login_form]`.

A [Pro version of Restrict Content](https://restrictcontentpro.com/?utm_campaign=restrict-content&utm_medium=readme&utm_source=description&utm_content=first-mention) is also available with an extensive feature set.

= Restrict Content Pro features =

* Payments - including one-time payments and recurring subscriptions.
* Integration with popular payment systems, including Stripe, PayPal Standard, PayPal Express, PayPal Pro, Authorize.net, 2Checkout, and Braintree.
* Discount codes
* Printable HTML invoices
* Complete member management
* Prevent account sharing
* WooCommerce integration
* And much more. See the [Features](https://restrictcontentpro.com/tour/features/?utm_campaign=restrict-content&utm_medium=readme&utm_source=description&utm_content=features-link) page for additional details.

Visit the Restrict Content Pro website for more information about the Pro version.

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

= 2.2.2 =
* Fix: Content visibility in the REST API.

= 2.2.1 =
* Tweak: Remove hard-coded red color from restricted message. A class `rc-restricted-content-message` has been added to the span tag if you'd like to add the color back in with CSS.
* Tweak: The Restrict Content plugin is now auto deactivated when Restrict Content Pro is activated.

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

= 2.2.1 =

Removed hard-coded red color from restriction message and deactivate plugin when pro version is activated.

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
