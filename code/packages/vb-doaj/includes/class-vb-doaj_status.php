<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj_common.php';

if (!class_exists('VB_DOAJ_Status')) {

    class VB_DOAJ_Status
    {
        protected $common;

        public function __construct($common)
        {
            $this->common = $common;
        }

        public function set_last_update() {
            return update_option($this->common->plugin_name . "_status_last_update", time());
        }

        public function get_last_update() {
            $timestamp = get_option($this->common->plugin_name . "_status_last_update", false);
            if (empty($timestamp)) {
                return false;
            }
            $datetime = (new DateTime())->setTimestamp($timestamp)->setTimezone(wp_timezone());
            $time_format = get_option('time_format');
            $date_format = get_option('date_format');
            return date_i18n("d-m-Y H:i:s e", $datetime->getTimestamp() + $datetime->getOffset());
        }

        public function action_init() {
            add_option($this->common->plugin_name . "_status_last_update", "never");
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
        }

    }

}