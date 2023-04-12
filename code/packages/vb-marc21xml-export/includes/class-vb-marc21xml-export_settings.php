<?php

require_once plugin_dir_path(__FILE__) . 'class-vb-marc21xml-export_common.php';
require_once plugin_dir_path(__FILE__) . 'class-vb-marc21xml-export_renderer.php';

if (!class_exists('VB_Marc21Xml_Export_Settings')) {

    class VB_Marc21Xml_Export_Settings
    {

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Marc21Xml_Export_Common($plugin_name);
        }

        public function action_init()
        {
            add_settings_section(
                $this->common->settings_section_name,
                __("Settings"),
                array($this, 'callback_section'),
                $this->common->settings_page_name
            );

            $default_values = array();

            foreach (array_values($this->common->get_settings_fields()) as $i => $field) {
                $field_id = $this->common->get_value_field_id($field["name"]);

                // delete_option($field_id);
                register_setting($this->common->settings_page_name, $field_id);

                add_settings_field(
                    $field_id,
                    __($field["label"]),
                    array($this, 'callback_field'),
                    $this->common->settings_page_name,
                    $this->common->settings_section_name,
                    array(
                        'label_for' => $field["name"],
                    )
                );
            }
        }

        public function callback_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how Marc21 XML documents are created.',
                    "vb-marc21xml-export"
                );
                ?>
            </p>
            <?php
        }

        public function callback_field($args)
        {
            $field_name = $args['label_for'];
            $field_id = $this->common->get_value_field_id($field_name);
            $field = $this->common->get_settings_field_info($field_name);
            $option = get_option($field_id, $field["default"]);
            ?>
            <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" class="regular-text code"
                type="text" value="<?php echo $option ?>" placeholder="<?php echo $field["placeholder"] ?>">
            <p class="description">
                <?php echo $field["description"] ?>
            </p>
            <?php
        }

        public function action_admin_menu()
        {
            add_submenu_page(
                'options-general.php',
                'Verfassungsblog Marc21Xml Export',
                'VB Marc21Xml Export',
                'manage_options',
                'vb_marc21xml_export',
                array($this, 'render')
            );
        }

        public function render()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (isset($_GET['settings-updated'])) {
                add_settings_error(
                    'vb_marc21xml_export_messages',
                    'vb_marc21xml_export_message',
                    __('Settings Saved', "vb-marc21xml-export"),
                    'updated'
                );
            }

            ?>

            <div class="wrap">
                <h1>
                    <?php echo esc_html(get_admin_page_title()); ?>
                </h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields($this->common->settings_page_name);
                    do_settings_sections($this->common->settings_page_name);
                    submit_button(__('Save Settings', "vb-marc21xml-export"));
                    ?>
                </form>
                <?php

                $args = array(
                    'numberposts' => 1,
                );
                $posts = get_posts($args);
                $renderer = new VB_Marc21Xml_Export_Renderer($this->common->plugin_name);

                ?>
                <h2>
                    <?php echo __("Example", "vb-marc21xml-export") ?>
                </h2>

                <pre><?php echo esc_html($renderer->render($posts[0])) ?></pre>
            </div>
            <?php
        }

        public function run()
        {
            add_action('admin_init', array($this, 'action_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
        }

    }

}