=== AUS Telegram Channel ===
Contributors: ulugov
Donate link: http://anvar.ulugov.uz/donate/
Tags: telegram, bot, channel, newsletter
Requires at least: 3.4
Tested up to: 4.6
Version:	1.0.7
Stable tag:	trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Broadcast Wordpress posts on your Telegram channel

== Description ==

This is a simple plugin that lets you automatically send your posts to your telegram channel via your channel admin bot.

[youtube https://www.youtube.com/watch?v=Vjzx_grcVdI]

== Installation ==

1. Download AUS Telegram Channel plugin from WordPress Plugin directory.
2. Upload zip file to your WordPress Plugins directory. /wp-content/plugins
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > AUS Telegram page enter your channel admin bot token and @channelusername.
5. If you want to send posts that were added after particular date you can set the start date, it is optional. If not set then all posts will be send.
6. Set the recurrence of sending process.

[youtube https://www.youtube.com/watch?v=Vjzx_grcVdI]

You can find a detailed instructions here:
http://anvar.ulugov.uz/aus-telegram-channel-configuration-example/

== Frequently Asked Questions ==

= Where can I get telegram bot token? =

We advice you to check this page:
https://core.telegram.org/bots/faq

= How can I set my telegram bot as admin to my telegram channel? =

We advice you to check this page:
http://telegram.wiki/tips:channels

== Screenshots ==

1. Message example on web browser:
2. Message example on Desktop client:
3. Options page:

== Changelog ==

= 1.0 =
* Initial Release
= 1.0.1 =
* Text limit option has been added
= 1.0.2 =
* Tested on Wordpress 4.4
= 1.0.3 =
* Added Custom text options before and after message
* file_get_contents replaced by curl
= 1.0.4 =
* Added Broadcast by Categories functionality
= 1.0.5 =
* Added Datepicker to date input field
= 1.0.6 =
* Added New post broadcast option
= 1.0.7 =
* Add new option: Category as hashtag
* Add new option: Instant message sending
* Formatting mode changed from Markdown to HTML