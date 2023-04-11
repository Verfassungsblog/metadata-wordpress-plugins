<?php

require_once plugin_dir_path(__FILE__) . 'class-vb-marc21xml-export_settings.php';

if (!class_exists('VB_Marc21Xml_Export')) {

    class VB_Marc21Xml_Export
    {
        protected $plugin_name;

        protected $plugin_version;

        protected $base_file;

        protected $settings;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_name = $plugin_name;
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->settings = new VB_Marc21Xml_Export_Settings($plugin_name);
        }

        public function activate()
        {
            global $wp_rewrite;
            $wp_rewrite->flush_rules(false);
        }

        public function deactivate()
        {

        }

        public function uninstall()
        {

        }

        public function action_init()
        {
            # add_rewrite_rule('^marc21/?$', 'index.php?vb_marc21xml_export=true', 'top');
            add_rewrite_tag('%marc21xml%', '([^&]+)');

            load_plugin_textdomain(
                $this->plugin_name,
                false,
                dirname(plugin_basename($this->base_file)) . '/languages'
            );
        }

        public function action_template_include($template)
        {
            global $wp_query;

            if (isset($wp_query->query_vars['marc21xml']) && is_single()) {
                return dirname($this->base_file) . '/public/index.php';
            }
            return $template;
        }

        public function run()
        {


            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, array($this, 'uninstall'));

            add_action("init", array($this, 'action_init'));
            add_action("template_include", array($this, 'action_template_include'));

            $this->settings->run();
        }

    }

}