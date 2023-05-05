<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Status')) {

    class VB_DOAJ_Submit_Status
    {
        protected $common;

        public function __construct($common)
        {
            $this->common = $common;
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
            return human_time_diff($date->getTimestamp() + $date->getOffset()) . " ago";
            # date_i18n("d-m-Y H:i:s e", $date->getTimestamp() + $date->getOffset());
        }

        public function action_init() {
            add_option($this->common->plugin_name . "_status_last_update", "never");
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
        }

    }

}