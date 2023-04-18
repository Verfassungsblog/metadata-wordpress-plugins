<?php

if (!class_exists('VB_Marc21Xml_Export_Renderer')) {

    class VB_Marc21Xml_Export_Renderer
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Marc21Xml_Export_Common($plugin_name);
        }

        protected function get_general_field_value($field_name)
        {
            return get_option($this->common->get_value_field_id($field_name));
        }

        protected function get_acf_field_value($field_name, $post)
        {
            if (!function_exists("get_field")) {
                return;
            }
            $doi_acf_key = get_option($this->common->get_value_field_id($field_name));
            return get_field($doi_acf_key, $post->ID);
        }

        protected function get_post_author($post) {
            $last_name = esc_html(get_the_author_meta("last_name", $post->post_author));
            $first_name = esc_html(get_the_author_meta("first_name", $post->post_author));

            $post_author = "";
            if (!empty($last_name) && !empty($first_name)) {
                $post_author = $last_name . ", " . $first_name;
            } else if (!empty($last_name)) {
                $post_author = $last_name;
            }
            return $post_author;
        }

        public function render_datafield_100($post)
        {
            $post_author = $this->get_post_author($post);
            if (!empty($post_author)) {
                return "<marc21:datafield tag=\"100\" ind1=\"1\" ind2=\" \">
                    <marc21:subfield code=\"a\">${post_author}</marc21:subfield>
                    <marc21:subfield code=\"e\">Author</marc21:subfield>
                    <marc21:subfield code=\"4\">aut</marc21:subfield>
                </marc21:datafield>";
            }
            return "";
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
            $post_author = $this->get_post_author($post);
            if (!empty($post_author)) {
                return "<marc21:subfield code=\"c\">{$post_author}</marc21:subfield>";
            }
            return "";
        }

        public function render_datafield_264($post)
        {
            $date = get_the_date("Y-m-d", $post);
            if(!empty($date)) {
                return "<marc21:datafield tag=\"264\" ind1=\" \" ind2=\"1\">
                    <marc21:subfield code=\"c\">{$date}</marc21:subfield>
                </marc21:datafield>";
            }
            return "";
        }

        public function render_datafield_336($post)
        {
            return "<marc21:datafield tag=\"336\" ind1=\" \" ind2=\" \">
                <marc21:subfield code=\"a\">Text</marc21:subfield>
                <marc21:subfield code=\"b\">txt</marc21:subfield>
                <marc21:subfield code=\"2\">rdacontent</marc21:subfield>
            </marc21:datafield>";
        }

        public function render_datafield_337($post)
        {
            return "<marc21:datafield tag=\"337\" ind1=\" \" ind2=\" \">
                <marc21:subfield code=\"a\">Computermedien</marc21:subfield>
                <marc21:subfield code=\"b\">c</marc21:subfield>
                <marc21:subfield code=\"2\">rdamedia</marc21:subfield>
            </marc21:datafield>";
        }

        public function render_datafield_338($post)
        {
            return "<marc21:datafield tag=\"338\" ind1=\" \" ind2=\" \">
                <marc21:subfield code=\"a\">Online-Ressource</marc21:subfield>
                <marc21:subfield code=\"b\">cr</marc21:subfield>
                <marc21:subfield code=\"2\">rdacarrier</marc21:subfield>
            </marc21:datafield>";
        }

        public function render_datafield_536($post) {
            $funding_general = $this->get_general_field_value("funding_general");
            $funding_acf = $this->get_acf_field_value("funding_acf", $post);
            $funding = !empty($funding_acf) ? $funding_acf : $funding_general;
            if (!empty($funding)) {
                return implode("", array(
                    "<marc21:datafield tag=\"536\" ind1=\" \" ind2=\" \">",
                    "<marc21:subfield code=\"a\">{$funding}</marc21:subfield>",
                    "</marc21:datafield>"
                ));
            }
            return "";
        }

        public function render_datafield_540($post) {
            $copyright_general = $this->get_general_field_value("copyright_general");
            $copyright_acf = $this->get_acf_field_value("copyright_acf", $post);
            $copyright = !empty($copyright_acf) ? $copyright_acf : $copyright_general;
            if (!empty($copyright)) {
                return implode("", array(
                    "<marc21:datafield tag=\"540\" ind1=\" \" ind2=\" \">",
                    "<marc21:subfield code=\"a\">{$copyright}</marc21:subfield>",
                    "</marc21:datafield>"
                ));
            }
            return "";
        }

        public function render_datafield_856($post)
        {
            $post_url = get_the_permalink($post);
            if (!empty($post_url)) {
                return implode("", array(
                    "<marc21:datafield tag=\"856\" ind1=\" \" ind2=\" \">",
                    "<marc21:subfield code=\"u\">{$post_url}</marc21:subfield>",
                    "<marc21:subfield code=\"y\">raw object</marc21:subfield>",
                    "</marc21:datafield>"
                ));
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
            $leader = esc_html(str_replace("_", " ", $this->get_general_field_value("leader")));
            if (!empty($leader)) {
                return "<marc21:leader>{$leader}</marc21:leader>";
            }
            return "";
        }

        public function render_datafield_084($post)
        {
            $global_ddc = $this->get_general_field_value("ddc_general");
            $post_ddc = $this->get_acf_field_value("ddc_acf", $post);
            $combined_ddc = array_merge(explode(",", $global_ddc), explode(",", $post_ddc));
            $trimmed_ddc = array_filter(array_map('trim', $combined_ddc));
            $xml = "";
            foreach ($trimmed_ddc as $ddc) {
                $xml = $xml . "<marc21:datafield tag=\"084\" ind1=\" \" ind2=\" \">
                    <marc21:subfield code=\"a\">{$ddc}</marc21:subfield>
                    <marc21:subfield code=\"2\">ddc</marc21:subfield>
                </marc21:datafield>";
            }
            return $xml;
        }

        public function render_datafield_773($post)
        {
            // https://www.loc.gov/marc/bibliographic/bd773.html
            $subfield_773a = $this->render_subfield_from_option("blog_owner", "a"); // blog owner
            $subfield_773t = $this->render_subfield_from_option("blog_title", "t"); // blog title
            $subfield_773x = $this->render_subfield_from_option("issn", "x"); // issn

            $subfields = $subfield_773a . $subfield_773t . $subfield_773x;
            if (!empty($subfields)) {
                return "<marc21:datafield tag=\"773\" ind1=\"0\" ind2=\" \">{$subfields}</marc21:datafield>";
            }
            return "";
        }

        public function render_doi($post)
        {
            $doi = esc_html($this->get_acf_field_value("doi_acf", $post));
            if (!empty($doi)) {
                return "<marc21:datafield tag=\"024\" ind1=\"7\" ind2=\" \">
                    <marc21:subfield code=\"a\">{$doi}</marc21:subfield>
                    <marc21:subfield code=\"2\">doi</marc21:subfield>
                </marc21:datafield>";
            }
            return "";
        }

        public function render_subfield_from_option($field_name, $subfield_code)
        {
            $default = $this->common->get_settings_field_info($field_name)["default"];
            $value = esc_html(get_option($this->common->get_value_field_id($field_name), $default));
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
            $xml_str = implode("", array(
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
                "<marc21:record xmlns:marc21=\"http://www.loc.gov/MARC21/slim\">\n",
                $this->render_leader($post),
                $this->render_control_number($post),
                $this->render_doi($post),
                $this->render_datafield_084($post),
                $this->render_datafield_100($post),
                $this->render_datafield_245($post),
                $this->render_datafield_264($post),
                $this->render_datafield_336($post),
                $this->render_datafield_337($post),
                $this->render_datafield_338($post),
                $this->render_datafield_536($post),
                $this->render_datafield_540($post),
                $this->render_datafield_773($post),
                $this->render_datafield_856($post),
                "</marc21:record>"
            ));

            return $this->formatXml($xml_str);
        }

    }

}