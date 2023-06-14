<?php

/**
 * Renders DOAJ article json. See:
 * - Api Documentation: https://doaj.org/api/v3/docs
 * - Json Data Format: https://doaj.github.io/doaj-docs/master/data_models/IncomingAPIArticle
 * - DOAJ Validation Code: https://github.com/DOAJ/doaj/blob/bc9187c6dfaf4c4ad552baac1c874cd257ca1468/portality/api/current/data_objects/article.py#L195
 * - DOAJ Article Creation Test: https://github.com/DOAJ/doaj/blob/752f8fe34d2a09846aa4af6a0900fb7db285cce7/doajtest/unit/test_create_article.py
 * - DOAJ Journal Metadata Code: https://github.com/DOAJ/doaj/blob/bc9187c6dfaf4c4ad552baac1c874cd257ca1468/portality/models/article.py#L247
 */

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit-common.php';

if (!class_exists('VB_DOAJ_Submit_Render')) {

    class VB_DOAJ_Submit_Render
    {
        protected $common;

        protected $last_error;

        protected $last_post;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
        }

        protected function escape($str)
        {
            return html_entity_decode($str);
        }

        protected function get_author_name($author)
        {
            $last_name = get_the_author_meta("last_name", $author);
            $first_name = get_the_author_meta("first_name", $author);

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
            $last_name = $coauthor->last_name;
            $first_name = $coauthor->first_name;

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
            return $this->common->get_user_meta_field_value("orcid_meta_key", $user_id);
        }

        protected function get_post_coauthors($post)
        {
            if (!function_exists("get_coauthors")) {
                return array();
            }
            return array_slice(get_coauthors($post->ID), 1);
        }

        protected function get_abstract($post)
        {
            $include_excerpt = $this->common->get_settings_field_value("include_excerpt");
            if (!$include_excerpt) {
                return false;
            }
            return strip_tags(get_the_excerpt($post));
        }

        protected function get_all_author_affiliations($post)
        {
            if (!function_exists("get_the_vb_author_affiliations")) {
                return array();
            }
            return get_the_vb_author_affiliations($post);
        }

        protected function get_affiliation_name_for_author($post, $userid)
        {
            $author_affiliations = $this->get_all_author_affiliations($post);
            if (array_key_exists($userid, $author_affiliations)) {
                $name = $author_affiliations[$userid]["name"] ?? false;
                if (!empty($name)) {
                    return $name;
                }
            }
            return false;
        }

        protected function render_author($name, $orcid, $affiliation_name)
        {
            if (empty($name)) {
                return array();
            }
            return array_filter(array(
                "name" => $this->escape($name),
                "orcid_id" => !empty($orcid) ? $this->escape("https://orcid.org/{$orcid}") : false,
                "affiliation" => $this->escape($affiliation_name),
            ));
        }

        protected function render_post_author($post)
        {
            $post_author_name = $this->get_author_name($post->post_author);
            $post_author_orcid = $this->get_orcid($post->post_author);
            $post_author_affiliation_name = $this->get_affiliation_name_for_author($post, $post->post_author);
            return $this->render_author($post_author_name, $post_author_orcid, $post_author_affiliation_name);
        }

        protected function render_post_coauthors($post)
        {
            $coauthors = $this->get_post_coauthors($post);
            $result = array();
            foreach ($coauthors as $coauthor) {
                $coauthor_name = $this->get_coauthor_name($coauthor);
                $coauthor_orcid = $this->get_orcid($coauthor->ID);
                $coauthor_affiliation_name = $this->get_affiliation_name_for_author($post, $coauthor->ID);
                $coauthor_result = $this->render_author($coauthor_name, $coauthor_orcid, $coauthor_affiliation_name);
                $result = array_merge($result, array($coauthor_result));
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

            $gnd_terms = get_the_terms($post, "gnd");
            $gnd_terms = !empty($gnd_terms) && !is_wp_error($gnd_terms) ? $gnd_terms : array();
            if (!empty($gnd_terms)) {
                $tags = array_merge($tags, $gnd_terms);
            }

            $keywords = array();
            foreach($tags as $tag) {
                $keywords = array_merge($keywords, array($this->escape($tag->name)));
            }
            return array_slice(array_values(array_filter($keywords)), 0, 6);
        }

        protected function render_eissn($post) {
            $eissn = $this->common->get_settings_field_value("eissn");
            if (empty($eissn)) {
                return false;
            }
            return array(
                'id' => $this->escape($eissn),
                'type' => 'eissn',
            );
        }

        protected function render_pissn($post) {
            $pissn = $this->common->get_settings_field_value("pissn");
            if (empty($pissn)) {
                return false;
            }
            return array(
                'id' => $this->escape($pissn),
                'type' => 'pissn',
            );
        }

        protected function render_doi($post) {
            $doi = $this->common->get_post_meta_field_value("doi_meta_key", $post);
            if (empty($doi)) {
                return false;
            }
            return array(
                "type" => "doi",
                "id" => $this->escape($doi),
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

        protected function get_issue_number($post)
        {
            $issue_general = $this->common->get_settings_field_value("issue");
            $issue_custom = $this->common->get_post_meta_field_value("issue_meta_key", $post);
            $issue = !empty($issue_custom) ? $issue_custom : $issue_general;
            return empty($issue) ? false : $issue;
        }

        protected function get_volume($post)
        {
            $volume_general = $this->common->get_settings_field_value("volume");
            $volume_custom = $this->common->get_post_meta_field_value("volume_meta_key", $post);
            $volume = !empty($volume_custom) ? $volume_custom : $volume_general;
            return empty($volume) ? false : $volume;
        }

        protected function render_journal($post)
        {
            return array_filter(array(
                'number' => $this->escape($this->get_issue_number($post)),
                'volume' => $this->escape($this->get_volume($post)),
                // title is overwritten by DOAJ
                // publisher is overwritten by DOAJ
                // country is overwritten by DOAJ
                // language list is overwritten by DOAJ
            ));
        }

        protected function get_title($post)
        {
            $title = get_the_title($post);
            $include_subheadline = $this->common->get_settings_field_value("include_subheadline");
            if ($include_subheadline) {
                $subheadline = $this->common->get_post_meta_field_value("subheadline_meta_key", $post);
                if (!empty($subheadline)) {
                    $title = $title . " - " . $subheadline;
                }
            }

            return empty($title) ? false : $title;
        }

        public function get_last_error()
        {
            return "[Post id=" . $this->last_post->ID . "] " .  $this->last_error;
        }

        public function render($post)
        {
            // random example: https://doaj.org/api/articles/831bac8cf73e4f2f855189504d29f54d
            // verfassungsblog example: https://doaj.org/api/articles/19f5e959bca74eb29f303e6bf4078640
            $this->last_error = null;
            $this->last_post = $post;

            // rewrite staging permalinks
            $permalink = get_the_permalink($post);
            $permalink = str_replace("staging.verfassungsblog.de", "verfassungsblog.de", $permalink);

            $json_data = array(
                'bibjson' => array_filter(array(
                    'title' => $this->escape($this->get_title($post)),
                    'author' =>  $this->render_authors($post),
                    'identifier' => $this->render_identifier($post),
                    'abstract' => $this->escape($this->get_abstract($post)),
                    'journal' => $this->render_journal($post),
                    'keywords' => $this->render_keywords($post),
                    'link' => array(array(
                        'url' => $this->escape($permalink),
                        'content_type' => 'HTML', # see FAQ at https://doaj.org/api/v3/docs
                        'type' => 'fulltext',
                    )),
                    'month' => get_the_time('m', $post),
                    'year' => get_the_time('Y', $post),
                    // subject list is overwritten by DOAJ
                )),
            );

            // check mandatory fields
            if (!isset($json_data["bibjson"]["title"]) || empty($json_data["bibjson"]["title"])) {
                $this->last_error = "article has no title";
                return false;
            }

            if (!isset($json_data["bibjson"]["author"])
                    || count($json_data["bibjson"]["author"]) < 1
                    || empty($json_data["bibjson"]["author"][0]["name"])) {
                $this->last_error = "article has no author or author name is empty";
                return false;
            }

            if (!isset($json_data["bibjson"]["identifier"])
                    || count($json_data["bibjson"]["identifier"]) < 1
                    || empty($json_data["bibjson"]["identifier"][0]["id"])) {
                $this->last_error = "article has no identifier or identifier is empty";
                return false;
            }
            return json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}