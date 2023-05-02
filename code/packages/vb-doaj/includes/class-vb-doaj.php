<?php

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-doaj_admin.php';

if (!class_exists('VB_DOAJ')) {

    class VB_DOAJ
    {
        protected $common;

        protected $plugin_version;

        protected $base_file;

        protected $admin;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->common = new VB_DOAJ_Common($plugin_name);
            $this->admin = new VB_DOAJ_Admin($plugin_name);
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

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_doaj_uninstall');

            add_action("init", array($this, 'action_init'));

            $this->admin->run();
        }

    }

}