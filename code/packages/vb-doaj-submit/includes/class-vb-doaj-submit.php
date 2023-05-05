<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_update.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_status.php';
require_once plugin_dir_path(__FILE__) . '../admin/class-vb-doaj-submit_admin.php';

if (!class_exists('VB_DOAJ_Submit')) {

    class VB_DOAJ_Submit
    {
        protected $common;

        protected $plugin_version;

        protected $base_file;

        protected $admin;

        protected $status;

        protected $update;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($this->common);
            $this->update = new VB_DOAJ_Submit_Update($this->common, $this->status);
            $this->admin = new VB_DOAJ_Submit_Admin($this->common, $this->status, $this->update);
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
            $this->status->run();
            $this->update->run();
        }

    }

}