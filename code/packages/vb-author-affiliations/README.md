# Verfassungsblog Author Affiliations

This WordPress plugin allows to store author affiliations for each post individually. This is necessary in case the affiliation of an author changes over time. This plugin allows to preserve the original affiliation (at publication time).

The post-specific author affiliation is supported by both the [vb-doaj-submit](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-doaj-submit) and [vb-crossref-doi](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-crossref-doi) plugin.

## Features

The plugin provides the following features:

- Post edit meta box that allows to specify the affiliation name and ROR-ID for an author
- Automatic copying of the affiliation name and ROR-ID from custom fields of each author
- Automatic extraction of the affiliation name from the ROR-ID or ORCID

## Installation

The plugin is currently not yet available in the official WordPress plugin directory, and thus, needs to be installed manually by downloading the Zip file from the [releases](https://github.com/Verfassungsblog/metadata-wordpress-plugins/releases) section.

The Zip file can be installed via the "Upload Plugin" button on the "Add Plugins" page in the WordPress admin interface.

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- [Classic Editor](https://github.com/WordPress/classic-editor/) plugin

Currently, author affiliations can only be viewed and changed in the classic editor mode.

## Usage

When editing a post, a new author affiliation box is available, which allows to enter both the affiliation name and ROR-ID of the affiliation.

If enabled, the information is automatically copied from the custom fields of each new author.

If enabled, the affiliation name is automatically extracted from the ROR-ID or ORCID.

## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB Author Affiliations".

## Theme Integration

The post-specific author affiliations can be accessed using a template function.

`get_the_vb_author_affiliations(WP_Post $post) : array`

Parameters:
- `$post: WP_Post` - the post whose author affiliations are returned

Returns:
- An array that stores the affiliation name and ROR-ID for each author identified by its user id, meaning `[(user_id) => ['name' => string, 'rorid' => string]]`.

## Changelog

### v0.2.2

- Add link to GitHub as plugin website
- Detect and ignore `https://ror.org` prefix in case a user accidentally added it to the metadata

### v0.2.0

- Added this readme file
- Added link to this GitHub readme file to help section in admin settings page

### v0.1.0

Initial release
