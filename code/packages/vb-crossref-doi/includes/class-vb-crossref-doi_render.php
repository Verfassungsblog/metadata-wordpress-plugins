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

        protected function date_to_timestamp_format($date)
        {
            return $date->format("YmdHisv");
        }

        protected function render_head($post)
        {
            $timestamp = $this->date_to_timestamp_format(new DateTime());
            $depositor_name = esc_html($this->common->get_settings_field_value("depositor_name"));
            $depositor_email = esc_html($this->common->get_settings_field_value("depositor_email"));
            $registrant = esc_html($this->common->get_settings_field_value("registrant"));

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
                "<doi_batch_id>{$post->ID}.{$timestamp}</doi_batch_id>",
                "<timestamp>{$timestamp}</timestamp>",
                "<depositor>",
                "<depositor_name>{$depositor_name}</depositor_name>",
                "<email_address>{$depositor_email}</email_address>",
                "</depositor>",
                "<registrant>{$registrant}</registrant>",
                "</head>"
            ));
        }

        protected function render_journal_metadata($post)
        {
            $journal_title = esc_html($this->common->get_settings_field_value("journal_title"));
            $eissn = esc_html($this->common->get_settings_field_value("eissn"));

            if (empty($journal_title)) {
                $this->last_error = "journal title required";
                return false;
            }

            return implode("", array(
                "<journal_metadata reference_distribution_opts=\"none\">",
                "<full_title>{$journal_title}</full_title>",
                !empty($eissn) ? "<issn media_type=\"electronic\">{$eissn}</issn>" : "",
                "</journal_metadata>",
            ));
        }


        protected function render_publication_date($post)
        {
            $year = get_the_date("Y", $post);
            $month = get_the_date("m", $post);
            $day = get_the_date("d", $post);

            return implode("", array(
                "<publication_date media_type=\"online\">",
                "<month>{$month}</month>",
                "<day>{$day}</day>",
                "<year>{$year}</year>",
                "</publication_date>"
            ));
        }

        protected function generate_doi($post)
        {
            $doi_prefix = $this->common->get_settings_field_value("doi_prefix");
            $doi_suffix_length = (int)$this->common->get_settings_field_value("doi_suffix_length");
            $doi_suffix_length = $doi_suffix_length < 8 ? 8 : $doi_suffix_length;
            $doi_suffix_length = $doi_suffix_length > 64 ? 64 : $doi_suffix_length;

            if (empty($doi_prefix)) {
                $this->last_error = "doi prefix is required";
                return false;
            }

            $blog_title = get_bloginfo("name");
            $title = esc_html(get_the_title($post));
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
                    "<resource content_version=\"vor\" mime_type=\"text/html\">{$permalink}</resource>",
                "</doi_data>",
            ));
        }

        protected function render_journal_article($post)
        {
            $title = esc_html(get_the_title($post));
            $doi_data = $this->render_doi_data($post);

            if (empty($doi_data)) {
                return false;
            }

            return implode("", array(
                "<journal_article language=\"en\" publication_type=\"full_text\" reference_distribution_opts=\"none\">",
                "<titles>",
                    "<title>{$title}</title>",
                "</titles>",
                $this->render_publication_date($post),
                $doi_data,
                "</journal_article>",
            ));
        }

        public function get_last_error()
        {
            return "[Post id=" . $this->last_post->ID . "] " .  $this->last_error;
        }

        public function render($post)
        {
            // documentation see: https://data.crossref.org/reports/help/schema_doc/5.3.1/index.html
            // examples see: https://www.crossref.org/xml-samples/

            $this->last_post = $post;
            $this->last_error = null;

            $head = $this->render_head($post);
            $journal_metadata = $this->render_journal_metadata($post);
            $journal_article = $this->render_journal_article($post);

            if (empty($head) || empty($journal_metadata) || empty($journal_article)) {
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
                "<journal>",
                $journal_metadata,
                $journal_article,
                "</journal>",
                "</body>",
                "</doi_batch>"
            ));

            return $this->common->format_xml($xml_str);
        }

    }

}