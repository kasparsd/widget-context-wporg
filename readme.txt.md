# Widget Context

Contributors: kasparsd, jamescollins  
Tags: widget, widgets, widget context, context, logic, widget logic, visibility, widget visibility  
Requires at least: 3.0  
Tested up to: 5.4  
Stable tag: {{ version }}  
License: GPLv2 or later  
Requires PHP: 5.6  
Donate link: https://widgetcontext.com/pro

Show and hide widgets on specific posts, pages and sections of your site.


## Description

Use [Widget Context](https://widgetcontext.com) to show and hide widgets on certain sections of your site -- front page, posts, pages, archives, search, etc. Use targeting by URLs (with wildcard support) for maximum flexibility.

https://www.youtube.com/watch?v=rEHvqsWoXAE


### Premium Support

Subscribe to our [Premium Support service](https://widgetcontext.com/pro) and get the PRO 🚀 version of the plugin for free when it’s launched! Your support enables consistent maintenance and new feature development, and is greatly appreciated.


### Contribute

- Suggest code improvements [on GitHub](https://github.com/kasparsd/widget-context-wporg).
- Report bugs and suggestions on [WordPress.org forums](http://wordpress.org/support/plugin/widget-context).
- [Help translate](https://translate.wordpress.org/projects/wp-plugins/widget-context) to your language.


### Documentation

Widget visibility can be configured under individual widget settings under "Appearance → Widgets" in your WordPress administration area or through the widget editing interface in the Customizer.

#### Target by URL

The "Target by URL" is a powerful feature for targeting sections of your website based on the request URLs. It was inspired by a similar feature in the [Drupal CMS](https://www.drupal.org).

Use relative URLs such as `page/sub-page` instead of absolute URLs `https://example.com/page/sub-page` because relative URLs are more flexible and make the logic portable between different domains and server environments.

##### Wildcards

Use the wildcard symbol `*` for matching dynamic parts of the URL. For example:

- `topic/widgets/*` to match all posts in the widgets category, if your permalink structure is set to `/topic/%category%/%postname%`.

- `page-slug/*` to match all child pages of the page-slug parent page.

- Use a trailing `?*` to capture URL with all query arguments such as `utm_source`, etc. For example, for every `blog/post-slug` also include `blog/post-slug?*`.

#### Exclude by URL

Specify URLs to ignore even if they're matched by any of the other context rules. For example, enter `example/sub-page` to hide a widget on this page even when "All Posts" is selected under "Global Sections".


## Installation

- Search for **Widget Context** under "Plugins → Add New" in your WordPress dashboard.
- Widget Context settings will appear automatically under **each widget** under "Appearance → Widgets".
- Visit "Settings → Widget Context" to configure the available widget visibility contexts.


## Changelog

### 1.3.2 (April 27, 2020)

- Bugfix: Fix the Widget Context settings link in the widget controls after moving the settings under the "Appearance" menu for usability (closer to the widget settings).
- Feature: Add a link to the plugin settings in the plugin admin list, too.


### 1.3.1 (April 24, 2020)

- Bugfix: better support for URL rules with query parameters.

### 1.3.0 (April 23, 2020)

- Introduce the long-awaited "Exclude by URL" feature to prevent certain URLs from showing or hiding a widget when it's matched by any other visibility rule.
- Introduce [premium support](https://widgetcontext.com/pro) to help maintain the plugin. Subscribe now to get the PRO version of the Widget Context for free when it's launched!

### 1.2.0 (August 20, 2019)

- Set PHP 5.6 as the minimum supported version of PHP to match WordPress core.
- Developer tooling update: introduce PHP autoloading, PHP unit tests with proper mocking, linting for JS, switch to Docker inside a Vagrant wrapper for local development environment and update to the latest version of WordPress coding standards (see [#50](https://github.com/kasparsd/widget-context-wporg/pull/50)).

### 1.1.1 (June 9, 2019)

- Mark as tested with WordPress 5.2.
- Add test coverage reporting and remove [Debug Bar](https://wordpress.org/plugins/debug-bar/) integration since it wasn't complete. Refactor plugin structure to support dependency integration. See [#47](https://github.com/kasparsd/widget-context-wporg/pull/47).
- Added local development environment, see [#48](https://github.com/kasparsd/widget-context-wporg/pull/48).

### 1.1.0 (June 13, 2018)
- Fix URL matching for URLs with query strings.
- Introduce unit tests for the URL context.

### 1.0.7 (June 5, 2018)
- Mark as tested with WordPress 4.9.6.
- Use the localisation service provided by [WP.org](https://translate.wordpress.org/projects/wp-plugins/widget-context).
- Support for Composer.

### 1.0.6 (January 20, 2018)
- Fix path to admin scripts and styles, props @tedgeving.
- Mark as tested with WordPress 4.9.2.

### 1.0.5 (May 8, 2017)
- Confirm the plugin works with the latest version of WordPress.
- Add support for continuous testing via [wp-dev-lib](https://github.com/xwp/wp-dev-lib).

### 1.0.4 (May 6, 2016)
- Confirm the plugin works with the latest version of WordPress.
- Fix the PHP class constructor warning.
- Move the widget context settings link.
- Fix the initial context state in the customizer.

### 1.0.3
- Include Russian translation (Thanks Flector!).
- Add textdomain to the remaining strings.
- Enable debugging if [Debug Bar](https://wordpress.org/plugins/debug-bar/) is available.

### 1.0.2
- Load available custom post types and taxonomies right before visibility checks to avoid PHP warnings.
- Run visibility checks only after the main post query has run. Fixes issues with WooCommerce.
- Load our CSS and Javascript files only on widget and customizer admin pages.

### 1.0.1
- Fix PHP warning in custom post type and taxonomy module.

### 1.0
- Public release of the 1.0 refactoring.

### 1.0-beta
- Improved settings page.

### 1.0-alpha
- Refactor code to allow custom widget context modules.

### 0.8.3

- Fix PHP warning that occurred on PHP 5.2.x.

### 0.8.2

- Improved SSL/HTTPS detection.
- Fix: Ensure that is_active_sidebar() & is_dynamic_sidebar() don't return true when there are no widgets displayed on a page.
- Two new filters so that other plugins can override widget context display/visibility logic.

### 0.8.1

- Revert back to changing callback function in `$wp_registered_widgets` for attaching widget context setting controls.
- Fix the word count logic.

### 0.8

- Major code rewrite and refactoring to improve performance and usability.
- Fix bugs with URL targeting and empty lines in the "Target by URL" textarea.

### 0.7.2

- Fix PHP warnings/notices. Props to [James Collins](http://om4.com.au/).

### 0.7.1

- Confirm that the plugin works with the latest version of WP.

### 0.7

- Bug fix: check for active sidebars only after $paged has been set.

### 0.6

- Don't check for used sidebars on each widget load. Allow absolute URLs in the URL check.

### 0.5

- Added distinction between is_front_page() and is_home(). Remove widgets from wp_get_sidebars_widgets() if they are not being displayed -- this way you can check if a particular sidebar is empty.

### 0.4.5

- Widget output callback couldn't determine the widget_id.

### 0.4.4

- Fixed widget control parameter transfer for widgets that don't use the new widget api.

### 0.4.2

- Initial release on Plugin repository.


## Upgrade Notice

### 1.2.0

PHP 5.6 is now the minimum supported version of PHP. Also included is developer tooling update and improved PHP unit tests.


## Screenshots

1. Widget Context settings at the bottom of every widget
2. Widget Context plugin settings
