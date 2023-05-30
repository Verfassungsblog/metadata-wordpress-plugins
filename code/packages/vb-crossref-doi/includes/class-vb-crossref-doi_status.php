<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';

if (!class_exists('VB_CrossRef_DOI_Status')) {

    class VB_CrossRef_DOI_Status
    {
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

        public function set_post_doi($post, $doi) {
            update_post_meta(
                $post->ID,
                $this->common->get_doi_meta_key(),
                $doi,
            );
        }

        public function reset_status()
        {
            $meta_keys = array(
                $this->common->get_submit_timestamp_meta_key(),
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