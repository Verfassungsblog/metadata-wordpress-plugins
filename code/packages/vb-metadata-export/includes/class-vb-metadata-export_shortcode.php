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

        public function render_link($atts, $content, $shortcode_tag) {
            global $post;

            $format_labels = $this->common->get_format_labels();

            if (!array_key_exists("format", $atts) || !array_key_exists($atts["format"], $format_labels)) {
                // format is mandatory argument
                return;
            }

            $format = $atts["format"];
            $permalink = $this->common->get_the_permalink($format, $post);

            $attributes = shortcode_atts( array(
                "format" => "",
                "title" => $format_labels[$format],
                "unavailable" => "",
                "class" => "",
            ), $atts );

            $classes = implode(" ", array(
                $this->plugin_name . "-link",
                $this->plugin_name . "-" . $attributes["format"] . "-link",
                empty($marc21_permalink) ? $this->plugin_name . "-unavailable" : "",
                $attributes["class"],
            ));

            if (empty($permalink)) {
                return "<a class=\"{$classes}\">" . $attributes["unavailable"] . "</a>";
            }
            return "<a class=\"{$classes}\" href=\"{$permalink}\">" . $attributes["title"] . "</a>";
        }

        public function run()
        {
            add_action("init", array($this, 'action_init'));
        }

    }

}