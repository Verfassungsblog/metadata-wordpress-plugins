<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-doaj-submit_render.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-doaj-submit_setting_fields.php';

if (!class_exists('VB_DOAJ_Submit_Admin')) {

    class VB_DOAJ_Submit_Admin
    {
        protected $setting_fields;

        protected $setting_pages_by_tab;

        protected $setting_section_names;

        protected $tab_render_functions;

        protected $common;

        protected $status;

        protected $update;

        public function __construct($common, $status, $update)
        {
            $this->common = $common;
            $this->status = $status;
            $this->update = $update;
            $this->setting_fields = new VB_DOAJ_Submit_Setting_Fields();
        }

        protected function get_tab_labels()
        {
            return array(
                "settings" => "Settings",
                "example" => "Example",
                "status" => "Status",
            );
        }

        protected function get_settings_section_labels()
        {
            return array(
                "general" => "General",
                "post_acf" => "Advanced Custom Fields for Posts",
                "user_acf" => "Advanced Custom Fields for Users",
            );
        }

        protected function get_tab_by_section_map()
        {
            return array(
                "general" => "settings",
                "post_acf" => "settings",
                "user_acf" => "settings",
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

        public function action_init()
        {
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

        public function render_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how metadata is submitted to the DOAJ.',
                    "vb-doaj"
                );
                ?>
            </p>
            <?php
        }

        public function render_post_acf_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each individual post via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.",
                    "vb-doaj"
                );
                ?>
            </p>
            <?php
        }

        public function render_user_acf_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each author (or user) via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.
                    The corresponding ACF field needs to be assigned to users instead of posts. This can be achieved by
                    an ACF \"location rule\" for the field group: <code>User Role : is equal to : All</code>.",
                    "vb-doaj-export"
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
                    type="text" value="<?php echo $value ?>" placeholder="<?php echo $field["placeholder"] ?>">
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
                'Verfassungsblog DOAJ Submit',
                'VB DOAJ Submit',
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
                    "content" => "<h2>Verfassungsblog DOAJ Submit</h2>",
                )
            );
        }

        public function render()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (!empty($_POST["reset"])) {
                foreach ($this->setting_fields->get_list() as $field) {
                    $field_id = $this->common->get_settings_field_id($field["name"]);
                    delete_option($field_id);
                }
            }

            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : "settings";
            $current_tab = isset($this->get_tab_labels()[$current_tab]) ? $current_tab : "settings";

            ?>
            <div class="vb-doaj-submit-admin-header">
                <div class="vb-doaj-submit-title-section">
                    <h1>
                        <?php echo esc_html(get_admin_page_title()); ?>
                    </h1>
                </div>
                <nav class="vb-doaj-submit-admin-header-nav">
                    <?php
                    foreach ($this->get_tab_labels() as $tab_name => $tab_label) {
                        ?>
                        <a class="vb-doaj-submit-admin-header-tab <?php echo $current_tab == $tab_name ? "active" : "" ?>"
                            href="?page=<?php echo $this->common->plugin_name ?>&tab=<?php echo $tab_name ?>">
                            <?php echo $tab_label ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
            </div>
            <hr class="wp-header-end">
            <div class="vb-doaj-submit-admin-content">
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
                submit_button(__('Save Settings', "vb-doaj"));
                ?>
            </form>
            <hr />
            <form method="post" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="reset" value="true" />
                <p>
                    The following action will reset all options of this plugin to their default value
                    (including options in other tabs). Use with care only.
                </p>
                <?php
                submit_button(__('Reset Settings to Default', "vb-doaj"), "secondary", "reset");
                ?>
            </form>
            <?php
        }

        public function render_example_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            if (count($posts) >= 1) {
                $renderer = new VB_DOAJ_Submit_Render($this->common);
                $doaj_data = $renderer->render($posts[0]);
                ?>
                <h2>
                    <?php echo __("Example", "vb-doaj-submit") ?>
                </h2>

                <pre><?php echo htmlspecialchars($doaj_data) ?></pre>

                <?php
            }
        }

        public function render_status_tab()
        {
            // empty
            ?>
            <ul>
                <li>Automatic Update: <?php echo $this->common->get_settings_field_value("auto_update") ? "enabled" : "disabled" ?></li>
                <li>Update Interval: <?php echo $this->update->get_update_interval_in_minutes() ?> min</li>
                <li>Last Update: <?php echo $this->status->get_last_update_text() ?></li>
            </ul>
            <?php
        }

        public function run()
        {
            if (!is_admin()) {
                // settings should not be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_init', array($this, 'action_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
        }

    }

}