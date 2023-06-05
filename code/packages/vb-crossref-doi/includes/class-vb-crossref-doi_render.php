<?php

if (!class_exists('VB_CrossRef_DOI_Render')) {

    class VB_CrossRef_DOI_Render
    {
        public $common;

        protected $last_error;

        protected $last_post;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
        }

        protected function escape($str)
        {
            return htmlspecialchars(html_entity_decode($str), ENT_XML1, 'UTF-8');
        }

        protected function date_to_timestamp_format($date)
        {
            return $date->format("YmdHisv");
        }

        protected function get_author_name($author)
        {
            $last_name = get_the_author_meta("last_name", $author);
            $first_name = get_the_author_meta("first_name", $author);

            return array("given_name" => $first_name, "surname" => $last_name);
        }

        protected function get_coauthor_name($coauthor)
        {
            $last_name = $coauthor->last_name;
            $first_name = $coauthor->first_name;

            return array("given_name" => $first_name, "surname" => $last_name);
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

        protected function render_head($post, $submit_timestamp)
        {
            $timestamp = $this->date_to_timestamp_format(new DateTime());
            $depositor_name = $this->common->get_settings_field_value("depositor_name");
            $depositor_email = $this->common->get_settings_field_value("depositor_email");
            $registrant = $this->common->get_settings_field_value("registrant");

            if (empty($depositor_name)) {
                $this->last_error = "depositor name required";
                return false;
            }

            if (empty($depositor_email)) {
                $this->last_error = "depositor email required";
                return false;
            }

            if (empty($registrant)) {
                $this->last_error = "registrant required";
                return false;
            }

            return implode("", array(
                "<head>",
                "<doi_batch_id>{$post->ID}.{$submit_timestamp}</doi_batch_id>",
                "<timestamp>{$timestamp}</timestamp>",
                "<depositor>",
                "<depositor_name>" . $this->escape($depositor_name) . "</depositor_name>",
                "<email_address>" . $this->escape($depositor_email) . "</email_address>",
                "</depositor>",
                "<registrant>" . $this->escape($registrant) . "</registrant>",
                "</head>"
            ));
        }

        protected function render_posted_date($post)
        {
            $year = get_the_date("Y", $post);
            $month = get_the_date("m", $post);
            $day = get_the_date("d", $post);

            return implode("", array(
                "<posted_date media_type=\"online\">",
                "<month>{$month}</month>",
                "<day>{$day}</day>",
                "<year>{$year}</year>",
                "</posted_date>"
            ));
        }

        protected function generate_doi($post)
        {
            $doi_prefix = $this->common->get_settings_field_value("doi_prefix");
            $doi_suffix_length = (int)$this->common->get_settings_field_value("doi_suffix_length");
            $doi_suffix_length = $doi_suffix_length < 12 ? 12 : $doi_suffix_length;
            $doi_suffix_length = $doi_suffix_length > 64 ? 64 : $doi_suffix_length;

            if (empty($doi_prefix)) {
                $this->last_error = "doi prefix is required";
                return false;
            }

            $blog_title = get_bloginfo("name");
            $title = get_the_title($post);
            $date = get_the_date("Y-m-d H:i:s", $post);
            $suffix_str = $blog_title . $title . $date . $post->ID;
            $doi_suffix = hash("sha256", $suffix_str, false);

            return $doi_prefix . "/" . substr($doi_suffix, 0, $doi_suffix_length);
        }

        protected function get_or_generate_doi($post)
        {
            $stored_doi = $this->common->get_post_meta_field_value("doi_meta_key", $post);
            if (empty($stored_doi)) {
                return $this->generate_doi($post);
            }
            return $stored_doi;
        }

        protected function render_doi_data($post)
        {
            $permalink = get_the_permalink($post);
            $doi = $this->get_or_generate_doi($post);

            if (empty($doi)) {
                return false;
            }

            return implode("", array(
                "<doi_data>",
                    "<doi>$doi</doi>",
                    "<resource content_version=\"vor\" mime_type=\"text/html\">",
                    $this->escape($permalink),
                    "</resource>",
                "</doi_data>",
            ));
        }

        protected function render_post_author_affiliation($affiliation)
        {
            if (!empty($affiliation)) {
                return implode("", array(
                    "<affiliations>",
                        "<institution>",
                            "<institution_name>" . $this->escape($affiliation) . "</institution_name>",
                        "</institution>",
                    "</affiliations>",
                ));
            }
            return "";
        }


        protected function render_post_author($name, $orcid, $affiliation, $is_main)
        {
            if (empty($name["surname"])) {
                $this->last_error = "author needs to provide a surname (last name)";
                return false;
            }

            if ($is_main) {
                $sequence = "first";
            } else {
                $sequence = "additional";
            }

            return implode("", array(
                "<person_name sequence=\"{$sequence}\" contributor_role=\"author\">",
                    !empty($name["given_name"]) ? "<given_name>" . $this->escape($name["given_name"]) . "</given_name>" : "",
                    "<surname>" . $this->escape($name["surname"]) . "</surname>",
                    $this->render_post_author_affiliation($affiliation),
                    !empty($orcid) ? "<ORCID>https://orcid.org/" . $this->escape($orcid) . "</ORCID>" : "",
                "</person_name>",
            ));
        }

        protected function render_post_main_author($post)
        {
            $name = $this->get_author_name($post->post_author);
            $orcid = $this->common->get_user_meta_field_value("orcid_meta_key", $post->post_author);
            $affiliation = ""; // $this->affiliation->get_author_affiliation_for_post($post, $post->post_author);
            return $this->render_post_author($name, $orcid, $affiliation, true);
        }

        protected function render_post_coauthors($post)
        {
            $coauthors = $this->get_post_coauthors($post);
            $result = array();
            foreach ($coauthors as $coauthor) {
                $name = $this->get_coauthor_name($coauthor);
                $orcid = $this->common->get_user_meta_field_value("orcid_meta_key", $coauthor->ID);
                $affiliation = ""; // $this->affiliation->get_author_affiliation_for_post($post, $coauthor->ID);
                $result = array_merge($result, array($this->render_post_author($name, $orcid, $affiliation, false)));
            }
            return $result;
        }

        protected function render_contributors($post)
        {
            $contributors = implode("", array_values(array_filter(array_merge(
                array($this->render_post_main_author($post)),
                $this->render_post_coauthors($post)
            ))));

            if (empty($contributors)) {
                return "";
            }

            return implode("", array(
                "<contributors>",
                $contributors,
                "</contributors>",
            ));
        }

        protected function render_institution_wikidata_id($wikidata)
        {
            if (!empty($wikidata)) {
                return implode("", array(
                    "<institution_id type=\"wikidata\">",
                    "https://www.wikidata.org/entity/",
                    $this->escape($wikidata),
                    "</institution_id>",
                ));
            }
            return "";
        }

        protected function render_institution_isni($isni)
        {
            if (!empty($isni)) {
                return implode("", array(
                    "<institution_id type=\"isni\">",
                    "https://www.isni.org/",
                    $this->escape($isni),
                    "</institution_id>",
                ));
            }
            return "";
        }

        protected function render_institution_rorid($rorid)
        {
            if (!empty($rorid)) {
                return implode("", array(
                    "<institution_id type=\"isni\">",
                    "https://ror.org/",
                    $this->escape($rorid),
                    "</institution_id>",
                ));
            }
            return "";
        }

        protected function render_institution($post)
        {
            $rorid = $this->common->get_settings_field_value("institution_rorid");
            $isni = $this->common->get_settings_field_value("institution_isni");
            $wikidata = $this->common->get_settings_field_value("institution_wikidata_id");
            $name = $this->common->get_settings_field_value("institution_name");

            if (empty($rorid) && empty($isni) && empty($wikidata)) {
                // there is no id available, but an id is required by CrossRef
                return "";
            }

            return implode("", array(
                "<institution>",
                !empty($name) ? "<institution_name>" . $this->escape($name) . "</institution_name>" : "",
                $this->render_institution_rorid($rorid),
                $this->render_institution_isni($isni),
                $this->render_institution_wikidata_id($wikidata),
                "</institution>",
            ));
        }

        protected function get_creative_commons_link_from_name($copyright)
        {
            $lowercase = strtolower($copyright);
            $no_minus = str_replace("-", " ", $lowercase);
            $no_double_spaces = str_replace("  ", " ", $no_minus);
            $trimmed = trim($no_double_spaces);

            if (str_starts_with($trimmed, "cc0")) {
                return "https://creativecommons.org/publicdomain/zero/1.0/legalcode";
            }

            $available_versions = array("4.0", "3.0", "2.5", "2.0", "1.0");
            $available_variants = array("by", "by-sa", "by-nc", "by-nc-sa", "by-nd", "by-nc-nd");

            preg_match('/^cc(\D+)([1234]\.[05])?$/', $trimmed, $matches);
            if (!$matches) {
                return "";
            }
            $variant = trim($matches[1]);
            $version = trim($matches[2]);
            if (!in_array($version, $available_versions)) {
                $version = "4.0";
            }
            $variant = str_replace(" ", "-", $variant);
            if (!in_array($variant, $available_variants)) {
                return "";
            }


            return "https://creativecommons.org/licenses/{$variant}/${version}/legalcode";
        }

        protected function get_copyright_link($post)
        {
            // use post-specific link if provided
            $copyright_link_custom = $this->common->get_post_meta_field_value("copyright_link_meta_key", $post);
            if (!empty($copyright_link_custom)) {
                return $copyright_link_custom;
            }

            // generate link from post-specific name if provided
            $copyright_name_custom = $this->common->get_post_meta_field_value("copyright_name_meta_key", $post);
            if (!empty($copyright_name_custom)) {
                return $this->get_creative_commons_link_from_name($copyright_name_custom);
            }

            // use general link if provided
            $copyright_link_general = $this->common->get_settings_field_value("copyright_link_general");
            if (!empty($copyright_link_general)) {
                return $copyright_link_general;
            }

            // generate link from general name if provided
            $copyright_name_general = $this->common->get_settings_field_value("copyright_name_general");
            if (!empty($copyright_name_general)) {
                return $this->get_creative_commons_link_from_name($copyright_name_general);
            }
            return "";
        }

        protected function render_copyright($post)
        {
            $copyright_link = $this->get_copyright_link($post);
            if (!empty($copyright_link)) {
                return implode("", array(
                    "<program xmlns=\"http://www.crossref.org/AccessIndicators.xsd\">",
                    "<license_ref>",
                    $copyright_link,
                    "</license_ref>",
                    "</program>",
                ));
            }
            return "";
        }

        protected function render_issn($post)
        {
            $issn = $this->common->get_settings_field_value("issn");
            if (!empty($issn)) {
                return implode("", array(
                    "<program xmlns=\"http://www.crossref.org/relations.xsd\">",
                    "<related_item>",
                    "<inter_work_relation relationship-type=\"isPartOf\" identifier-type=\"issn\">",
                    $this->escape($issn),
                    "</inter_work_relation>",
                    "</related_item>",
                    "</program>",
                ));
            }
            return "";
        }

        protected function render_excerpt($post)
        {
            $include_excerpt = $this->common->get_settings_field_value("include_excerpt");
            if ($include_excerpt) {
                $excerpt = strip_tags(get_the_excerpt($post));
                if (!empty($excerpt)) {
                    return "<jats:abstract><jats:p>" . $this->escape($excerpt) . "</jats:p></jats:abstract>";
                }
            }
            return "";
        }

        protected function render_posted_content($post)
        {

            $title = get_the_title($post);
            $doi_data = $this->render_doi_data($post);

            if (empty($doi_data)) {
                return false;
            }

            return implode("", array(
                "<posted_content type=\"preprint\">",
                $this->render_contributors($post),
                "<titles>",
                    "<title>" . $this->escape($title) . "</title>",
                "</titles>",
                $this->render_posted_date($post),
                $this->render_institution($post),
                $this->render_excerpt($post),
                $this->render_copyright($post),
                $this->render_issn($post),
                $doi_data,
                "</posted_content>"
            ));
        }

        public function get_last_error()
        {
            return "[Post id=" . $this->last_post->ID . "] " .  $this->last_error;
        }

        public function render($post, $submit_timestamp)
        {
            // documentation see: https://data.crossref.org/reports/help/schema_doc/5.3.1/index.html
            // examples see: https://www.crossref.org/xml-samples/

            $this->last_post = $post;
            $this->last_error = null;

            $head = $this->render_head($post, $submit_timestamp);
            $posted_content = $this->render_posted_content($post);

            if (empty($head) || empty($posted_content)) {
                return false;
            }

            $xml_str = implode("", array(
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <doi_batch xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
                    xsi:schemaLocation=\"http://www.crossref.org/schema/5.3.0 https://www.crossref.org/schemas/crossref5.3.0.xsd\"
                    xmlns=\"http://www.crossref.org/schema/5.3.0\" xmlns:jats=\"http://www.ncbi.nlm.nih.gov/JATS1\"
                    xmlns:fr=\"http://www.crossref.org/fundref.xsd\" xmlns:mml=\"http://www.w3.org/1998/Math/MathML\" version=\"5.3.0\">",
                $head,
                "<body>",
                $posted_content,
                "</body>",
                "</doi_batch>"
            ));

            return $this->common->format_xml($xml_str);
        }

    }

}