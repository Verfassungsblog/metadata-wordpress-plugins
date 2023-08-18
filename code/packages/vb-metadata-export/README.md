# Verfassungsblog Metadata Export

This WordPress plugin allows to export post metadata in standard formats often used by libraries, in particular:

- [Marc21 XML](http://www.loc.gov/standards/marcxml/)
- [MODS 3.7 XML](https://www.loc.gov/standards/mods/)
- [Dublin Core XML](https://www.dublincore.org/schemas/xmls/)

Additionally, the metadata can be exposed via the [OAI-PMH 2.0 interface](https://www.openarchives.org/pmh/) (the Open Archives Initiative Protocol for Metadata Harvesting). For example, the OAI-PMH 2.0 interface could be used to [submit metadata to the German National Library (DNB)](https://www.dnb.de/DE/Professionell/Sammeln/Unkoerperliche_Medienwerke/unkoerperliche_medienwerke_node.html) for the purpose of long-term archiving.

## Features

The plugin supports to export the following metadata:

- Author name and [ORCID](https://orcid.org)
- Coauthors via the [co-authors-plus](https://de.wordpress.org/plugins/co-authors-plus/) plugin
- Title and subtitle
- Abstract
- Language
- Blog name, blog owner, publisher name
- Tags
- Dewey Decimal Classification (DDC) subjects
- GND (ger: Gemeinsame Normdatei) subjects, see [vb-gnd-taxonomy](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-gnd-taxonomy)
- International Standard Serial Number (ISSN)
- Digital Object Identifier (DOI), see plugin
[vb-crossref-doi](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-crossref-doi)
- Content type (textual article, podcast)
- Funding notes
- License notes

## Installation

The plugin is currently not yet available in the official WordPress plugin directory, and thus, needs to be installed manually by downloading the Zip file from the [releases](https://github.com/Verfassungsblog/metadata-wordpress-plugins/releases) section.

The Zip file can be installed via the "Upload Plugin" button on the "Add Plugins" page in the WordPress admin interface.

https://github.com/Verfassungsblog/metadata-wordpress-plugins/assets/6214043/d6ccf1c7-3e0c-4040-9b7e-60e0512c9d75

Mandatory Requirements:
- Wordpress >= 5
- PHP >= 7.4
- A plugin to edit custom fields for posts and users (see blow)

Optional Dependencies:
- [co-authors-plus](https://de.wordpress.org/plugins/co-authors-plus/) plugin to add more than one author to a post
- [vb-crossref-doi](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-crossref-doi) to generate DOIs for posts
- [vb-gnd-taxonomy](https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-gnd-taxonomy) to add GND subjects to posts
- PHP extension "XSL" to convert Marc21 XML to MODS 3.7 XML.


### WordPress Docker Image

The official WordPress Docker image does not include the "XSL" PHP extension by default. It can be installed via the following commands:

```Docker
FROM docker.io/library/wordpress:6

# install xsl php extension
RUN apt-get update && apt-get install -y libxslt-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install xsl
```



## Configuration

The plugin adds a custom settings page to the WordPress admin interface. It can be accessed from the "Settings" menu, option "VB Metadata Export".

### Managing Metadata with Custom Fields

A lot of metadata information is not supported by WordPress without additional plugins (e.g. co-authors, the DOI, the post language, etc.). Most of the additional metadata is stored as [custom fields](https://wordpress.org/documentation/article/assign-custom-fields/). Thus, a third-party plugin that allows to edit custom fields is required to add and edit this metadata.

There are a number of plugins that allow to manage custom fields for posts and users. The most popular plugin to manage custom fields is called [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/). However, other plugins can be used as well.

In order for this plugin to be able to access information from a custom field, its "field name" or "meta key" needs to be configured in the "Custom Fields" tab.

https://github.com/Verfassungsblog/metadata-wordpress-plugins/assets/6214043/301464e4-5f21-4ee0-991c-51d168a7c922

## Theme Integration

The plugin allows to download the metadata XML files via custom permalinks. The permalinks to metadata XML files are constructed by adding the URL argument:

`vb-metadata-export=<format>`

Available formats are `marc21xml`, `mods`, `dc`. For example, a custom permalink to the Marc21 XML export for a post with the slug "some-post" would be:

`https://example.com/some-post/?vb-metadata-export=marc21xml`

### Shortcode

The permalinks can be embedded via the shortcode `vb-metadata-export-link`. The shortcode supports the following options:

- `format` - either `marc21xml`, `mods`, `dc` or `oai-pmh`
- `title` - the title of the link
- `unavailable` - the title of the link in case the export format is not available
- `class` - any additional css classes

For example, a shortcode could look like this:

```
[vb-metadata-export-link format="marc21xml" title="Marc21 XML"]
```

The output for that shortcode would be:

```
<a href="https://example.com/some-post/?vb-metadata-export=marc21xml">Marc21 XML</a>
```

### Template Functions

Lastly, the permalink can be generated via the following PHP template functions.

`get_the_vb_metadata_export_permalink(string $format): string`

Returns the permalink to the metadata export format for the current global post.

Parameters:
- `$format: string` \
  the format to which the link is pointing to, either `marc21xml`, `mods`, `dc` or `oai-pmh`

`get_the_vb_metadata_export_link(string $format, string $title, string $extra_class, string $unavailable) : string`

Return a a-tag link to the metadata export format for the current global post as string.

Parameters:
- `$format : string` \
  the format to which the link is pointing to, either `marc21xml`, `mods`, `dc` or `oai-pmh`.
- `$title : string = ''` \
   the link text
- `$extra_class : string = ''` \
   any additional css class tag
- `$unavailable : string = ''` \
  the link text in case the format is not available (e.g. disabled in settings)

Both template functions are also available as `the_vb_metadata_export_permalink` and `the_vb_metadata_export_link`, which immediately output the link.

### OAI-PMH 2.0

The OAI-PMH 2.0 base URL is `https://example.com/oai/repository/`. There is no option to change that.

## Changelog

### v0.2.1

- Fix resumption token error due to missing required options that were not encoded
- Fix problem that unavailable class is always added to metadata export links

### v0.2.0

- Show error message in case XSL files can not be downloaded
- Added link to this GitHub readme file to help section in admin settings page

### v0.1.0

Initial release
