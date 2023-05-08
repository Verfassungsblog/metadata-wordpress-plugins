<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_status.php';

if (!class_exists('VB_DOAJ_Submit_REST')) {

    class VB_DOAJ_Submit_REST
    {
        protected $common;
        protected $status;

        public function __construct($common, $status)
        {
            $this->common = $common;
            $this->status = $status;
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
            $response = wp_remote_get($url);
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
            }

            $this->status->set_post_identify_timestamp($post);
            return true;
        }

        public function submit_new_or_existing_post($post)
        {
            $renderer = new VB_DOAJ_Submit_Render($this->common);
            $json = $renderer->render($post);
            if (!$json) {
                $this->status->set_last_error($renderer->get_last_error());
                return false;
            }

            $apikey = $this->common->get_settings_field_value("api_key");
            if (empty($apikey)) {
                $this->status->set_last_error("DOAJ api key required for submitting new posts");
                return false;
            }

            $article_id = get_post_meta($post->ID, $this->common->get_doaj_article_id_key(), true);

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
            $response = wp_remote_request($url, array(
                "method" => empty($article_id) ? "POST" : "PUT",
                "headers" => array(
                    "Content-Type" => "application/json",
                    "Accept" =>  "application/json",
                ),
                "body" => $json,
            ));

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
            $apikey = $this->common->get_settings_field_value("api_key");
            if (empty($apikey)) {
                $this->status->set_last_error("DOAJ api key required for deleting articles");
                return false;
            }

            $article_id = get_post_meta($post->ID, $this->common->get_doaj_article_id_key(), true);

            if (!empty($article_id)) {

                // build request url
                $baseurl = $this->common->get_settings_field_value("api_baseurl");
                if (empty($baseurl)) {
                    $this->status->set_last_error("DOAJ api base URL can not be empty!");
                    return false;
                }
                $url = $baseurl . "articles/" . rawurlencode($article_id) . "?api_key=" . rawurlencode($apikey);

                // do http request
                $response = wp_remote_request($url, array(
                    "method" => "DELETE",
                    "headers" => array(
                        "Accept" =>  "application/json",
                    ),
                ));

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
                $this->status->set_post_submit_timestamp($post);
            } else {
                // do nothing for un-identified trashed article
            }

            // update global status
            return true;
        }

    }

}