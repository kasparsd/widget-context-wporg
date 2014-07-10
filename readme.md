# Widget Context

Contributors: kasparsd, jamescollins    
Tags: widget, widget context, context, logic, widget logic, cms   
Requires at least: 3.0   
Tested up to: 3.9.1   
Stable tag: 0.8.3   
License: GPLv2 or later   

Show or hide widgets on specific posts, pages or sections of your site.


## Description

Widget Context allows you to show or hide widgets on certain sections of your site — front page, posts, pages, archives, search, etc. It also features section targeting by URLs (with wildcard support) for maximum flexibility.


### Get Involved:

* News and updates on [kaspars.net](http://kaspars.net/blog),
* Development and pull requests [on GitHub](https://github.com/kasparsd/widget-context-wporg),
* Bug reports and suggestions on [WordPress.org forums](http://wordpress.org/support/plugin/widget-context).


## Installation

* Install the plugin through **Add New Plugin** feature in your WordPress dashboard -- search for Widget Context.
* Widget Context settings will appear automatically under each widget under Design > Widgets.


## Changelog

### 1.0-alpha
* Refactor code to allow custom widget context modules.

### 0.8.3

* Fix PHP warning that occurred on PHP 5.2.x.

### 0.8.2

* Improved SSL/HTTPS detection.
* Fix: Ensure that is_active_sidebar() & is_dynamic_sidebar() don't return true when there are no widgets displayed on a page.
* Two new filters so that other plugins can override widget context display/visibility logic.

### 0.8.1

* Revert back to changing callback function in `$wp_registered_widgets` for attaching widget context setting controls.
* Fix the word count logic.

### 0.8

* Major code rewrite and refactoring to improve performance and usability.
* Fix bugs with URL targeting and empty lines in the "Target by URL" textarea.

### 0.7.2

* Fix PHP warnings/notices. Props to [James Collins](http://om4.com.au/).

### 0.7.1

* Confirm that the plugin works with the latest version of WP.

### 0.7

* Bug fix: check for active sidebars only after $paged has been set.

### 0.6

* Don't check for used sidebars on each widget load. Allow absolute URLs in the URL check.

### 0.5

* Added distinction between is_front_page() and is_home(). Remove widgets from wp_get_sidebars_widgets() if they are not being displayed -- this way you can check if a particular sidebar is empty.

### 0.4.5

* Widget output callback couldn't determine the widget_id.

### 0.4.4

* Fixed widget control parameter transfer for widgets that don't use the new widget api.

### 0.4.2

* Initial release on Plugin repository.


## Upgrade Notice

### 0.8.1

(1) Revert to a legacy method for attaching widget control settings in order to make it work with old plugins. (2) Fix the word count context logic.

### 0.8

Major code rewrite and refactoring to improve plugin performance and usability.

### 0.7.2

Fix PHP warnings/notices.

### 0.7.1

Confirm that plugin works with the latest version of WordPress.

### 0.7

Bug fix: check for active sidebars only after $paged has been set.

### 0.6

Performance improvements - don't check if sidebar has any widgets on every widget load.


## Screenshots

1. Widget Context settings at the bottom of every widget

