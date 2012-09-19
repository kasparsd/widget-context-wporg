=== Widget Context ===
Contributors: kasparsd
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Widget%20Context%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: widget, widget context, context, logic, widget logic, cms
Requires at least: 2.8
Tested up to: 3.4.2
Stable tag: 0.7.1

Show widgets in context - only on certain posts, front page, category or tag pages etc.

== Description ==

Widget Context allows you to specify widget visibility settings.

For news and updates regarding this plugin, check http://konstruktors.com/blog/


== Installation ==

* Install the plugin through **Add New Plugin** feature in your WordPress dashboard -- search for Widget Context.
* Widget Context settings will appear automatically under each widget in Design > Widgets.


== Changelog ==

*	**0.7.1** - Confirm that the plugin works with the latest version of WP.
*	**0.7** - Bug fix: check for active sidebars only after $paged has been set.
*	**0.6** - Don't check for used sidebars on each widget load. Allow absolute URLs in the URL check.
*	**0.5** - Added distinction between is_front_page() and is_home(). Remove widgets from wp_get_sidebars_widgets() if they are not being displayed -- this way you can check if a particular sidebar is empty.
*	**0.4.5** - Widget output callback couldn't determine the widget_id.
*	**0.4.4** - Fixed widget control parameter transfer for widgets that don't use the new widget api.
*	**0.4.2** - Initial release on Plugin repository.


== Upgrade Notice ==

= 0.7.1 =
Confirm that plugin works with the latest version of WordPress.

= 0.7 =
Bug fix: check for active sidebars only after $paged has been set.

= 0.6 =
Performance improvements - don't check if sidebar has any widgets on every widget load.


== Screenshots == 

1. Widget Context settings added at the end of every widget settings