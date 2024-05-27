# Verfassungsblog DOAJ Submit

This WordPress plugin allows to automatically submit metadata for new posts to the [Directory of Open Access Journals](https://doaj.org/) (DOAJ).

## Features

This plugin provides the following features:

- Export metadata in DOAJ article JSON format
- Match already existing DOAJ articles with WordPress posts using their permalink
- Automatically submit modified or new posts to the DOAJ on a regular schedule
- Report errors, e.g. missing metadata required by the DOAJ
- Filtering posts that are submitted to the DOAJ based on
  - include and exclude categories
  - whether a DOI is assigned to a post

## Installation

The plugin is currently not yet available in the official WordPress plugin directory, and thus, needs to be installed manually by downloading the Zip file from the [releases](https://github.com/Verfassungsblog/metadata-wordpress-plugins/releases) section.

The Zip file can be installed via the "Upload Plugin" button on the "Add Plugins" page in the WordPress admin interface.

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- DOAJ API Key and ISSN
- A plugin to edit custom fields for posts and users (see blow)

Optional Dependencies:
- [co-authors-plus](https://de.wordpress.org/plugins/co-authors-plus/) plugin to add more than one author to a post
- [vb-author-affiliations](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-author-affiliations) to include the name of the author's affiliation
- [vb-crossref-doi](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-crossref-doi) to generate DOIs for posts
- [vb-gnd-taxonomy](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-gnd-taxonomy) to add GND subjects as keywords

## Usage

The plugin runs in the background and, if enabled, regularly submits metadata for new or modified posts to the DOAJ.

## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB DOAJ Submit".

### Managing Metadata with Custom Fields

A lot of metadata information is not supported by WordPress without additional plugins (e.g. co-authors, the DOI, the post language, etc.). Most of the additional metadata is stored as [custom fields](https://wordpress.org/documentation/article/assign-custom-fields/). Thus, a third-party plugin that allows to edit custom fields is required to add and edit this metadata.

There are a number of plugins that allow to manage custom fields for posts and users. The most popular plugin to manage custom fields is called [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/). However, other plugins can be used as well.

In order for this plugin to be able to access information from a custom field, its "field name" or "meta key" needs to be configured in the "Custom Fields" tab.

## Submission Status & Statistics

The plugin stores the submission status for each post in custom fields. Each post has to go through multiple steps to be considered successfully submitted to the DOAJ:

### 1. Identification

Before a post is submitted as a new article to the DOAJ, it is checked whether the post is already known to the DOAJ. In order to identify a post, a search for its permalink is performed. If a DOAJ article with the same permalink is found, the WordPress post and DOAJ article is considered a match. The corresponding DOAJ article id is stored for the post.

Posts with a known matching DOAJ article will not be added as a new article, but metadata will be updated instead.

### 2. Submission & Update

New or modified posts are submitted or updated by sending the generated JSON file to the DOAJ. The JSON file can be inspected in the "Example" tab in the admin settings of this plugin.

### 3. Errors

In case something goes wrong, an error is recorded for the post. The posts with errors can be viewed in the "Statistics" tab in the admin settings of this plugin. After a certain amount of minutes (see settings), the submission is tried again. If the error persists, the submission is repeated indefinitely. In this case, the post needs to be added to the "exclude" category.

## Theme Integration

The plugin does not support any theme integration.

## Changelog

### v0.3.0

- Allow to reset error status for all posts
- Accept 404 as valid response when deleting a post from the DOAJ

### v0.2.2

- Add link to GitHub as plugin website
- Use first name as author name in case only first name is available
- Fix incorrect error message "response is invalid json" in case of successful article update
- Include post id in error messages to better track which post caused which error

### v0.2.0

- Fix admin setting option "retry minutes" is not rendered
- Added this readme file
- Added link to this GitHub readme file to help section in admin settings page

### v0.1.0

Initial release
