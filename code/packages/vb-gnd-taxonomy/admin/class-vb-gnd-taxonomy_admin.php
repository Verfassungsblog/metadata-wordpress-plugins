<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-gnd-taxonomy_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-gnd-taxonomy_setting_fields.php';

if (!class_exists('VB_GND_Taxonomy_Admin')) {

    class VB_GND_Taxonomy_Admin
    {
        protected $common;

        protected $setting_fields;

        public function __construct($plugin_name)
        {
            $this->common = new VB_GND_Taxonomy_Common($plugin_name);
            $this->setting_fields = new VB_GND_Taxonomy_Setting_Fields();
        }

        protected function get_tab_labels()
        {
            return array(
                "settings" => "Settings",
            );
        }

        protected function get_settings_section_labels()
        {
            return array(
                "general" => "General",
            );
        }

        protected function get_tab_by_section_map()
        {
            return array(
                "general" => "settings",
            );
        }

        protected function get_setting_page_id_by_tab($tab_name)
        {
            return $this->common->plugin_name . "_" . $tab_name . "_tab_settings";
        }

        protected function get_render_function_by_tab($tab_name)
        {
            return array($this, "render_" . $tab_name . "_tab");
        }

        protected function get_setting_section_render_function_by_name($section_name)
        {
            return array($this, 'render_' . $section_name . '_section');
        }

        public function render_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how GND entities are suggested and visualized.',
                    "vb-gnd-taxonomy"
                );
                ?>
            </p>
            <?php
        }

        public function render_field($args)
        {
            $field_id = $args['label_for'];
            $field_name = $args["field_name"];
            $field = $this->setting_fields->get_field($field_name);
            $value = get_option($field_id);
            if ($field["type"] == "boolean") {
                ?>
                <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" type="checkbox"
                    <?php echo $value ? "checked" : "" ?>
                >
                <?php
            } else {
                ?>
                <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" class="regular-text code"
                    type="text" value="<?php echo esc_attr($value) ?>" placeholder="<?php echo $field["placeholder"] ?>">
                <?php
            }
            ?>
            <p class="description">
                <?php echo $field["description"] ?>
            </p>
            <?php
        }

        public function action_admin_menu()
        {
            if (!current_user_can('manage_options')) {
                // admin menu should not be loaded for non-admin users
                return;
            }

            $admin_page_hook = add_submenu_page(
                'options-general.php',
                'Verfassungsblog GND Taxonomy',
                'VB GND Taxonomy',
                'manage_options',
                $this->common->plugin_name,
                array($this, 'render')
            );

            add_action("load-" . $admin_page_hook, array($this, "help_tab"));
            add_action("admin_print_styles-{$admin_page_hook}", array($this, "action_admin_print_styles"));
        }

        public function action_admin_print_styles()
        {
            wp_enqueue_style($this->common->plugin_name . "-admin-styles");
        }

        public function help_tab()
        {
            $screen = get_current_screen();
            $screen->add_help_tab(
                array(
                    "id" => $this->common->plugin_name . "_help_tab",
                    "title" => __("Help"),
                    "content" => "<h2>Verfassungsblog GND Taxonomy</h2>",
                )
            );
        }

        public function render()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (!empty($_POST["reset_settings"])) {
                foreach ($this->setting_fields->get_list() as $field) {
                    $field_id = $this->common->get_settings_field_id($field["name"]);
                    delete_option($field_id);
                }
            }

            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : "settings";
            $current_tab = isset($this->get_tab_labels()[$current_tab]) ? $current_tab : "settings";

            ?>
            <div class="vb-gnd-taxonomy-admin-header">
                <div class="vb-gnd-taxonomy-title-section">
                    <h1>
                        <?php echo esc_html(get_admin_page_title()); ?>
                    </h1>
                </div>
                <nav class="vb-gnd-taxonomy-admin-header-nav">
                    <?php
                    foreach ($this->get_tab_labels() as $tab_name => $tab_label) {
                        ?>
                        <a class="vb-gnd-taxonomy-admin-header-tab <?php echo $current_tab == $tab_name ? "active" : "" ?>"
                            href="?page=<?php echo $this->common->plugin_name ?>&tab=<?php echo $tab_name ?>">
                            <?php echo $tab_label ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
            </div>
            <hr class="wp-header-end">
            <div class="vb-gnd-taxonomy-admin-content">
                <?php
                call_user_func_array($this->get_render_function_by_tab($current_tab), array());
                ?>
            </div>
            <div class="clear"></div>
            <?php
        }

        public function render_settings_tab()
        {
            ?>
            <?php
            $settings_page_id = $this->get_setting_page_id_by_tab("settings");
            ?>
            <form action="options.php" method="post">
                <?php
                settings_fields($settings_page_id);
                do_settings_sections($settings_page_id);
                submit_button(__('Save Settings', "vb-gnd-taxonomy"));
                ?>
            </form>
            <hr />
            <form method="post" onsubmit="return confirm('Are you sure?');">
                <p>
                    The following action will reset all options of this plugin to their default value
                    (including options in other tabs). Use with care only.
                </p>
                <?php
                submit_button(__('Reset Settings to Default', "vb-gnd-taxonomy"), "secondary", "reset_settings");
                ?>
            </form>
            <?php
        }

        public function render_meta_box($post) {
            ?>
            <div class="<?php echo $this->common->plugin_name ?>_meta_box_content">
                <input type="text" id="<?php echo $this->common->plugin_name ?>_input" value="" />
                <input type="button" class="button" value="Add" />
            </div>
            <?php
        }

        public function add_meta_boxes() {
            add_meta_box(
                $this->common->plugin_name . "_meta_box",
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
                ),
            );
        }

        public function action_admin_init()
        {
            if (!current_user_can('manage_options')) {
                // settings are not allowed for non-admin users
                return;
            }

            // create sections
            $section_labels = $this->get_settings_section_labels();
            $tab_by_section = $this->get_tab_by_section_map();


            foreach ($section_labels as $section_name => $section_label) {
                add_settings_section(
                    $section_name,
                    $section_label,
                    $this->get_setting_section_render_function_by_name($section_name),
                    $this->get_setting_page_id_by_tab($tab_by_section[$section_name])
                );
            }

            // create fields
            foreach ($this->setting_fields->get_list() as $field) {
                $field_id = $this->common->get_settings_field_id($field["name"]);
                $default = $this->common->get_settings_field_default_value($field["name"]);
                $settings_page_id = $this->get_setting_page_id_by_tab($tab_by_section[$field["section"]]);

                register_setting(
                    $settings_page_id,
                    $field_id,
                    array(
                        "type" => $field["type"],
                        "default" => $default
                    )
                );

                add_settings_field(
                    $field_id,
                    __($field["label"]),
                    array($this, 'render_field'),
                    $settings_page_id,
                    $field["section"],
                    array(
                        'label_for' => $field_id,
                        "field_name" => $field["name"]
                    )
                );
            }

            // add css
            wp_register_style(
                $this->common->plugin_name . '-admin-styles',
                plugins_url("css/settings.css", __FILE__),
                array(),
                filemtime(realpath(plugin_dir_path(__FILE__) . "css/settings.css")),
                "screen"
            );
        }

        public function action_wp_head()
        {
            ?>
            <script>

            </script>
            <?php
        }

        public function run()
        {
            if (!is_admin()) {
                // settings should not be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_init', array($this, 'action_admin_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action("add_meta_boxes", array($this, 'add_meta_boxes'));
            add_action('wp_head', array($this, 'action_wp_head'));
        }

    }

}