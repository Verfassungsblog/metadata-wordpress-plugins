<?php

require_once plugin_dir_path(__FILE__) . './class-vb-author-affiliations_common.php';
require_once plugin_dir_path(__FILE__) . '../admin/class-vb-author-affiliations_admin.php';
require_once plugin_dir_path(__FILE__) . '../admin/class-vb-author-affiliations_meta_box.php';

if (!class_exists('VB_Author_Affiliations')) {

    class VB_Author_Affiliations
    {
        protected $common;

        protected $base_file;

        protected $admin;

        protected $meta_box;

        public function __construct($base_file, $plugin_name)
        {
            $this->base_file = $base_file;
            $this->common = new VB_Author_Affiliations_Common($plugin_name);
            $this->admin = new VB_Author_Affiliations_Admin($plugin_name);
            $this->meta_box = new VB_Author_Affiliations_Meta_Box($plugin_name);
        }

        public function activate()
        {
            // empty
        }

        public function deactivate()
        {
            // empty
        }

        public function action_init()
        {
            load_plugin_textdomain(
                $this->common->plugin_name,
                false,
                dirname(plugin_basename($this->base_file)) . '/languages'
            );
        }

        public function filter_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
        {
            if (strpos($plugin_file, plugin_basename($this->base_file)) !== false ) {
                $developed_by = array(
                    "Developed by <a href=\"https://knopflogik.de/\" target=\"_blank\">knopflogik GmbH</a>",
                );

                $plugin_meta = array_merge($plugin_meta, $developed_by);
            }
            return $plugin_meta;
        }

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_author_affiliations_uninstall');

            add_action("init", array($this, 'action_init'));
            add_filter("plugin_row_meta", array($this, 'filter_plugin_row_meta'), 10, 4);

            $this->admin->run();
            $this->meta_box->run();
        }

    }

}