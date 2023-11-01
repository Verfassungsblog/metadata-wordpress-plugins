<?php
/**
 * Class that performs API requests against orcid.org and ror.org.
 *
 * @package vb-author-affiliations
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-author-affiliations-common.php';

if ( ! class_exists( 'VB_Author_Affiliations_REST' ) ) {

	/**
	 * Class that performs API requests against orcid.org and ror.org.
	 */
	class VB_Author_Affiliations_REST {

		/**
		 * Common methods
		 *
		 * @var VB_Author_Affiliations_Common
		 */
		protected $common;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_Author_Affiliations_Common( $plugin_name );
		}

		/**
		 * Retrieve textual author affiliation (meaning the name) stored in a user meta field.
		 *
		 * @param int $user_id the id of the user who has a affiliation name stored as meta field.
		 * @return string the affiliation name
		 */
		protected function get_textual_author_affiliation( $user_id ) {
			return trim( $this->common->get_user_meta_field_value( 'affiliation_meta_key', $user_id ) );
		}

		/**
		 * Retrieve the author affiliation name from ror.org meta data given a ror id.
		 *
		 * @param int $user_id the id of the user who has a ror id stored as meta field.
		 * @return string the affiliation name extracted from ror.org
		 */
		protected function get_affiliation_from_rorid( $user_id ) {
			$rorid = trim( $this->common->get_user_meta_field_value( 'rorid_meta_key', $user_id ) );
			$rorid = str_replace("https://ror.org/", "", $rorid);
			if ( empty( $rorid ) ) {
				return false;
			}

			$rorid_encoded = rawurlencode( $rorid );
			$url           = "https://api.ror.org/organizations?query=%22{$rorid_encoded}%22";
			$timeout       = (int) $this->common->get_settings_field_value( 'timeout' );
			$timeout       = $timeout < 1 ? 1 : $timeout;

			// do http request.
			$response = wp_remote_request(
				$url,
				array(
					'method'  => 'GET',
					'headers' => array(
						'Accept' => 'application/json',
					),
					'timeout' => $timeout,
				),
			);

			// validate response.
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
				// invalid status_code.
				return false;
			}

			// parse response.
			$json_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				// invalid json.
				return false;
			}
			if ( 1 !== $json_data->number_of_results || 1 !== count( $json_data->items ) ) {
				// invalid rorid.
				return false;
			}

			return $json_data->items[0]->name . ', ' . $json_data->items[0]->country->country_name;
		}

		/**
		 * Retrieve the author affiliation name from orcid.org meta data given a orcid.
		 *
		 * The affiliation is extracted from the last employment meta data.
		 *
		 * @param int $user_id the id of the user who has a orcid is stored as meta field.
		 * @return string the affiliation name extracted from orcid.org
		 */
		protected function get_affiliation_from_orcid( $user_id ) {
			$orcid = trim( $this->common->get_user_meta_field_value( 'orcid_meta_key', $user_id ) );
			if ( empty( $orcid ) ) {
				return false;
			}

			$orcid_encoded = rawurlencode( $orcid );

			$url     = "https://pub.orcid.org/v3.0/{$orcid_encoded}/record";
			$timeout = (int) $this->common->get_settings_field_value( 'timeout' );
			$timeout = $timeout < 1 ? 1 : $timeout;

			// do http request.
			$response = wp_remote_request(
				$url,
				array(
					'method'  => 'GET',
					'headers' => array(
						'Accept' => 'application/xml',
					),
					'timeout' => $timeout,
				),
			);

			// validate response.
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
				// invalid status code.
				return false;
			}

			// parse response.
			$xml_data = wp_remote_retrieve_body( $response );
			$xml      = new SimpleXMLElement( $xml_data );
			$path     = '/record:record/activities:activities-summary' .
				'/activities:employments/activities:affiliation-group' .
				'/employment:employment-summary/common:organization';

			$organization_array = $xml->xpath( $path );
			if ( ! $organization_array || count( $organization_array ) < 1 ) {
				// no employment info.
				return false;
			}
			$name_array    = $organization_array[0]->xpath( 'common:name' );
			$country_array = $organization_array[0]->xpath( 'common:address/common:country' );

			if ( ! $name_array || count( $name_array ) < 1 ) {
				// no employment name.
				return false;
			}

			$affiliation = $name_array[0]->__toString();

			// add country to affiliation name.
			if ( $country_array && count( $country_array ) > 0 ) {
				// country available.
				$affiliation = $affiliation . ', ' . $country_array[0]->__toString();
			}
			return $affiliation;
		}

		/**
		 * Retrieve author affiliation from user meta data. First the textual affiliation name is returned.
		 * If not available, the affiliation name is extracted from ror.org. If not available, the affiliation name
		 * is extracted from orcid.org.
		 *
		 * @param int $user_id the id of the user whose affiliation is supposed to be retrieved.
		 * @return string the affiliation name
		 */
		public function retrieve_author_affiliation( $user_id ) {
			$affiliation = $this->get_textual_author_affiliation( $user_id );
			if ( ! empty( $affiliation ) ) {
				return $affiliation;
			}
			$extract_from_rorid = $this->common->get_settings_field_value( 'extract_from_rorid' );
			if ( $extract_from_rorid ) {
				$affiliation = $this->get_affiliation_from_rorid( $user_id );
				if ( ! empty( $affiliation ) ) {
					return $affiliation;
				}
			}
			$extract_from_orcid = $this->common->get_settings_field_value( 'extract_from_orcid' );
			if ( $extract_from_orcid ) {
				$affiliation = $this->get_affiliation_from_orcid( $user_id );
				if ( ! empty( $affiliation ) ) {
					return $affiliation;
				}
			}
			return '';
		}
	}

}
