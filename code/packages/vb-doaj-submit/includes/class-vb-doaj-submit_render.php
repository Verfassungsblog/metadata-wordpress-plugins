<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Render')) {

    class VB_DOAJ_Submit_Render
    {
        protected $common;

        public function __construct($common)
        {
            $this->common = $common;
        }

        protected function get_author_name($author)
        {
            $last_name = esc_html(get_the_author_meta("last_name", $author));
            $first_name = esc_html(get_the_author_meta("first_name", $author));

            $author = "";
            if (!empty($last_name) && !empty($first_name)) {
                $author = $first_name . " " . $last_name;
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
                $author = $first_name . " " . $last_name;
            } else if (!empty($last_name)) {
                $author = $last_name;
            }
            return $author;
        }

        protected function get_orcid($user_id)
        {
            return $this->common->get_acf_settings_user_field_value("orcid_acf", $user_id);
        }

        protected function get_post_coauthors($post)
        {
            if (!function_exists("get_coauthors")) {
                return array();
            }
            return array_slice(get_coauthors($post->ID), 1);
        }

        protected function render_abstract($post)
        {
            $include_excerpt = $this->common->get_settings_field_value("include_excerpt");
            if (!$include_excerpt) {
                return false;
            }
            return esc_html(get_the_excerpt($post));
        }

        protected function render_author($name, $orcid, $affiliation)
        {
            if (empty($name)) {
                return array();
            }
            return array_filter(array(
                "name" => $name,
                "orcid_id" => $orcid,
                "affiliation" => $affiliation,
            ));
        }

        protected function render_post_author($post)
        {
            $post_author_name = $this->get_author_name($post->post_author);
            $post_author_orcid = $this->get_orcid($post->post_author);
            return $this->render_author($post_author_name, $post_author_orcid, null);
        }

        protected function render_post_coauthors($post)
        {
            $coauthors = $this->get_post_coauthors($post);
            $result = array();
            foreach ($coauthors as $coauthor) {
                $coauthor_name = $this->get_coauthor_name($coauthor);
                $coauthor_orcid = $this->get_orcid($coauthor->ID);
                $result = array_merge($result, array($this->render_author($coauthor_name, $coauthor_orcid, null)));
            }
            return $result;
        }

        protected function render_authors($post)
        {
            return array_values(array_filter(array_merge(
                array($this->render_post_author($post)),
                $this->render_post_coauthors($post)
            )));
        }

        public function render_keywords($post)
        {
            $include_tags = $this->common->get_settings_field_value("include_tags");
            if (!$include_tags) {
                return array();
            }
            $tags = get_the_tags($post);
            $tags = $tags ? $tags : array();
            $keywords = array();
            foreach($tags as $tag) {
                $keywords = array_merge($keywords, array(esc_html($tag->name)));
            }
            return array_slice(array_values(array_filter($keywords)), 0, 6);
        }

        protected function render_eissn($post) {
            $eissn = $this->common->get_settings_field_value("eissn");
            if (empty($eissn)) {
                return false;
            }
            return array(
                'id' => $eissn,
                'type' => 'eissn',
            );
        }

        protected function render_pissn($post) {
            $pissn = $this->common->get_settings_field_value("pissn");
            if (empty($pissn)) {
                return false;
            }
            return array(
                'id' => $pissn,
                'type' => 'pissn',
            );
        }

        protected function render_doi($post) {
            $doi = esc_html($this->common->get_acf_settings_post_field_value("doi_acf", $post));
            if (empty($doi)) {
                return false;
            }
            return array(
                "type" => "doi",
                "id" => $doi,
            );
        }

        protected function render_identifier($post)
        {
            return array_values(array_filter(array(
                $this->render_eissn($post),
                $this->render_pissn($post),
                $this->render_doi($post),
            )));
        }

        protected function render_issue_number($post)
        {
            $issue_general = esc_html($this->common->get_settings_field_value("issue"));
            $issue_acf = esc_html($this->common->get_acf_settings_post_field_value("issue_acf", $post));
            $issue = !empty($issue_acf) ? $issue_acf : $issue_general;
            return empty($issue) ? false : $issue;
        }

        protected function render_volume($post)
        {
            $volume_general = esc_html($this->common->get_settings_field_value("volume"));
            $volume_acf = esc_html($this->common->get_acf_settings_post_field_value("volume_acf", $post));
            $volume = !empty($volume_acf) ? $volume_acf : $volume_general;
            return empty($volume) ? false : $volume;
        }

        protected function render_journal($post)
        {
            return array_filter(array(
                'number' => $this->render_issue_number($post),
                'volume' => $this->render_volume($post),
            ));
        }

        protected function render_title($post)
        {
            $title = esc_html(get_the_title($post));
            $subheadline = esc_html($this->common->get_acf_settings_post_field_value("subheadline_acf", $post));
            if (!empty($subheadline)) {
                $title = $title . " - " . $subheadline;
            }
            return empty($title) ? false : $title;
        }

        public function render($post)
        {
            // example Random: https://doaj.org/api/articles/831bac8cf73e4f2f855189504d29f54d
            // verfassungsblog example: https://doaj.org/api/articles/19f5e959bca74eb29f303e6bf4078640
            $json_data = array(
                'bibjson' => array_filter(array(
                    'title' => $this->render_title($post),
                    'author' =>  $this->render_authors($post),
                    'identifier' => $this->render_identifier($post),
                    'abstract' => $this->render_abstract($post),
                    'journal' => $this->render_journal($post),
                    'keywords' => $this->render_keywords($post),
                    'link' => array(array(
                        'url' => get_the_permalink($post),
                        'content_type' => 'HTML', # see FAQ at https://doaj.org/api/v3/docs
                        'type' => 'fulltext',
                    )),
                    'month' => get_the_time('m', $post),
                    'year' => get_the_time('Y', $post),
                )),
            );

            // check mandatory fields
            if (empty($json_data["bibjson"]["title"])) {
                // article has no title
                return false;
            }

            if (count($json_data["bibjson"]["author"]) < 1 || empty($json_data["bibjson"]["author"][0]["name"])) {
                // article has no author or author name is empty
                return false;
            }

            if (count($json_data["bibjson"]["identifier"]) < 1 || empty($json_data["bibjson"]["identifier"][0]["id"])) {
                // article has no identifier or identifier is empty
                return false;
            }
            return json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}