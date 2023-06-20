# Verfassungsblog GND Taxonomy

This plugin adds the GND (ger: Gemeinsame Normdatei) taxonomy to WordPress. Posts can be assigned to GND entities.
GND entities are suggested and validated based on the [lobig.org](http://lobid.org/gnd) service.

## Features

The plugin provides the following features:

- Custom GND taxonomy
- Autocomplete suggestions based on [lobig.org](http://lobid.org/gnd)
  - Custom labels formats
  - Custom query filters (e.g. only GND entities of a certain topic or type)
- Automatic validation of GND-IDs
- Optional merging of tags and GND entities

## Installation

The plugin is currently not yet available in the official WordPress plugin directory, and thus, needs to be installed manually by downloading the Zip file from the [releases](https://github.com/Verfassungsblog/metadata-wordpress-plugins/releases) section.

The Zip file can be installed via the "Upload Plugin" button on the "Add Plugins" page in the WordPress admin interface.

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- [Classic Editor](https://github.com/WordPress/classic-editor/) plugin

Currently, GND entities can only be assigned in the classic editor mode.

## Usage

GND entities can be added the same way as regular tags. The only difference is that GND entities are suggested based on [lobig.org](http://lobid.org/gnd), and that the GND entity id is used as "slug".

## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB GND Taxonomy".
The available options allow to influence how GND entities are suggested and visualized.

## Theme Integration

GND entities can be embedded with standard WordPress methods using `gnd` as the taxonomy name. For example [the_terms](https://developer.wordpress.org/reference/functions/the_terms/):

`the_terms($post_id, 'gnd')`

The output will be the list of assigned GND entities separated by comma.

Other WordPress methods that can be used are:
- [get_the_term_list](https://developer.wordpress.org/reference/functions/get_the_term_list/)
- [wp_tag_cloud](https://developer.wordpress.org/reference/functions/wp_tag_cloud/)
- [taxonomy_exists](https://developer.wordpress.org/reference/functions/taxonomy_exists/)

You can also use custom templates as described in the WordPress [documentation](https://developer.wordpress.org/themes/template-files-section/taxonomy-templates/#custom-taxonomy).

