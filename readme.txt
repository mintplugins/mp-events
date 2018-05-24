=== MP Events ===
Contributors: johnstonphilip
Donate link: http://mintplugins.com/
Tags: message bar, header
Requires at least: 3.5
Tested up to: 4.9.4
Stable tag: 1.0.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create Events. Display them with themes or other plugins.

== Description ==

Create Events. Display them with themes or other plugins. See "MP Stacks + EventGrid" for a great and free displaying option.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the 'mp-events' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Events in your dashboard to set up events.

== Frequently Asked Questions ==

= What do I do with this?  =

Create Events

== Screenshots ==

== Changelog ==

= 1.0.1.3 = May 24, 2018
* Make sure recurring checks aren’t single events

= 1.0.1.2 = March 7, 2018
* Remove any single events that are older than the loop’s beginning date before creating the event array

= 1.0.1.1 = October 6, 2017
* Remove all HTML5 date pickers and change them to normal text boxes.

= 1.0.1.0 = August 8, 2017
* Set the default date for events to today

= 1.0.0.9 = June 21, 2017
* Add checks for single events being empty

= 1.0.0.8 = June 21, 2017
* Fix: Daily recurring events were not working because of an array formatting issue.

= 1.0.0.7 = April 19, 2017
* Fix: Recurring events were recurring into the past and future. Now they begin at the right time.
* Fix: Recurring events with an end time were causing never-ending looping issues in some cases. Those are now fixed.

= 1.0.0.6 = January 23, 2017
* Fix: Single events end dates were not getting set properly. Make sure mp_events_modify_event uses correct post id and object.

= 1.0.0.5 = January 23, 2017
* Fix: Single events end dates were not getting set properly. Make sure mp_events_modify_event uses correct post id and object.

= 1.0.0.4 = December 6, 2016
* Fix: Double check that post exists has a post type. Removes error on dashboard caused in yesterday's 1.0.0.3 release.

= 1.0.0.3 = December 5, 2016
* Fix: Number of events was incorrect if a repeating event is ended during our loop.
* Added event video field
* Simplified event edit screen

= 1.0.0.2 = June 17, 2016
* Fix bug with repeating events that have an end repeat date.

= 1.0.0.1 = November 4, 2015
* Fixes for when there are no repeating events.

= 1.0.0.0 = September 14, 2015
* Original release
