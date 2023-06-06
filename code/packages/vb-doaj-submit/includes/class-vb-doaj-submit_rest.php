<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_status.php';

if (!class_exists('VB_DOAJ_Submit_REST')) {

    class VB_DOAJ_Submit_REST
    {
        protected $common;
        protected $status;
        protected $affiliation;
        protected $last_doaj_request_time;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($plugin_name);
            $this->last_doaj_request_time = null;
        }

        protected function wait_for_next_request()
        {
            if (!empty($this->last_doaj_request_time)) {
                $last_request_ms = (int)$this->last_doaj_request_time->format('Uv');

                $now = new DateTime("now", new DateTimeZone("UTC"));
                $now_ms = (int)$now->format('Uv');

                $diff_ms = $now_ms - $last_request_ms;

                $max_requests_per_second = (float)$this->common->get_settings_field_value("requests_per_second");
                $max_requests_per_second = $max_requests_per_second < 1.0 ? 1.0 : $max_requests_per_second;
                $minimum_time_between_requests_ms = 1000 / $max_requests_per_second;

                if ($minimum_time_between_requests_ms > $diff_ms) {
                    $wait_time_ms = $minimum_time_between_requests_ms - $diff_ms;

                    // wait for next request in order not to trigger DOAJ rate limit
                    usleep($wait_time_ms * 1000);
                }
            }
        }

        protected function update_last_request_time()
        {
            // save last request time for next request
            $this->last_doaj_request_time = new DateTime("now", new DateTimeZone("UTC"));
        }

        public function identify_post($post)
        {
            $issn = rawurlencode($this->common->get_settings_field_value("eissn"));
            if (empty($issn)) {
                $issn = rawurlencode($this->common->get_settings_field_value("pissn"));
            }

            if (empty($issn)) {
                $this->status->set_last_error("Either eISSN or pISSN needs to be provided!");
                return false;
            }

            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            if (empty($baseurl)) {
                $this->status->set_last_error("DOAJ api base URL can not be empty!");
                return false;
            }

            $identify_by_permalink = $this->common->get_settings_field_value("identify_by_permalink");
            if ($identify_by_permalink) {
                // identify by permalink
                $permalink = rawurlencode(get_the_permalink($post));

                // rewrite staging permalinks
                $permalink = str_replace("staging.verfassungsblog.de", "verfassungsblog.de", $permalink);

                if (empty($permalink)) {
                    $this->status->set_last_error("[ Post id=" . $post->ID . "] has no permalink?!");
                    return false;
                }
                $url = $baseurl . "search/articles/bibjson.link.url.exact:%22{$permalink}%22%20AND%20issn:%22{$issn}%22";
            } else {
                // identify by title
                $title = rawurlencode(get_the_title($post));
                if (empty($title)) {
                    $this->status->set_last_error("[ Post id=" . $post->ID . "] post has no title?!");
                    return false;
                }
                $url = $baseurl . "search/articles/bibjson.title.exact:%22{$title}%22%20AND%20issn:%22{$issn}%22";
            }

            // do http request
            $this->wait_for_next_request();
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
            ));
            $this->update_last_request_time();

            if (is_wp_error($response)) {
                $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $this->status->set_last_error("search request has invalid status code '" . $status_code . "'");
                return false;
            }
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->status->set_last_error("response is invalid json");
                return false;
            }

            if ($json_data->total == 1 && count($json_data->results) == 1) {
                // exact match found
                $article_id = $json_data->results[0]->id;
                $this->status->set_post_article_id($post, $article_id);
            } else {
                // no match found (article is new)
            }

            $this->status->set_post_identify_timestamp($post);
            return true;
        }

        public function submit_new_or_existing_post($post)
        {
            $renderer = new VB_DOAJ_Submit_Render($this->common->plugin_name);
            $json = $renderer->render($post);
            if (!$json) {
                $this->status->set_last_error($renderer->get_last_error());
                return false;
            }

            $article_id = get_post_meta($post->ID, $this->common->get_article_id_meta_key(), true);

            $test_without_api_key = $this->common->get_settings_field_value("test_without_api_key");
            if ($test_without_api_key) {
                if (empty($article_id)) {
                    // generate random article id for testing purposes
                    $article_id = uniqid();
                }

                // update post status
                $this->status->set_post_article_id($post, $article_id);
                $this->status->set_post_submit_timestamp($post);
                $this->status->set_post_identify_timestamp($post);

                return true;
            }

            $apikey = $this->common->get_settings_field_value("api_key");
            if (empty($apikey)) {
                $this->status->set_last_error("DOAJ api key required for submitting new posts");
                return false;
            }

            // build request url
            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            if (empty($baseurl)) {
                $this->status->set_last_error("DOAJ api base URL can not be empty!");
                return false;
            }
            if (empty($article_id)) {
                // new article
                $url = $baseurl . "articles?api_key=" . rawurlencode($apikey);
            } else {
                // update existing article
                $url = $baseurl . "articles/" . rawurlencode($article_id) . "?api_key=" . rawurlencode($apikey);
            }

            // do http request
            $this->wait_for_next_request();
            $response = wp_remote_request($url, array(
                "method" => empty($article_id) ? "POST" : "PUT",
                "headers" => array(
                    "Content-Type" => "application/json",
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
                "body" => $json,
            ));
            $this->update_last_request_time();

            // validate response
            if (is_wp_error($response)) {
                $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 401 || $status_code == 403) {
                $this->status->set_last_error("DOAJ api key not correct (status code '" . $status_code . "')");
                return false;
            } else if ($status_code !== 200) {
                $this->status->set_last_error("create new article request has invalid status code '" . $status_code . "'");
                return false;
            }

            // parse response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->status->set_last_error("response is invalid json");
                return false;
            }
            $article_id = $json_data->id;

            // update post status
            $this->status->set_post_article_id($post, $article_id);
            $this->status->set_post_submit_timestamp($post);
            $this->status->set_post_identify_timestamp($post);

            return true;
        }

        public function submit_trashed_post($post)
        {
            $article_id = get_post_meta($post->ID, $this->common->get_article_id_meta_key(), true);

            if (!empty($article_id)) {

                $test_without_api_key = $this->common->get_settings_field_value("test_without_api_key");
                if ($test_without_api_key) {
                    // update post status
                    $this->status->clear_post_article_id($post);
                    $this->status->set_post_submit_timestamp($post);
                    return true;
                }

                $apikey = $this->common->get_settings_field_value("api_key");
                if (empty($apikey)) {
                    $this->status->set_last_error("DOAJ api key required for deleting articles");
                    return false;
                }

                // build request url
                $baseurl = $this->common->get_settings_field_value("api_baseurl");
                if (empty($baseurl)) {
                    $this->status->set_last_error("DOAJ api base URL can not be empty!");
                    return false;
                }
                $url = $baseurl . "articles/" . rawurlencode($article_id) . "?api_key=" . rawurlencode($apikey);

                // do http request
                $this->wait_for_next_request();
                $response = wp_remote_request($url, array(
                    "method" => "DELETE",
                    "headers" => array(
                        "Accept" =>  "application/json",
                    ),
                    "timeout" => 30,
                ));
                $this->update_last_request_time();

                // validate response
                if (is_wp_error($response)) {
                    $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                    return false;
                }
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code == 401 || $status_code == 403) {
                    $this->status->set_last_error("DOAJ api key not correct (status code '" . $status_code . "')");
                    return false;
                } else if ($status_code !== 204) {
                    $this->status->set_last_error("delete article request has invalid status code '" . $status_code . "'");
                    return false;
                }

                // update post status
                $this->status->clear_post_article_id($post);
                $this->status->clear_post_submit_timestamp($post);
            } else {
                // do nothing for un-identified trashed article
            }

            // update global status
            return true;
        }

    }

}