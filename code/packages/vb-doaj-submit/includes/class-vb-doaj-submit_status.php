<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Status')) {

    class VB_DOAJ_Submit_Status
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
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

        public function set_last_updated_post_modified_date($post) {
            return update_option($this->common->plugin_name . "_modified_date_of_last_updated_post", $post->post_modified_gmt);
        }

        public function get_last_updated_post_modified_date() {
            $date = get_option($this->common->plugin_name . "_modified_date_of_last_updated_post", false);
            if (empty($date)) {
                return false;
            }
            $datetime = new DateTime();
            $datetime->setTimezone(new DateTimeZone("UTC"));
            $datetime->setTimestamp(strtotime($date));
            return $datetime;
        }

        public function clear_last_updated_post_modified_date() {
            delete_option($this->common->plugin_name . "_modified_date_of_last_updated_post");
        }

        public function get_last_updated_post_modified_text() {
            $date = $this->get_last_updated_post_modified_date();
            if (empty($date)) {
                return "no post submitted yet";
            }
            $date->setTimezone(wp_timezone());
            $timestamp = $date->getTimestamp() + $date->getOffset();
            return date_i18n(get_option('date_format'), $timestamp) . " at " . date_i18n(get_option('time_format'), $timestamp);
        }

        public function set_post_submit_timestamp($post) {
            update_post_meta(
                $post->ID,
                $this->common->get_submit_timestamp_meta_key(),
                (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp()
            );
        }

        public function clear_post_submit_timestamp($post)
        {
            delete_post_meta($post->ID, $this->common->get_submit_timestamp_meta_key());
        }

        public function set_post_article_id($post, $article_id) {
            update_post_meta(
                $post->ID,
                $this->common->get_article_id_meta_key(),
                $article_id,
            );
        }

        public function clear_post_article_id($post) {
            delete_post_meta($post->ID, $this->common->get_article_id_meta_key());
        }

        public function set_post_identify_timestamp($post)
        {
            update_post_meta(
                $post->ID,
                $this->common->get_identify_timestamp_meta_key(),
                (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp()
            );
        }

        public function clear_post_identify_timestamp($post)
        {
            delete_post_meta($post->ID, $this->common->get_identify_timestamp_meta_key());
        }

        public function reset_status()
        {
            $this->clear_last_updated_post_modified_date();

            $meta_keys = array(
                $this->common->get_identify_timestamp_meta_key(),
                $this->common->get_submit_timestamp_meta_key(),
                $this->common->get_article_id_meta_key(),
            );

            foreach($meta_keys as $meta_key) {
                delete_metadata('post', 0, $meta_key, false, true);
            }

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