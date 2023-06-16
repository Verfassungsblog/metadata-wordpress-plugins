<?php
/**
 * Class that renders CrossRef deposit XML.
 *
 * @package vb-crossref-doi
 */

if ( ! class_exists( 'VB_CrossRef_DOI_Render' ) ) {

	/**
	 * Class that renders CrossRef deposit XML.
	 */
	class VB_CrossRef_DOI_Render {
		/**
		 * Common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		public $common;

		/**
		 * Remembers the last error that occurred when rendering a deposit xml.
		 *
		 * @var string
		 */
		protected $last_error;

		/**
		 * Remembers the last post that was used to render a deposit xml.
		 *
		 * @var WP_Post
		 */
		protected $last_post;

		/**
		 * Initialize the class with the plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_CrossRef_DOI_Common( $plugin_name );
		}

		/**
		 * The escape method that is used throughout this class.
		 *
		 * @param string $str the string that is escaped for rendering.
		 * @return string the escaped string
		 */
		protected function escape( $str ) {
			return htmlspecialchars( html_entity_decode( $str ), ENT_XML1, 'UTF-8' );
		}

		/**
		 * Converts a DateTime object to the CrossRef deposit timestamp format.
		 *
		 * @param DateTime|DateTimeImmutable $date the date to be converted.
		 * @return string the timestamp as string in the CrossRef deposit format
		 */
		protected function date_to_timestamp_format( $date ) {
			return $date->format( 'YmdHisv' );
		}

		/**
		 * Return the first and last name of an author as array.
		 *
		 * @param int $author the user id of the post author.
		 * @return array the first and last name as an array
		 */
		protected function get_author_name( $author ) {
			$last_name  = get_the_author_meta( 'last_name', $author );
			$first_name = get_the_author_meta( 'first_name', $author );

			return array(
				'given_name' => $first_name,
				'surname'    => $last_name,
			);
		}

		/**
		 * Return the first and last name of an coauthor as array.
		 * Uses the "co-author-plus" plugin.
		 *
		 * @param object $coauthor the coauthor object provided by the "co-author-plus" plugin.
		 * @return array the first and last name as an array
		 */
		protected function get_coauthor_name( $coauthor ) {
			$last_name  = $coauthor->last_name;
			$first_name = $coauthor->first_name;

			return array(
				'given_name' => $first_name,
				'surname'    => $last_name,
			);
		}

		/**
		 * Return the first and last name of the main post author.
		 *
		 * @param WP_Post $post the post.
		 * @return array the first and last of the main post author as array
		 */
		protected function get_post_author_name( $post ) {
			return $this->get_author_name( $post->post_author );
		}

		/**
		 * Return the list of coauthor objects of the post.
		 * Uses the "co-author-plus" plugin.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of coauthor objects
		 */
		protected function get_post_coauthors( $post ) {
			if ( ! function_exists( 'get_coauthors' ) ) {
				return array();
			}
			return array_slice( get_coauthors( $post->ID ), 1 );
		}

		/**
		 * Returns the rendered head of the deposit XML as string.
		 *
		 * @param WP_Post $post the post.
		 * @param int     $submit_timestamp the UTC timestamp when the submission is performed.
		 * @return string the rendered head section of the deposit XML
		 */
		protected function render_head( $post, $submit_timestamp ) {
			$timestamp       = $this->date_to_timestamp_format( new DateTime() );
			$depositor_name  = $this->common->get_settings_field_value( 'depositor_name' );
			$depositor_email = $this->common->get_settings_field_value( 'depositor_email' );
			$registrant      = $this->common->get_settings_field_value( 'registrant' );

			if ( empty( $depositor_name ) ) {
				$this->last_error = 'depositor name required';
				return false;
			}

			if ( empty( $depositor_email ) ) {
				$this->last_error = 'depositor email required';
				return false;
			}

			if ( empty( $registrant ) ) {
				$this->last_error = 'registrant required';
				return false;
			}

			return implode(
				'',
				array(
					'<head>',
					'<doi_batch_id>' . $this->escape( $post->ID . $submit_timestamp ) . '</doi_batch_id>',
					'<timestamp>' . $this->escape( $timestamp ) . '</timestamp>',
					'<depositor>',
					'<depositor_name>' . $this->escape( $depositor_name ) . '</depositor_name>',
					'<email_address>' . $this->escape( $depositor_email ) . '</email_address>',
					'</depositor>',
					'<registrant>' . $this->escape( $registrant ) . '</registrant>',
					'</head>',
				),
			);
		}

		/**
		 * Returns the rendered posted_date section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered posted_date tag
		 */
		protected function render_posted_date( $post ) {
			$year  = get_the_date( 'Y', $post );
			$month = get_the_date( 'm', $post );
			$day   = get_the_date( 'd', $post );

			return implode(
				'',
				array(
					'<posted_date media_type="online">',
					'<month>' . $this->escape( $month ) . '</month>',
					'<day>' . $this->escape( $day ) . '</day>',
					'<year>' . $this->escape( $year ) . '</year>',
					'</posted_date>',
				),
			);
		}

		/**
		 * Generates a DOI for a post as a sha256 hash calculated from basic metadata of the post: the blog
		 * title, post title, post publication date and post id.
		 *
		 * It is not explicitly checked whether this DOI is unique. However, the chances are very slim.
		 *
		 * @param WP_Post $post the post.
		 * @return string the generated DOI
		 */
		protected function generate_doi( $post ) {
			$doi_prefix        = $this->common->get_settings_field_value( 'doi_prefix' );
			$doi_suffix_length = (int) $this->common->get_settings_field_value( 'doi_suffix_length' );
			$doi_suffix_length = $doi_suffix_length < 12 ? 12 : $doi_suffix_length;
			$doi_suffix_length = $doi_suffix_length > 64 ? 64 : $doi_suffix_length;

			if ( empty( $doi_prefix ) ) {
				$this->last_error = 'doi prefix is required';
				return false;
			}

			$blog_title = get_bloginfo( 'name' );
			$title      = get_the_title( $post );
			$date       = get_the_date( 'Y-m-d H:i:s', $post );
			$suffix_str = $blog_title . $title . $date . $post->ID;
			$doi_suffix = hash( 'sha256', $suffix_str, false );

			return $doi_prefix . '/' . substr( $doi_suffix, 0, $doi_suffix_length );
		}

		/**
		 * Returns preferrably the stored DOI or the generated DOI for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return string the stored or generated DOI
		 */
		protected function get_or_generate_doi( $post ) {
			$stored_doi = $this->common->get_post_meta_field_value( 'doi_meta_key', $post );
			if ( empty( $stored_doi ) ) {
				return $this->generate_doi( $post );
			}
			return $stored_doi;
		}

		/**
		 * Returns the rendered doi_data section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered doi_data section
		 */
		protected function render_doi_data( $post ) {
			$permalink = get_the_permalink( $post );
			$doi       = $this->get_or_generate_doi( $post );

			if ( empty( $doi ) ) {
				return false;
			}

			return implode(
				'',
				array(
					'<doi_data>',
					'<doi>' . $this->escape( $doi ) . '</doi>',
					'<resource content_version="vor" mime_type="text/html">',
					$this->escape( $permalink ),
					'</resource>',
					'</doi_data>',
				),
			);
		}

		/**
		 * Returns the rendered affiliations section for an author.
		 *
		 * @param array $affiliation the affiliation array provided by the "vb-author-affiliation" plugin containing
		 *                           the name and rorid of the affiliation.
		 * @return string the rendered affiliations section
		 */
		protected function render_post_author_affiliation( $affiliation ) {
			$name  = $affiliation['name'] ?? false;
			$rorid = $affiliation['rorid'] ?? false;

			if ( ! empty( $name ) ) {
				return implode(
					'',
					array(
						'<affiliations>',
						'<institution>',
						'<institution_name>' . $this->escape( $name ) . '</institution_name>',
						! empty( $rorid ) ?
							'<institution_id type="ror">https://ror.org/' . $this->escape( $rorid ) . '</institution_id>' : '',
						'</institution>',
						'</affiliations>',
					),
				);
			}
			return '';
		}

		/**
		 * Return the rendered person_name section of the deposit XML.
		 *
		 * @param array  $name the author name as array (surname, given_name).
		 * @param string $orcid the orcid of the author.
		 * @param array  $affiliation the affiliation array as provided by the "vb-author-affiliations" plugin.
		 * @param bool   $is_main whether this author is the main author.
		 * @return string the rendered person_name section
		 */
		protected function render_post_author( $name, $orcid, $affiliation, $is_main ) {
			if ( empty( $name['surname'] ) ) {
				$this->last_error = 'author needs to provide a surname (last name)';
				return false;
			}

			if ( $is_main ) {
				$sequence = 'first';
			} else {
				$sequence = 'additional';
			}

			return implode(
				'',
				array(
					'<person_name sequence="' . $sequence . '" contributor_role="author">',
					! empty( $name['given_name'] ) ? '<given_name>' . $this->escape( $name['given_name'] ) . '</given_name>' : '',
					'<surname>' . $this->escape( $name['surname'] ) . '</surname>',
					$this->render_post_author_affiliation( $affiliation ),
					! empty( $orcid ) ? '<ORCID>https://orcid.org/' . $this->escape( $orcid ) . '</ORCID>' : '',
					'</person_name>',
				),
			);
		}

		/**
		 * Retrieves the author affiliations array for a post from the "vb-author-affiliations" plugin if available.
		 *
		 * @param WP_Post $post the post.
		 * @return array the author affiliations array providing the affiliation name and rorid for multiple authors
		 */
		protected function get_all_author_affiliations( $post ) {
			if ( ! function_exists( 'get_the_vb_author_affiliations' ) ) {
				return array();
			}
			return get_the_vb_author_affiliations( $post );
		}

		/**
		 * Return the author affiliations array for a specific author.
		 *
		 * @param WP_Post $post the post.
		 * @param int     $userid the user id of the author.
		 * @return array the author affiliation information for this particular author
		 */
		protected function get_affiliation_of_author( $post, $userid ) {
			$author_affiliations = $this->get_all_author_affiliations( $post );
			if ( array_key_exists( $userid, $author_affiliations ) ) {
				return $author_affiliations[ $userid ];
			}
			return array();
		}

		/**
		 * Return the rendered person_name section for the main post author.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered person_name section
		 */
		protected function render_post_main_author( $post ) {
			$name        = $this->get_author_name( $post->post_author );
			$orcid       = $this->common->get_user_meta_field_value( 'orcid_meta_key', $post->post_author );
			$affiliation = $this->get_affiliation_of_author( $post, $post->post_author );
			return $this->render_post_author( $name, $orcid, $affiliation, true );
		}

		/**
		 * Return the rendered person_name sections for all coauthors as array of strings.
		 *
		 * @param WP_Post $post the post.
		 * @return array the rendered person_name sections as array of strings
		 */
		protected function render_post_coauthors( $post ) {
			$coauthors = $this->get_post_coauthors( $post );
			$result    = array();
			foreach ( $coauthors as $coauthor ) {
				$name        = $this->get_coauthor_name( $coauthor );
				$orcid       = $this->common->get_user_meta_field_value( 'orcid_meta_key', $coauthor->ID );
				$affiliation = $this->get_affiliation_of_author( $post, $coauthor->ID );
				$result      = array_merge( $result, array( $this->render_post_author( $name, $orcid, $affiliation, false ) ) );
			}
			return $result;
		}

		/**
		 * Return the rendered contributors section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered contributors section
		 */
		protected function render_contributors( $post ) {
			$contributors = implode(
				'',
				array_values(
					array_filter(
						array_merge(
							array( $this->render_post_main_author( $post ) ),
							$this->render_post_coauthors( $post )
						),
					),
				),
			);

			if ( empty( $contributors ) ) {
				return '';
			}

			return implode(
				'',
				array(
					'<contributors>',
					$contributors,
					'</contributors>',
				),
			);
		}

		/**
		 * Return the rendered institution_id section of the deposit XML if a wikidata ID is available.
		 *
		 * @param string $wikidata the wikidata ID.
		 * @return string the rendered institution section
		 */
		protected function render_institution_wikidata_id( $wikidata ) {
			if ( ! empty( $wikidata ) ) {
				return implode(
					'',
					array(
						'<institution_id type="wikidata">',
						'https://www.wikidata.org/entity/',
						$this->escape( $wikidata ),
						'</institution_id>',
					),
				);
			}
			return '';
		}

		/**
		 * Return the rendered institution_id section of the deposit XML if a ISNI is available.
		 *
		 * @param string $isni the ISNI.
		 * @return string the rendered institution section
		 */
		protected function render_institution_isni( $isni ) {
			if ( ! empty( $isni ) ) {
				return implode(
					'',
					array(
						'<institution_id type="isni">',
						'https://www.isni.org/',
						$this->escape( $isni ),
						'</institution_id>',
					),
				);
			}
			return '';
		}

		/**
		 * Return the rendered institution_id section of the deposit XML if a ROR ID is available.
		 *
		 * @param string $rorid the ROR ID.
		 * @return string the rendered institution section
		 */
		protected function render_institution_rorid( $rorid ) {
			if ( ! empty( $rorid ) ) {
				return implode(
					'',
					array(
						'<institution_id type="isni">',
						'https://ror.org/',
						$this->escape( $rorid ),
						'</institution_id>',
					),
				);
			}
			return '';
		}

		/**
		 * Return the rendered institution section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered institution section
		 */
		protected function render_institution( $post ) {
			$rorid    = $this->common->get_settings_field_value( 'institution_rorid' );
			$isni     = $this->common->get_settings_field_value( 'institution_isni' );
			$wikidata = $this->common->get_settings_field_value( 'institution_wikidata_id' );
			$name     = $this->common->get_settings_field_value( 'institution_name' );

			if ( empty( $rorid ) && empty( $isni ) && empty( $wikidata ) ) {
				// there is no id available, but an id is required by CrossRef.
				return '';
			}

			return implode(
				'',
				array(
					'<institution>',
					! empty( $name ) ? '<institution_name>' . $this->escape( $name ) . '</institution_name>' : '',
					$this->render_institution_rorid( $rorid ),
					$this->render_institution_isni( $isni ),
					$this->render_institution_wikidata_id( $wikidata ),
					'</institution>',
				),
			);
		}

		/**
		 * Generates a Creative Commons License URL from a simple short name (e.g. "CC BY-SA").
		 *
		 * @param string $copyright the license name.
		 * @return string the license URL, if the name could be recognized, otherwise an empty string
		 */
		protected function get_creative_commons_link_from_name( $copyright ) {
			$lowercase        = strtolower( $copyright );
			$no_minus         = str_replace( '-', ' ', $lowercase );
			$no_double_spaces = str_replace( '  ', ' ', $no_minus );
			$trimmed          = trim( $no_double_spaces );

			if ( $this->common->starts_with( $trimmed, 'cc0' ) ) {
				return 'https://creativecommons.org/publicdomain/zero/1.0/legalcode';
			}

			$available_versions = array( '4.0', '3.0', '2.5', '2.0', '1.0' );
			$available_variants = array( 'by', 'by-sa', 'by-nc', 'by-nc-sa', 'by-nd', 'by-nc-nd' );

			preg_match( '/^cc(\D+)([1234]\.[05])?$/', $trimmed, $matches );
			if ( ! $matches ) {
				return '';
			}
			$variant = trim( $matches[1] );
			$version = trim( $matches[2] );
			if ( ! in_array( $version, $available_versions, true ) ) {
				$version = '4.0';
			}
			$variant = str_replace( ' ', '-', $variant );
			if ( ! in_array( $variant, $available_variants, true ) ) {
				return '';
			}

			return 'https://creativecommons.org/licenses/' . $variant . '/' . $version . '/legalcode';
		}

		/**
		 * Return the copyright link by either retrieving it from the post meta custom field or generating it from the
		 * license name.
		 *
		 * @param WP_Post $post the post.
		 * @return string the license URL or an empty string
		 */
		protected function get_copyright_link( $post ) {
			// use post-specific link if provided.
			$copyright_link_custom = $this->common->get_post_meta_field_value( 'copyright_link_meta_key', $post );
			if ( ! empty( $copyright_link_custom ) ) {
				return $copyright_link_custom;
			}

			// generate link from post-specific name if provided.
			$copyright_name_custom = $this->common->get_post_meta_field_value( 'copyright_name_meta_key', $post );
			if ( ! empty( $copyright_name_custom ) ) {
				return $this->get_creative_commons_link_from_name( $copyright_name_custom );
			}

			// use general link if provided.
			$copyright_link_general = $this->common->get_settings_field_value( 'copyright_link_general' );
			if ( ! empty( $copyright_link_general ) ) {
				return $copyright_link_general;
			}

			// generate link from general name if provided.
			$copyright_name_general = $this->common->get_settings_field_value( 'copyright_name_general' );
			if ( ! empty( $copyright_name_general ) ) {
				return $this->get_creative_commons_link_from_name( $copyright_name_general );
			}
			return '';
		}

		/**
		 * Return the rendered copyright section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered copyright section
		 */
		protected function render_copyright( $post ) {
			$copyright_link = $this->get_copyright_link( $post );
			if ( ! empty( $copyright_link ) ) {
				return implode(
					'',
					array(
						'<program xmlns="http://www.crossref.org/AccessIndicators.xsd">',
						'<license_ref>',
						$copyright_link,
						'</license_ref>',
						'</program>',
					)
				);
			}
			return '';
		}

		/**
		 * Return the rendered relation section of the deposit XML containing the ISSN.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered relation section
		 */
		protected function render_issn( $post ) {
			$issn = $this->common->get_settings_field_value( 'issn' );
			if ( ! empty( $issn ) ) {
				return implode(
					'',
					array(
						'<program xmlns="http://www.crossref.org/relations.xsd">',
						'<related_item>',
						'<inter_work_relation relationship-type="isPartOf" identifier-type="issn">',
						$this->escape( $issn ),
						'</inter_work_relation>',
						'</related_item>',
						'</program>',
					),
				);
			}
			return '';
		}

		/**
		 * Return the rendered post excerpt as abstract of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered abstract section
		 */
		protected function render_excerpt( $post ) {
			$include_excerpt = $this->common->get_settings_field_value( 'include_excerpt' );
			if ( $include_excerpt ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
				if ( ! empty( $excerpt ) ) {
					return '<jats:abstract><jats:p>' . $this->escape( $excerpt ) . '</jats:p></jats:abstract>';
				}
			}
			return '';
		}

		/**
		 * Return the rendered posted_content section of the deposit XML.
		 *
		 * @param WP_Post $post the post.
		 * @return string the rendered posted_content section
		 */
		protected function render_posted_content( $post ) {

			$title    = get_the_title( $post );
			$doi_data = $this->render_doi_data( $post );

			if ( empty( $doi_data ) ) {
				return false;
			}

			return implode(
				'',
				array(
					'<posted_content type="preprint">',
					$this->render_contributors( $post ),
					'<titles>',
					'<title>' . $this->escape( $title ) . '</title>',
					'</titles>',
					$this->render_posted_date( $post ),
					$this->render_institution( $post ),
					$this->render_excerpt( $post ),
					$this->render_copyright( $post ),
					$this->render_issn( $post ),
					$doi_data,
					'</posted_content>',
				),
			);
		}

		/**
		 * Return the last error that occurred while rendering a post.
		 *
		 * @return string the last error message
		 */
		public function get_last_error() {
			return '[Post id=' . $this->last_post->ID . '] ' . $this->last_error;
		}

		/**
		 * Render the full deposit XML for a post.
		 *
		 * @param WP_Post $post the post.
		 * @param int     $submit_timestamp the UTC timestamp of the submission date.
		 * @return string the rendered deposit XML
		 */
		public function render( $post, $submit_timestamp ) {
			// documentation see: https://data.crossref.org/reports/help/schema_doc/5.3.1/index.html .
			// examples see: https://www.crossref.org/xml-samples/ .

			$this->last_post  = $post;
			$this->last_error = null;

			$head           = $this->render_head( $post, $submit_timestamp );
			$posted_content = $this->render_posted_content( $post );

			if ( empty( $head ) || empty( $posted_content ) ) {
				return false;
			}

			$xml_str = implode(
				'',
				array(
					'<?xml version="1.0" encoding="UTF-8"?>
					<doi_batch xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
						xsi:schemaLocation="http://www.crossref.org/schema/5.3.0 https://www.crossref.org/schemas/crossref5.3.0.xsd"
						xmlns="http://www.crossref.org/schema/5.3.0" xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1"
						xmlns:fr="http://www.crossref.org/fundref.xsd" xmlns:mml="http://www.w3.org/1998/Math/MathML" version="5.3.0">',
					$head,
					'<body>',
					$posted_content,
					'</body>',
					'</doi_batch>',
				),
			);

			return $this->common->format_xml( $xml_str );
		}

	}

}
