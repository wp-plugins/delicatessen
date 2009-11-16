=== Plugin Name ===

Contributors: sole
Donate link: http://soledadpenades.com/projects/wordpress/#donate
Tags: bookmarks, social networks, social web, delicious.com, del.icio.us, links
Requires at least: 2.8.5
Tested up to: 2.8.6
Stable tag: trunk

Find out who's linking you in delicious.com

== Description ==

This plug-in works while you sleep and tries to find out who's linked your blog posts and/or pages in delicious.com (formerly known as del.icio.us).

== Installation ==

- Upload the full `delicatessen` folder to the `/wp-content/plugins/` directory
- Activate the plugin through the 'Plugins' menu in WordPress
- That's it!
- If there are any issues, the plug-in will let you know ;)

**Warning:** php5 and WP2.8.5+ are required. The plug-in has not been tested with any other configuration.

== Usage ==

When installed, the plug-in runs automatically in the background when somebody visits your blog - you don't need to add any tag or edit any template to make it work, just make sure you have visitors ;)

You can view the results of these queries by clicking the new _Delicatessen_ option under the _Dashboard_ header on the top left area of the admin area (i.e. exactly the same place where Akismet places its results link).

== Frequently Asked Questions ==

= Why are there time limits? Can I just set it to zero and get results more often? =

Because otherwise the plug-in would end up doing too many requests in too little time and delicious.com won't like it -- and will ban you while they are on it.

The default settings correspond to the absolute minimum they suggest. In fact, if you have to change them, it's got to be in order to increase the values, so that the plug-in queries _less_ often.

= So how does this work? =

OK, let's get technical. When somebody visits any post or page in your blog (and I am talking about the WordPress concept of 'page' here), the plug-in checks if we have any information about this page in delicious. If that's the case, and the data hasn't expired yet, there's nothing else to be done. Otherwise, we'll try to query delicious, but only if a minimum amount of time has elapsed since the last time we contacted delicious.

So for this plug-in to work, you need to have visitors. Although if your blog is indexed by search engines, it should be fine because they act as visitors anyway.

= Why does the plug-in say there are only 100 bookmarks for a given page? There are more! =

Because delicious doesn't provide that data. They only show up to the latest 100 bookmarks. If you want to see more, you have to visit the bookmark page in delicious :)

= Planned features =

- Add a widget so that proud site owners can show how popular their pages are.

== Screenshots ==

1. Information page, showing bookmarked pages, tags, and number of bookmarks.
2. Settings page.

== Changelog ==

= 2.0 =
* Totally revamped version, net friendly and all that.

= 1.0 =
* First public release (2007)

== Uninstall ==

Just deactivate the plug-in, and remove its folder from the wp-content/plugins directory. Or click _Delete_ for the same result.

