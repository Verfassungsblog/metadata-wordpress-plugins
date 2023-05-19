<?php

require_once plugin_dir_path(__FILE__) . './class-vb-gnd-taxonomy_common.php';

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-gnd-taxonomy_admin_settings.php';
require_once plugin_dir_path(__FILE__) . '../admin/class-vb-gnd-taxonomy_admin_edit.php';


if (!class_exists('VB_GND_Taxonomy')) {

    class VB_GND_Taxonomy
    {
        protected $common;

        protected $plugin_version;

        protected $base_file;

        protected $admin_settings;

        protected $admin_edit;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->common = new VB_GND_Taxonomy_Common($plugin_name);
            $this->plugin_version = $plugin_version;
            $this->base_file = $base_file;
            $this->admin_settings = new VB_GND_Taxonomy_Admin_Settings($plugin_name);
            $this->admin_edit = new VB_GND_Taxonomy_Admin_Edit($plugin_name);
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
            // register gnd taxonomy
            $labels = array(
                'name'              => _x( 'GND Taxonomy', 'taxonomy general name', 'vb-gnd-taxonomy'),
                'singular_name'     => _x( 'GND Entity', 'taxonomy singular name', 'vb-gnd-taxonomy'),
                'search_items'      => __( 'Search GND Entities', 'vb-gnd-taxonomy'),
                'all_items'         => __( 'All Entities', 'vb-gnd-taxonomy'),
                'parent_item'       => __( 'Parent GND Entities', 'vb-gnd-taxonomy'),
                'parent_item_colon' => __( 'Parent GND Entity:', 'vb-gnd-taxonomy'),
                'edit_item'         => __( 'Edit GND Entity', 'vb-gnd-taxonomy'),
                'update_item'       => __( 'Update GND Entity', 'vb-gnd-taxonomy'),
                'add_new_item'      => __( 'Add New GND Entity', 'vb-gnd-taxonomy'),
                'new_item_name'     => __( 'New GND Entity Name', 'vb-gnd-taxonomy'),
                'menu_name'         => __( 'GND Taxonomy', 'vb-gnd-taxonomy'),
                'slug_field_description' => __('The GND-ID of the entity (not the URI). For example: ', 'vb-gnd-taxonomy') .
                    "<code>4036934-1</code><br>" . __('You can search for GND entities at ', 'vb-gnd-taxonomy') .
                    "<a href=\"https://lobid.org/gnd/\" target=\"_blank\">lobid.org</a>."
            );
            $args   = array(
                'hierarchical'      => false,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => [ 'slug' => 'gnd' ],
                'meta_box_sanitize_cb' => array($this->admin_edit, 'meta_box_sanitize_cb'),
            );
            register_taxonomy( 'gnd', [ 'post' ], $args );

            $merge_with_tags = $this->common->get_settings_field_value("merge_with_tags");
            if ($merge_with_tags) {
                add_filter("get_the_terms", array($this, 'filter_get_the_terms'), 10, 3);
            }

            load_plugin_textdomain(
                $this->common->plugin_name,
                false,
                dirname(plugin_basename($this->base_file)) . '/languages'
            );
        }

        public function filter_get_the_terms($terms, $post, $taxonomy)
        {
            if (is_admin() || $taxonomy != "post_tag") {
                // dont modify terms in admin area
                return $terms;
            }

            $gnd_terms = get_the_terms($post, "gnd");
            if (!empty($gnd_terms) && count($gnd_terms) > 0) {
                $terms = array_merge($terms, $gnd_terms);
                usort($terms, function($t1, $t2) {
                    return strcmp($t1->name, $t2->name);
                });
            }

            return $terms;
        }

        public function run()
        {
            register_activation_hook($this->base_file, array($this, 'activate'));
            register_deactivation_hook($this->base_file, array($this, 'deactivate'));
            register_uninstall_hook($this->base_file, 'vb_doaj_submit_uninstall');

            add_action("init", array($this, 'action_init'));

            $this->admin_settings->run();
            $this->admin_edit->run();
        }

    }

}