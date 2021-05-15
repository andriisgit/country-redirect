=== country-redirect  ===
Contributors: web8moon
Tags: redirect, country, ip, geo, redirect by country, redirect by geo, redirect by ip
Requires at least: 4.7
Tested up to: 5.7.2
Stable tag: 1.3.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple to use free WordPress plugin for redirection depending visitor's country

== Description ==

The plugin very useful for companies (such as Alfa Romeo, Bosch, Biir, Siemens) and blogs who have different web-sites for not logged in visitors from different countries.
It creates Settings page named Country Redirect in the WordPress's admin section where it is possible to point redirect for any country in the world. And when unauthorized visitor visits the frontend site, this visit will be redirected to URL matched the country he visits from with the WordPress default `302` status.
The aim of plugin is reliable country recognition of both desktop and mobile visitors using three independent determining engines based on visitor's IP.
The using of plugin is pretty simple and does not require of any special or programming skills.

If you have any suggestions or need some special function, please let me know.

== Installation ==

1. Upload the plugin into the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Country Redirect to configure the plugin

== Frequently Asked Questions ==

= Whom will useful this plugin to =
The plugin very useful for companies (such as Alfa Romeo, Bosch, Biir, Siemens) and blogs who have different web-sites for not logged in visitors from different countries.

= What is plugin exactly do =
The plugin creates Settings page named Country Redirect in the WordPress's admin section where it is possible to point redirect for any country in the world.

When unauthorized visitor visits the frontend site, this visit will be redirected to URL matched the country he visits from with the WordPress default `302` status.

This plugin does not store any data.

= Has the country determination reliable =
Both desktop and mobile visitors should be determined correctly using three independent determining engines based on visitor's IP.

= Does the plugin uses any 3rd party service
Yes. The plugin uses three independent 3rd party services for determine visitor's country by IP:
First service is "Sypex Geo". It's free for using both commercial and non commercial purposes. More at https://sypexgeo.net/ru/faq/
Second service is "GeoLite2 Free". It's distributed under the Creative Commons Attribution-ShareAlike 4.0 International License. More at https://dev.maxmind.com/geoip/geoip2/geolite2/. This product includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com">https://www.maxmind.com</a>.
Third service is "ip-api". This is online service and it's free for non commercial using only. By default, it switched off when plugin activated. More at https://ip-api.com/

= Does it need special skills for using =
The using of plugin does not require of any special or programming skills.

= If I need something special =
Everybody who wants to improve the plugin or to suggest some features are welcome.

== Screenshots ==
1. The screenshot shows country recognition engine settings. You can see at the screenshot, that by default, remote ip-api service is turned off, but if you use the plugin for non commercial purposes you can turn it on. More information at https://ip-api.com/
2. The screenshot shows redirection settings
3. The screenshot shows whitelist settings

== Changelog ==

= 1.3.2 =
* Correct SxGeo.php to meet PHP7.4 requirements
* Update SxGeo database to 2021-04-30
* Update GeoIP database to 2021-05-11

= 1.3.1 =
* Change updating algorithm to avoid using WordPress's action upgrader_process_complete.
Please, check your bot whitelist settings carefully.

= 1.3 =
* Add interface tab for whitelist settings
* Eliminate strict settings for redirect to different domain zones
* Update local IP databases

= 1.2 =
* Add whitelist IPs for Yandex bot and loading speed services
* Bug fixes
* Performance improvement

= 1.1 =
* Add whitelist IPs for Alexa, Bing, DuckDuck, Google, Yandex
* Update databases

= 1.0 =
* The first release of the plugin

== Upgrade Notice ==

= 1.3 =
Add whitelist settings. Allow by default redirect to different domain zones. Update databases.


= 1.2 =
Add whitelist IPs for Yandex bot and loading speed services. Bug fixes. Performance improvement.

= 1.1 =
Add whitelist IPs for Alexa, Bing, DuckDuck, Google, Yandex. Update databases.

= 1.0 =
The first release of the plugin.

== Translations ==
* English - default
* Ukrainian: Українська