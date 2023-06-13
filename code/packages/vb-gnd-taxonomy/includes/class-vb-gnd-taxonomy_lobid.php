<?php
/**
 * Class that performs Lobid.org API calls
 *
 * @package vb-gnd-taxonomy
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-gnd-taxonomy_common.php';

if ( ! class_exists( 'VB_GND_Taxonomy_Lobid' ) ) {

	/**
	 * Class that performs Lobid.org API calls
	 */
	class VB_GND_Taxonomy_Lobid {
		/**
		 * Common methods
		 *
		 * @var VB_GND_Taxonomy_Common
		 */
		protected $common;

		/**
		 * Simple cache (url => json) that prevents that the same HTTP request is performed twice.
		 *
		 * @var array
		 */
		protected $cache;

		/**
		 * Initialize with plugin name
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_GND_Taxonomy_Common( $plugin_name );
			$this->cache  = array();
		}

		/**
		 * Perform a HTTP request to get the lobid.org JSON that describes a GND entity.
		 *
		 * @param string $gnd_id the id of the gnd entity.
		 * @return object|bool the decoded JSON as object, or false if the information could not be found
		 */
		protected function get_gnd_entity_json( $gnd_id ) {
			$base_url = $this->common->get_settings_field_value( 'api_baseurl' );
			$url      = "{$base_url}{$gnd_id}.json";
			if ( ! array_key_exists( $url, $this->cache ) ) {
				$response = wp_remote_request(
					$url,
					array(
						'method'  => 'GET',
						'headers' => array(
							'Accept' => 'application/json',
						),
						'timeout' => 30,
					),
				);

				// validate response.
				if ( is_wp_error( $response ) ) {
					return false;
				}
				$status_code = wp_remote_retrieve_response_code( $response );
				if ( 200 !== $status_code ) {
					return false;
				}

				// parse response.
				$json_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					return false;
				}

				$this->cache[ $url ] = $json_data;
				return $json_data;
			}
			return $this->cache[ $url ];
		}

		/**
		 * Checks whether a GND ID is a known entity in lobid.org.
		 *
		 * @param string $gnd_id the ID of the GND entity
		 * @return bool true, if a GND entity with that ID exists in lobid.org
		 */
		public function check_gnd_entity_exists( $gnd_id ) {
			$json_data = $this->get_gnd_entity_json( $gnd_id );
			if ( ! empty( $json_data ) && ! empty( $json_data->gndIdentifier ) ) { // phpcs:ignore
				return $gnd_id == $json_data->gndIdentifier; // phpcs:ignore
			}
			return false;
		}

		/**
		 * Returns the description of a GND entity if it exists in the lobid.org data.
		 *
		 * @param string $gnd_id the ID of the GND entity.
		 * @return string the description for the GND entity if it exists, or an empty string
		 */
		public function load_gnd_entity_description( $gnd_id ) {
			$json_data = $this->get_gnd_entity_json( $gnd_id );
			if ( ! empty( $json_data ) && ! empty( $json_data->definition ) && count( $json_data->definition ) > 0 ) {
				return $json_data->definition[0];
			}
			return '';
		}

	}

}
