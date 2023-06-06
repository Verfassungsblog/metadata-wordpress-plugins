<?php

if (!function_exists('get_the_vb_author_affiliations')) {
    function get_the_vb_author_affiliations($post)
    {
        $common = new VB_Author_Affiliations_Common("vb-author-affiliations");
        $json = $common->get_post_meta_field_value("author_affiliations_meta_key", $post);
        $author_affiliations = json_decode($json, true);
        if (empty($author_affiliations)) {
            $author_affiliations = array();
        }
        return $author_affiliations;
    }
}