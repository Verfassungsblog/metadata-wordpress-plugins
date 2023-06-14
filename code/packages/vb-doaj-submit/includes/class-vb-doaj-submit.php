<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit-common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit-update.php';
require_once plugin_dir_path(__FILE__) . '../admin/class-vb-doaj-submit-admin.php';

if (!class_exists('VB_DOAJ_Submit')) {

    class VB_DOAJ_Submit
    {
        protected $common;

        protected $base_file;

        protected $admin;

        protected $update;

        public function __construct($base_file, $plugin_name)
        {
            $this->base_file = $base_file;
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->update = new VB_DOAJ_Submit_Update($plugin_name);
            $this->admin = new VB_DOAJ_Submit_Admin($plugin_name);
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
            register_uninstall_hook($this->base_file, 'vb_doaj_submit_uninstall');

            add_action("init", array($this, 'action_init'));

            $this->admin->run();
            $this->update->run();
        }

    }

}