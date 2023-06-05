<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';

if (!class_exists('VB_CrossRef_DOI_Status')) {

    class VB_CrossRef_DOI_Status
    {
        public const SUBMIT_SUCCESS = "success";
        public const SUBMIT_ERROR = "error";
        public const SUBMIT_PENDING = "pending";
        public const SUBMIT_NOT_POSSIBLE = "not-possible";
        public const SUBMIT_MODIFIED = "modified";

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
        }

        public function set_last_error($error) {
            return update_option($this->common->plugin_name . "_status_last_error", $error);
        }

        public function get_last_error() {
            return get_option($this->common->plugin_name . "_status_last_error", false);
        }

        public function clear_last_error() {
            delete_option($this->common->plugin_name . "_status_last_error");
        }

        public function set_last_update() {
            return update_option($this->common->plugin_name . "_status_last_update", time());
        }

        public function get_last_update_date() {
            $timestamp = get_option($this->common->plugin_name . "_status_last_update", false);
            if (empty($timestamp)) {
                return false;
            }
            return (new DateTime())->setTimestamp($timestamp)->setTimezone(wp_timezone());
        }

        public function get_last_update_text() {
            $date = $this->get_last_update_date();
            if (empty($date)) {
                return "never";
            }
            return human_time_diff($date->getTimestamp()) . " ago";
        }

        public function set_date_of_last_modified_check($timestamp = null) {
            if ($timestamp == null) {
                $timestamp = (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp();
            }
            return update_option($this->common->plugin_name . "_date_of_last_modified_check", $timestamp);
        }

        public function get_date_of_last_modified_check() {
            $timestamp = get_option($this->common->plugin_name . "_date_of_last_modified_check", false);
            if (empty($timestamp)) {
                // no date available, set it
                $this->set_date_of_last_modified_check();
                $timestamp = (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp();
            }
            return (new DateTime())->setTimestamp($timestamp)->setTimezone(wp_timezone());
        }

        public function get_text_of_last_modified_check()
        {
            $date = $this->get_date_of_last_modified_check();
            return human_time_diff($date->getTimestamp()) . " ago";
        }

        public function clear_date_of_last_modified_check() {
            delete_option($this->common->plugin_name . "_date_of_last_modified_check");
        }

        public function get_post_submit_timestamp($post)
        {
            return get_post_meta(
                $post->ID,
                $this->common->get_post_submit_timestamp_meta_key(),
                true
            );
        }

        public function set_post_submit_timestamp($post, $timestamp) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_submit_timestamp_meta_key(),
                $timestamp,
            );
        }

        public function clear_post_submit_timestamp($post_id)
        {
            delete_post_meta($post_id, $this->common->get_post_submit_timestamp_meta_key());
        }

        public function set_post_submit_id($post, $submission_id) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_submit_id_meta_key(),
                $submission_id,
            );
        }

        public function set_post_submit_status($post, $status) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_submit_status_meta_key(),
                $status,
            );
        }

        public function get_post_submit_error($post) {
            return get_post_meta(
                $post->ID,
                $this->common->get_post_submit_error_meta_key(),
                true,
            );
        }

        public function set_post_submit_error($post, $msg) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_submit_error_meta_key(),
                $msg,
            );
        }

        public function clear_post_submit_error($post) {
            delete_post_meta(
                $post->ID,
                $this->common->get_post_submit_error_meta_key(),
            );
        }


        public function set_post_doi($post, $doi) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_doi_meta_key(),
                $doi,
            );
        }

        public function reset_status()
        {
            $meta_keys = array(
                $this->common->get_post_submit_timestamp_meta_key(),
                $this->common->get_post_submit_id_meta_key(),
                $this->common->get_post_submit_error_meta_key(),
                $this->common->get_post_submit_status_meta_key(),
            );

            foreach($meta_keys as $meta_key) {
                delete_metadata('post', 0, $meta_key, false, true);
            }

            $this->clear_date_of_last_modified_check();
            $this->clear_last_error();
        }

        public function action_init() {
            add_option($this->common->plugin_name . "_status_last_update", 0);
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
        }

    }

}