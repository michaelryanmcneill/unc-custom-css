=== UNC Custom CSS ===
Contributors: michaelryanmcneill, earnjam, webdotunc
Tags: appearance, CSS, custom css, custom post type, edit css, live edit css, revisions css, themes
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows for Custom CSS to be easily added to WordPress.

== Description ==
An easy way to modify the CSS on your site without modifying your theme's code.

== Installation ==
Install the UNC Custom CSS either via the "Upload Plugin" page, or by adding the files to your server.
Install and activate the plugin.
Navigate to Appearance -> Custom CSS and enter your custom CSS! 
That's it. You're CSS is live on your site!

== Changelog ==
= 1.0 =
*  First version, creates custom CSS admin page.

== Frequently Asked Questions ==
Why isn't the CSS showing up on the blog?
Remember that this plugin depends on standard WordPress hooks to operate. If the active theme does not have `wp_head()` in its code, this plugin would be ineffective. To fix this, add `<?php wp_head(); ?>` to the theme files in the section.

Why can't I add JavaScript with this plugin?
This plugin will only operate for Cascading Style Sheets code. The custom CSS is escaped and outputted within a set of `<style>` tags, preventing malicious people from abusing the functionality to inject malicious code. Allowing users to inject JavaScript into the header is a security vulnerability, thus this plugin does not permit it.