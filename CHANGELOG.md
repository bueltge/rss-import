# Changelog

All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org/).

## v4.6.2 (2022-12-23)
* Fix for security on the target attribute

## v4.6.1 (2019-12-25)
* Fix a simple bug on shortcode to output the content.

## v4.6.0 (2019-12-23)
* Add a new parameter to set the order of date, title, creator, and description.
* Maintain simple topics inside the code.
* Ready for usage in PHP 7.2*, replace `create_function`

## v4.5.1 (2017-12-14)
* Fix escaping in the widget admin form

## v4.5.0 (2017-12-01)
* Added new parameter random_sort

## v4.4.18 (2017-11-30)
* Small code changes for more strict coding.
* Fix formatting topics on readme.

## v4.4.17 (2016-04-17)
* Fix for usage under php 7*
* Code Formatting, WP Codex
* *You should always use the SimplePie library as a Feed Parser - check your parameter.*

## v4.4.16 (09/24/2015)
* Fix the widget PHP4 style

## v4.4.15 (08/22/2014)
* Added `%picture_url%` for `before_desc`, `after_desc`, `start_item` and `end_item`

## v4.4.14 (07/08/2014)
* Fix typo in readme
* Fix a bug with `%href%` and `%title%` in after_desc
* Shortened some over-long lines in the code

## v4.4.13 (08/22/2013)
* Set SimplePie to default settings; this is the default way of WP since a lot of versions
* Fix for Quicktag on Post/Page Editor; use now the core functions there we have since WP 3.3

## v4.4.12 (04/02/2012)
* Bugfix: restored RSSImport QuickTag for Wordpress 3.3 and later
* Improvement: avoid PHP-notice when description is missing for an item
* TODO: add parameter to allow prefix of url (see https://wordpress.org/support/topic/plugin-rssimport-fix-for-headline-links-without-full-paths)
* TODO: check documentation of call to function (PHP); see https://wordpress.org/support/topic/plugin-rssimport-change-feed-display
* Documentation: corrected 'after_desc' (thanks to elricky for reporting)

## v4.4.11 (13/12/2011)
* Bugfix: noitems string display is back
* Improvement: html_entity_decode feedurl when using shortcodes
* Maintenance: Add Romanian language files

## v4.4.10 (01/12/2011)
* Bugfix: add param desc4title on shortcodes
* Bugfix: Filter Feed-Url for masked `&`; now works Yahoo Pipes feeds
* Maintenance: Translate strings from options

## v4.4.9 (09/16/2010)
* Feature: add new param `desc4title` to add the description to title-attribute on title-links
* Bugfix: target parameter in widget
* Maintenance: rescan/rewrite de_DE language file
* Maintenance: rescan .pot

## v4.4.8 (06/04/2010)
* small changes for better debugging
* change metadata for WordPress
* multilanguage plugin-description
* change error-handling on feeds; use WP-Error

## v4.4.7 (05/20/2010)
* bugfix widget parameter for description
* small changes on source

## v4.4.6 (07/10/2009)
* add function for WordPress lower version 2.8
* add option for formatting the date

## v4.4.5 (30/09/2009)
* bugfix Widget-title
* include class SimpliePie for alternative parse
* new parameter `$use_simplepie` for active parse with class SimplePie
* change for boolean type, possible to use `true` or `false` and `1` or `0`

## v4.4.4 (15/09/2009)
* change updatenotice to standard WP

## v4.4.3 (14/09/2009)
* add strings %title% and %href% to replace in after-desc-option

## v4.4.2 (07/09/2009)
* Bugfix for utl-value on shortcode
* change clean the title-attribut an links for multilanguage-support

## v4.4.1 (14/07/2009)

* add rel attribute for links
* add widget support, WP 2.8 and higher

See on [the official website](https://bueltge.de/wp-rss-import-plugin/55/#historie "RSSImport Changelog") for older entries on changelog.
