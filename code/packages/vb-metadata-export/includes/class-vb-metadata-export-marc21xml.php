<?php
/**
 * Class that renders Marc21 XML.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-common.php';

if ( ! class_exists( 'VB_Metadata_Export_Marc21Xml' ) ) {

	/**
	 * Class that renders Marc21 XML.
	 */
	class VB_Metadata_Export_Marc21Xml {
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
		 * Escape function that is used throughout Marc21 XML rendering.
		 *
		 * @param string $str the string that is escaped.
		 */
		protected function escape( $str ) {
			return htmlspecialchars( html_entity_decode( wp_strip_all_tags( $str ) ), ENT_XML1, 'UTF-8' );
		}

		/**
		 * Return rendered XML for a subfield.
		 *
		 * @param string $value the value of the subfield.
		 * @param string $subfield_code the code of the subfield.
		 * @return string rendered XML for the subfield as string
		 */
		protected function render_subfield_from_value( $value, $subfield_code ) {
			if ( ! empty( $value ) ) {
				return '<marc21:subfield code="' . esc_attr( $subfield_code ) . '">' .
					$this->escape( $value ) . '</marc21:subfield>';
			}
			return '';
		}

		/**
		 * Return rendered XML for a subfield based from an settings field.
		 *
		 * @param string $field_name the settings field name.
		 * @param string $subfield_code the code of the subfield.
		 * @return string rendered XML for the subfield as string
		 */
		protected function render_subfield_from_option( $field_name, $subfield_code ) {
			$value = $this->common->get_settings_field_value( $field_name );
			return $this->render_subfield_from_value( $value, $subfield_code );
		}

		/**
		 * Return rendered Marc21 XML leader field.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML leader as string
		 */
		protected function render_leader( $post ) {
			// leader definition see: https://www.loc.gov/marc/bibliographic/bdleader.html .
			$podcast_category = $this->common->get_settings_field_value( 'podcast_category' );
			$podcast_post     = $this->common->is_post_in_category( $post, $podcast_category );
			$leader_field     = $podcast_post ? 'marc21_podcast_leader' : 'marc21_leader';
			$leader           = str_replace( '_', ' ', $this->common->get_settings_field_value( $leader_field ) );
			if ( ! empty( $leader ) ) {
				return '<marc21:leader>' . $this->escape( $leader ) . '</marc21:leader>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 XML identifier control field (003).
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML identifier control field (003) as string
		 */
		protected function render_control_field_identifier( $post ) {
			$identifier = $this->common->get_settings_field_value( 'marc21_control_number_identifier' );

			if ( ! empty( $identifier ) ) {
				return '<marc21:controlfield tag="003">' . $this->escape( $identifier ) . '</marc21:controlfield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 XML physical description control field (007).
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML physical description control field (007) as string
		 */
		protected function render_control_field_physical_description( $post ) {
			$podcast_category = $this->common->get_settings_field_value( 'podcast_category' );
			$podcast_post     = $this->common->is_post_in_category( $post, $podcast_category );
			$pd_field         = $podcast_post ? 'marc21_podcast_physical_description' : 'marc21_physical_description';
			$pd_value         = $this->common->get_settings_field_value( $pd_field );

			if ( ! empty( $pd_value ) ) {
				return '<marc21:controlfield tag="007">' . $this->escape( $pd_value ) . '</marc21:controlfield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 XML control field 008.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML control field 008 as string
		 */
		protected function render_control_field_008( $post ) {
			$date     = get_the_date( 'ymd', $post );
			$date     = empty( $date ) ? '||||||' : $date;
			$year     = get_the_date( 'Y', $post );
			$year     = empty( $year ) ? '||||' : $year;
			$language = $this->common->get_post_language( $post );
			$language = empty( $language ) ? '|||' : $language;
			$code     = "{$date}s{$year}||||xx#|||||o|||| ||| 0|{$language}||";
			return '<marc21:controlfield tag="008">' . $this->escape( $code ) . '</marc21:controlfield>';
		}

		/**
		 * Return rendered Marc21 XML control number field (001).
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML control number field as string
		 */
		protected function render_control_numbers( $post ) {
			// control number definition see: https://www.loc.gov/marc/bibliographic/bd001.html .
			$use_doi = $this->common->get_settings_field_value( 'marc21_doi_as_control_number' );
			if ( $use_doi ) {
				$doi            = $this->common->get_post_doi( $post );
				$control_number = $doi;
			} else {
				$control_number = $post->ID;
			}

			if ( ! empty( $control_number ) ) {
				return implode(
					'',
					array(
						'<marc21:controlfield tag="001">' . $this->escape( $control_number ) . '</marc21:controlfield>',
						$this->render_control_field_identifier( $post ),
						$this->render_control_field_physical_description( $post ),
						$this->render_control_field_008( $post ),
					),
				);
			}
			return '';
		}

		/**
		 * Return rendered Marc21 XML ORCID subfield.
		 *
		 * @param int $user_id the id of the user that contains the ORCID.
		 * @return string the Marc21 XML ORCID subfield as string
		 */
		public function render_subfield_orcid( $user_id ) {
			$orcid = $this->common->get_user_meta_field_value( 'orcid_meta_key', $user_id );
			if ( ! empty( $orcid ) ) {
				$orcid = '(orcid)' . $orcid;
			}
			return $this->render_subfield_from_value( $orcid, '0' );
		}

		/**
		 * Return rendered Marc21 XML GND-ID subfield.
		 *
		 * @param int $user_id the id of the user that contains the GND-ID.
		 * @return string the Marc21 XML GND-ID subfield as string
		 */
		public function render_subfield_gndid( $user_id ) {
			$gndid = $this->common->get_user_meta_field_value( 'gndid_meta_key', $user_id );
			if ( ! empty( $gndid ) ) {
				$gndid = '(DE-588)' . $gndid;
			}
			return $this->render_subfield_from_value( $gndid, '0' );
		}

		/**
		 * Return rendered Marc21 field 024 containing DOI.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 024 containing the DOI as string
		 */
		public function render_datafield_024( $post ) {
			$doi = $this->common->get_post_doi( $post );
			if ( ! empty( $doi ) ) {
				return '<marc21:datafield tag="024" ind1="7" ind2=" ">' .
					'<marc21:subfield code="a">' . $this->escape( $doi ) . '</marc21:subfield>' .
					'<marc21:subfield code="2">doi</marc21:subfield>' .
					'</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 035 containing post id.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 035 as string containing the post id
		 */
		public function render_datafield_035( $post ) {
			$control_number = $post->ID;
			if ( ! empty( $control_number ) ) {
				return '<marc21:datafield tag="035" ind1=" " ind2=" ">' .
					'<marc21:subfield code="a">' . $this->escape( $control_number ) . '</marc21:subfield>' .
					'</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 041 containing the post language.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 041 as string containing the post language
		 */
		public function render_datafield_041( $post ) {
			$language = $this->common->get_post_language( $post );
			if ( ! empty( $language ) ) {
				return '<marc21:datafield tag="041" ind1=" " ind2=" ">' .
					'<marc21:subfield code="a">' . $this->escape( $language ) . '</marc21:subfield>' .
					'</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 fields 084 containing DDC subjects.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML fields 084 as string containing DDC subjects
		 */
		public function render_datafield_084( $post ) {
			$global_ddc   = $this->common->get_settings_field_value( 'ddc_general' );
			$post_ddc     = $this->common->get_post_meta_field_value( 'ddc_meta_key', $post );
			$combined_ddc = array_merge( explode( ',', $global_ddc ), explode( ',', $post_ddc ) );
			$trimmed_ddc  = array_filter( array_map( 'trim', $combined_ddc ) );
			$xml          = '';
			foreach ( $trimmed_ddc as $ddc ) {
				$xml = $xml . '<marc21:datafield tag="082" ind1="0" ind2="4">' .
					'<marc21:subfield code="a">' . $this->escape( $ddc ) . '</marc21:subfield>' .
					'<marc21:subfield code="2">23</marc21:subfield>' .
					'</marc21:datafield>';
			}
			return $xml;
		}

		/**
		 * Return rendered Marc21 field 100 containing post author information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 100 as string containing post author information
		 */
		public function render_datafield_100( $post ) {
			$post_author = $this->common->get_post_author_name( $post );
			if ( ! empty( $post_author ) ) {
				return implode(
					'',
					array(
						'<marc21:datafield tag="100" ind1="1" ind2=" ">',
						'<marc21:subfield code="a">' . $this->escape( $post_author ) . '</marc21:subfield>',
						'<marc21:subfield code="e">Author</marc21:subfield>',
						'<marc21:subfield code="4">aut</marc21:subfield>',
						$this->render_subfield_orcid( $post->post_author ),
						$this->render_subfield_gndid( $post->post_author ),
						'</marc21:datafield>',
					)
				);
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 245 containing the post title and subheadline.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 245 as string containing the post title and subheadline
		 */
		public function render_datafield_245( $post ) {
			$title               = get_the_title( $post );
			$subheadline         = $this->common->get_post_meta_field_value( 'subheadline_meta_key', $post );
			$include_subheadline = $this->common->get_settings_field_value( 'include_subheadline' );
			if ( $include_subheadline && ! empty( $subheadline ) ) {
				$title       = $title . ' - ' . $subheadline;
				$subheadline = '';
			}

			$author_name = $this->common->get_post_author_name( $post );

			$subfields = implode(
				'',
				array(
					$this->render_subfield_from_value( $title, 'a' ),
					$this->render_subfield_from_value( $subheadline, 'b' ),
					$this->render_subfield_from_value( $author_name, 'c' ),
				),
			);

			if ( ! empty( $subfields ) ) {
				return '<marc21:datafield tag="245" ind1="1" ind2="0">' . $subfields . '</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 264 containing publish information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 264 as string containing publish information
		 */
		public function render_datafield_264( $post ) {
			$publisher = $this->common->get_settings_field_value( 'publisher' );
			$date      = get_the_date( 'Y-m-d', $post );

			$subfields = implode(
				'',
				array(
					$this->render_subfield_from_value( $publisher, 'b' ),
					$this->render_subfield_from_value( $date, 'c' ),
				),
			);

			if ( ! empty( $subfields ) ) {
				return '<marc21:datafield tag="264" ind1=" " ind2="1">' . $subfields . '</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 336 containing content type information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 336 as string containing content type information
		 */
		public function render_datafield_336( $post ) {
			$podcast_category   = $this->common->get_settings_field_value( 'podcast_category' );
			$podcast_post       = $this->common->is_post_in_category( $post, $podcast_category );
			$content_type_field = $podcast_post ? 'marc21_podcast_content_type' : 'marc21_content_type';
			$content_type       = $this->common->get_settings_field_value( $content_type_field );
			$content_type_array = explode( ',', $content_type );
			if ( ! empty( $content_type ) && count( $content_type_array ) === 3 ) {
				return implode(
					'',
					array(
						'<marc21:datafield tag="336" ind1=" " ind2=" ">',
						'<marc21:subfield code="a">' . $this->escape( $content_type_array[0] ) . '</marc21:subfield>',
						'<marc21:subfield code="b">' . $this->escape( $content_type_array[1] ) . '</marc21:subfield>',
						'<marc21:subfield code="2">' . $this->escape( $content_type_array[2] ) . '</marc21:subfield>',
						'</marc21:datafield>',
					),
				);
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 337 containing content type information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 337 as string containing content type information
		 */
		public function render_datafield_337( $post ) {
			return '<marc21:datafield tag="337" ind1=" " ind2=" ">
                <marc21:subfield code="a">Computermedien</marc21:subfield>
                <marc21:subfield code="b">c</marc21:subfield>
                <marc21:subfield code="2">rdamedia</marc21:subfield>
            </marc21:datafield>';
		}

		/**
		 * Return rendered Marc21 field 338 containing content type information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 338 as string containing content type information
		 */
		public function render_datafield_338( $post ) {
			return '<marc21:datafield tag="338" ind1=" " ind2=" ">
                <marc21:subfield code="a">Online-Ressource</marc21:subfield>
                <marc21:subfield code="b">cr</marc21:subfield>
                <marc21:subfield code="2">rdacarrier</marc21:subfield>
            </marc21:datafield>';
		}

		/**
		 * Return rendered Marc21 field 520 containing the post excerpt.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 520 as string containing the post excerpt
		 */
		public function render_datafield_520( $post ) {
			$include_excerpt = $this->common->get_settings_field_value( 'include_excerpt' );
			if ( $include_excerpt ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
				if ( ! empty( $excerpt ) ) {
					return '<marc21:datafield tag="520" ind1=" " ind2=" ">' .
						'<marc21:subfield code="a">' . $this->escape( $excerpt ) . '</marc21:subfield>' .
						'</marc21:datafield>';
				}
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 536 containing the funding note.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 536 as string containing the funding note
		 */
		public function render_datafield_536( $post ) {
			$funding_general = $this->common->get_settings_field_value( 'funding_general' );
			$funding_custom  = $this->common->get_post_meta_field_value( 'funding_meta_key', $post );
			$funding         = ! empty( $funding_custom ) ? $funding_custom : $funding_general;
			if ( ! empty( $funding ) ) {
				return implode(
					'',
					array(
						'<marc21:datafield tag="536" ind1=" " ind2=" ">',
						'<marc21:subfield code="a">' . $this->escape( $funding ) . '</marc21:subfield>',
						'</marc21:datafield>',
					),
				);
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 540 containing the licence note
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 540 as string containing the licence note
		 */
		public function render_datafield_540( $post ) {
			$copyright_general = $this->common->get_settings_field_value( 'copyright_general' );
			$copyright_custom  = $this->common->get_post_meta_field_value( 'copyright_meta_key', $post );
			$copyright         = ! empty( $copyright_custom ) ? $copyright_custom : $copyright_general;
			if ( ! empty( $copyright ) ) {
				return implode(
					'',
					array(
						'<marc21:datafield tag="540" ind1=" " ind2=" ">',
						'<marc21:subfield code="a">' . $this->escape( $copyright ) . '</marc21:subfield>',
						'</marc21:datafield>',
					),
				);
			}
			return '';
		}

		/**
		 * Return rendered Marc21 fields 650 containing post tags.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML fields 650 as string containing post tags
		 */
		public function render_datafield_650( $post ) {
			$tags = get_the_tags( $post );
			$tags = ! empty( $tags ) && ! is_wp_error( $tags ) ? $tags : array();
			$xml  = '';
			foreach ( $tags as $tag ) {
				$xml = $xml . '<marc21:datafield tag="650" ind1="1" ind2="4">' .
					'<marc21:subfield code="a">' . $this->escape( $tag->name ) . '</marc21:subfield>' .
					'</marc21:datafield>';
			}

			$gnd_terms = get_the_terms( $post, 'gnd' );
			$gnd_terms = ! empty( $gnd_terms ) && ! is_wp_error( $gnd_terms ) ? $gnd_terms : array();
			foreach ( $gnd_terms as $gnd_term ) {
				$xml = $xml . '<marc21:datafield tag="650" ind1="1" ind2="7">' .
					'<marc21:subfield code="0">(DE-588)' . $this->escape( $gnd_term->slug ) . '</marc21:subfield>' .
					'<marc21:subfield code="2">gnd</marc21:subfield>' .
					'<marc21:subfield code="a">' . $this->escape( $gnd_term->name ) . '</marc21:subfield>' .
					'</marc21:datafield>';
			}
			return $xml;
		}

		/**
		 * Return rendered Marc21 fields 700 containing the post coauthor information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML fields 700 as string containing the post coauthor information
		 */
		public function render_datafield_700( $post ) {
			$coauthors = $this->common->get_post_coauthors( $post );
			$xml       = '';
			foreach ( $coauthors as $coauthor ) {
				$coauthor_name = $this->common->get_coauthor_name( $coauthor );
				if ( empty( $coauthor_name ) ) {
					continue;
				}
				$xml = $xml . implode(
					'',
					array(
						'<marc21:datafield tag="700" ind1="1" ind2=" ">',
						'<marc21:subfield code="a">' . $this->escape( $coauthor_name ) . '</marc21:subfield>',
						'<marc21:subfield code="e">Author</marc21:subfield>',
						'<marc21:subfield code="4">aut</marc21:subfield>',
						$this->render_subfield_orcid( $coauthor->ID ),
						$this->render_subfield_gndid( $coauthor->ID ),
						'</marc21:datafield>',
					),
				);
			}
			return $xml;
		}

		/**
		 * Return rendered Marc21 fields 773 containing the basic blog information.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML fields 773 as string containing the basic blog information
		 */
		public function render_datafield_773( $post ) {
			// definition: https://www.loc.gov/marc/bibliographic/bd773.html .
			$subfield_773a = $this->render_subfield_from_option( 'blog_owner', 'a' );
			$subfield_773t = $this->render_subfield_from_option( 'blog_title', 't' );
			$subfield_773x = $this->render_subfield_from_option( 'issn', 'x' );

			$subfields = $subfield_773a . $subfield_773t . $subfield_773x;
			if ( ! empty( $subfields ) ) {
				return '<marc21:datafield tag="773" ind1="0" ind2=" ">' . $subfields . '</marc21:datafield>';
			}
			return '';
		}

		/**
		 * Return rendered Marc21 field 856 containing the post permalink.
		 *
		 * @param WP_Post $post the post.
		 * @return string the Marc21 XML field 856 as string containing the post permalink
		 */
		public function render_datafield_856( $post ) {
			$post_url = get_the_permalink( $post );
			if ( ! empty( $post_url ) ) {
				return implode(
					'',
					array(
						'<marc21:datafield tag="856" ind1="4" ind2="0">',
						'<marc21:subfield code="u">' . $this->escape( $post_url ) . '</marc21:subfield>',
						'<marc21:subfield code="y">raw object</marc21:subfield>',
						'</marc21:datafield>',
					),
				);
			}
			return '';
		}

		/**
		 * Return rendering of complete Marc21 XML document by combining all partial render functions.
		 *
		 * @param WP_Post $post the post.
		 * @return string the complete Marc21 XML document as string
		 */
		public function render( $post ) {
			// marc21 definition see: https://www.loc.gov/marc/bibliographic/ .
			$xml_str = implode(
				'',
				array(
					'<?xml version="1.0" encoding="UTF-8"?>',
					"\n",
					'<marc21:record xmlns:marc21="http://www.loc.gov/MARC21/slim">',
					"\n",
					$this->render_leader( $post ),
					$this->render_control_numbers( $post ),
					$this->render_datafield_024( $post ),
					$this->render_datafield_041( $post ),
					$this->render_datafield_084( $post ),
					$this->render_datafield_100( $post ),
					$this->render_datafield_245( $post ),
					$this->render_datafield_264( $post ),
					$this->render_datafield_336( $post ),
					$this->render_datafield_337( $post ),
					$this->render_datafield_338( $post ),
					$this->render_datafield_520( $post ),
					$this->render_datafield_536( $post ),
					$this->render_datafield_540( $post ),
					$this->render_datafield_650( $post ),
					$this->render_datafield_700( $post ),
					$this->render_datafield_773( $post ),
					$this->render_datafield_856( $post ),
					'</marc21:record>',
				)
			);

			return $this->common->format_xml( $xml_str );
		}

	}

}
