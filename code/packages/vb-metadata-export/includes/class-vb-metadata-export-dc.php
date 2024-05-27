<?php
/**
 * Class that renders Dublin Core XML.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-common.php';

if ( ! class_exists( 'VB_Metadata_Export_DC' ) ) {

	/**
	 * Class that renders Dublin Core XML.
	 */
	class VB_Metadata_Export_DC {
		/**
		 * Common methods
		 *
		 * @var VB_Metadata_Export_Common
		 */
		protected $common;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_Metadata_Export_Common( $plugin_name );
		}

		/**
		 * Escape function that is used throughout Dublin Core XML rendering.
		 *
		 * @param string $str the string that is escaped.
		 */
		protected function escape( $str ) {
			return htmlspecialchars( html_entity_decode( wp_strip_all_tags( $str ) ), ENT_XML1, 'UTF-8' );
		}

		/**
		 * Return rendered Dublin Core identifier tags containing the DOI and permalink of the post.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core identifier tags as string
		 */
		public function render_identifier( $post ) {
			$doi      = $this->common->get_post_doi( $post );
			$post_url = get_the_permalink( $post );

			$xml = implode(
				'',
				array(
					! empty( $doi ) ?
						'<dc:identifier>http://dx.doi.org/' . $this->escape( $doi ) . '</dc:identifier>' : '',
					! empty( $post_url ) ?
						'<dc:identifier>' . $this->escape( $post_url ) . '</dc:identifier>' : '',
				),
			);

			return $xml;
		}

		/**
		 * Return rendered Dublin Core relation tags containg the blog title and ISSN.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core relation tags as string containg the blog title and ISSN
		 */
		public function render_relation( $post ) {
			$blog_title = $this->common->get_settings_field_value( 'blog_title' );
			$issn       = $this->common->get_settings_field_value( 'issn' );
			$relation   = $blog_title;
			if ( ! empty( $issn ) ) {
				$relation = $relation . '--' . $issn;
			}
			if ( ! empty( $relation ) ) {
				return '<dc:relation>' . $this->escape( $relation ) . '</dc:relation>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core language tag containg the post language.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core language tag as string containg the post language
		 */
		public function render_language( $post ) {
			$language = $this->common->get_post_language( $post );
			if ( ! empty( $language ) ) {
				return '<dc:language>' . $this->escape( $language ) . '</dc:language>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core subject tags containg the DDC subjects.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core subject tags as string containg the DDC subjects
		 */
		public function render_ddc_subjects( $post ) {
			$global_ddc   = $this->common->get_settings_field_value( 'ddc_general' );
			$post_ddc     = $this->common->get_post_meta_field_value( 'ddc_meta_key', $post );
			$combined_ddc = array_merge( explode( ',', $global_ddc ), explode( ',', $post_ddc ) );
			$trimmed_ddc  = array_filter( array_map( 'trim', $combined_ddc ) );

			$subjects = array();
			foreach ( $trimmed_ddc as $ddc ) {
				$subjects = array_merge(
					$subjects,
					array( '<dc:subject>ddc:' . $this->escape( $ddc ) . '</dc:subject>' ),
				);
			}
			return implode( '', $subjects );
		}

		/**
		 * Return rendered Dublin Core subject tags containg the post tags.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core subject tags as string containg the post tags
		 */
		public function render_tags( $post ) {
			$tags     = get_the_tags( $post );
			$tags     = $tags ? $tags : array();
			$subjects = array();
			foreach ( $tags as $tag ) {
				$subjects = array_merge(
					$subjects,
					array( '<dc:subject>' . $this->escape( $tag->name ) . '</dc:subject>' ),
				);
			}
			return implode( '', $subjects );
		}

		/**
		 * Return rendered Dublin Core subject tags containg the GND subjects
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core subject tags as string containg the GND subjects
		 */
		public function render_gnd_subjects( $post ) {
			$gnd_terms = get_the_terms( $post, 'gnd' );
			$gnd_terms = ! empty( $gnd_terms ) && ! is_wp_error( $gnd_terms ) ? $gnd_terms : array();
			$subjects  = array();
			foreach ( $gnd_terms as $gnd_term ) {
				$subjects = array_merge(
					$subjects,
					array( '<dc:subject>' . $this->escape( $gnd_term->name ) . '</dc:subject>' ),
				);
			}
			return implode( '', $subjects );
		}

		/**
		 * Return rendered Dublin Core creator tag containg the post main author.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core creator tag as string containg the post main author
		 */
		public function render_author( $post ) {
			$post_author = $this->common->get_post_author_name( $post );
			if ( ! empty( $post_author ) ) {
				return '<dc:creator>' . $this->escape( $post_author ) . '</dc:creator>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core creator tags containg the post coauthors.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core creator tags as string containg the post coauthors
		 */
		public function render_coauthors( $post ) {
			$coauthors = $this->common->get_post_coauthors( $post );
			$xml       = '';
			foreach ( $coauthors as $coauthor ) {
				$coauthor_name = $this->common->get_coauthor_name( $coauthor );
				if ( empty( $coauthor_name ) ) {
					continue;
				}
				$xml = $xml . '<dc:creator>' . $this->escape( $coauthor_name ) . '</dc:creator>';
			}
			return $xml;
		}

		/**
		 * Return rendered Dublin Core title tag containg the post title.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core title tag as string containg the post title
		 */
		public function render_title( $post ) {
			$title               = get_the_title( $post );
			$subheadline         = $this->common->get_post_meta_field_value( 'subheadline_meta_key', $post );
			$include_subheadline = $this->common->get_settings_field_value( 'include_subheadline' );
			if ( $include_subheadline && ! empty( $subheadline ) ) {
				$title = $title . ' - ' . $subheadline;
			}

			if ( ! empty( $title ) ) {
				return '<dc:title>' . $this->escape( $title ) . '</dc:title>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core description tag containg the post excerpt.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core description tag as string containg the post excerpt
		 */
		public function render_excerpt( $post ) {
			$include_excerpt = $this->common->get_settings_field_value( 'include_excerpt' );
			if ( $include_excerpt ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
				if ( ! empty( $excerpt ) ) {
					return '<dc:description>' . $this->escape( $excerpt ) . '</dc:description>';
				}
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core publisher tag containg the publisher name.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core publisher tag as string containg the publisher name
		 */
		public function render_publisher( $post ) {
			$publisher = $this->common->get_settings_field_value( 'publisher' );
			if ( ! empty( $publisher ) ) {
				return '<dc:publisher>' . $this->escape( $publisher ) . '</dc:publisher>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core rights tag containg the post licence name.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core rights tag as string containg the post licence name
		 */
		public function render_copyright( $post ) {
			$copyright_general = $this->common->get_settings_field_value( 'copyright_general' );
			$copyright_custom  = $this->common->get_post_meta_field_value( 'copyright_meta_key', $post );
			$copyright         = ! empty( $copyright_custom ) ? $copyright_custom : $copyright_general;
			if ( ! empty( $copyright ) ) {
				return '<dc:rights>' . $this->escape( $copyright ) . '</dc:rights>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core date tag containg the post date.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core date tag as string containg the post date
		 */
		public function render_date( $post ) {
			$date = get_the_date( 'Y-m-d', $post );

			if ( ! empty( $date ) ) {
				return '<dc:date>' . $this->escape( $date ) . '</dc:date>';
			}
			return '';
		}

		/**
		 * Return rendered Dublin Core type tag containing the value 'electronic resource'.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core type tag as string containing the value 'electronic resource'
		 */
		public function render_type( $post ) {
			return '<dc:type>electronic resource</dc:type>';
		}

		/**
		 * Return rendered Dublin Core format tag containing the value text/html.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core format tag as string containg the text text/html
		 */
		public function render_format( $post ) {
			return '<dc:format>text/html</dc:format>';
		}

		/**
		 * Return fully rendered Dublin Core XML document for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core XML document as string
		 */
		public function render( $post ) {
			$schema_location_list = array(
				'http://www.openarchives.org/OAI/2.0/oai_dc/',
				'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
				'http://purl.org/dc/elements/1.1/',
				'http://dublincore.org/schemas/xmls/simpledc20021212.xsd',
			);
			$schema_location_str  = implode( ' ', $schema_location_list );

			$xml_str = implode(
				'',
				array(
					'<?xml version="1.0" encoding="UTF-8"?>',
					"\n",
					'<dc
						xmlns="http://www.openarchives.org/OAI/2.0/oai_dc/"
						xmlns:dc="http://purl.org/dc/elements/1.1/"
						xsi:schemaLocation="' . $schema_location_str . '"
						xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">',
					"\n",
					$this->render_identifier( $post ),
					$this->render_title( $post ),
					$this->render_author( $post ),
					$this->render_coauthors( $post ),
					$this->render_language( $post ),
					$this->render_date( $post ),
					$this->render_type( $post ),
					$this->render_format( $post ),
					$this->render_ddc_subjects( $post ),
					$this->render_tags( $post ),
					$this->render_gnd_subjects( $post ),
					$this->render_publisher( $post ),
					$this->render_relation( $post ),
					$this->render_copyright( $post ),
					$this->render_excerpt( $post ),
					'</dc>',
				),
			);
			return $this->common->format_xml( $xml_str );
		}

	}

}
