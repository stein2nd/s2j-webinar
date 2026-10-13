=== S2J Webinar ===
Contributors: stein2nd
Tags: gatherpress, webinar
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 0.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

GatherPress companion plugin to create and sync Zoom Webinars from the event editor.

== Description ==

S2J Webinar connects a WordPress site to Zoom, stores webinar settings on GatherPress events, and runs sync through the s2j/webinar-service library.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the distribution zip.
2. Run `composer install --no-dev` in the plugin directory if `vendor/` is not bundled.
3. Activate through the Plugins screen.
4. Open **S2J Webinar** in the admin menu to connect Zoom.

== Changelog ==

= 0.0.1 =
* Initial plugin skeleton: settings, meta registration, GatherPress panel mount, service bridge.
