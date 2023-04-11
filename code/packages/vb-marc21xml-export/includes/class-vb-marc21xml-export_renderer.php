<?php

if (!class_exists('VB_Marc21Xml_Export_Renderer')) {

    class VB_Marc21Xml_Export_Renderer
    {
        public function __construct()
        {

        }

        public function render($post)
        {
            // marc21 definition see: https://www.loc.gov/marc/bibliographic/

            // leader definition see: https://www.loc.gov/marc/bibliographic/bdleader.html
            $leader = "     nam  22     uu 4500";
            // control number definition see: https://www.loc.gov/marc/bibliographic/bd001.html
            $control_number = $post->ID;

            $post_title = get_the_title($post);
            $post_author = get_the_author_meta("last_name", $post->post_author) . ", " . get_the_author_meta("first_name", $post->post_author);


            // https://www.loc.gov/marc/bibliographic/bd773.html
            $blog_owner = "Steinbeis, Maximilian";
            $blog_title = get_bloginfo("name");
            $blog_issn = "2366-7044";

            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <marc21:record xmlns:marc21=\"http://www.loc.gov/MARC21/slim\">
            <marc21:leader>{$leader}</marc21:leader>
            <marc21:controlfield tag=\"001\">{$control_number}</marc21:controlfield>
            <marc21:datafield tag=\"245\" ind1=\"1\" ind2=\"0\">
                <marc21:subfield code=\"a\">{$post_title}</marc21:subfield>
                <marc21:subfield code=\"c\">{$post_author}</marc21:subfield>
            </marc21:datafield>
            <marc21:datafield tag=\"773\" ind1=\"0\" ind2=\" \">
            <marc21:subfield code=\"a\">{$blog_owner}</marc21:subfield>
            <marc21:subfield code=\"t\">{$blog_title}</marc21:subfield>
            <marc21:subfield code=\"x\">{$blog_issn}</marc21:subfield>
        </marc21:datafield>
        </marc21:record>";
        }

    }

}