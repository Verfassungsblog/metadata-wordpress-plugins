<?php
/**
 * Class that renders HTML meta tags.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-common.php';

if ( ! class_exists( 'VB_Metadata_Export_Metatags' ) ) {

	/**
	 * Class that renders HTML meta tags.
	 */
	class VB_Metadata_Export_Metatags {
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
			return esc_attr( htmlspecialchars( html_entity_decode( $str ), ENT_XML1, 'UTF-8' ) );
		}

		/**
		 * Return a meta tag as string.
		 *
		 * @param string $name the name of the meta tag.
		 * @param string $content the content of the meta tag.
		 * @return string the meta tag
		 */
		protected function render_metatag( $name, $content ) {
			if ( empty( $content ) || empty( $name ) ) {
				return false;
			}
			return '<meta name="' . $this->escape( $name ) . '" content="' . $this->escape( $content ) . '">';
		}

		/**
		 * Return an array of ddc subject codes.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of ddc subject codes.
		 */
		protected function get_ddc_subjects( $post ) {
			$global_ddc   = $this->common->get_settings_field_value( 'ddc_general' );
			$post_ddc     = $this->common->get_post_meta_field_value( 'ddc_meta_key', $post );
			$combined_ddc = array_unique( array_merge( explode( ',', $global_ddc ), explode( ',', $post_ddc ) ) );
			$trimmed_ddc  = array_filter( array_map( 'trim', $combined_ddc ) );

			array_walk(
				$trimmed_ddc,
				function( &$value, $key ) {
					$value = 'ddc:' . $value;
				}
			);
			return $trimmed_ddc;
		}

		/**
		 * Return an array of GND subject names.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of GND subject names.
		 */
		protected function get_gnd_subjects( $post ) {
			$gnd_terms = get_the_terms( $post, 'gnd' );
			$gnd_terms = ! empty( $gnd_terms ) && ! is_wp_error( $gnd_terms ) ? $gnd_terms : array();
			$gnd_terms = array_map(
				function ( $tag ) {
					return $tag->name;
				},
				$gnd_terms
			);
			return $gnd_terms;
		}

		/**
		 * Return array of tag names.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of tag names.
		 */
		protected function get_tag_subjects( $post ) {
			$tag_subjects = get_the_tags( $post );
			$tag_subjects = $tag_subjects ? $tag_subjects : array();
			$tag_subjects = array_map(
				function ( $tag ) {
					return $tag->name;
				},
				$tag_subjects
			);
			return $tag_subjects;
		}

		/**
		 * Return rendered Dublin Core subject tags.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core subject tags as string
		 */
		public function render_dc_subjects( $post ) {
			$ddc_subjects = $this->get_ddc_subjects( $post );
			$gnd_subjects = $this->get_gnd_subjects( $post );
			$tag_subjects = $this->get_tag_subjects( $post );

			$subjects = array_unique( array_merge( $ddc_subjects, $gnd_subjects, $tag_subjects ) );
			$metatags = array_map(
				function( $value ) {
					return $this->render_metatag( 'DC.subject', $value );
				},
				$subjects,
			);
			return implode( "\n", $metatags );
		}

		/**
		 * Return rendered Highwire subjects.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core subject tags as string
		 */
		public function render_highwire_keywords( $post ) {
			$gnd_subjects = $this->get_gnd_subjects( $post );
			$tag_subjects = $this->get_tag_subjects( $post );

			$subjects = array_unique( array_merge( $gnd_subjects, $tag_subjects ) );
			$subjects = array_map(
				function( $keyword ) {
					return str_replace( ';', ' ', $keyword );
				},
				$subjects
			);
			if ( ! empty( $subjects ) ) {
				return $this->render_metatag( 'citation_keywords' , implode( '; ', $subjects ) ); // phpcs:ignore
			}
			return false;
		}

		/**
		 * Return authors and coauthors a meta tags.
		 *
		 * @param WP_Post $post the post.
		 * @param string  $meta_name the name of the meta tag.
		 * @return string the rendered meta tags for all authors, including coauthors
		 */
		public function render_authors( $post, $meta_name ) {
			$author    = $this->common->get_post_author_name( $post );
			$coauthors = $this->common->get_post_coauthors( $post );
			$coauthors = array_filter(
				array_map(
					function ( $coauthor ) {
						return $this->common->get_coauthor_name( $coauthor );
					},
					$coauthors
				),
			);
			$authors   = array_merge( array( $author ), $coauthors );
			return implode(
				"\n",
				array_map(
					function( $author ) use ( $meta_name ) {
						return $this->render_metatag( $meta_name, $author );
					},
					$authors,
				)
			);
		}

		/**
		 * Render copyright information as meta tag
		 *
		 * @param WP_Post $post the post.
		 * @param string  $meta_name the name of the meta tag.
		 * @return string the copyright information as meta tag
		 */
		public function render_copyright( $post, $meta_name ) {
			$copyright_general = $this->common->get_settings_field_value( 'copyright_general' );
			$copyright_custom  = $this->common->get_post_meta_field_value( 'copyright_meta_key', $post );
			$copyright         = ! empty( $copyright_custom ) ? $copyright_custom : $copyright_general;

			return $this->render_metatag( $meta_name, $copyright );
		}

		/**
		 * Return fully rendered Dublin Core XML document for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered Dublin Core XML document as string
		 */
		public function render( $post ) {
			$dc_str         = '';
			$highwire_str   = '';
			$post_title     = get_the_title( $post );
			$post_date      = get_the_date( 'Y-m-d', $post );
			$post_doi       = $this->common->get_post_doi( $post );
			$post_excerpt   = wp_strip_all_tags( get_the_excerpt( $post ) );
			$post_publisher = $this->common->get_settings_field_value( 'publisher' );
			$post_permalink = get_the_permalink( $post );

			if ( $this->common->get_settings_field_value( 'metatags_dc_enabled' ) ) {
				$post_language = $this->common->get_post_language( $post );

				$dc_str = implode(
					"\n",
					array_filter(
						array(
							'<!-- vb-metadata-export: dublin core meta tags -->',
							'<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />',
							$this->render_metatag( 'DC.type', 'electronic resource' ),
							$this->render_metatag( 'DC.format', 'text/html' ),
							$this->render_metatag( 'DC.title', $post_title ),
							$this->render_authors( $post, 'DC.creator' ),
							$this->render_metatag( 'DC.description', $post_excerpt ),
							$this->render_metatag( 'DC.language', $post_language ),
							$this->render_metatag( 'DC.date', $post_date ),
							$this->render_metatag( 'DC.publisher', $post_publisher ),
							$this->render_metatag( 'DC.identifier', 'http://dx.doi.org/' . $post_doi ),
							$this->render_metatag( 'DC.identifier', $post_permalink ),
							$this->render_copyright( $post, 'DC.rights' ),
							$this->render_dc_subjects( $post ),
						),
					),
				);
			}

			if ( $this->common->get_settings_field_value( 'metatags_hw_enabled' ) ) {
				$post_blog_title = $this->common->get_settings_field_value( 'blog_title' );
				$post_issn       = $this->common->get_settings_field_value( 'issn' );
				$highwire_str    = implode(
					"\n",
					array_filter(
						array(
							'<!-- vb-metadata-export: highwire meta tags -->',
							$this->render_metatag( 'citation_title', $post_title ),
							$this->render_authors( $post, 'citation_author' ),
							$this->render_metatag( 'citation_abstract', $post_excerpt ),
							$this->render_metatag( 'citation_date', $post_date ),
							$this->render_metatag( 'citation_journal_title', $post_blog_title ),
							$this->render_metatag( 'citation_publisher', $post_publisher ),
							$this->render_metatag( 'citation_doi', 'http://dx.doi.org/' . $post_doi ),
							$this->render_metatag( 'citation_fulltext_html_url', $post_permalink ),
							$this->render_metatag( 'citation_issn', $post_issn ),
							$this->render_highwire_keywords( $post ),
						),
					),
				);
			}

			return implode( "\n\n", array( $dc_str, $highwire_str ) );
		}

		/**
		 * Is called when WordPress renders the HTML header and will inject meta tags.
		 */
		public function wp_head() {
			global $post;
			if ( is_single( $post ) && $this->common->get_settings_field_value( 'metatags_enabled' ) ) {
				echo "\n" . $this->render( $post ) . "\n\n"; // phpcs:ignore
			}
		}

		/**
		 * Run function that is called by main class.
		 */
		public function run() {
			add_action( 'wp_head', array( $this, 'wp_head' ) );
		}

	}

}
