# Verfassungsblog CrossRef DOI

This WordPress plugin allows to automatically register DOIs by submitting metadata for new posts to [CrossRef](https://www.crossref.org/).

## Features

This plugin provides the following features:

- Export metadata as "posted_content" according to the [Deposit XML specification](https://data.crossref.org/reports/help/schema_doc/5.3.1/index.html)
- Generate DOIs based on the metadata of a post
- Automatically submit modified or new posts to CrossRef on a regular schedule
- Report errors, e.g. missing metadata required by CrossRef
- Filtering posts based include and exclude categories

## Installation

The plugin is currently not yet available in the official WordPress plugin directory, and thus, needs to be installed manually by downloading the Zip file from the [releases](https://github.com/Verfassungsblog/metadata-wordpress-plugins/releases) section.

The Zip file can be installed via the "Upload Plugin" button on the "Add Plugins" page in the WordPress admin interface.

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- CrossRef Deposit User and Password
- A plugin to edit custom fields for posts and users (see blow)

Optional Dependencies:
- [co-authors-plus](https://de.wordpress.org/plugins/co-authors-plus/) plugin to add more than one author to a post
- [vb-author-affiliations](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-author-affiliations) to include the name of the author's affiliation

## Usage

The plugin runs in the background and, if enabled, regularly submits metadata for new or modified posts to CrossRef.

## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB CrossRef DOI".

### Managing Metadata with Custom Fields

A lot of metadata information is not supported by WordPress without additional plugins (e.g. co-authors, the DOI, the post language, etc.). Most of the additional metadata is stored as [custom fields](https://wordpress.org/documentation/article/assign-custom-fields/). Thus, a third-party plugin that allows to edit custom fields is required to add and edit this metadata.

There are a number of plugins that allow to manage custom fields for posts and users. The most popular plugin to manage custom fields is called [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/). However, other plugins can be used as well.

In order for this plugin to be able to access information from a custom field, its "field name" or "meta key" needs to be configured in the "Custom Fields" tab.

## Theme Integration

The plugin does not support any theme integration.

## Changelog

### v0.3.0

- Allow to conveniently dismiss last error message

### v0.2.2

- Add link to GitHub as plugin website
- Include post id in error messages to better track which post caused which error

### v0.2.0

- Added this readme file
- Added link to this GitHub readme file to help section in admin settings page

### v0.1.0

Initial release