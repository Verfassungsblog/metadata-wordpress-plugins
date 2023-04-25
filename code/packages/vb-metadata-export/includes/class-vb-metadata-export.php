<?php

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-metadata-export_admin.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_shortcode.php';

if (!class_exists('VB_Metadata_Export')) {

    class VB_Metadata_Export
    {
        protected $common;

        protected $plugin_version;

        protected $base_file;

        protected $admin;

        protected $shortcode;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->common = new VB_Metadata_Export_Common($plugin_name);
            $this->admin = new VB_Metadata_Export_Admin($plugin_name);
            $this->shortcode = new VB_Metadata_Export_Shortcode($plugin_name);
        }

        public function activate()
        {
            global $wp_rewrite;
            $wp_rewrite->flush_rules(false);
        }

        public function deactivate()
        {

        }

        public function action_init()
        {
            # add_rewrite_rule('^marc21/?$', 'index.php?vb_metadata_export=true', 'top');
            add_rewrite_tag('%marc21xml%', '([^&]+)');

            /*register_block_type("vb-metadata-export/marc21xml-link", array(
                "api_version" => 2,
                "title" => "Marc21 XML Link",
                "description" => "Adds a link to download the post metadata as Marc21 XML document.",
                "category" => "text",
                "icon" => "star",
                "render_callback" => array($this, "shortcode_marc21xml_link")
            ));*/

            load_plugin_textdomain(
                $this->common->plugin_name,
                false,
                dirname(plugin_basename($this->base_file)) . '/languages'
            );
        }

        public function action_template_include($template)
        {
            global $wp_query;
            global $post;

            if (isset($_GET[$this->common->plugin_name]) && is_single()) {
                $format = $_GET[$this->common->plugin_name];
                if (in_array($format, $this->common->get_available_formats())) {
                    if ($this->common->is_format_available($format, $post)) {
                        return dirname($this->base_file) . '/public/' . $format . '.php';
                    }
                }
            }
            return $template;
        }

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_metadata_export_uninstall');

            add_action("init", array($this, 'action_init'));
            add_action("template_include", array($this, 'action_template_include'));

            $this->admin->run();
            $this->shortcode->run();
        }

    }

}