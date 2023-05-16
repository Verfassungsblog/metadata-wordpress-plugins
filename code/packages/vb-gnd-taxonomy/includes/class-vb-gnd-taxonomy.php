<?php

require_once plugin_dir_path(__FILE__) . '../admin/class-vb-gnd-taxonomy_admin.php';
require_once plugin_dir_path(__FILE__) . './class-vb-gnd-taxonomy_common.php';

if (!class_exists('VB_GND_Taxonomy')) {

    class VB_GND_Taxonomy
    {
        protected $common;

        protected $plugin_version;

        protected $base_file;

        protected $admin;

        public function __construct($base_file, $plugin_name, $plugin_version)
        {
            $this->common = new VB_GND_Taxonomy_Common($plugin_name);
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

        protected function extract_gnd_id_from_term($term) {
            preg_match('/\[gnd:([^\]]+)\]/', $term, $matches);
            if ($matches) {
                return $matches[1];
            }
            return null;
        }

        protected function load_gnd_entity_description($gnd_id) {
            $url = "https://lobid.org/gnd/{$gnd_id}.json";
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
            ));

            // validate response
            if (is_wp_error($response)) {
                return "";
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code != 200) {
                return "";
            }

            // parse response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "";
            }
            if (!empty($json_data) && !empty($json_data->definition) && count($json_data->definition) > 0) {
                return $json_data->definition[0];
            }
            return "";
        }

        public function meta_box_sanitize_cb($taxonomy, $terms)
        {
            if ($taxonomy !== "gnd") {
                // only sanitize gnd taxonomy terms
                return array();
            }

            // split terms by comma
            if ( ! is_array( $terms ) ) {
                $terms = explode( ',', trim( $terms, " \n\t\r\0\x0B," ) );
            }

            $term_ids = array();
            foreach ( $terms as $term ) {
                // empty terms are invalid input.
                if ( empty( $term ) ) {
                    continue;
                }

                $gnd_id = $this->extract_gnd_id_from_term($term);
                $term_name = trim(str_replace("[gnd:{$gnd_id}]", "", $term), " \n\t\r\0\x0B,");

                if (empty($term_name)) {
                    // no term name
                    continue;
                }

                if (empty($gnd_id)) {
                    // check if gnd entity with that name already exists
                    $_term = get_terms(
                        array(
                            'taxonomy'   => $taxonomy,
                            'name'       => $term,
                            'fields'     => 'ids',
                            'hide_empty' => false,
                        )
                    );

                    if (empty($_term)) {
                        // invalid input (no gnd-id for non-existing entity)
                        continue;
                    } else {
                        $term_ids[] = (int) $_term[0];
                    }
                } else {
                    // gnd id is available
                    // check if term with corresponding slug already exists
                    $_term = get_term_by(
                        'slug', $gnd_id, $taxonomy
                    );

                    if (empty($_term)) {
                        // this is a new term
                        $inserted_term = wp_insert_term(
                            $term_name,
                            $taxonomy,
                            array(
                                "slug" => $gnd_id,
                                "description" => $this->load_gnd_entity_description($gnd_id),
                            )
                        );

                        if (!is_wp_error($inserted_term)) {
                            $term_ids[] = (int)$inserted_term["term_id"];
                        } else {
                            // something went wrong saving the new term
                            continue;
                        }

                    } else {
                        $term_ids[] = (int)$_term->term_id;
                    }
                }
            }

            return $term_ids;
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
                'meta_box_sanitize_cb' => array($this, 'meta_box_sanitize_cb'),
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


            $this->admin->run();
        }

    }

}