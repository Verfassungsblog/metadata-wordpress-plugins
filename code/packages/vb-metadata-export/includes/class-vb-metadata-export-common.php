<?php
/**
 * Class that provides common and utility methods.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-oai-pmh.php';

if ( ! class_exists( 'VB_Metadata_Export_Common' ) ) {

	/**
	 * Class that provides common and utility methods.
	 */
	class VB_Metadata_Export_Common {
		/**
		 * The name of the plugin as string.
		 *
		 * @var string
		 */
		public $plugin_name;

		/**
		 * Associative array of settings fields and their default values (field name => default value).
		 *
		 * @var array
		 */
		protected $setting_field_defaults;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->plugin_name = $plugin_name;

			$blog_title = get_bloginfo( 'name' );

			if ( 'Verfassungsblog' === $blog_title ) {
				// default settings for Verfassungsblog.
				$this->setting_field_defaults = array(
					// general.
					'blog_owner'                          => 'Max Steinbeis Verfassungsblog gGmbH',
					'blog_title'                          => $blog_title,
					'issn'                                => '2366-7044',
					'publisher'                           => $blog_title,
					'require_doi'                         => true,
					'include_excerpt'                     => true,
					'include_subheadline'                 => false,
					'ddc_general'                         => '342',
					'copyright_general'                   => 'CC BY-SA 4.0',
					'funding_general'                     => 'funded by the government',

					// language.
					'language'                            => 'ger',
					'language_alternate'                  => 'eng',
					'language_alternate_category'         => 'English Articles',

					// content types.
					'podcast_category'                    => 'Podcast',

					// theme.
					'template_priority'                   => 120,

					// custom fields.
					'doi_meta_key'                        => 'doi',
					'subheadline_meta_key'                => 'subheadline',
					'orcid_meta_key'                      => 'orcid',
					'gndid_meta_key'                      => 'gndid',
					'ddc_meta_key'                        => 'ddc',
					'copyright_meta_key'                  => 'copyright',
					'funding_meta_key'                    => 'funding',

					// meta tags
					'metatags_enabled'                    => true,
					'metatags_dc_enabled'                 => true,
					'metatags_hw_enabled'                 => true,

					// marc21xml.
					'marc21xml_enabled'                   => true,
					'marc21_doi_as_control_number'        => true,
					'marc21_control_number_identifier'    => 'DE-Verfassungsblog',
					'marc21_leader'                       => '_____nam__22_____uu_4500',
					'marc21_physical_description'         => 'cr|||||',
					'marc21_content_type'                 => 'Text,txt,rdacontent',
					'marc21_podcast_leader'               => '_____nim__22_____uu_4500',
					'marc21_podcast_physical_description' => 'sr|||||',
					'marc21_podcast_content_type'         => 'Spoken Word,spw,rdacontent',

					// mods.
					'mods_enabled'                        => true,

					// oai-pmh.
					'oai-pmh_enabled'                     => true,
					'oai-pmh_admin_email'                 => 'admin@example.com',
					'oai-pmh_list_size'                   => 10,

					// dublin core.
					'dc_enabled'                          => true,
				);
			} else {
				// default settings for any other blog than Verfassungsblog.
				$this->setting_field_defaults = array(
					// general.
					'blog_title'                          => $blog_title,
					'require_doi'                         => false,
					'include_excerpt'                     => false,

					// language.
					'language'                            => 'eng',

					// theme.
					'template_priority'                   => 10,

					// meta tags
					'metatags_enabled'                    => true,
					'metatags_dc_enabled'                 => true,
					'metatags_hw_enabled'                 => true,

					// marc21xml.
					'marc21xml_enabled'                   => true,
					'marc21_doi_as_control_number'        => false,
					'marc21_leader'                       => '_____nam__22_____uu_4500',
					'marc21_physical_description'         => 'cr|||||',
					'marc21_content_type'                 => 'Text,txt,rdacontent',
					'marc21_podcast_leader'               => '_____nim__22_____uu_4500',
					'marc21_podcast_physical_description' => 'sr|||||',
					'marc21_podcast_content_type'         => 'Spoken Word,spw,rdacontent',

					// mods.
					'mods_enabled'                        => true,

					// oai-pmh.
					'oai-pmh_enabled'                     => true,
					'oai-pmh_list_size'                   => 10,

					// dublin core.
					'dc_enabled'                          => true,
				);
			}
		}

		/**
		 * Checks admin settings for whether a metadata format is requiring a DOI to be allowed for export.
		 *
		 * @param string $format the metadata format (marc21-xml, dc, mods, oai-pmh).
		 * @return bool true if that format is requiring a DOI based on admin settings
		 */
		protected function is_format_requiring_doi( $format ) {
			if ( 'marc21xml' === $format ) {
				return $this->get_settings_field_value( 'require_doi' ) || $this->get_settings_field_value( 'marc21_doi_as_control_number' );
			}
			return $this->get_settings_field_value( 'require_doi' );
		}

		/**
		 * Return the array of available metadata formats (marc21-xml, dc, mods, oai-pmh).
		 *
		 * @return array the list of available metadata formats
		 */
		public function get_available_formats() {
			return array_keys( $this->get_format_labels() );
		}

		/**
		 * Return an associative array of labels for each metadata format.
		 *
		 * @return array the array of (format => label)
		 */
		public function get_format_labels() {
			return array(
				'marc21xml' => 'Marc21 XML',
				'mods'      => 'MODS',
				'dc'        => 'Dublin Core',
				'oai-pmh'   => 'OAI PMH 2.0',
			);
		}

		/**
		 * Checks whether a given format is a valid metadata format.
		 *
		 * @param string $format the format to be checked.
		 * @return bool true, if the provided format is a valid metadata format
		 */
		public function is_valid_format( $format ) {
			return in_array( $format, $this->get_available_formats(), true );
		}

		/**
		 * Checks admin settings for whether a given format is enabled.
		 *
		 * @param string $format the format to be checked.
		 * @return bool true, if the provided format is enabled in the admin settings
		 */
		public function is_format_enabled( $format ) {
			if ( in_array( $format, $this->get_available_formats(), true ) ) {
				return $this->get_settings_field_value( $format . '_enabled' );
			}
			return false;
		}

		/**
		 * Checks whether a metadata format is allowed and available for a given post depending an admin settings and
		 * available information (e.g. DOI) for that post.
		 *
		 * @param string  $format the metadata format.
		 * @param WP_Post $post the post to be checked.
		 * @return bool true, if the post can be exported for the given metadata format
		 */
		public function is_metadata_available( $format, $post ) {
			if ( ! $this->is_format_enabled( $format ) ) {
				return false;
			}
			if ( $this->is_format_requiring_doi( $format ) ) {
				$doi = $this->get_post_doi( $post );
				if ( empty( $doi ) ) {
					return false;
				}
			}
			if ( ! is_post_publicly_viewable( $post ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Return stored admin settings field value.
		 *
		 * @param string $field_name the name of the settings field whose value is supposed to be returned.
		 * @return mixed the value of the settings field
		 */
		public function get_settings_field_value( $field_name ) {
			$default = $this->get_settings_field_default_value( $field_name );
			return get_option( $this->get_settings_field_id( $field_name ), $default );
		}

		/**
		 * Return stored meta field value associated with a post.
		 *
		 * @param string  $field_name the name of the meta field whose value is supposed to be returned.
		 * @param WP_Post $post the post.
		 * @return mixed the meta field value
		 */
		public function get_post_meta_field_value( $field_name, $post ) {
			$meta_key = $this->get_settings_field_value( $field_name );
			if ( empty( $meta_key ) ) {
				return false;
			}
			return get_post_meta( $post->ID, $meta_key, true );
		}

		/**
		 * Return stored meta field value associated with a user.
		 *
		 * @param string $field_name the name of the meta field whose value is supposed to be returned.
		 * @param int    $user_id the id of the user.
		 * @return mixed the meta field value
		 */
		public function get_user_meta_field_value( $field_name, $user_id ) {
			$meta_key = $this->get_settings_field_value( $field_name );
			if ( empty( $meta_key ) ) {
				return false;
			}
			return get_user_meta( $user_id, $meta_key, true );
		}

		/**
		 * Return the id of the admin settings field.
		 *
		 * @param string $field_name the name of the settings field.
		 * @return string the id of the settings field.
		 */
		public function get_settings_field_id( $field_name ) {
			return $this->plugin_name . '_field_' . $field_name . '_value';
		}

		/**
		 * Return the default value for the admin settings field.
		 *
		 * @param string $field_name the name of the settings field.
		 * @return string the default value of the settings field.
		 */
		public function get_settings_field_default_value( $field_name ) {
			if ( array_key_exists( $field_name, $this->setting_field_defaults ) ) {
				return $this->setting_field_defaults[ $field_name ];
			}
			return false;
		}

		/**
		 * Return the permalink to a metadata export for a post in a specific format.
		 *
		 * @param string  $format the metadata format the permalink is supposed to link to.
		 * @param WP_Post $post the post the permalink is supposed to link to.
		 * @return string the permalink to the metadata export for the post
		 */
		public function get_the_permalink( $format, $post ) {
			// check settings.
			if ( ! $this->is_metadata_available( $format, $post ) ) {
				// format must be valid and enabled.
				return false;
			}

			// check permalink.
			$permalink = get_the_permalink( $post ) ?? get_post_permalink( $post );
			if ( empty( $permalink ) ) {
				return false;
			}

			if ( 'oai-pmh' === $format ) {
				$oaipmh = new VB_Metadata_Export_OAI_PMH( $this->plugin_name );
				return $oaipmh->get_permalink( $post );
			}

			// transform permalink.
			if ( strpos( $permalink, '?' ) !== false ) {
				return $permalink . '&' . $this->plugin_name . '=' . $format;
			}
			return $permalink . '?' . $this->plugin_name . '=' . $format;
		}

		/**
		 * Check and pretty-print an XML document.
		 *
		 * @param string $xml_str the XML string that is checked and pretty-printed.
		 * @return string the re-formatted XML string
		 */
		public function format_xml( $xml_str ) {
			$dom                     = new DOMDocument( '1.0' );
			$dom->preserveWhiteSpace = false; // phpcs:ignore
			$dom->formatOutput       = true; // phpcs:ignore
			if ( $dom->loadXML( $xml_str ) ) {
				return $dom->saveXML();
			}
			return false;
		}

		/**
		 * Return doi from the first doi meta field that is not empty.
		 *
		 * @param WP_Post $post the post whose doi is supposed to be returned.
		 * @return string the first available doi for the post or false
		 */
		public function get_post_doi( $post ) {
			$doi_meta_keys = explode(",", $this->get_settings_field_value( 'doi_meta_key' ), 2);
			$doi_meta_keys = array_filter(array_map('trim', $doi_meta_keys));

			foreach ( $doi_meta_keys as $meta_key ) {
				$doi = get_post_meta( $post->ID, $meta_key, true );
				if ( !empty($doi) ) {
					return $doi;
				}
			}

			return false;
		}

		/**
		 * Return the name of the post author in the format of last name, first name.
		 * In case only a first name or last name is available, return that.
		 *
		 * @param int $author user id of the post author.
		 * @return string the name of the post author
		 */
		public function get_author_name( $author ) {
			$last_name  = get_the_author_meta( 'last_name', $author );
			$first_name = get_the_author_meta( 'first_name', $author );

			$author = '';
			if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
				$author = $last_name . ', ' . $first_name;
			} elseif ( ! empty( $last_name ) ) {
				$author = $last_name;
			} elseif ( ! empty( $first_name ) ) {
				$author = $first_name;
			}
			return $author;
		}

		/**
		 * Return coauthor name in the format of last name, first name.
		 *
		 * @param mixed $coauthor the coauthor object.
		 * @return string the name of the coauthor
		 */
		public function get_coauthor_name( $coauthor ) {
			$last_name  = $coauthor->last_name;
			$first_name = $coauthor->first_name;

			$author = '';
			if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
				$author = $last_name . ', ' . $first_name;
			} elseif ( ! empty( $last_name ) ) {
				$author = $last_name;
			}
			return $author;
		}

		/**
		 * Return name of the post author in the format of last name, first name.
		 *
		 * @param WP_Post $post the post.
		 * @return string the name of the post author
		 */
		public function get_post_author_name( $post ) {
			return $this->get_author_name( $post->post_author );
		}

		/**
		 * Return all coauthors of a post as array.
		 *
		 * @param WP_Post $post the post.
		 * @return array the list of coauthor objects (see plugin 'co-authors-plus')
		 */
		public function get_post_coauthors( $post ) {
			if ( ! function_exists( 'get_coauthors' ) ) {
				return array();
			}
			return array_slice( get_coauthors( $post->ID ), 1 );
		}

		/**
		 * Checks whether the post is assigned to a category.
		 *
		 * @param WP_Post $post the post to check.
		 * @param string  $category the name of the category.
		 * @return bool true if the post is assigned to the category
		 */
		public function is_post_in_category( $post, $category ) {
			if ( ! empty( $category ) ) {
				$categories = array_map(
					function ( $category ) {
						return $category->name;
					},
					get_the_category( $post->ID )
				);
				if ( in_array( $category, $categories, true ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Return the post language by checking whether a post is assigned to the alternative language category.
		 *
		 * @param WP_Post $post the post.
		 * @return string the language of the post
		 */
		public function get_post_language( $post ) {
			$language                    = $this->get_settings_field_value( 'language' );
			$language_alternate_category = $this->get_settings_field_value( 'language_alternate_category' );
			if ( $this->is_post_in_category( $post, $language_alternate_category ) ) {
				$language_alternate = $this->get_settings_field_value( 'language_alternate' );
				$language           = $language_alternate;
			}
			return $language;
		}

	}

}
