=== Plugin Name ===
Contributors: jimisaacs
Author URI: http://jidd.jimisaacs.com/
Donate link: http://jidd.jimisaacs.com/donate
Plugin URI: http://jidd.jimisaacs.com/archives/863
Tags: widgets, patterns, management, manager, mvc, model, view, controller
Requires at least: 2.8
Tested up to: 3.0-alpha
Stable tag: trunk

Includes extremely useful widgets for developers and users alike, and is a system written on a PHP 5 object oriented structure.

== Description ==

In addition to adding numerous extremely useful widgets for developers and users alike, this plugin is a system written on a PHP 5 object oriented structure. In short, it is built for modification and extension. It wraps the WordPress Widget API to allow for an alternative, and in my opinion more robust method to hook into and use it. Widgets are WordPress's version of user interface modules. They already support an administrative and client-side view. This system simply leverages that with a higher potential in mind.

This plugin started as a collection of widgets that I developed over time and used in numerous projects. I eventually merged them into one conglomerate which is now known as the 'Extensible Widgets' plugin. Currently I do not have extensive documentation on the plugin functionality and code-base yet.

A Quick summary is that this plugin in its current state is a PHP widget class manager, as well as a collection of useful widget classes that build on each other for extended functionality. When the plugin is first activated, the widget classes included are not registered automatically, and will not appear within your WordPress widgets administration page. To activate your desired widgets you must go to the 'Extensible Widgets' Registration page. From there you can read a short description before registering anything.

This plugin also comes with an Export and Import page. You will notice this functionality is very useful in backing up your current data of all your widgets and 'Extensible Widgets' settings. I felt this was a major necessity since while using 'Extensible Widgets' these little pieces of data suddenly turn into major aspects in the whole of your website. I needed to give an acceptable method of retrieving, backing up, and restoring that data if something bad happens.

A quick summary of the most useful of the widgets included:

* Widget Group: Use this widget to create a new widget group, as a widget? Yes... this is where it gets interesting.
* Query Posts: A Widget than can create and use a sub-query or use the current global query and output the results in a view template.
* Context: More basic options that would be good for any widget, but this widget is specifically used for controlling where widgets appear.

== Installation ==

1. Upload the `extensible-widgets` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Extensible Widgets' administration page to configure settings and to register desired widgets

== Frequently Asked Questions ==

= Why PHP 5? =

This is what the codebase is written for, it is not going to change. Sorry, welcome to the future.

= What exactly is an extensible widget'? =

It is a PHP widget class built within the parameters of the 'Extensible Widgets' framework for widgets. Any extensible widget can be extended, but retain the functionality of all the parent classes that it may extend. 'Extensible Widgets' also provides administration to allow for users to control this functionality within the UI of that widget.

= What is widget registration? =

The term 'registered widget' is not necessarily what is added to your widgetized areas. Instead this is like the blue-print for those instances. When you register a widget, what you are doing is allowing for instances of that widget to be created and added to your areas from that registered blue-print. Beware! When you unregister a widget, all instances that were added across all widgetized areas automatically disappear. You can get them back, but I don't suggest unregistering something unless you are really sure you want to.

= Do the widgets depend on each other, do I need to activate one with another? =

Yes, and no. They do depend on each other, but this is on the PHP side of things, and in no way important to the registration of the widgets in the administration pages. Basically you can register the 'Widget Group' widget, without having to register the 'Widget Base' widget, or any other of it's PHP dependencies.

= Is 'Extensible Widgets' right for me? =

Honestly this is a pretty advanced plugin that I would say is mostly for developers. Though I am trying to make it easier for developers to control the capabilities of the widget included with 'Extensible Widgets' to make it less developer centric on a per user basis (role/capability level).

= Is 'Extensible Widgets' right for my site? =

Numerous times I need a way to put the same content in different places around my site. This content is usually represented as posts, pages, or basically anything. I developed this plugin as the answer to this problem, using the already built WordPress Widgets framework. Widgets made sense to use because of their very nature. If you are looking to answer a lot of these same problems I was having, then this plugin will serve you well.

= What about the data? =

Once you start using this plugin, you will notice that widgets will become a major part in the whole of your website. Because of this fact, I included Import/Export functionality for the settings of 'Extensible Widgets', and all of your widget data from WordPress itself.

== Screenshots ==

Please view this post for [screenshots](http://jidd.jimisaacs.com/archives/863/ "Screenshots") of this plugin's administration.

== Changelog ==

= 0.9.4 =
* Fixed major reference errors affecting more recent minor versions of PHP 5
* Added autoload initiation for entire plugin library
* Fixed various errors admin markup
* Added date to export filename

= 0.9.3 =
* Added autoload initiation for core php framework for portability
* Fixed a bug when editing Widget Group defaults
* Removed one major E_SCRICT notice occurring (yes, I can be anal...)
* Fixed some grammatical errors in notices here and there
* Further testing in WordPress 3.0 (Note: The new default widget works great in a Widget Group)

= 0.9.2.3 =
* Very minor fixes with references in core php framework
* Tested extensively in WordPress 3.0 using new default theme TwentyTen

= 0.9.2.2 =
* Fixed a reference warning with admin controllers
* Fixed minor Javascript error failing in older browsers

= 0.9.2.1 =
* Cleaned up some notice errors regarding references within core files.

= 0.9.2 =
* Slight bug fixes in widget registration
* Some framework code cleaning

= 0.9.1 =
* Too many changes in this version to list, all are transparent to the functionality and interface
* Cleaned up the overall framework to better support the possibility of multiple plugins
* Changed admin classes into admin controller classes
* Cleaned up the capability functionality to use ONLY the WordPress API underneath
* Added platform checking for the export/import formats
* Hooked the import and export pages together underneath to use these platform check throughout the entire plugin
* Noticed a random bug with formatting xml on export, still works fine 99% of the time, a fix in in-progress
* Fixed many grammar and spelling errors in my text (sorry)

= 0.9 =
* Added the ability to use custom default settings for each registered Extensible Widget
* Also with the previous functionality, added the ability to reset back to system default settings
* Added a documentation page that is included with this plugin

= 0.8.1 =
* Cosmetic changes on the administration pages
* Reworking of the admin pages JavaScript

= 0.8 =
* Added sub-navigation to plugin administration pages
* Changed plugin page method calls to be more intuitive and familiar with other PHP frameworks
* Added more checks with widget registration and admin pages regarding users editing a another widget scope (Widget Group)
* Continued testing on multiple server platforms fixing small bugs here and there

= 0.7.4 =
* Fixed the Query Posts widget not reinitiating the global query correctly after render
* Many fixes regarding the Windows platform. This one should be the final since I finally tested on my own brand new Windows server.
* Fixes for the script queue in the base class also regarding Windows file paths
* Fixed widget contexts which were not saving correctly
* Fixed many instances of passing by reference which failed in PHP 5.3.* (This was fixed using the in-between reference workaround)
* Fixed export page not reading the sidebars_widgets option correctly
* Fixed registration page rendered list twice in certain instances
* Turned off xf package debug mode by default (This might have been setting your error handling without you knowing it, sorry)
* Changed internal setup of plugin hooks to fix many possible memory leaks
* Fixed force edit when user is editing local scope for all necessary pages
* Added plugin registration class method hooks for better control when and how a widget registers and unregisters

= 0.7.3 =
* Many fixes in the jQuery Ajaxify plugin. It had major issues in certain browsers and the version included with this plugin is now an official fork from what is available from the jQuery community.
* Fixed more errors regarding file paths and Windows, these were less fatal but should help some things
* Removed some depreciated PHP functions I was still using (cough)

= 0.7.2 =
* Fixed another fatal error with the widget directory not using the correct path or structure, this was a PHP bug regarding Windows file paths
* Updated the xf system Path class to convert to absolute paths to POSIX format when necessary (This still needs testing)
* Removed many unnecessary xf framework class files
* Cleaned much of the code bulk in admin page classes
* Updated admin footer links and added a very small donate button
* Started setting up the new method of defining and loading default widget class views (in-progress, stable)
* Started setting up the new method of defining default widget class settings (in-progress, stable)
* Thinking about a new way of creating the widget scope. Although it is stable, I do not like sessions. Debating on how to do this with only database cache.

= 0.7.1 =
* The first maintenance release of this plugin, even though it is still in beta
* Fixed fatal error with the widget directory not using the correct path or structure (This should fix the Windows server issues)
* Removed all uses of PHP short-tags for better server compatibility
* Fixed error with twitter plugin to hide vital information when no user or password is supplied
* Fixed minor css admin issues with the multiple select

= 0.7 =
* The first release of this plugin. It is beta and I have already received some bug reports regarding Windows servers.

== Upgrade Notice ==

= 0.7.4 =
This version has the most bug fixes since the first beta, I strongly encourage all users to upgrade.

== Widgets ==

These are the widgets that come with this plugin. No widget here is pre-registered, this is to let you register them as needed. Each widgets builds on another's functionality, and you may view the hierarchy from within the 'Extensible Widgets' registration page.

= Widget Base =
The base for 'Extensible Widgets' and not much on its own, it can still serve as a useful dynamic element.

= Context =
More basic options that would be good for any widget, but this widget is specifically used for controlling where widgets appear.
	
= View =
Use the view template control system and pass custom parameters to display data in any desired format.

= Content =
Use this widget to enter any data (ex: text/HTML/XML/JavaScript) and optionally access it within in a view template.
	
= Date =
Use this widget to select a view template and handle any arbitrary date.

= Widget Group =
Use this widget to create a new widget group, as a widget? Yes... this is where it gets interesting.
	
= Query Posts =
A Widget than can create and use a sub-query or use the current global query and output the results in a view template.
	
= QP Extended =
This is an extended version of the Query Posts widget with a controlled form.

= Twitter =
Use this widget to retrieve statuses from a specified twitter account.