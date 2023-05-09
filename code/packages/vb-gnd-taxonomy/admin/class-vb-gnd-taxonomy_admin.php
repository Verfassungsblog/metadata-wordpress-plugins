<?php

if (!class_exists('VB_GND_Taxonomy_Admin')) {

    class VB_GND_Taxonomy_Admin
    {
        protected $plugin_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
        }

        public function render_meta_box($post) {
            ?>
            <div class="<?php echo $this->plugin_name ?>_meta_box_content">
                <input type="text" id="<?php echo $this->plugin_name ?>_input" value="" />
                <input type="button" class="button" value="Add" />
            </div>
            <?php
        }

        public function add_meta_boxes() {
            add_meta_box(
                $this->plugin_name . "_meta_box",
                'GND Taxonomy',
                array($this, 'render_meta_box'),
                'post',
                'side',
                'default'
            );
        }

        public function admin_enqueue_scripts()
        {
            wp_enqueue_script(
                $this->plugin_name . '-admin-script',
                plugins_url("js/index.js", __FILE__),
                array('jquery'),
                filemtime(realpath(plugin_dir_path(__FILE__) . "js/index.js")),
                false
            );
        }

        public function action_init()
        {

            if (!is_admin()) {
                // nothing should be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action("add_meta_boxes", array($this, 'add_meta_boxes'));
        }

        public function run()
        {
            add_action("init", array($this, 'action_init'));

        }

    }

}