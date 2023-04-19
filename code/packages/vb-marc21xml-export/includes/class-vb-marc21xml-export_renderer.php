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
            $default = $this->common->get_settings_field_info($field_name)["default"];
            return get_option($this->common->get_value_field_id($field_name), $default);
        }

        protected function get_acf_post_field_value($field_name, $post)
        {
            if (!function_exists("get_field")) {
                return;
            }
            $default = $this->common->get_settings_field_info($field_name)["default"];
            $acf_key = get_option($this->common->get_value_field_id($field_name), $default);
            return get_field($acf_key, $post->ID);
        }

        protected function get_acf_user_field_value($field_name, $user_id)
        {
            if (!function_exists("get_field")) {
                return;
            }
            $field_id = $this->common->get_value_field_id($field_name);
            $default = $this->common->get_settings_field_info($field_name)["default"];
            $acf_key = get_option($field_id, $default);
            return get_field($acf_key, 'user_' . $user_id);
        }

        protected function render_subfield_from_value($value, $subfield_code)
        {
            if (!empty($value)) {
                return "<marc21:subfield code=\"{$subfield_code}\">{$value}</marc21:subfield>";
            }
            return "";
        }

        protected function render_subfield_from_option($field_name, $subfield_code)
        {
            $value = esc_html($this->get_general_field_value($field_name));
            return $this->render_subfield_from_value($value, $subfield_code);
        }

        protected function get_author_name($author)
        {
            $last_name = esc_html(get_the_author_meta("last_name", $author));
            $first_name = esc_html(get_the_author_meta("first_name", $author));

            $author = "";
            if (!empty($last_name) && !empty($first_name)) {
                $author = $last_name . ", " . $first_name;
            } else if (!empty($last_name)) {
                $author = $last_name;
            }
            return $author;
        }

        protected function get_coauthor_name($coauthor)
        {
            $last_name = esc_html($coauthor->last_name);
            $first_name = esc_html($coauthor->first_name);

            $author = "";
            if (!empty($last_name) && !empty($first_name)) {
                $author = $last_name . ", " . $first_name;
            } else if (!empty($last_name)) {
                $author = $last_name;
            }
            return $author;
        }

        protected function get_post_author_name($post)
        {
            return $this->get_author_name($post->post_author);
        }

        protected function get_post_coauthors($post)
        {
            if (!function_exists("get_coauthors")) {
                return array();
            }
            return array_slice(get_coauthors($post->ID), 1);
        }

        protected function get_post_language($post) {
            $language = esc_html($this->get_general_field_value("language"));
            $language_alternate = esc_html($this->get_general_field_value("language_alternate"));
            $language_alternate_category = esc_html($this->get_general_field_value("language_alternate_category"));
            $categories = array_map(function($category) { return $category->name; }, get_the_category($post->ID));
            if (in_array($language_alternate_category, $categories)) {
                $language = $language_alternate;
            }
            return $language;
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

        public function render_control_numbers($post)
        {
            // control number definition see: https://www.loc.gov/marc/bibliographic/bd001.html
            $control_number = $post->ID;
            $date = get_the_date("ymd", $post);
            $date = empty($date) ? "||||||" : $date;
            $year = get_the_date("Y", $post);
            $year = empty($year) ? "||||" : $year;
            $language = $this->get_post_language($post);
            $language = empty($language) ? "|||" : $language;

            if (!empty($control_number)) {
                return "
                    <marc21:controlfield tag=\"001\">{$control_number}</marc21:controlfield>
                    <marc21:controlfield tag=\"007\">cr|||||</marc21:controlfield>
                    <marc21:controlfield tag=\"008\">{$date}s{$year}||||xx#|||||o|||| ||| 0|{$language}||</marc21:controlfield>
                ";
            }
            return "";
        }

        public function render_subfield_orcid($user_id)
        {
            $orcid = $this->get_acf_user_field_value("orcid_acf", $user_id);
            if (!empty($orcid)) {
                $orcid = "(orcid)" .$orcid;
            }
            return $this->render_subfield_from_value($orcid, "0");
        }

        public function render_datafield_024($post)
        {
            $doi = esc_html($this->get_acf_post_field_value("doi_acf", $post));
            if (!empty($doi)) {
                return "<marc21:datafield tag=\"024\" ind1=\"7\" ind2=\" \">
                    <marc21:subfield code=\"a\">{$doi}</marc21:subfield>
                    <marc21:subfield code=\"2\">doi</marc21:subfield>
                </marc21:datafield>";
            }
            return "";
        }

        public function render_datafield_041($post)
        {
            $language = $this->get_post_language($post);
            if (!empty($language)) {
                return "<marc21:datafield tag=\"041\" ind1=\" \" ind2=\" \">
                    <marc21:subfield code=\"a\">{$language}</marc21:subfield>
                </marc21:datafield>";
            }
            return "";
        }

        public function render_datafield_084($post)
        {
            $global_ddc = $this->get_general_field_value("ddc_general");
            $post_ddc = $this->get_acf_post_field_value("ddc_acf", $post);
            $combined_ddc = array_merge(explode(",", $global_ddc), explode(",", $post_ddc));
            $trimmed_ddc = array_filter(array_map('trim', $combined_ddc));
            $xml = "";
            foreach ($trimmed_ddc as $ddc) {
                $xml = $xml . "<marc21:datafield tag=\"082\" ind1=\"0\" ind2=\"4\">
                    <marc21:subfield code=\"a\">{$ddc}</marc21:subfield>
                    <marc21:subfield code=\"2\">23</marc21:subfield>
                </marc21:datafield>";
            }
            return $xml;
        }

        public function render_datafield_100($post)
        {
            $post_author = $this->get_post_author_name($post);
            if (!empty($post_author)) {
                return implode("", array(
                    "<marc21:datafield tag=\"100\" ind1=\"1\" ind2=\" \">",
                    "<marc21:subfield code=\"a\">${post_author}</marc21:subfield>",
                    "<marc21:subfield code=\"e\">Author</marc21:subfield>",
                    "<marc21:subfield code=\"4\">aut</marc21:subfield>",
                    $this->render_subfield_orcid($post->post_author),
                    "</marc21:datafield>")
                );
            }
            return "";
        }

        public function render_datafield_245($post)
        {
            $title = esc_html(get_the_title($post));
            $subheadline = esc_html($this->get_acf_post_field_value("subheadline_acf", $post));
            $author_name = $this->get_post_author_name($post);

            $subfields = implode("", array(
                $this->render_subfield_from_value($title, "a"),
                $this->render_subfield_from_value($subheadline, "b"),
                $this->render_subfield_from_value($author_name, "c"),
            ));

            if (!empty($subfields)) {
                return "<marc21:datafield tag=\"245\" ind1=\"1\" ind2=\"0\">{$subfields}</marc21:datafield>";
            }
            return "";
        }

        public function render_datafield_264($post)
        {
            $publisher = esc_html($this->get_general_field_value("publisher"));
            $date = get_the_date("Y-m-d", $post);

            $subfields = implode("", array(
                $this->render_subfield_from_value($publisher, "b"),
                $this->render_subfield_from_value($date, "c"),
            ));

            if (!empty($subfields)) {
                return "<marc21:datafield tag=\"264\" ind1=\" \" ind2=\"1\">
                    {$subfields}
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

        public function render_datafield_536($post)
        {
            $funding_general = $this->get_general_field_value("funding_general");
            $funding_acf = $this->get_acf_post_field_value("funding_acf", $post);
            $funding = !empty($funding_acf) ? $funding_acf : $funding_general;
            if (!empty($funding)) {
                return implode(
                    "",
                    array(
                        "<marc21:datafield tag=\"536\" ind1=\" \" ind2=\" \">",
                        "<marc21:subfield code=\"a\">{$funding}</marc21:subfield>",
                        "</marc21:datafield>"
                    )
                );
            }
            return "";
        }

        public function render_datafield_540($post)
        {
            $copyright_general = $this->get_general_field_value("copyright_general");
            $copyright_acf = $this->get_acf_post_field_value("copyright_acf", $post);
            $copyright = !empty($copyright_acf) ? $copyright_acf : $copyright_general;
            if (!empty($copyright)) {
                return implode(
                    "",
                    array(
                        "<marc21:datafield tag=\"540\" ind1=\" \" ind2=\" \">",
                        "<marc21:subfield code=\"a\">{$copyright}</marc21:subfield>",
                        "</marc21:datafield>"
                    )
                );
            }
            return "";
        }

        public function render_datafield_650($post)
        {
            $tags = get_the_tags($post);
            $tags = $tags ? $tags : array();
            $xml = "";
            foreach($tags as $tag) {
                $tag_escaped = esc_html($tag->name);
                $xml = $xml . "<marc21:datafield tag=\"650\" ind1=\"1\" ind2=\"4\">
                        <marc21:subfield code=\"a\">{$tag_escaped}</marc21:subfield>
                    </marc21:datafield>
                ";
            }
            return $xml;
        }

        public function render_datafield_700($post)
        {
            $coauthors = $this->get_post_coauthors($post);
            $xml = "";
            foreach ($coauthors as $coauthor) {
                $coauthor_name = $this->get_coauthor_name($coauthor);
                if (empty($coauthor_name)) {
                    continue;
                }
                $xml = $xml . implode("", array(
                    "<marc21:datafield tag=\"700\" ind1=\"1\" ind2=\" \">",
                    "<marc21:subfield code=\"a\">{$coauthor_name}</marc21:subfield>",
                    "<marc21:subfield code=\"e\">Author</marc21:subfield>",
                    "<marc21:subfield code=\"4\">aut</marc21:subfield>",
                    $this->render_subfield_orcid($coauthor->ID),
                    "</marc21:datafield>",
                ));
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

        public function render_datafield_856($post)
        {
            $post_url = get_the_permalink($post);
            if (!empty($post_url)) {
                return implode(
                    "",
                    array(
                        "<marc21:datafield tag=\"856\" ind1=\" \" ind2=\" \">",
                        "<marc21:subfield code=\"u\">{$post_url}</marc21:subfield>",
                        "<marc21:subfield code=\"y\">raw object</marc21:subfield>",
                        "</marc21:datafield>"
                    )
                );
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
            $xml_str = implode(
                "",
                array(
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
                    "<marc21:record xmlns:marc21=\"http://www.loc.gov/MARC21/slim\">\n",
                    $this->render_leader($post),
                    $this->render_control_numbers($post),
                    $this->render_datafield_024($post),
                    $this->render_datafield_041($post),
                    $this->render_datafield_084($post),
                    $this->render_datafield_100($post),
                    $this->render_datafield_245($post),
                    $this->render_datafield_264($post),
                    $this->render_datafield_336($post),
                    $this->render_datafield_337($post),
                    $this->render_datafield_338($post),
                    $this->render_datafield_536($post),
                    $this->render_datafield_540($post),
                    $this->render_datafield_650($post),
                    $this->render_datafield_700($post),
                    $this->render_datafield_773($post),
                    $this->render_datafield_856($post),
                    "</marc21:record>"
                )
            );

            return $this->formatXml($xml_str);
        }

    }

}