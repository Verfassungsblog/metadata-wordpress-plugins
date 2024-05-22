<?php
/**
 * Definitions of admin settings fields.
 *
 * @package vb-metadata-export
 */

if ( ! class_exists( 'VB_Metadata_Export_Settings_Fields' ) ) {

	/**
	 * Class defining admin settings fields.
	 */
	class VB_Metadata_Export_Settings_Fields {

		/**
		 * List of all available settings fields.
		 *
		 * @var array
		 */
		protected $settings_fields;

		/**
		 * Associative array of all available settings fields indexed by field name.
		 *
		 * @var array
		 */
		protected $settings_fields_by_name;

		/**
		 * Initialize class
		 */
		public function __construct() {

		}

		/**
		 * Initializes all settings fields and stores them in class variable.
		 */
		protected function load_settings_fields() {
			if ( $this->settings_fields ) {
				return;
			}
			$this->settings_fields = array(
				// ------------------- general settings ---------------------
				array(
					'name'        => 'blog_owner',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => __( 'Blog Owner', 'vb-metadata-export' ),
					'placeholder' => __( 'blog owner', 'vb-metadata-export' ),
					'description' => 'The blog owner (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd773.html" target="_blank">Marc21 773a</a>).',
				),
				array(
					'name'        => 'blog_title',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => __( 'Blog Title', 'vb-metadata-export' ),
					'placeholder' => __( 'blog title', 'vb-metadata-export' ),
					'description' => 'The blog title (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd773.html" target="_blank">Marc21 773t</a>).',
				),
				array(
					'name'        => 'issn',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'ISSN',
					'placeholder' => 'ISSN',
					'description' => 'The
						<a href="https://en.wikipedia.org/wiki/International_Standard_Serial_Number" target="_blank">
                        International Standard Serial Number
						</a>
						(ISSN) of this journal (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd773.html" target="_blank">Marc21 773x</a>).
                        <br>For example: <code>2366-7044</code> = ISSN of the Verfassungsblog',
				),
				array(
					'name'        => 'publisher',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => __( 'Publisher', 'vb-metadata-export' ),
					'placeholder' => __( 'name of publisher', 'vb-metadata-export' ),
					'description' => 'The publisher (used in <a href="https://www.loc.gov/marc/bibliographic/bd264.html"
                    target="_blank">Marc21 264b</a>).',
				),
				array(
					'name'        => 'require_doi',
					'type'        => 'boolean',
					'section'     => 'general',
					'label'       => __( 'Require DOI', 'vb-metadata-export' ),
					'description' => 'Whether to require the DOI to be available for a post in order to export metadata
                        (see ACF tab). If this option is set, a post that has no DOI available will not generate any
                        metadata output.',
				),
				array(
					'name'        => 'include_excerpt',
					'type'        => 'boolean',
					'section'     => 'general',
					'label'       => __( 'Include Excerpt', 'vb-metadata-export' ),
					'description' => 'Whether to include the article excerpt when exporting metadata (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd520.html" target="_blank">Marc21 520a</a>).',
				),
				array(
					'name'        => 'include_subheadline',
					'type'        => 'boolean',
					'section'     => 'general',
					'label'       => __( 'Include Subheadline in Title', 'vb-doaj-submit' ),
					'description' => 'Whether to include the subheadline in the main title with a minus
                        symbol as separator.',
				),
				array(
					'name'        => 'ddc_general',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'DDC<br>for all posts',
					'placeholder' => 'DDC as comma seperated codes',
					'description' => 'The comma-separated list of
                        <a href="https://deweysearchde.pansoft.de/webdeweysearch/mainClasses.html"
                        target="_blank">Dewey Decimal Classification</a> codes that are applicable to every post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd084.html" target="_blank">Marc21 084a</a>).
                        Additional codes can be provided via a custom field, see tab "Custom Fields".
                        <br>For Example: <code>342</code> = "Verfassungs- und Verwaltungsrecht"',
				),
				array(
					'name'        => 'copyright_general',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Copyright / Licence <br>for all posts',
					'placeholder' => 'a copyright / license note',
					'description' => 'The default copyright or licence note for all posts (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd540.html" target="_blank">Marc21 540a</a>).
                        This note can be overwritten with a post-specific copyright note if it is provided via via a
                        custom field, see tab "Custom Fields". <br>For example: <code>CC BY-SA 4.0</code>
                         = Creative Commons Attribution-ShareAlike 4.0 International',
				),
				array(
					'name'        => 'funding_general',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Funding<br/>for all posts',
					'placeholder' => 'a funding note',
					'description' => 'The default funding note for all posts (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd540.html" target="_blank">Marc21 536a</a>).
                        This note can be overwritten with a post-specific funding note if it is provided via via a
                        custom field, see tab "Custom Fields".',
				),

				// ------------------ language settings ---------------------

				array(
					'name'        => 'language',
					'type'        => 'string',
					'section'     => 'language',
					'label'       => 'Language<br>Code',
					'placeholder' => 'language code',
					'description' => 'The default <a href="https://www.loc.gov/marc/languages/language_code.html"
                        target="_blank">Marc21 Language Code</a> (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd041.html" target="_blank">Marc21 041a</a>).
                        The language can be overwritten by assigning posts to a category, see below.
                        <br>For Example: <code>ger</code> = "German"',
				),
				array(
					'name'        => 'language_alternate',
					'type'        => 'string',
					'section'     => 'language',
					'label'       => 'Alternate Language<br>Code',
					'placeholder' => 'language code',
					'description' => 'The alternate <a href="https://www.loc.gov/marc/languages/language_code.html"
                        target="_blank">Marc21 Language Code</a> (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd041.html" target="_blank">Marc21 041a</a>).
                        This language code is used in case a post is assigned to a specific category, see below.
                        <br>For Example: <code>eng</code> = "English"',
				),
				array(
					'name'        => 'language_alternate_category',
					'type'        => 'string',
					'section'     => 'language',
					'label'       => 'Alternate Language<br>Category',
					'placeholder' => 'category name',
					'description' => 'The name of the category, which posts are assigned to, in case they are written
                        in the alternate language.
                        <br>For Example: <code>English Articles</code>',
				),

				// ------------------ content type category -----------------

				array(
					'name'        => 'podcast_category',
					'type'        => 'string',
					'section'     => 'content_type',
					'label'       => 'Podcast Category',
					'placeholder' => 'category name',
					'description' => 'The name of the category, which posts are assigned to, in case they are podcasts.
                        <br>For Example: <code>Podcast</code>',
				),

				// ---------------- theme settings ---------------------

				array(
					'name'        => 'template_priority',
					'type'        => 'string',
					'section'     => 'theme',
					'label'       => 'Template Priority',
					'placeholder' => 'priority value',
					'description' => 'The Wordpress template priority of the meta data export template. In case meta
                        data is not rendered, try a higher priority or use debug tools to figure out an appropriate
                        priority.',
				),

				// ----------------  custom field settings -----------------

				array(
					'name'        => 'doi_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'DOI<br>(custom field / meta key)',
					'placeholder' => 'meta key for the DOI',
					'description' => 'The meta key for the custom field that contains the DOI for a post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd024.html" target="_blank">Marc21 024a</a>).
						Two meta keys may be provided by separating them with a comma. The first available doi is
						used in all exports.
                        <br>The DOI should be provided as code (not as URI), e.g. <code>10.1214/aos/1176345451</code>.',
				),
				array(
					'name'        => 'subheadline_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Sub-Headline<br>(custom field / meta key)',
					'placeholder' => 'meta key for a sub-headline',
					'description' => 'The meta key for the custom field that contains the sub-headline for a particular
                        post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd245.html" target="_blank">Marc21 245b</a>).',
				),
				array(
					'name'        => 'ddc_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'DDC<br>(custom field / meta key)',
					'placeholder' => 'meta key for comma separated DDC codes',
					'description' => 'The meta key for the custom field that contains the list of
                        <a href="https://deweysearchde.pansoft.de/webdeweysearch/mainClasses.html"
                        target="_blank">Dewey Decimal Classification</a> codes that is applicable for a particular
                        post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd084.html" target="_blank">Marc21 084a</a>).',
				),
				array(
					'name'        => 'copyright_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Copyright / Licence<br>(custom field / meta key)',
					'placeholder' => 'meta key for a copyright / licence note',
					'description' => 'The meta key for the custom field that contains the copyright or licence note for
                        a specific post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd540.html" target="_blank">Marc21 540a</a>).
                        If a post-specific copyright note is provided, the default copyright note is overwritten.',
				),
				array(
					'name'        => 'funding_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Funding<br>(custom field / meta key)',
					'placeholder' => 'meta key for a funding note',
					'description' => 'The meta key for the custom field that contains a funding note for a particular
                        post (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd540.html" target="_blank">Marc21 536a</a>).
                        If a post-specific funding note is provided, the default funding note is overwritten.',
				),
				array(
					'name'        => 'orcid_meta_key',
					'type'        => 'string',
					'section'     => 'user_meta',
					'label'       => 'ORCID<br>(custom field / meta key)',
					'placeholder' => 'meta key for the ORCID of the author',
					'description' => 'The meta key for the custom field that contains the ORCID of the post author
                        (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd100.html" target="_blank">Marc21 100 0</a>).',
				),
				array(
					'name'        => 'gndid_meta_key',
					'type'        => 'string',
					'section'     => 'user_meta',
					'label'       => 'GND-ID<br>(custom field / meta key)',
					'placeholder' => 'meta key for the GND-ID of the author',
					'description' => 'The meta key for the custom field that contains the GND-ID of the post author
                        (used in
                        <a href="https://www.loc.gov/marc/bibliographic/bd100.html" target="_blank">Marc21 100 0</a>).',
				),

				// ----------------- html meta tags settings ----------------

				array(
					'name'        => 'metatags_enabled',
					'type'        => 'boolean',
					'section'     => 'metatags',
					'label'       => __( 'HTML Meta Tags Enabled', 'vb-metadata-export' ),
					'description' => 'Whether any HTML meta tags will be added or not.',
				),

				array(
					'name'        => 'metatags_dc_enabled',
					'type'        => 'boolean',
					'section'     => 'metatags',
					'label'       => __( 'Add Dublin Core Meta Tags', 'vb-metadata-export' ),
					'description' => 'If enabled, adds Dublin Core meta tags according to the
						<a href="https://www.dublincore.org/specifications/dublin-core/dcq-html/" target="_blank">
						documentation</a>.',
				),

				array(
					'name'        => 'metatags_hw_enabled',
					'type'        => 'boolean',
					'section'     => 'metatags',
					'label'       => __( 'Add Highwire Meta Tags', 'vb-metadata-export' ),
					'description' => 'If enabled, adds Highwire meta tags according to the
						<a href="https://www.zotero.org/support/dev/exposing_metadata" target="_blank">Zotero
						documentation</a>.',
				),

				// ------------------ marc21 xml settings -------------------

				array(
					'name'        => 'marc21xml_enabled',
					'type'        => 'boolean',
					'section'     => 'marc21',
					'label'       => __( 'Marc21 Export Enabled', 'vb-metadata-export' ),
					'description' => 'Whether the Marc21 XML export is active or not.',
				),
				array(
					'name'        => 'marc21_doi_as_control_number',
					'type'        => 'boolean',
					'section'     => 'marc21',
					'label'       => __( 'Use DOI as Control Number', 'vb-metadata-export' ),
					'description' => 'Whether to use the DOI (see ACF tab) for the Marc21 Control Number Field (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd001.html" target="_blank">Marc21 001</a>),
                        or otherwise the sequential post number (post id). If enabled, posts without a DOI will not output
                        any metadata.',
				),
				array(
					'name'        => 'marc21_control_number_identifier',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Control Number Identifier', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 control number identifier', 'vb-metadata-export' ),
					'description' => 'The Marc Code for the organization that provides control numbers (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd003.html" target="_blank">Marc21 003</a>).
                        This code is issued by the Library of Congress and other national libraries, for example the
                        <a href="https://sigel.staatsbibliothek-berlin.de/vergabe/marc-orgcode">
                        Staatsbibliothek zu Berlin</a>.',
				),
				array(
					'name'        => 'marc21_leader',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Default Marc21 Leader', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 leader attribute', 'vb-metadata-export' ),
					'description' => implode(
						'',
						array(
							__( 'The', 'vb-metadata-export' ),
							' <a href="https://www.loc.gov/marc/bibliographic/bdleader.html" target="_blank">',
							__( 'Marc21 leader attribute', 'vb-metadata-export' ),
							'</a>. ',
							__( 'Use the underscore (_) instead of a space ( ).', 'vb-metadata-export' ),
							'<br>',
							__( 'For example:', 'vb-metadata-export' ),
							'<code>_____nam__22_____uu_4500</code>',
						),
					),
				),
				array(
					'name'        => 'marc21_physical_description',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Default Physical Description', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 physical description code', 'vb-metadata-export' ),
					'description' => 'The physical description code (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd007.html" target="_blank">Marc21 007</a>).
                        <br>For example: <code>cr|||||</code> = Remote Electronic Resource',
				),
				array(
					'name'        => 'marc21_content_type',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Default Content Type', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 content type', 'vb-metadata-export' ),
					'description' => 'The content type (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd336.html" target="_blank">Marc21 336</a>)
                        in the format of comma separated subfield values (336a,336b,336-2), see
                        <a href="https://www.loc.gov/standards/sourcelist/genre-form.html">LoC Codes</a> for possible
                        values.
                        <br>For example: <code>Text,txt,rdacontent</code> = Text Content Type',
				),
				array(
					'name'        => 'marc21_podcast_leader',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Podcast Marc21 Leader', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 leader attribute', 'vb-metadata-export' ),
					'description' => implode(
						'',
						array(
							__( 'The', 'vb-metadata-export' ),
							' <a href="https://www.loc.gov/marc/bibliographic/bdleader.html" target="_blank">',
							__( 'Marc21 leader attribute', 'vb-metadata-export' ),
							'</a> for podcast posts. ',
							__( 'Use the underscore (_) instead of a space ( ).', 'vb-metadata-export' ),
							'<br>',
							__( 'For example:', 'vb-metadata-export' ),
							'<code>_____nim__22_____uu_4500</code>',
						),
					),
				),
				array(
					'name'        => 'marc21_podcast_physical_description',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Podcast Physical Description', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 physical description code', 'vb-metadata-export' ),
					'description' => 'The physical description code (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd007.html" target="_blank">Marc21 007</a>)
                        for podcast posts.
                        <br>For example: <code>sr|||||</code> = Remote Sound Recording',
				),
				array(
					'name'        => 'marc21_podcast_content_type',
					'type'        => 'string',
					'section'     => 'marc21',
					'label'       => __( 'Podcast Content Type', 'vb-metadata-export' ),
					'placeholder' => __( 'marc21 content type', 'vb-metadata-export' ),
					'description' => 'The content type (see
                        <a href="https://www.loc.gov/marc/bibliographic/bd336.html" target="_blank">Marc21 336</a>)
                        of podcast posts in the format of comma separated subfield values (336a,336b,336-2), see
                        <a href="https://www.loc.gov/standards/sourcelist/genre-form.html">LoC Codes</a> for possible
                        values.
                        <br>For example: <code>Spoken Word,spw,rdacontent</code> = Spoken Word Content Type',
				),

				// -------------------- mods settings -----------------------

				array(
					'name'        => 'mods_enabled',
					'type'        => 'boolean',
					'section'     => 'mods',
					'label'       => __( 'MODS Export Enabled', 'vb-metadata-export' ),
					'description' => 'Whether the MODS export is active or not.',
				),

				// ----------------- dublin core settings -------------------

				array(
					'name'        => 'dc_enabled',
					'type'        => 'boolean',
					'section'     => 'dc',
					'label'       => __( 'Dublin Core Export Enabled', 'vb-metadata-export' ),
					'description' => 'Whether the Dublin Core export is active or not.',
				),

				// ------------------- oai-pmh settings ---------------------

				array(
					'name'        => 'oai-pmh_enabled',
					'type'        => 'boolean',
					'section'     => 'oai_pmh',
					'label'       => __( 'OAI-PMH Enabled', 'vb-metadata-export' ),
					'description' => 'Whether the OAI-PMH interface is active or not.
                        <br>(OAI-PMH requires Dublic Core to be enabled)',
				),
				array(
					'name'        => 'oai-pmh_admin_email',
					'type'        => 'string',
					'section'     => 'oai_pmh',
					'label'       => __( 'OAI Admin Email', 'vb-metadata-export' ),
					'placeholder' => __( 'the OAI admin email address', 'vb-metadata-export' ),
					'description' => 'The admin email address that provides support for the OAI PMH interface.',
				),
				array(
					'name'        => 'oai-pmh_list_size',
					'type'        => 'integer',
					'section'     => 'oai_pmh',
					'label'       => __( 'OAI List Size', 'vb-metadata-export' ),
					'placeholder' => __( 'the list size', 'vb-metadata-export' ),
					'description' => 'The maximum number of records to show in a response to a OAI-PMH list request.',
				),
			);
			// index settings field by their name.
			$this->settings_fields_by_name = array();
			foreach ( $this->settings_fields as $field ) {
				$this->settings_fields_by_name[ $field['name'] ] = $field;
			}
		}

		/**
		 * Get the list of settings fields.
		 *
		 * @return array the list of settings fields
		 */
		public function get_list() {
			$this->load_settings_fields();
			return $this->settings_fields;
		}

		/**
		 * Get a settings field definition by that name of the field.
		 *
		 * @param string $field_name the name of the settings field.
		 * @return array the settings field definition
		 */
		public function get_field( $field_name ) {
			$this->load_settings_fields();
			return $this->settings_fields_by_name[ $field_name ];
		}

	}

}
