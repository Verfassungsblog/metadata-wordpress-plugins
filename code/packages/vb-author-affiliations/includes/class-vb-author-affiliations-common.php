<?php
/**
 * Class providing common methods.
 *
 * @package vb-author-affiliations
 */

if ( ! class_exists( 'VB_Author_Affiliations_Common' ) ) {

	/**
	 * Class providing common methods.
	 */
	class VB_Author_Affiliations_Common {
		/**
		 * The name of this plugin.
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
					'autofill'                     => true,
					'extract_from_orcid'           => true,
					'extract_from_rorid'           => true,
					'timeout'                      => 30,
					'author_affiliations_meta_key' => 'author_affiliations',
					'affiliation_name_meta_key'    => 'affiliation',
					'rorid_meta_key'               => 'rorid',
					'orcid_meta_key'               => 'orcid',
				);
			} else {
				// default settings for any other blog than Verfassungsblog.
				$this->setting_field_defaults = array(
					'autofill'                     => true,
					'extract_from_orcid'           => true,
					'extract_from_rorid'           => true,
					'timeout'                      => 30,
					'author_affiliations_meta_key' => 'author_affiliations',
					'affiliation_name_meta_key'    => 'affiliation',
					'rorid_meta_key'               => 'rorid',
					'orcid_meta_key'               => 'orcid',
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

	}

}
