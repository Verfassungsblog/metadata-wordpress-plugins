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

        protected $oaipmh;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->common = new VB_Metadata_Export_Common($plugin_name);
            $this->admin = new VB_Metadata_Export_Admin($plugin_name);
            $this->shortcode = new VB_Metadata_Export_Shortcode($plugin_name);
            $this->oaipmh = new VB_Metadata_Export_OAI_PMH($plugin_name);
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
            // add rewrite rule to output custom metadata formats instead of html
            add_rewrite_tag('%' . $this->common->plugin_name . '%', '([^&]+)');

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

            if (isset($wp_query->query_vars[$this->common->plugin_name])) {
                $format = $wp_query->query_vars[$this->common->plugin_name];
                if (in_array($format, $this->common->get_available_formats())) {
                    return dirname($this->base_file) . '/public/' . $format . '.php';
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

            $template_priority = (int)$this->common->get_settings_field_value("template_priority");
            add_filter("template_include", array($this, 'action_template_include'), $template_priority, 1);

            $this->admin->run();
            $this->shortcode->run();
            $this->oaipmh->run();
        }

    }

}