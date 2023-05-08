<?php

if (!class_exists('VB_DOAJ_Submit_Affiliation')) {

    class VB_DOAJ_Submit_Affiliation
    {

        protected $common;

        public function __construct($common)
        {
            $this->common = $common;
        }

        protected function get_textual_author_affiliation($user_id)
        {
            return trim($this->common->get_user_meta_field_value("affiliation_meta_key", $user_id));
        }

        protected function get_rorid_author_affiliation($user_id)
        {
            $rorid = trim($this->common->get_user_meta_field_value("rorid_meta_key", $user_id));
            if (empty($rorid)) {
                return false;
            }

            $rorid_encoded = rawurlencode($rorid);
            $url = "https://api.ror.org/organizations?query=%22{$rorid_encoded}%22";
            // do http request
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
            ));

            // validate response
            if (is_wp_error($response)) {
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                return false;
            }

            // parse response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                // invalid json
                return false;
            }
            if ($json_data->number_of_results != 1 || count($json_data->items) != 1) {
                return false;
            }

            return $json_data->items[0]->name . ", " . $json_data->items[0]->country->country_name;
        }

        protected function find_orcid_author_affiliation($user_id)
        {
            $orcid = trim($this->common->get_user_meta_field_value("orcid_meta_key", $user_id));
            if (empty($orcid)) {
                return false;
            }

            $orcid_encoded = rawurlencode($orcid);

            $url = "https://pub.orcid.org/v3.0/{$orcid_encoded}/record";
            // do http request
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/xml",
                ),
            ));

            // validate response
            if (is_wp_error($response)) {
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                return false;
            }

            // parse response
            $xml_data = wp_remote_retrieve_body($response);
            $xml = new SimpleXMLElement($xml_data);
            $organization_array = $xml->xpath('/record:record/activities:activities-summary/activities:employments/activities:affiliation-group/employment:employment-summary/common:organization');
            if (!$organization_array || count($organization_array) < 1) {
                return false;
            }
            $name_array = $organization_array[0]->xpath("common:name");
            $country_array = $organization_array[0]->xpath("common:address/common:country");
            if (!$name_array || count($name_array) < 1 || !$country_array || count($country_array) < 1) {
                return false;
            }
            return $name_array[0]->__toString() . ", " .$country_array[0]->__toString();
        }

        public function find_author_affiliation($user_id)
        {
            $affiliation = $this->get_textual_author_affiliation($user_id);
            if (empty($affiliation)) {
                $affiliation = $this->get_rorid_author_affiliation($user_id);
            }
            if (empty($affiliation)) {
                $affiliation = $this->find_orcid_author_affiliation($user_id);
            }
            return $affiliation;
        }







    }

}