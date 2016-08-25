=== SG CachePress ===
Contributors: Hristo Sg, danielkanchev
Tags: nginx, caching, speed, memcache, memcached, performance, siteground, nginx, supercacher
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The SG CachePress is a plugin that allows you to use the SiteGround dynamic cache and Memcached to optimize the performance of your WordPress.

== Description ==

The main functionality of SG CachePress is to purge your dynamic cache whenever your content updates. For example, when you create a new post, someone comments your articles, etc. In addition to that, if you have a working Memcached service on your server, the plugin will allow you to easily configure and enable WordPress to use it.

= Requirements =

In order to work correctly, this plugin requires that your server meets the following criteria:

* Your SiteGround SuperCacher enabled
* If you're not hosted with SiteGround this plugin may not work because it relies on a specific server configuration

== Installation ==

= Automatic Installation =

1. Go to Plugins -> Add New
1. Search for "SG CachePress"
1. Click on the Install button under the SG CachePress plugin
1. Once the plugin is installed, click on the Activate plugin link

= Manual Installation =

1. Login to the WordPress admin panel and go to Plugins -> Add New
1. Select the 'Upload' menu 
1. Click the 'Choose File' button and point your browser to the SGCachePress.zip file you've downloaded
1. Click the 'Install Now' button
1. Go to Plugins -> Installed Plugins and click the 'Activate' link under the WordPress SG CachePress listing


== Configuration ==

= Dynamic Cache Settings =

* Dynamic Cache ON/OFF - enable or disable the SiteGround Dynamic caching system
* AutoFlush Cache ON/OFF - automatically flush the Dynamic cache when you edit your content
* Purge Cache - Manually purge all cached data from the dynamic cache

= Exclude URLs From Dynamic Caching = 

This field allows you to exclude URLs from the cache. This means that if you need certain parts of your site to be completely dynamic, you need to add them into this list. Type in the last part of the URL that you want to be excluded. For example, if you type in 'url', then '/path/to/url/' will be excluded but '/path/to/' and '/path/to/url/else/' won't.
		
= Memcached Settings =
* Enable Memcached - Store in the server's memory (using Memcached) frequently executed queries to the database for a faster access on a later use.

== Changelog ==

= Version 2.3.9 =
* Improved WordPress 4.6 compatibilitty

= Version 2.3.8 =
* Improved compatibility with SiteGround Staging System

= Version 2.3.7 =
* Fixed PHP warnings in Object Cache classes

= Version 2.3.6 =
* Minor URL handling bug fixes

= Version 2.3.5 =
* Improved cache testing URL detection

= Version 2.3.4 =
* CSS Bug fixes

= Version 2.3.3 =
* Improved Memcache work
* Interface improvements
* Bug fixes

= Version 2.3.2 =
* Fixed bug with Memcached cache purge

= Version 2.3.1 =
* Interface improventes
* Internationalization support added
* Spanish translation added by <a href="https://www.siteground.es">SiteGround.es</a>
* Bulgarian translation added

= Version 2.3.0 =
* Memcached support added
* Better PHP7 compatibility

= Version 2.2.11 =
* Improved compatibility with WP Rocket
* Bug fixes

= Version 2.2.10 =
* Revamped notices work
* Bug fixes

= Version 2.2.9 =
* Bug fixes

= Version 2.2.8 =
* Bug fixing and improved notification behaviour
* Fixed issues with MS installations

= Version 2.2.7 =
* Added testing box and notification if Dynamic Cache is not enabled in cPanel

= Version 2.2.6 =
* Fixed bug with Memcached causing issues after WP Database update

= Version 2.2.5 =
* Minor system improvements

= Version 2.2.4 =
* Minor system improvements

= Version 2.2.3 =
* Admin bar link visible only for admin users

= Version 2.2.2 =
* Minor bug fixes

= Version 2.2.1 =
* Added Purge SG Cache button
* Redesigned mobile-friendly interface

= Version 2.2.0 =
* Added NGINX support

= Version 2.1.7 =
* Fixed plugin activation bug

= Version 2.1.6 =
* The purge button will now clear the Static cache even if Dynamic cache is not enabled
* Better and more clear button labeling

= Version 2.1.5 =
* Better plugin activation and added to the wordpress.org repo

= Version 2.1.2 =
* Fixed bug that prevents you from enabling Memcached if using a wildcard SSL Certificate

= Version 2.1.1 =
* Cache will flush when scheduled posts become live

= Version 2.1.0 =
* Cache will be purged if WordPress autoupdates

= Version 2.0.3 =
* Minor bug fixes

= Version 2.0.2 =
* 3.8 support added

= Version 2.0.1 =
* Interface improvements
* Minor bug fixes

= Version 2.0 =
* New interface
* Minor bug fixes
* Settings and Purge pages combined into one

= Version 1.2.3 =
* Minor bug fixes
* SiteGround Memcached support added
* URL Exclude from caching list added

= 1.0 =
* Plugin created.
