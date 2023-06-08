<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-crossref-doi_setting_fields.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi_common.php';

if (!class_exists('VB_CrossRef_DOI_Sanitize')) {

    class VB_CrossRef_DOI_Sanitize
    {
        protected $common;
        protected $settings_fields;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->settings_fields = new VB_CrossRef_DOI_Setting_Fields();
        }

        protected function get_field_from_option($option) {
            $field_name = preg_replace("/^" . $this->common->plugin_name . "_field_/", "", $option);
            $field_name = preg_replace("/_value$/", "", $field_name);
            $field = $this->settings_fields->get_field($field_name);
            if (empty($field)) {
                return array(
                    "label" => "unknown"
                );
            }
            return $field;
        }

        public function can_not_be_empty_text($input, $option)
        {
            $field = $this->get_field_from_option($option);
            if (empty(trim($input))) {
                add_settings_error(
                    $option,
                    "can-not-be-empty",
                    "\"" . $field["label"] . "\" can not be empty",
                    "error");
            }
            return trim($input);
        }

        public function can_not_be_empty_positive_integer($input, $option)
        {
            $field = $this->get_field_from_option($option);
            if (!is_numeric(trim($input))) {
                add_settings_error(
                    $option,
                    "must-be-numeric",
                    "\"" . $field["label"] . "\" must be number above 0",
                    "error"
                );
                return 1;
            }

            $number = (int)(trim($input));

            if ($number < 1) {
                add_settings_error(
                    $option,
                    "must-be-above-zero",
                    "\"" . $field["label"] . "\" must be positive number (>= 1)",
                    "error"
                );
                return max($number, 1);
            }

            return $number;
        }

        public function doi_suffix_length($input, $option)
        {
            $field = $this->get_field_from_option($option);
            if (!is_numeric(trim($input))) {
                add_settings_error(
                    $option,
                    "must-be-numeric",
                    "\"" . $field["label"] . "\" must be number between 12 and 64",
                    "error"
                );
                return 16;
            }

            $number = (int)(trim($input));
            if ($number < 12 || $number > 64) {
                add_settings_error(
                    $option,
                    "must-be-between-12-and-64",
                    "\"" . $field["label"] . "\" must be between 12 and 64",
                    "error"
                );
                return max(min($number, 64), 12);
            }

            return $number;
        }

        public function meta_key($input, $option)
        {
            $field = $this->get_field_from_option($option);
            if (!preg_match('/^[\w-]+$/', trim($input))) {
                $label = str_replace("<br>", " ", $field["label"]);
                add_settings_error(
                    $option,
                    "must-be-meta-key",
                    "\"" . $label . "\" can only consist of a single word (letters, digits, underscore and minus,
                    but no spaces, umlauts or other special characters).",
                    "error"
                );
                return preg_replace("/[^\w-]/", "", trim($input));
            }

            return trim($input);
        }

    }
}