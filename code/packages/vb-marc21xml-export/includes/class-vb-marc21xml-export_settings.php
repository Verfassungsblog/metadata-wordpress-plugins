<?php

require_once plugin_dir_path(__FILE__) . 'class-vb-marc21xml-export_renderer.php';

if (!class_exists('VB_Marc21Xml_Export_Settings')) {

    class VB_Marc21Xml_Export_Settings
    {

        protected $plugin_name;

        protected $fields;

        protected $page_name;

        protected $settings_section;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
            $this->fields = array(
                array(
                    "name" => "leader",
                    "label" => "Marc21 Leader",
                    "placeholder" => "marc21 leader attribute",
                    "description" => "The Marc21 <a href=\"https://www.loc.gov/marc/bibliographic/bdleader.html\"
                        target=\"_blank\">leader attribute</a>, for example:
                         <pre><code>     nam  22     uu 4500</code></pre>",
                    "default" => "     nam  22     uu 4500",
                ),
                array(
                    "name" => "773a",
                    "label" => "Blog Owner<br>(Marc21 773a)",
                    "placeholder" => "blog owner",
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">main entry heading</a> of the host item entry, for example the blog owner.",
                    "default" => null,
                ),
                array(
                    "name" => "773t",
                    "label" => "Blog Title<br>(Marc21 773t)",
                    "placeholder" => "blog title",
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">title</a> of the host item entry, for example the blog title.",
                    "default" => get_bloginfo("name"),
                ),
                array(
                    "name" => "773x",
                    "label" => "ISSN<br>(Marc21 773x)",
                    "placeholder" => "ISSN",
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">International Standard Serial Number</a> (ISSN) of the host item entry, for
                    example the ISSN of this blog.",
                    "default" => null,
                )
            );
            $this->page_name = $plugin_name . "_settings";
            $this->settings_section = $this->page_name . "_section";
        }

        public function action_init()
        {
            add_settings_section(
                $this->settings_section,
                __("Settings"),
                array($this, 'callback_section'),
                $this->page_name
            );

            $default_values = array();

            foreach (array_values($this->fields) as $i => $field) {
                $field_id = $this->page_name . '_field_' . $field["name"] . '_value';

                // delete_option($field_id);
                register_setting($this->page_name, $field_id);

                add_settings_field(
                    $field_id,
                    __($field["label"]),
                    array($this, 'callback_field'),
                    $this->page_name,
                    $this->settings_section,
                    array(
                        'label_for' => $field_id,
                        'index' => $i
                    )
                );
            }



        }

        public function callback_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php esc_html_e('The following options influence how Marc21 XML documents are created.', $this->plugin_name); ?>
            </p>
            <?php
        }

        public function callback_field($args)
        {
            $field_id = $args['label_for'];
            $field = $this->fields[$args['index']];
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
                    settings_fields($this->page_name);
                    do_settings_sections($this->page_name);
                    submit_button(__('Save Settings', "vb-marc21xml-export"));
                    ?>
                </form>
                <?php

                $args = array(
                    'numberposts' => 1,
                );
                $posts = get_posts($args);
                $renderer = new VB_Marc21Xml_Export_Renderer();

                ?>
                <h2><?php echo __("Example", "vb-marc21xml-export") ?></h2>

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