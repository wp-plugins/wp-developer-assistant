=== WP Developer Assistant ===
Contributors: chrisjean, paultgoodchild
Tags: developer, development, debug
Requires at least: 2.2
Tested up to: 4.3
Stable tag: 1.0.3

A plugin by a WordPress developer for WordPress developers.

== Description ==

__WP Developer Assistant__ is a WordPress plugin developed by a WordPress developer for WordPress developers.

* Have you ever needed to run a query when you didn't have access to phpMyAdmin or SSH?
* Don't you hate it when you need to upload a plugin, theme, or other file and don't have FTP access?
* Have you ever wondered where that action or filter hook gets called?
* Want to enable errors while hiding them from everyone else?
* Wouldn't it be great if you could output a full listing of PHP global variable values on each page so debugging would be easier?
* Ever wanted to modify one of those serialized options?
* Would you like to quickly see a full list of defined constants?

It's thoughts like these that caused me to make this plugin. WP Developer Assistant is the first WordPress plugin of its kind. It essentially is a toolkit that makes life as a WordPress developer easier.

= Features =

* Customizable enabling of PHP errors that only show for your user.
* Display values of PHP's built-in global variables (_POST, _REQUEST, _FILE, _ENV, etc) on each page.
* Easily modify Options table values, including serialized data.
* View a full list of all the add_action, do_action, add_filter, and apply_filters function calls complete with information on function names, priorities, number of accepted arguments, source file name, and file line number.
* Quickly execute queries with the Run Query tool.
* Show phpinfo().
* View a comprehensive list of all the defined named constants, their current value, the declared value, the source file name of the definition, and the file line number of the definition.
* Quickly and easily upload files to any place inside your WordPress installation. The uploader will even automatically extract archives to the destination directory.

There are many more features planned for this plugin. For more information about this plugin and its development, visit the [WP Developer Assistant Home Page](http://blog.realthemes.com/wp-developer-assistant/ "wp developer assistant home page").

== Installation ==

1. Download and unzip the latest release zip file
2. Upload the entire wp-developer-assistant directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Click on the __Developer__ link in the main administration navigation to access the tools

== Screenshots ==

1. The main Developer page with options to enable errors and variable output. These settings are set per-user, so no one else will see your debug data cluttering up their screen.
2. The Options page for easy modification of entries in the options table, inlcluding serialized data. Ability to add and remove options will be added soon.
3. The Hooks page displays detailed information about add_action, do_action, add_filter, and apply_filters function calls and where they are located in the source files.
4. Run Query is a simple tool that lets you run arbitrary queries on the database. Very useful for development, testing, and repairs.
5. The PHP Info page simply shows the output from phpinfo(). It may not be fancy, but it can be extremely useful.
6. The Defines page shows detailed information about defined named constants. Can't remember what that constant was called that holds the location to the plugins directory? This should help you remember.
7. The Upload Files page is an invaluable tool for quickly and easily uploading any type of file (including archives) to any location inside WordPress. You can even upload from a remote URL.

== Requirements ==

* PHP 4+
* WordPress 2.2+

== Version History ==

* 1.0.1 - 2008-06-26 - Initial release version
* 1.0.2 - 2008-06-26 - Slight modification that required a new version
* 1.0.3 - 2008-07-01 - Added support for PHP 4
* 1.0.4 - 2015-08-12 - Removed sources of PHP Warnings and replaced uses of deprecated WordPress functions with newer equivalents

== More Information ==

For more information about this plugin and its development, visit the [WP Developer Assistant Home Page](http://blog.realthemes.com/wp-developer-assistant/ "wp developer assistant home page").

