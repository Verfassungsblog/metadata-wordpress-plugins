<?php
/**
 * Class providng common methods
 *
 * @package vb-gnd-taxonomy
 */

if ( ! class_exists( 'VB_GND_Taxonomy_Common' ) ) {

	/**
	 * Class providing common methods
	 */
	class VB_GND_Taxonomy_Common {
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
					'suggest_enabled' => true,
					'api_baseurl'     => 'https://lobid.org/gnd/',
					'label_format'    => 'suggest',
					'merge_with_tags' => true,
					'query_filter'    => '',
					'query_size'      => 10,
					'verify_gnd_id'   => true,
				);
			} else {
				// default settings for any other blog than Verfassungsblog.
				$this->setting_field_defaults = array(
					'suggest_enabled' => true,
					'api_baseurl'     => 'https://lobid.org/gnd/',
					'label_format'    => 'suggest',
					'merge_with_tags' => false,
					'query_filter'    => '',
					'query_size'      => 10,
					'verify_gnd_id'   => true,
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
		 * Set global last error for this plugin.
		 *
		 * @param string $error the error message.
		 * @return bool true if the value was updated, false otherwise.
		 */
		public function set_last_error( $error ) {
			return update_option( $this->plugin_name . '_status_last_error', $error );
		}

		/**
		 * Return global last error message for this plugin.
		 *
		 * @return string the last error message for this plugin
		 */
		public function get_last_error() {
			return get_option( $this->plugin_name . '_status_last_error', false );
		}

		/**
		 * Remove global last error message for this plugin.
		 */
		public function clear_last_error() {
			delete_option( $this->plugin_name . '_status_last_error' );
		}

		/**
		 * Extracts a GND ID from a string of the format "label [gnd:id]".
		 *
		 * @param string $term the string that is parsed for a GND ID.
		 * @return string the extracted GND ID or false
		 */
		public function extract_gnd_id_from_term( $term ) {
			preg_match( '/\[gnd:([^\]]+)\]/', $term, $matches );
			if ( $matches ) {
				return $matches[1];
			}
			return false;
		}

	}

}
