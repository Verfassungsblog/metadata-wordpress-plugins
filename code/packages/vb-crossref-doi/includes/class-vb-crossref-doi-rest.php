<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi-common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi-status.php';

if (!class_exists('VB_CrossRef_DOI_REST')) {

    class VB_CrossRef_DOI_REST
    {
        protected $common;
        protected $status;
        protected $last_crossref_request_time;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->status = new VB_CrossRef_DOI_Status($plugin_name);
            $this->last_crossref_request_time = null;
        }

        protected function wait_for_next_request()
        {
            if (!empty($this->last_crossref_request_time)) {
                $last_request_ms = (int)$this->last_crossref_request_time->format('Uv');

                $now = new DateTime("now", new DateTimeZone("UTC"));
                $now_ms = (int)$now->format('Uv');

                $diff_ms = $now_ms - $last_request_ms;

                $max_requests_per_second = (float)$this->common->get_settings_field_value("requests_per_second");
                $max_requests_per_second = $max_requests_per_second < 1.0 ? 1.0 : $max_requests_per_second;
                $minimum_time_between_requests_ms = 1000 / $max_requests_per_second;

                if ($minimum_time_between_requests_ms > $diff_ms) {
                    $wait_time_ms = $minimum_time_between_requests_ms - $diff_ms;

                    // wait for next request in order not to trigger CrossRef rate limit
                    usleep($wait_time_ms * 1000);
                }
            }
        }

        protected function update_last_request_time()
        {
            // save last request time for next request
            $this->last_crossref_request_time = new DateTime("now", new DateTimeZone("UTC"));
        }

        protected function build_multipart_body($boundary, $elements, $xml)
        {
            $data = "";

            foreach($elements as $key => $value) {
                $data .= "--" . $boundary . "\r\n";
                $data .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n";
                $data .= $value . "\r\n";
            }

            if (!empty($xml)) {
                $data .= "--" . $boundary . "\r\n";
                $data .= "Content-Disposition: form-data; name=\"mdFile\"; filename=\"deposit.xml\"\r\n";
                $data .= "Content-Type: application/xml\r\n\r\n";
                $data .= $xml . "\r\n";
            }

            $data .= "--" . $boundary . "--\r\n";
            return $data;
        }

        protected function build_form_elements_for_authentication()
        {
            // check settings are available
            $api_user = $this->common->get_settings_field_value("api_user");
            if (empty($api_user)) {
                $this->status->set_last_error("CrossRef API user required for submitting new posts");
                return false;
            }

            $api_password = $this->common->get_settings_field_value("api_password");
            if (empty($api_password)) {
                $this->status->set_last_error("CrossRef API password required for submitting new posts");
                return false;
            }

            return array(
                "usr" => $api_user,
                "pwd" => $api_password,
            );
        }

        protected function build_request_parameters($form_elements, $xml = "")
        {
            $boundary = bin2hex(random_bytes(20));
            $body = $this->build_multipart_body($boundary, $form_elements, $xml);

            $params = array(
                "method" => "POST",
                "headers" => array(
                    "Content-Type" => "multipart/form-data; boundary=" . $boundary,
                    "Accept" =>  "application/xml",
                ),
                "timeout" => 30,
                "body" => $body,
            );

            return $params;
        }

        protected function parse_submission_response($post, $response)
        {
            // validate response
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $error_code = $response->get_error_code();
                if (!str_contains($error_message, "Operation timed out")) {
                    $this->status->set_last_error("[Request Error '" . $error_code . "'] " . $error_message);
                }
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 401) {
                $this->status->set_last_error("CrossRef API Key not correct (status code '" . $status_code . "')");
                return false;
            } else if ($status_code !== 200 && $status_code !== 403) {
                $this->status->set_last_error("CrossRef request has invalid status code '" . $status_code . "'");
                return false;
            }

            // parse 200 or 403 response
            $response_body = wp_remote_retrieve_body($response);
            $response_xml = new \DOMDocument();
            $response_xml->loadXML($response_body);
            $submission_id = $response_xml->getElementsByTagName('submission_id')->item(0)->nodeValue;
            if ($submission_id == 0) {
                // checking status for batch is maybe not yet known to crossref
                $msg = "submission unkown";
                $this->status->set_post_submit_error($post, $msg);
                return false;
            }

            $batch_id = $response_xml->getElementsByTagName('batch_id')->item(0)->nodeValue;
            $success_count = (int)$response_xml->getElementsByTagName('success_count')->item(0)->nodeValue;
            $warning_count = (int)$response_xml->getElementsByTagName('warning_count')->item(0)->nodeValue;
            $failure_count = (int)$response_xml->getElementsByTagName('failure_count')->item(0)->nodeValue;

            // check for failure (403 response)
            if ($status_code == 403 || $success_count < 1 || $failure_count > 0 || $warning_count > 0) {
                $msg = $response_xml->getElementsByTagName('msg')->item(0)->nodeValue;
                $this->status->set_post_submit_error($post, $msg);
                $this->status->set_post_submit_status($post, VB_CrossRef_DOI_Status::SUBMIT_ERROR);
                $this->status->set_last_error("CrossRef request failed with status code '" . $status_code . "' and
                    message '" . $msg . "' (batch id '" . $batch_id . "', submission id '" . $submission_id . "')");
                return false;
            }

            // retrieve doi from response
            $doi = $response_xml->getElementsByTagName('doi')->item(0)->nodeValue;

            // update post status
            $this->status->set_post_doi($post, $doi);
            $this->status->clear_post_submit_error($post);
            $this->status->set_post_submit_status($post, VB_CrossRef_DOI_Status::SUBMIT_SUCCESS);
            $this->status->set_post_submit_id($post, $submission_id);
        }

        public function submit_new_or_existing_post($post)
        {
            // check if potentially existing doi has correct prefix
            $doi_prefix = $this->common->get_settings_field_value("doi_prefix");
            $doi = $this->common->get_post_meta_field_value("doi_meta_key", $post);
            if (!empty($doi) && !str_starts_with($doi, $doi_prefix)) {
                // this is some other doi that we can not process
                $this->status->set_post_submit_status($post, VB_CrossRef_DOI_Status::SUBMIT_NOT_POSSIBLE);
            }

            // wait until a request does not cause a rate limit
            $this->wait_for_next_request();
            $this->update_last_request_time();

            // build request url
            $api_url_deposit = $this->common->get_settings_field_value("api_url_deposit");
            if (empty($api_url_deposit)) {
                $this->status->set_last_error("CrossRef Deposit API URL can not be empty!");
                return false;
            }

            // prepare submission
            $submit_timestamp = $this->common->get_current_utc_timestamp();
            $renderer = new VB_CrossRef_DOI_Render($this->common->plugin_name);
            $xml = $renderer->render($post, $submit_timestamp);
            if (empty($xml)) {
                $this->status->set_last_error($renderer->get_last_error());
                return false;
            }

            // build request content
            $form_elements = $this->build_form_elements_for_authentication();
            if (empty($form_elements)) {
                // some error occurred
                return false;
            }
            $form_elements["operation"] = "doMDUpload";

            // save submit timestamp
            $this->status->set_post_submit_timestamp($post, $submit_timestamp);
            $this->status->set_post_submit_status($post, VB_CrossRef_DOI_Status::SUBMIT_PENDING);

            // do http request
            $response = wp_remote_request($api_url_deposit, $this->build_request_parameters($form_elements, $xml));

            $this->parse_submission_response($post, $response);
            return true;
        }


        public function check_submission_result($post)
        {
            // wait until a request does not cause a rate limit
            $this->wait_for_next_request();
            $this->update_last_request_time();

            // build request url
            $api_url_submission = $this->common->get_settings_field_value("api_url_submission");
            if (empty($api_url_submission)) {
                $this->status->set_last_error("CrossRef Submission API URL can not be empty!");
                return false;
            }

            // check submission timestamp
            $submit_timestamp = $this->status->get_post_submit_timestamp($post);
            if (empty($submit_timestamp)) {
                // should not happen?
                $this->status->set_last_error("Trying to check submission status without prior submission?!?");
                return false;
            }

            // build form elements
            $form_elements = $this->build_form_elements_for_authentication();
            if (empty($form_elements)) {
                // some error occurred
                return false;
            }
            $form_elements["doi_batch_id"] = $post->ID . "." . $submit_timestamp;
            $form_elements["type"] = "result";

            // do http request
            $response = wp_remote_request($api_url_submission, $this->build_request_parameters($form_elements));

            $this->parse_submission_response($post, $response);
            return true;
        }

    }

}