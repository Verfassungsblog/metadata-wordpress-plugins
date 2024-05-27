<?php
/**
 * Definitions of admin settings fields.
 *
 * @package vb-crossref-doi
 */

if ( ! class_exists( 'VB_CrossRef_DOI_Settings_Fields' ) ) {

	/**
	 * Class defining admin settings fields.
	 */
	class VB_CrossRef_DOI_Settings_Fields {

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
					'name'        => 'api_user',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Deposit User',
					'placeholder' => 'CrossRef Deposit API Username/Role',
					'description' => 'The user and role to the CrossRef Deposit API as <code>USER/ROLE</code>. <br>
                        For example: <code>user@example.com/myrole</code>',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'api_password',
					'type'        => 'password',
					'section'     => 'general',
					'label'       => 'Deposit Password',
					'placeholder' => 'CrossRef Deposit API Password',
					'description' => 'The password to the CrossRef Deposit API.',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'api_url_deposit',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Deposit API URL',
					'placeholder' => 'URL to the CrossRef Deposit API',
					'description' => 'The URL to the CrossRef Deposit API.<br>
                        Usually <code>https://api.crossref.org/v2/deposits</code>, for testing
						<code>https://test.crossref.org/v2/deposits</code>',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'api_url_submission',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Submission API URL',
					'placeholder' => 'URL to the CrossRef Submission API',
					'description' => 'The URL to the CrossRef Submission API.<br>
                        Usually <code>https://doi.crossref.org/servlet/submissionDownload</code>, for testing
						<code>https://test.crossref.org/servlet/submissionDownload</code>',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'depositor_name',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Depositer Name',
					'placeholder' => 'Depositor Name',
					'description' => 'The name of the person or organization submitting the DOIs.',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'depositor_email',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Depositer eMail',
					'placeholder' => 'Depositor eMail',
					'description' => 'The e-mail address to which success and/or error messages are sent.',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'registrant',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'Registrant',
					'placeholder' => 'Name of Registrant',
					'description' => 'The name of the organization responsible for the information being registered.',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'doi_prefix',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'DOI Prefix',
					'placeholder' => 'Prefix for DOIs',
					'description' => 'The prefix that is used when generating new DOIs for posts.',
					'sanitize'    => 'can_not_be_empty_text',
				),
				array(
					'name'        => 'doi_suffix_length',
					'type'        => 'string',
					'section'     => 'general',
					'label'       => 'DOI Suffix Length',
					'placeholder' => 'DOI suffix length',
					'description' => 'The length of the randomly generated suffix (min 12, max 64).',
					'sanitize'    => 'doi_suffix_length',
				),
				array(
					'name'        => 'show_admin_notice',
					'type'        => 'boolean',
					'section'     => 'general',
					'label'       => 'Show Errors as Admin Notice',
					'placeholder' => 'Show Errors as Admin Notice',
					'description' => 'Whether to show an admin notice in case of errors.',
				),

				// -------------- meta data settings -----------------------

				array(
					'name'        => 'issn',
					'type'        => 'string',
					'section'     => 'meta',
					'label'       => 'ISSN',
					'placeholder' => 'ISSN',
					'description' => 'The <a href="https://en.wikipedia.org/wiki/International_Standard_Serial_Number" target="_blank">
                            International Standard Serial Number</a> (ISSN) of this journal.
                        <br>For example: <code>2366-7044</code> = ISSN of the Verfassungsblog',
				),
				array(
					'name'        => 'copyright_name_general',
					'type'        => 'string',
					'section'     => 'meta',
					'label'       => 'Copyright Licence Name<br>for all posts',
					'placeholder' => 'a Creative Commons license name',
					'description' => 'The default Creative Commons licence name for all posts. Based on the name, a link
                        is generated automatically. However, a custom licence link can be provided below. Also, the
                        licence name can be overwritten with a post-specific license name if it is provided via via a
                        custom field, see tab "Custom Fields". <br>
                        For example: <code>CC BY-SA 4.0</code> = Creative Commons Attribution-ShareAlike 4.0 International',
				),
				array(
					'name'        => 'copyright_link_general',
					'type'        => 'string',
					'section'     => 'meta',
					'label'       => 'Copyright Licence Link <br>for all posts',
					'placeholder' => 'a link to a license',
					'description' => 'The default link to the licence for all posts. This link can be overwritten with
                        a post-specific link if it is provided via via a custom field, see tab "Custom Fields". <br>
                        For example: <code>https://creativecommons.org/licenses/by-sa/4.0/legalcode</code> = Link to CC BY-SA 4.0',
				),
				array(
					'name'        => 'include_excerpt',
					'type'        => 'boolean',
					'section'     => 'meta',
					'label'       => __( 'Include Excerpt', 'vb-crossref-doi' ),
					'description' => 'Whether to include the post excerpt as abstract when submitting meta data to CrossRef.',
				),

				// ------------- institution fields -------------

				array(
					'name'        => 'institution_name',
					'type'        => 'string',
					'section'     => 'institution',
					'label'       => 'Institution Name',
					'placeholder' => 'name of the institution publishing articles.',
					'description' => 'The name of the institution that is publishing articles.',
				),
				array(
					'name'        => 'institution_rorid',
					'type'        => 'string',
					'section'     => 'institution',
					'label'       => 'Institution ROR-ID',
					'placeholder' => 'ROR id of the institution publishing articles.',
					'description' => 'The ROR-ID of the institution that is publishing articles.',
				),
				array(
					'name'        => 'institution_isni',
					'type'        => 'string',
					'section'     => 'institution',
					'label'       => 'Institution ISNI',
					'placeholder' => 'ISNI of the institution publishing articles.',
					'description' => 'The ISNI of the institution that is publishing articles.',
				),
				array(
					'name'        => 'institution_wikidata_id',
					'type'        => 'string',
					'section'     => 'institution',
					'label'       => 'Institution Wikidata ID',
					'placeholder' => 'wikidata id of the institution publishing articles.',
					'description' => 'The Wikidata ID of the institution that is publishing articles.',
				),

				// ----------------- post selection ------------------

				array(
					'name'        => 'submit_all_posts',
					'type'        => 'boolean',
					'section'     => 'post_selection',
					'label'       => 'Submit all Published Posts',
					'description' => 'Whether to submit all published posts. If enabled, any published post that is
                        not assigned to the "exclude" category (see below) will be submitted to CrossRef. If disabled,
                        only posts that are assigned to the "include" category (see below) will be submitted to
                        CrossRef, and the "exclude" category is ignored.',
				),
				array(
					'name'        => 'include_post_category',
					'type'        => 'string',
					'section'     => 'post_selection',
					'label'       => 'Include Category',
					'placeholder' => 'category name',
					'description' => 'The name of a category of posts that will be submitted.<br>
                        For example: <code>Posts with DOI</code>',
				),
				array(
					'name'        => 'exclude_post_category',
					'type'        => 'string',
					'section'     => 'post_selection',
					'label'       => 'Exclude Category',
					'placeholder' => 'category name',
					'description' => 'The name of a category of posts that will not be submitted.<br>
                        For example: <code>No DOI</code>',
				),

				// ------------- update settings fields -------------

				array(
					'name'        => 'auto_update',
					'type'        => 'boolean',
					'section'     => 'update',
					'label'       => 'Automatic Update',
					'description' => 'Whether new posts should be automatically submitted to CrossRef in regular intervals.',
				),
				array(
					'name'        => 'interval',
					'type'        => 'string',
					'section'     => 'update',
					'label'       => 'Update Interval',
					'placeholder' => 'update interval in minutes',
					'description' => 'The number of minutes between updates. On each update, the database is checked
                        and new posts are submitted to CrossRef.',
					'sanitize'    => 'can_not_be_empty_positive_integer',
				),
				array(
					'name'        => 'batch',
					'type'        => 'string',
					'section'     => 'update',
					'label'       => 'Batch Size',
					'placeholder' => 'batch size',
					'description' => 'The number of posts that are processed in one batch. High values (>1) might
                        trigger the CrossRef to block your IP for a while. Use at own risk!',
					'sanitize'    => 'can_not_be_empty_positive_integer',
				),
				array(
					'name'        => 'requests_per_second',
					'type'        => 'string',
					'section'     => 'update',
					'label'       => 'Requests per Second',
					'placeholder' => 'requests per second',
					'description' => 'The maximum number of API requests that are issued per second. CrossRef declares
                        that they limit the number of requests per second. High numbers might provoke CrossRef to block your IP address.',
					'sanitize'    => 'can_not_be_empty_positive_integer',
				),
				array(
					'name'        => 'timeout_minutes',
					'type'        => 'string',
					'section'     => 'update',
					'label'       => 'Timeout Minutes',
					'placeholder' => 'number of minutes',
					'description' => 'The number of minutes that need to pass before a submission is considered to have
                        failed without any response from CrossRef. Pending submissions are checked again on every
                        update. Therefore, the timeout needs to be larger than the update interval.',
					'sanitize'    => 'can_not_be_empty_positive_integer',
				),
				array(
					'name'        => 'retry_minutes',
					'type'        => 'string',
					'section'     => 'update',
					'label'       => 'Retry Minutes',
					'placeholder' => 'number of minutes',
					'description' => 'The number of minutes that need to pass before a failed submission is tried again.',
					'sanitize'    => 'can_not_be_empty_positive_integer',
				),

				// ------------- custom post fields -------------

				array(
					'name'        => 'doi_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Article DOI<br>(custom field / meta key)',
					'placeholder' => 'meta key for the DOI',
					'description' => 'The meta key for the custom field that stores the DOI for a post.<br>
                        The DOI will be saved as a code (not as URI), e.g. <code>10.1214/aos/1176345451</code>.',
					'sanitize'    => 'meta_key',
				),
				array(
					'name'        => 'copyright_name_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Copyright Licence Name<br>(custom field / meta key)',
					'placeholder' => 'meta key for a copyright licence name',
					'description' => 'The meta key for the custom field that contains the Creative Commons licence name
                        for a specific post. If a post-specific license name is provided, it is used instead of the
                        default license name.',
					'sanitize'    => 'meta_key',
				),
				array(
					'name'        => 'copyright_link_meta_key',
					'type'        => 'string',
					'section'     => 'post_meta',
					'label'       => 'Copyright Licence Link<br>(custom field / meta key)',
					'placeholder' => 'meta key for a copyright licence link',
					'description' => 'The meta key for the custom field that contains the licence link for a specific
                        post. If a post-specific license link is provided, it is used instead of the default license
                        link.',
					'sanitize'    => 'meta_key',
				),

				// ------------- custom user fields --------------

				array(
					'name'        => 'orcid_meta_key',
					'type'        => 'string',
					'section'     => 'user_meta',
					'label'       => 'Author ORCID<br>(custom field / meta key)',
					'placeholder' => 'meta key for the ORCID of the author',
					'description' => 'The meta key for the custom field that contains the ORCID of the post author.<br>
                        ORCIDs need to be provided as code (not as URI), e.g. <code>0000-0003-1279-3709</code>.',
					'sanitize'    => 'meta_key',
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
