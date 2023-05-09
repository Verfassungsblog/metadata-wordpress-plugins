<?php

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-gnd-taxonomy_admin.php';

if (!class_exists('VB_GND_Taxonomy')) {

    class VB_GND_Taxonomy
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
            $this->admin = new VB_GND_Taxonomy_Admin($plugin_name);
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
                $this->plugin_name,
                false,
                dirname(plugin_basename($this->base_file)) . '/languages'
            );
        }

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_doaj_submit_uninstall');

            add_action("init", array($this, 'action_init'));

            $this->admin->run();
        }

    }

}