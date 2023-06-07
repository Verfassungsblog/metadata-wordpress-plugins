<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Status')) {

    class VB_DOAJ_Submit_Status
    {
        public const SUBMIT_SUCCESS = "success";
        public const SUBMIT_PENDING = "pending";
        public const SUBMIT_ERROR = "error";
        public const SUBMIT_MODIFIED = "modified";

        protected $common;

        protected $date_of_last_modified_check_option_key;

        protected $last_error_option_key;

        protected $last_update_option_key;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->date_of_last_modified_check_option_key = $plugin_name . "_date_of_last_modified_check";
            $this->last_error_option_key = $plugin_name . "_status_last_error";
            $this->last_update_option_key = $plugin_name . "_status_last_update";
        }

        public function set_last_error($error) {
            return update_option($this->last_error_option_key, $error);
        }

        public function get_last_error() {
            return get_option($this->last_error_option_key, false);
        }

        public function clear_last_error() {
            delete_option($this->last_error_option_key);
        }

        public function set_last_update() {
            return update_option($this->last_update_option_key, time());
        }

        public function get_last_update_date() {
            $timestamp = get_option($this->last_update_option_key, false);
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
                $timestamp = $this->common->get_current_utc_timestamp();
            }
            return update_option($this->date_of_last_modified_check_option_key, $timestamp);
        }

        public function get_date_of_last_modified_check() {
            $timestamp = get_option($this->date_of_last_modified_check_option_key, false);
            if (empty($timestamp)) {
                // no date available, set it
                $this->set_date_of_last_modified_check();
                $timestamp = $this->common->get_current_utc_timestamp();
            }
            return (new DateTime())->setTimestamp($timestamp)->setTimezone(wp_timezone());
        }

        public function get_text_of_last_modified_check()
        {
            $date = $this->get_date_of_last_modified_check();
            return human_time_diff($date->getTimestamp()) . " ago";
        }

        public function clear_date_of_last_modified_check() {
            delete_option($this->date_of_last_modified_check_option_key);
        }

        public function set_post_submit_timestamp($post) {
            update_post_meta(
                $post->ID,
                $this->common->get_submit_timestamp_meta_key(),
                $this->common->get_current_utc_timestamp()
            );
        }

        public function clear_post_submit_timestamp($post)
        {
            delete_post_meta($post->ID, $this->common->get_submit_timestamp_meta_key());
        }

        public function set_post_submit_status($post, $status) {
            update_post_meta(
                $post->ID,
                $this->common->get_post_submit_status_meta_key(),
                $status,
            );
        }

        public function clear_post_submit_status($post)
        {
            delete_post_meta($post->ID, $this->common->get_post_submit_status_meta_key());
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

        public function set_post_doaj_article_id($post, $article_id) {
            update_post_meta(
                $post->ID,
                $this->common->get_doaj_article_id_meta_key(),
                $article_id,
            );
        }

        public function get_post_doaj_article_id($post) {
            return get_post_meta(
                $post->ID,
                $this->common->get_doaj_article_id_meta_key(),
                true,
            );
        }

        public function clear_post_doaj_article_id($post) {
            delete_post_meta($post->ID, $this->common->get_doaj_article_id_meta_key());
        }

        public function set_post_identify_timestamp($post)
        {
            update_post_meta(
                $post->ID,
                $this->common->get_identify_timestamp_meta_key(),
                $this->common->get_current_utc_timestamp()
            );
        }

        public function clear_post_identify_timestamp($post)
        {
            delete_post_meta($post->ID, $this->common->get_identify_timestamp_meta_key());
        }

        public function reset_post_status($post)
        {
            $this->clear_post_doaj_article_id($post);
            $this->clear_post_submit_timestamp($post);
            $this->clear_post_identify_timestamp($post);
            $this->clear_post_submit_error($post);
            $this->clear_post_submit_status($post);
        }

        public function reset_status()
        {
            $meta_keys = array(
                $this->common->get_identify_timestamp_meta_key(),
                $this->common->get_submit_timestamp_meta_key(),
                $this->common->get_post_submit_error_meta_key(),
                $this->common->get_post_submit_status_meta_key(),
                $this->common->get_doaj_article_id_meta_key(), // TODO: remove for final version?!?
            );

            foreach($meta_keys as $meta_key) {
                delete_metadata('post', 0, $meta_key, false, true);
            }

            $this->clear_last_error();
            $this->clear_date_of_last_modified_check();
        }

    }

}