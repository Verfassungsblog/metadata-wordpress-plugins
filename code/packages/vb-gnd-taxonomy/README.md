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

https://github.com/Verfassungsblog/metadata-wordpress-plugins/assets/6214043/d9f2bc97-42e1-4c08-aee1-6facb6d01e2d

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- [Classic Editor](https://github.com/WordPress/classic-editor/) plugin

Currently, GND entities can only be assigned in the classic editor mode.

## Usage

GND entities can be added the same way as regular tags. The only difference is that GND entities are suggested based on [lobig.org](http://lobid.org/gnd), and that the GND entity id is used as "slug".

https://github.com/Verfassungsblog/metadata-wordpress-plugins/assets/6214043/93ab1793-a284-4e4a-b52d-b1fe25a2322d

## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB GND Taxonomy".
The available options allow to influence how GND entities are suggested and visualized.

https://github.com/Verfassungsblog/metadata-wordpress-plugins/assets/6214043/adcee321-2ab9-4fa1-bc9f-4a703456f134

## Theme Integration

GND entities can be embedded with standard WordPress methods using `gnd` as the taxonomy name. For example [the_terms](https://developer.wordpress.org/reference/functions/the_terms/):

`the_terms($post_id, 'gnd')`

The output will be the list of assigned GND entities separated by comma.

Other WordPress methods that can be used are:
- [get_the_term_list](https://developer.wordpress.org/reference/functions/get_the_term_list/)
- [wp_tag_cloud](https://developer.wordpress.org/reference/functions/wp_tag_cloud/)
- [taxonomy_exists](https://developer.wordpress.org/reference/functions/taxonomy_exists/)

You can also use custom templates as described in the WordPress [documentation](https://developer.wordpress.org/themes/template-files-section/taxonomy-templates/#custom-taxonomy).

## Changelog

### v0.2.2

- Add link to GitHub as plugin website

### v0.2.0

- Fixed error that prevents uninstall
- Added this readme file
- Added link to this GitHub readme file to help section in admin settings page

### v0.1.0

Initial release
