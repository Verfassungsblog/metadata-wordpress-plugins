<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_template.php';

if (!class_exists('VB_Metadata_Export_Shortcode')) {

    class VB_Metadata_Export_Shortcode
    {
        protected $plugin_name;

        protected $common;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
            $this->common = new VB_Metadata_Export_Common($plugin_name);
        }

        public function action_init()
        {
            add_shortcode($this->plugin_name . "-link", array($this, "render_link"));
        }

        public function render_link($atts, $content, $shortcode_tag)
        {
            $format = $atts["format"];

            if (!$this->common->is_valid_format($format)) {
                // format is mandatory argument
                return;
            }

            $attributes = shortcode_atts(
                array(
                    "format" => $format,
                    "title" => $this->common->get_format_labels()[$format],
                    "unavailable" => "",
                    "class" => "",
                ), $atts);

            return get_the_vb_metadata_export_link($format, $attributes["title"], $attributes["class"], $attributes["unavailable"]);
        }

        public function run()
        {
            add_action("init", array($this, 'action_init'));
        }

    }

}