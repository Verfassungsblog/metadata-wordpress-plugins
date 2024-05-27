<?php
/**
 * Class providing common methods
 *
 * @package vb-crossref-doi
 */

if ( ! class_exists( 'VB_CrossRef_DOI_Common' ) ) {

	/**
	 * Class providing common methods
	 */
	class VB_CrossRef_DOI_Common {
		/**
		 * The name of this plugin
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
					'api_url_deposit'         => 'https://api.crossref.org/v2/deposits',
					'api_url_submission'      => 'https://doi.crossref.org/servlet/submissionDownload',
					'depositor_name'          => 'Wordpress Plugin ' . $this->plugin_name,
					'depositor_email'         => 'info@verfassungsblog.de',
					'registrant'              => $blog_title,
					'doi_prefix'              => 'example-prefix',
					'doi_suffix_length'       => 16,
					'show_admin_notice'       => true,
					// meta data.
					'issn'                    => '2366-7044',
					'copyright_name_general'  => 'CC BY-SA 4.0',
					'include_excerpt'         => true,
					// institution.
					'institution_name'        => $blog_title,
					'institution_wikidata_id' => 'Q97588182',
					// post selection.
					'submit_all_posts'        => true,
					'include_post_category'   => '',
					'exclude_post_category'   => 'No DOI',
					// update.
					'auto_update'             => false,
					'interval'                => 5,
					'batch'                   => 1,
					'requests_per_second'     => 2.0,
					'timeout_minutes'         => 20,
					'retry_minutes'           => 60,
					// post meta.
					'doi_meta_key'            => 'doi',
					'copyright_name_meta_key' => 'copyright',
					'copyright_link_meta_key' => 'copyright_link',
					// user meta.
					'orcid_meta_key'          => 'orcid',
				);
			} else {
				// default settings for any other blog than Verfassungsblog.
				$this->setting_field_defaults = array(
					// general.
					'api_url_deposit'       => 'https://api.crossref.org/v2/deposits',
					'api_url_submission'    => 'https://doi.crossref.org/servlet/submissionDownload',
					'depositor_name'        => 'Wordpress Plugin ' . $this->plugin_name,
					'doi_suffix_length'     => 16,
					'show_admin_notice'     => true,
					// meta data.
					'include_excerpt'       => true,
					// institution.
					'institution_name'      => $blog_title,
					// post selection.
					'submit_all_posts'      => false,
					'include_post_category' => 'DOI',
					'exclude_post_category' => 'No DOI',
					// update.
					'auto_update'           => false,
					'interval'              => 5,
					'batch'                 => 1,
					'requests_per_second'   => 2.0,
					'timeout_minutes'       => 20,
					'retry_minutes'         => 60,
					// post meta.
					'doi_meta_key'          => 'doi',
					// user meta.
					'orcid_meta_key'        => 'orcid',
				);
			}
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
		 * Return the post meta key that stores the DOI of a post.
		 *
		 * @return string the post meta key that stores the DOI
		 */
		public function get_post_doi_meta_key() {
			return $this->get_settings_field_value( 'doi_meta_key' );
		}

		/**
		 * Return meta key that stores the submit status of a post.
		 *
		 * @return string the meta key
		 */
		public function get_post_submit_status_meta_key() {
			return $this->plugin_name . '_submit-status';
		}

		/**
		 * Return meta key that stores the timestamp when a post was submitted to the DOAJ.
		 *
		 * @return string the meta key
		 */
		public function get_post_submit_timestamp_meta_key() {
			return $this->plugin_name . '_submit-timestamp';
		}

		/**
		 * Return meta key that stores the CrossRef submission id for a post.
		 *
		 * @return string the meta key
		 */
		public function get_post_submit_id_meta_key() {
			return $this->plugin_name . '_submit-id';
		}

		/**
		 * Return meta key that stores the submit error of a post.
		 *
		 * @return string the meta key
		 */
		public function get_post_submit_error_meta_key() {
			return $this->plugin_name . '_submit-error';
		}

		/**
		 * Return the timestamp of the current time in UTC.
		 *
		 * @return int the current UTC timestamp
		 */
		public function get_current_utc_timestamp() {
			return ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		}

		/**
		 * Convert a date and time to the ISO-8601 format.
		 *
		 * @param DateTime|DateTimeImmutable $date the date and time to be converted to the ISO-8601 format.
		 * @return string the data as string in the format of ISO-8601
		 */
		public function date_to_iso8601( $date ) {
			return $date->format( 'Y-m-d\TH:i:s\Z' );
		}

		/**
		 * Convert a date string formatted as ISO-8601 to a date object.
		 *
		 * @param string $iso the date string formatted as ISO-8601.
		 * @return DateTimeImmutable the date object
		 */
		public function iso8601_to_date( $iso ) {
			$date = date_create_immutable_from_format( 'Y-m-d\TH:i:s\Z', $iso, new DateTimeZone( 'UTC' ) );
			if ( ! $date ) {
				$date = date_create_immutable_from_format( 'Y-m-d', $iso, new DateTimeZone( 'UTC' ) );
			}
			return $date;
		}

		/**
		 * Subtract the WordPress timezone offset from the provided date string in ISO-8601 format.
		 *
		 * Is used to counteract the conversion from local to UTC time when a date is provided to WP_Query.
		 *
		 * @param string $utc_iso the date and time as string in ISO-8601 format.
		 * @return string the adjusted date and time in ISO-8601 format
		 */
		public function subtract_timezone_offset_from_utc_iso8601( $utc_iso ) {
			$local = new Datetime( 'now', wp_timezone() );
			$date  = new Datetime( 'now', new DateTimeZone( 'UTC' ) );
			$date->setTimestamp( $this->iso8601_to_date( $utc_iso )->getTimestamp() - wp_timezone()->getOffset( $local ) );
			return $this->date_to_iso8601( $date );
		}

		/**
		 * Checks whether a string starts with another string.
		 *
		 * @param string $haystack the string whose beginning is checked.
		 * @param string $needle the string that is excpected to be the beginnign of haystack.
		 * @return bool true if haystack starts with needle
		 */
		public function starts_with( $haystack, $needle ) {
			$length = strlen( $needle );
			return substr( $haystack, 0, $length ) === $needle;
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

	}

}
