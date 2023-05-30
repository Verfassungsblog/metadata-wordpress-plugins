<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_update.php';

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-crossref-doi_admin.php';

if (!class_exists('VB_CrossRef_DOI')) {

    class VB_CrossRef_DOI
    {
        protected $common;

        protected $base_file;

        protected $admin;

        protected $update;

        public function __construct($base_file, $plugin_name)
        {
            $this->base_file = $base_file;
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->admin = new VB_CrossRef_DOI_Admin($plugin_name);
            $this->update = new VB_CrossRef_DOI_Update($plugin_name);
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
            register_uninstall_hook($this->base_file, 'vb_crossref_doi_uninstall');

            add_action("init", array($this, 'action_init'));

            $this->admin->run();
            $this->update->run();
        }

    }

}