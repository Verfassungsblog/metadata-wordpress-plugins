<?php
/**
 * Class that renders DOAJ article json.
 *
 * See:
 * - Api Documentation: https://doaj.org/api/v3/docs
 * - Json Data Format: https://doaj.github.io/doaj-docs/master/data_models/IncomingAPIArticle
 * - DOAJ Validation Code: https://github.com/DOAJ/doaj/blob/bc9187c6dfaf4c4ad552baac1c874cd257ca1468/portality/api/current/data_objects/article.py#L195
 * - DOAJ Article Creation Test: https://github.com/DOAJ/doaj/blob/752f8fe34d2a09846aa4af6a0900fb7db285cce7/doajtest/unit/test_create_article.py
 * - DOAJ Journal Metadata Code: https://github.com/DOAJ/doaj/blob/bc9187c6dfaf4c4ad552baac1c874cd257ca1468/portality/models/article.py#L247
 *
 * @package vb-doaj-submit
 */

/**
 * Class Imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-common.php';

if ( ! class_exists( 'VB_DOAJ_Submit_Render' ) ) {

	/**
	 * Class that renders DOAJ article json.
	 */
	class VB_DOAJ_Submit_Render {
		/**
		 * Common methods
		 *
		 * @var VB_DOAJ_Submit_Common
		 */
		protected $common;

		/**
		 * Last error message that occurred while rendering DOAJ json
		 *
		 * @var string
		 */
		protected $last_error;

		/**
		 * Last post that was used to render a DOAJ json
		 *
		 * @var WP_Post
		 */
		protected $last_post;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_DOAJ_Submit_Common( $plugin_name );
		}

		/**
		 * Escape function that is used throughout this class.
		 *
		 * Only decodes html entities to UTF-8 characters. The actual escaping is done by wp_json_encode.
		 *
		 * @param string $str the string to be escaped.
		 * @return string the escaped string
		 */
		protected function escape( $str ) {
			return html_entity_decode( $str );
		}

		/**
		 * Return the name of the post author in the format of first name last name. 
		 * In case only a first name or last name is available, return that. 
		 *
		 * @param int $author user id of the post author.
		 * @return string the name of the post author
		 */
		protected function get_author_name( $author ) {
			$last_name  = get_the_author_meta( 'last_name', $author );
			$first_name = get_the_author_meta( 'first_name', $author );

			$author = '';
			if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
				$author = $first_name . ' ' . $last_name;
			} elseif ( ! empty( $last_name ) ) {
				$author = $last_name;
			} elseif ( ! empty( $first_name ) ) {
				$author = $first_name;
			}
			return $author;
		}

		/**
		 * Return the name of a post coauthor in the format of first name last name.
		 *
		 * @param object $coauthor coauthor object provided by the 'co-author-plus' plugin.
		 * @return string the name of the coauthor
		 */
		protected function get_coauthor_name( $coauthor ) {
			$last_name  = $coauthor->last_name;
			$first_name = $coauthor->first_name;

			$author = '';
			if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
				$author = $first_name . ' ' . $last_name;
			} elseif ( ! empty( $last_name ) ) {
				$author = $last_name;
			}
			return $author;
		}

		/**
		 * Returns the ORCID of a user, which is stored in a user meta custom field.
		 *
		 * @param int $user_id the id of the user whose ORCID is returned.
		 * @return string the ORCID of the user if available
		 */
		protected function get_orcid( $user_id ) {
			return $this->common->get_user_meta_field_value( 'orcid_meta_key', $user_id );
		}

		/**
		 * Returns the list of coauthor object provided by the "co-author-plus" plugin.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of coauthor objects for the post
		 */
		protected function get_post_coauthors( $post ) {
			if ( ! function_exists( 'get_coauthors' ) ) {
				return array();
			}
			return array_slice( get_coauthors( $post->ID ), 1 );
		}

		/**
		 * Returns the excerpt of a post as abstract if the corresponding admin setting is enabled.
		 *
		 * @param WP_Post $post the post.
		 * @return string the post excerpt
		 */
		protected function get_abstract( $post ) {
			$include_excerpt = $this->common->get_settings_field_value( 'include_excerpt' );
			if ( ! $include_excerpt ) {
				return false;
			}
			return wp_strip_all_tags( get_the_excerpt( $post ) );
		}

		/**
		 * Returns the author affiliations as array (user_id => (name => string, rorid => string)) if
		 * the "vb-author-affiliations" plugin is enabled.
		 *
		 * @param WP_Post $post the post.
		 * @return array the author affilations as array
		 */
		protected function get_all_author_affiliations( $post ) {
			if ( ! function_exists( 'get_the_vb_author_affiliations' ) ) {
				return array();
			}
			return get_the_vb_author_affiliations( $post );
		}

		/**
		 * Returns the affiliation name for an author based on the author affiliations provided by the
		 * "vb-author-affiliations" plugin.
		 *
		 * @param WP_Post $post the post.
		 * @param int     $userid the id of the user whose affiliation is requested.
		 * @return string the user's affiliation if available
		 */
		protected function get_affiliation_name_for_author( $post, $userid ) {
			$author_affiliations = $this->get_all_author_affiliations( $post );
			if ( array_key_exists( $userid, $author_affiliations ) ) {
				$name = $author_affiliations[ $userid ]['name'] ?? false;
				if ( ! empty( $name ) ) {
					return $name;
				}
			}
			return false;
		}

		/**
		 * Return the author information array that will be submitted to the DOAJ.
		 *
		 * @param string $name the name of the author.
		 * @param string $orcid the ORCID of the author.
		 * @param string $affiliation_name the name of the author's affiliation.
		 * @return array the array that contains all information for an author to be converted to JSON
		 */
		protected function render_author( $name, $orcid, $affiliation_name ) {
			if ( empty( $name ) ) {
				return array();
			}
			return array_filter(
				array(
					'name'        => $this->escape( $name ),
					'orcid_id'    => ! empty( $orcid ) ? $this->escape( 'https://orcid.org/' . $orcid ) : false,
					'affiliation' => $this->escape( $affiliation_name ),
				),
			);
		}

		/**
		 * Returns the author information for the main post author.
		 *
		 * @param WP_Post $post the post.
		 * @return array the author information for the main post author
		 */
		protected function render_post_author( $post ) {
			$post_author_name             = $this->get_author_name( $post->post_author );
			$post_author_orcid            = $this->get_orcid( $post->post_author );
			$post_author_affiliation_name = $this->get_affiliation_name_for_author( $post, $post->post_author );
			return $this->render_author( $post_author_name, $post_author_orcid, $post_author_affiliation_name );
		}

		/**
		 * Return the array of all coauthor information for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array a list of coauthor information that will be converted to JSON
		 */
		protected function render_post_coauthors( $post ) {
			$coauthors = $this->get_post_coauthors( $post );
			$result    = array();
			foreach ( $coauthors as $coauthor ) {
				$coauthor_name             = $this->get_coauthor_name( $coauthor );
				$coauthor_orcid            = $this->get_orcid( $coauthor->ID );
				$coauthor_affiliation_name = $this->get_affiliation_name_for_author( $post, $coauthor->ID );
				$coauthor_result           = $this->render_author( $coauthor_name, $coauthor_orcid, $coauthor_affiliation_name );
				$result                    = array_merge( $result, array( $coauthor_result ) );
			}
			return $result;
		}

		/**
		 * Return both main author and coauthor information as a list.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of both main author and coauthor information to be converted to JSON
		 */
		protected function render_authors( $post ) {
			return array_values(
				array_filter(
					array_merge(
						array( $this->render_post_author( $post ) ),
						$this->render_post_coauthors( $post )
					),
				),
			);
		}

		/**
		 * Return the list of post tags that are submitted as free-text keywords to the DOAJ.
		 *
		 * Includes GND subjects in case the GND taxonomy is enabled via the "vb-gnd-taxonomy" plugin.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of post tags
		 */
		public function render_keywords( $post ) {
			$include_tags = $this->common->get_settings_field_value( 'include_tags' );
			if ( ! $include_tags ) {
				return array();
			}
			$tags = get_the_tags( $post );
			$tags = $tags ? $tags : array();

			$gnd_terms = get_the_terms( $post, 'gnd' );
			$gnd_terms = ! empty( $gnd_terms ) && ! is_wp_error( $gnd_terms ) ? $gnd_terms : array();
			if ( ! empty( $gnd_terms ) ) {
				$tags = array_merge( $tags, $gnd_terms );
			}

			$keywords = array();
			foreach ( $tags as $tag ) {
				$keywords = array_merge( $keywords, array( $this->escape( $tag->name ) ) );
			}
			return array_slice( array_values( array_filter( $keywords ) ), 0, 6 );
		}

		/**
		 * Return electronic ISSN stored in the admin settings.
		 *
		 * @param WP_Post $post the post.
		 * @return string array containing the eISSN to be converted to JSON
		 */
		protected function render_eissn( $post ) {
			$eissn = $this->common->get_settings_field_value( 'eissn' );
			if ( empty( $eissn ) ) {
				return false;
			}
			return array(
				'id'   => $this->escape( $eissn ),
				'type' => 'eissn',
			);
		}

		/**
		 * Return print ISSN stored in the admin settings.
		 *
		 * @param WP_Post $post the post.
		 * @return array|bool array containing the pISSN to be converted to JSON
		 */
		protected function render_pissn( $post ) {
			$pissn = $this->common->get_settings_field_value( 'pissn' );
			if ( empty( $pissn ) ) {
				return false;
			}
			return array(
				'id'   => $this->escape( $pissn ),
				'type' => 'pissn',
			);
		}

		/**
		 * Return the DOI for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array|bool array containing the DOI of a post to be converted to JSON
		 */
		protected function render_doi( $post ) {
			$doi = $this->common->get_post_meta_field_value( 'doi_meta_key', $post );
			if ( empty( $doi ) ) {
				return false;
			}
			return array(
				'type' => 'doi',
				'id'   => $this->escape( $doi ),
			);
		}

		/**
		 * Return all identifier for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array the array containg all identifier for a post to be converted to JSON
		 */
		protected function render_identifier( $post ) {
			return array_values(
				array_filter(
					array(
						$this->render_eissn( $post ),
						$this->render_pissn( $post ),
						$this->render_doi( $post ),
					),
				),
			);
		}

		/**
		 * Return issue number for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array the array containg the issue number for a post to be converted to JSON
		 */
		protected function get_issue_number( $post ) {
			$issue_general = $this->common->get_settings_field_value( 'issue' );
			$issue_custom  = $this->common->get_post_meta_field_value( 'issue_meta_key', $post );
			$issue         = ! empty( $issue_custom ) ? $issue_custom : $issue_general;
			return empty( $issue ) ? false : $issue;
		}

		/**
		 * Return volume for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array the array containg the volume for a post to be converted to JSON
		 */
		protected function get_volume( $post ) {
			$volume_general = $this->common->get_settings_field_value( 'volume' );
			$volume_custom  = $this->common->get_post_meta_field_value( 'volume_meta_key', $post );
			$volume         = ! empty( $volume_custom ) ? $volume_custom : $volume_general;
			return empty( $volume ) ? false : $volume;
		}

		/**
		 * Return journal information for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array the array containg the journal information for a post to be converted to JSON
		 */
		protected function render_journal( $post ) {
			return array_filter(
				array(
					'number' => $this->escape( $this->get_issue_number( $post ) ),
					'volume' => $this->escape( $this->get_volume( $post ) ),
					// title is overwritten by DOAJ.
					// publisher is overwritten by DOAJ.
					// country is overwritten by DOAJ.
					// language list is overwritten by DOAJ.
				),
			);
		}

		/**
		 * Return title for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return array|false the array containg the title for a post to be converted to JSON
		 */
		protected function get_title( $post ) {
			$title               = get_the_title( $post );
			$include_subheadline = $this->common->get_settings_field_value( 'include_subheadline' );
			if ( $include_subheadline ) {
				$subheadline = $this->common->get_post_meta_field_value( 'subheadline_meta_key', $post );
				if ( ! empty( $subheadline ) ) {
					$title = $title . ' - ' . $subheadline;
				}
			}

			return empty( $title ) ? false : $title;
		}

		/**
		 * Return the last error that occurred while rendering the DOAJ json.
		 *
		 * @return string the last error message
		 */
		public function get_last_error() {
			return '[Post id=' . $this->last_post->ID . '] ' . $this->last_error;
		}

		/**
		 * Render metadata of a post as JSON that will be submitted to the DOAJ.
		 *
		 * @param WP_Post $post the post.
		 * @return string the JSON as string
		 */
		public function render( $post ) {
			// random example: https://doaj.org/api/articles/831bac8cf73e4f2f855189504d29f54d .
			// verfassungsblog example: https://doaj.org/api/articles/19f5e959bca74eb29f303e6bf4078640 .
			$this->last_error = null;
			$this->last_post  = $post;

			// rewrite staging permalinks.
			$permalink = get_the_permalink( $post );
			$permalink = str_replace( 'staging.verfassungsblog.de', 'verfassungsblog.de', $permalink );

			$json_data = array(
				'bibjson' => array_filter(
					array(
						'title'      => $this->escape( $this->get_title( $post ) ),
						'author'     => $this->render_authors( $post ),
						'identifier' => $this->render_identifier( $post ),
						'abstract'   => $this->escape( $this->get_abstract( $post ) ),
						'journal'    => $this->render_journal( $post ),
						'keywords'   => $this->render_keywords( $post ),
						'link'       => array(
							array(
								'url'          => $this->escape( $permalink ),
								'content_type' => 'HTML', // see FAQ at https://doaj.org/api/v3/docs .
								'type'         => 'fulltext',
							),
						),
						'month'      => get_the_time( 'm', $post ),
						'year'       => get_the_time( 'Y', $post ),
						// subject list is overwritten by DOAJ.
					),
				),
			);

			// check mandatory fields.
			if ( ! isset( $json_data['bibjson']['title'] ) || empty( $json_data['bibjson']['title'] ) ) {
				$this->last_error = 'article has no title';
				return false;
			}

			if ( ! isset( $json_data['bibjson']['author'] )
				|| count( $json_data['bibjson']['author'] ) < 1
				|| empty( $json_data['bibjson']['author'][0]['name'] ) ) {
				$this->last_error = 'article has no author or author name is empty';
				return false;
			}

			if ( ! isset( $json_data['bibjson']['identifier'] )
				|| count( $json_data['bibjson']['identifier'] ) < 1
				|| empty( $json_data['bibjson']['identifier'][0]['id'] ) ) {
				$this->last_error = 'article has no identifier or identifier is empty';
				return false;
			}
			return wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}
	}
}
