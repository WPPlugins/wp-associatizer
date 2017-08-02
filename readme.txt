=== WP-Associatizer ===
Contributors: dancingmammoth
Tags: amazon, associate, affiliate, link, rewrite, filter, revenue
Requires at least: 2.0
Tested up to: 4.6
Stable Tag: 2.9

Turn any word into an Amazon search link using square brackets and
automatically reformat Amazon.com urls to include a specified Amazon
Associates ID.

== Description ==

This plugin rewrites Amazon urls contained in posts and comments to use an
Amazon Associates Tracking ID, so you get a cut of the revenue generated from
any purchase made by someone following that link.

You can get an Amazon Associates Tracking ID through Amazon's
[Amazon Associates Affiliate Program](https://affiliate-program.amazon.com/).

You can also surround any term in your comment or post with triple square
brackets [[[Like so]]], and this plugin will automatically create an Amazon
search link with an Amazon Associates Tracking ID.

This plugin understands and can insert an Amazon Associates Tracking ID into many
different styles of product links from many different Amazon domains (`.com`,
`.co.uk`, `.co.jp`, `.de`, `.ca`, and `.fr`). It understands old-style links,
new style full links, two short-form links, and minimized links using the
`amzn.com` domain.

It also understands and can insert an Amazon Associates Tracking ID into
Amazon search links (links to a search result) and node links (links to a
product category rather than a specific product).

== Frequently Asked Questions ==

= If a url already has an associate id, will you change it? =

Yes. This plugin will overwrite exisiting Amazon Associate Tracking IDs with
the one you specify on the Options page.

= What is this "Give Back" feature? =

If checked, five percent (5%) of all urls this plugin rewrites will use our own
Amazon Associate Tracking ID instead of the one you specify. This is an easy
way for you to repay us a little for the work we've done developing this
plugin for you.

"Give back" is entirely optional, so if you don't want to participate simply
clear the checkbox on the options page for this plugin in the admin site and
100% of all Amazon urls will use the Amazon Associate Tracking ID you specify.

== Installation ==

1. Move the `wp-associatizer` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Click the 'WP-Associatizer' item in the 'Settings' menu in the left column of the Admin.
4. On the page that appears, enter your Amazon Associates Tracking ID and click 'Update Settings'

== Changelog ==

= 2.9 =
* Confirming it's stable with 4.6.

= 2.8 =
* Fixing Stable Tag declaration.

= 2.7 =
* Fix to logic for Giveback functionality to make it behave as intended under all conditions. Testing and minor changes for WordPress 4.5.2.

= 2.6 =
* Change to PHP5 style constructors for WordPress 4.3 and later. Additional fix to allow the plugin to work properly with https and http affliliate links.

= 2.5 =
* Change to version number so newest stable version will properly appear on plugins site.

= 2.4.1 =
* Minor fix to make behavior properly match documentation in Version 2.4.

= 2.4 =
* Updated information on being tested up to WordPress 3.8.1. Changed voluntary giveback percentage from one to five. We appreciate your support.

= 2.3 =
* Fix non-reaffiliating of Amazon links found after non-Amazon links under certain conditions

= 2.2 =
* Add automatic Amazon search links using square brackets

= 2.1 =
* Change settings menu title to be consistent
* Generate old-style /exec/ product urls so Amazon picks up the tracking id

= 2.0 =
* First public release
* Added admin panel
* Added "give back" feature
* Added support for Amazon search and node links
* Added unit tests.

= 1.0 =
* Internal release only
* Supported Amazon product links
