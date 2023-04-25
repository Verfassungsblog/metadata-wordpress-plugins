<?php

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-metadata-export_admin.php';

if (!class_exists('VB_Metadata_Export')) {

    class VB_Metadata_Export
    {
        protected $plugin_name;

        protected $plugin_version;

        protected $base_file;

        protected $admin;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_name = $plugin_name;
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->admin = new VB_Metadata_Export_Admin($plugin_name);
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
            add_shortcode("vb-metadata-export-marc21xml-link", array($this, "shortcode_marc21xml_link"));
            register_block_type("vb-metadata-export/marc21xml_link", array(
                "api_version" => 2,
                "title" => "Marc21 XML Link",
                "description" => "Adds a link to download the post metadata as Marc21 XML document.",
                "category" => "text",
                "icon" => "star",
                "render_callback" => array($this, "shortcode_marc21xml_link")
            ));

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

        public function get_the_marc21xml_permalink () {
            $permalink = get_the_permalink() ?? get_post_permalink();
            if (empty($permalink)) {
                return;
            }
            if (str_contains($permalink, "?")) {
                return $permalink . "&marc21xml";
            }
            return $permalink . "?marc21xml";
        }

        public function shortcode_marc21xml_link() {
            $marc21_permalink = $this->get_the_marc21xml_permalink();
            if (empty($marc21_permalink)) {
                return "<a>Marc21 XML (not available)</a>";
            }
            return "<a href=\"{$marc21_permalink}\">Marc21 XML</a>";
        }

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_metadata_export_uninstall');

            add_action("init", array($this, 'action_init'));
            add_action("template_include", array($this, 'action_template_include'));

            $this->admin->run();
        }

    }

}