<?php

if (!class_exists('VB_Marc21Xml_Export_Renderer')) {

    class VB_Marc21Xml_Export_Renderer
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Marc21Xml_Export_Common($plugin_name);
        }

        public function render_datafield_245($post)
        {
            $subfield_245a = $this->render_subfield_245a($post);
            $subfield_245c = $this->render_subfield_245c($post);

            $subfields = $subfield_245a . $subfield_245c;
            if (!empty($subfields)) {
                return "<marc21:datafield tag=\"245\" ind1=\"1\" ind2=\"0\">{$subfields}</marc21:datafield>";
            }
            return "";
        }

        public function render_subfield_245a($post)
        {
            $post_title = esc_html(get_the_title($post));

            if (!empty($post_title)) {
                return "<marc21:subfield code=\"a\">{$post_title}</marc21:subfield>";
            }
            return "";
        }

        public function render_subfield_245c($post)
        {
            $last_name = get_the_author_meta("last_name", $post->post_author);
            $first_name = get_the_author_meta("first_name", $post->post_author);

            $post_author = "";
            if (!empty($last_name) && !empty($first_name)) {
                $post_author = $last_name . ", " . $first_name;
            } else if (!empty($last_name)) {
                $post_author = $last_name;
            }
            if (!empty($post_author)) {
                return "<marc21:subfield code=\"c\">{$post_author}</marc21:subfield>";
            }
            return "";
        }

        public function render_control_number($post)
        {
            // control number definition see: https://www.loc.gov/marc/bibliographic/bd001.html
            $control_number = $post->ID;

            if (!empty($control_number)) {
                return "<marc21:controlfield tag=\"001\">{$control_number}</marc21:controlfield>";
            }
            return "";
        }

        public function render_leader($post)
        {
            // leader definition see: https://www.loc.gov/marc/bibliographic/bdleader.html
            $leader = get_option($this->common->get_value_field_id("leader"));
            # $leader = "     nam  22     uu 4500";
            if (!empty($leader)) {
                return "<marc21:leader>{$leader}</marc21:leader>";
            }
            return "";
        }

        public function render_datafield_773($post)
        {
            // https://www.loc.gov/marc/bibliographic/bd773.html
            $subfield_773a = $this->render_subfield_from_option("773a", "a"); // blog owner
            $subfield_773t = $this->render_subfield_from_option("773t", "t"); // blog title
            $subfield_773x = $this->render_subfield_from_option("773x", "x"); // issn

            $subfields = $subfield_773a . $subfield_773t . $subfield_773x;
            if (!empty($subfields)) {
                return "<marc21:datafield tag=\"773\" ind1=\"0\" ind2=\" \">{$subfields}</marc21:datafield>";
            }
            return "";
        }

        public function render_subfield_from_option($field_name, $subfield_code) {
            $default = $this->common->get_settings_field_info($field_name)["default"];
            $value = get_option($this->common->get_value_field_id($field_name), $default);
            if (!empty($value)) {
                return "<marc21:subfield code=\"{$subfield_code}\">{$value}</marc21:subfield>";
            }
            return "";
        }

        public function formatXml($xml_str)
        {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml_str);
            return $dom->saveXML();
        }

        public function render($post)
        {
            // marc21 definition see: https://www.loc.gov/marc/bibliographic/

            $xml_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $marc21_record_start = "<marc21:record xmlns:marc21=\"http://www.loc.gov/MARC21/slim\">\n";
            $marc21_record_end = "</marc21:record>";
            $datafield_245 = $this->render_datafield_245($post);
            $datafield_773 = $this->render_datafield_773($post);
            $leader = $this->render_leader($post);
            $control_number = $this->render_control_number($post);

            $xml_str = $xml_header . $marc21_record_start . $leader . $control_number . $datafield_245 . $datafield_773
                . $marc21_record_end;

            return $this->formatXml($xml_str);
        }

    }

}