<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';

if (!class_exists('VB_Metadata_Export_DC')) {

    class VB_Metadata_Export_DC
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
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
            $language = esc_html($this->common->get_settings_field_value("language"));
            $language_alternate_category = esc_html($this->common->get_settings_field_value("language_alternate_category"));
            if (!empty($language_alternate_category)) {
                $categories = array_map(function($category) { return $category->name; }, get_the_category($post->ID));
                if (in_array($language_alternate_category, $categories)) {
                    $language_alternate = esc_html($this->common->get_settings_field_value("language_alternate"));
                    $language = $language_alternate;
                }
            }
            return $language;
        }

        public function render_identifier($post)
        {
            $doi = esc_html($this->common->get_post_meta_field_value("doi_meta_key", $post));
            $post_url = get_the_permalink($post);

            $xml = implode("", array(
                !empty($doi) ? "<dc:identifier>http://dx.doi.org/" . esc_html($doi) . "</dc:identifier>" : "",
                !empty($post_url) ? "<dc:identifier>" . esc_html($post_url) . "</dc:identifier>" : "",
            ));

            return $xml;
        }

        public function render_relation($post)
        {
            $blog_title = $this->common->get_settings_field_value("blog_title");
            $issn = $this->common->get_settings_field_value("issn");
            $relation = $blog_title;
            if (!empty($issn)) {
                $relation = $relation . "--" . $issn;
            }
            if (!empty($relation)) {
                return "<dc:relation>". esc_html($relation) . "</dc:relation>";
            }
            return "";
        }

        public function render_language($post)
        {
            $language = $this->get_post_language($post);
            if (!empty($language)) {
                return "<dc:language>{$language}</dc:language>";
            }
            return "";
        }

        public function render_ddc_subjects($post)
        {
            $global_ddc = $this->common->get_settings_field_value("ddc_general");
            $post_ddc = $this->common->get_post_meta_field_value("ddc_meta_key", $post);
            $combined_ddc = array_merge(explode(",", $global_ddc), explode(",", $post_ddc));
            $trimmed_ddc = array_filter(array_map('trim', $combined_ddc));

            $subjects = array();
            foreach ($trimmed_ddc as $ddc) {
                $subjects = array_merge($subjects,
                    array("<dc:subject>ddc:" . esc_html($ddc) ."</dc:subject>")
                );
            }
            return implode("", $subjects);
        }

        public function render_tags($post)
        {
            $tags = get_the_tags($post);
            $tags = $tags ? $tags : array();
            $subjects = array();
            foreach($tags as $tag) {
                $subjects = array_merge($subjects,
                    array("<dc:subject>" . esc_html($tag->name) ."</dc:subject>")
                );
            }
            return implode("", $subjects);
        }

        public function render_gnd_subjects($post)
        {
            $gnd_terms = get_the_terms($post, "gnd");
            $gnd_terms = !empty($gnd_terms) && !is_wp_error($gnd_terms) ? $gnd_terms : array();
            $subjects = array();
            foreach($gnd_terms as $gnd_term) {
                $subjects = array_merge($subjects,
                    array("<dc:subject>" . esc_html($gnd_term->name) ."</dc:subject>")
                );
            }
            return implode("", $subjects);
        }

        public function render_author($post)
        {
            $post_author = $this->get_post_author_name($post);
            if (!empty($post_author)) {
                return "<dc:creator>{$post_author}</dc:creator>";
            }
            return "";
        }

        public function render_coauthors($post)
        {
            $coauthors = $this->get_post_coauthors($post);
            $xml = "";
            foreach ($coauthors as $coauthor) {
                $coauthor_name = $this->get_coauthor_name($coauthor);
                if (empty($coauthor_name)) {
                    continue;
                }
                $xml = $xml . "<dc:creator>{$coauthor_name}</dc:creator>";
            }
            return $xml;
        }

        public function render_title($post)
        {
            $title = esc_html(get_the_title($post));
            $subheadline = esc_html($this->common->get_post_meta_field_value("subheadline_meta_key", $post));
            $include_subheadline = $this->common->get_settings_field_value("include_subheadline");
            if ($include_subheadline && !empty($subheadline)) {
                $title = $title . " - " . $subheadline;
            }

            if (!empty($title)) {
                return "<dc:title>{$title}</dc:title>";
            }
            return "";
        }

        public function render_excerpt($post)
        {
            $include_excerpt = $this->common->get_settings_field_value("include_excerpt");
            if ($include_excerpt) {
                $excerpt = esc_html(strip_tags(get_the_excerpt($post)));
                if (!empty($excerpt)) {
                    return "<dc:description>{$excerpt}</dc:description>";
                }
            }
            return "";
        }

        public function render_publisher($post)
        {
            $publisher = $this->common->get_settings_field_value("publisher");
            if (!empty($publisher)) {
                return "<dc:publisher>{$publisher}</dc:publisher>";
            }
            return "";
        }

        public function render_copyright($post)
        {
            $copyright_general = $this->common->get_settings_field_value("copyright_general");
            $copyright_custom = $this->common->get_post_meta_field_value("copyright_meta_key", $post);
            $copyright = !empty($copyright_custom) ? $copyright_custom : $copyright_general;
            if (!empty($copyright)) {
                return "<dc:rights>{$copyright}</dc:rights>";
            }
            return "";
        }

        public function render_date($post)
        {
            $date = get_the_date("Y-m-d", $post);

            if (!empty($date)) {
                return "<dc:date>{$date}</dc:date>";
            }
            return "";
        }

        public function render_type($post)
        {
            return "<dc:type>electronic resource</dc:type>";
        }

        public function render_format($post)
        {
            return "<dc:format>text/html</dc:format>";
        }

        public function render($post)
        {
            $xml_str = implode(
                "",
                array(
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
                    "<dc xmlns=\"http://www.openarchives.org/OAI/2.0/oai_dc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd http://dublincore.org/schemas/xmls/simpledc20021212.xsd\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\n",
                    $this->render_identifier($post),
                    $this->render_title($post),
                    $this->render_author($post),
                    $this->render_coauthors($post),
                    $this->render_language($post),
                    $this->render_date($post),
                    $this->render_type($post),
                    $this->render_format($post),
                    $this->render_ddc_subjects($post),
                    $this->render_tags($post),
                    $this->render_gnd_subjects($post),
                    $this->render_publisher($post),
                    $this->render_relation($post),
                    $this->render_copyright($post),
                    $this->render_excerpt($post),
                    "</dc>"
                )
            );
            return $this->common->format_xml($xml_str);
        }

    }

}