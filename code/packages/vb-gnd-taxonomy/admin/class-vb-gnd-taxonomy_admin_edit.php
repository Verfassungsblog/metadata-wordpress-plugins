<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-gnd-taxonomy_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-gnd-taxonomy_lobid.php';

if (!class_exists('VB_GND_Taxonomy_Admin_Edit')) {

    class VB_GND_Taxonomy_Admin_Edit
    {
        protected $common;

        protected $lobid;

        public function __construct($plugin_name)
        {
            $this->common = new VB_GND_Taxonomy_Common($plugin_name);
            $this->lobid = new VB_GND_Taxonomy_Lobid($plugin_name);
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

                $gnd_id = $this->common->extract_gnd_id_from_term($term);
                $term_name = trim(str_replace("[gnd:{$gnd_id}]", "", $term), " \n\t\r\0\x0B,");

                if (!empty($term_name)) {
                    // check if gnd entity with the same name already exists
                    $_term = get_terms(
                        array(
                            'taxonomy'   => $taxonomy,
                            'name'       => $term,
                            'fields'     => 'ids',
                            'hide_empty' => false,
                        )
                    );

                    if (!empty($_term)) {
                        // found term by name
                        $term_ids[] = (int) $_term[0];
                        continue;
                    }
                }

                if (!empty($gnd_id)) {
                    // check if term with corresponding slug already exists
                    $_term = get_term_by(
                        'slug', $gnd_id, $taxonomy
                    );

                    if (!empty($_term)) {
                        // found term by slug
                        $term_ids[] = (int)$_term->term_id;
                        continue;
                    }
                }

                // this is a new term (no term wiht same name or slug exists)
                if (empty($term_name)) {
                    // no term name given, invalid input
                    $this->common->set_last_error("No GND entity name given. Please provide a name for the GND
                        entity in the format of 'Entity Name [gnd:id]', e.g., 'Magdeburg [gnd:4036934-1]'.");
                    continue;
                }

                if (empty($gnd_id)) {
                    // no gnd id for term, invalid input
                    $this->common->set_last_error("No GND-ID found. Please provide a GND-ID in the format of
                        'Entity Name [gnd:id]', e.g., 'Magdeburg [gnd:4036934-1]'.");
                    continue;
                }

                $inserted_term = wp_insert_term(
                    $term_name,
                    $taxonomy,
                    array(
                        "slug" => $gnd_id,
                        "description" => $this->lobid->load_gnd_entity_description($gnd_id),
                    )
                );
                if (!empty($inserted_term) && !is_wp_error($inserted_term)) {
                    $term_ids[] = (int)$inserted_term["term_id"];
                    continue;
                }

                // something went wrong saving the new term
                if (is_wp_error($inserted_term)) {
                    $this->common->set_last_error($inserted_term->get_error_message());
                    continue;
                }

                $this->common->set_last_error("Unknown error adding new term '{$term_name}' with GND-ID '{$gnd_id}'");
            }

            return $term_ids;
        }

        public function admin_enqueue_scripts()
        {
            $suggest_enabled = $this->common->get_settings_field_value("suggest_enabled");
            if (!$suggest_enabled) {
                // skip adding any javascript that will perform autocomplete via lobid.org
                return;
            }
            wp_enqueue_script(
                $this->common->plugin_name . '-admin-script',
                plugins_url("js/index.js", __FILE__),
                array('jquery', 'jquery-ui-autocomplete'),
                filemtime(realpath(plugin_dir_path(__FILE__) . "js/index.js")),
                false
            );
            wp_localize_script(
                $this->common->plugin_name . '-admin-script',
                "vb_gnd_taxonomy_options",
                array(
                    "api_baseurl" => $this->common->get_settings_field_value("api_baseurl"),
                    "query_filter" => $this->common->get_settings_field_value("query_filter"),
                    "query_size" => $this->common->get_settings_field_value("query_size"),
                    "label_format" => $this->common->get_settings_field_value("label_format"),
                ),
            );
        }

        public function filter_pre_insert_term($term, $taxonomy, $args)
        {

            if ($taxonomy !== "gnd") {
                // do not do anything for other taxonomies
                return $term;
            }

            if (empty($term)) {
                return new WP_Error(
                    $this->common->plugin_name . "_empty_name",
                    "You need to provide a name for the GND entity."
                );
            }


            // check if gnd entity with the same name already exists
            $_term = get_terms(
                array(
                    'taxonomy'   => $taxonomy,
                    'name'       => $term,
                    'fields'     => 'ids',
                    'hide_empty' => false,
                )
            );

            if (!empty($_term)) {
                // found term by name
                return new WP_Error(
                    $this->common->plugin_name . "_name_exists",
                    "Another GND entity with same name already exists."
                );
            }

            // check if gnd id
            $gnd_id = $args["slug"];
            if (empty($gnd_id)) {
                return new WP_Error(
                    $this->common->plugin_name . "_empty_gndid",
                    "You have to provide a valid GND-ID as Slug."
                );
            }

            // check if slug already exists
            $_term = get_term_by(
                'slug', $gnd_id, $taxonomy
            );

            if (!empty($_term)) {
                // found term by slug
                return new WP_Error(
                    $this->common->plugin_name . "_slug_exists",
                    "Another entity with the same GND-ID already exists."
                );
            }

            $verify_gnd_id = $this->common->get_settings_field_value("verify_gnd_id");
            if ($verify_gnd_id) {
                if (!$this->lobid->check_gnd_entity_exists($gnd_id)) {
                    return new WP_Error(
                        $this->common->plugin_name . "_invalid_gndid",
                        "The provided GND-ID '${gnd_id}' does not exist in lobid.org."
                    );
                }
            }

            return $term;
        }

        public function action_admin_init()
        {
            add_action('admin_notices', array($this, 'admin_notices'));
        }

        public function admin_notices()
        {
            $error = $this->common->get_last_error();
            if (!empty($error)) {
                ?>
                <div class="error notice">
                    <p><?php echo "Error in " . $this->common->plugin_name . ": " . esc_html($error) ?></p>
                </div>
                <?php
                $this->common->clear_last_error();
            }
        }

        public function run()
        {
            if (!is_admin()) {
                // settings should not be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_init', array($this, 'action_admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_filter('pre_insert_term', array($this, 'filter_pre_insert_term'), 10, 3);

        }

    }
}
