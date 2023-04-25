<?php

if (!class_exists('VB_Metadata_Export_Converter')) {

    class VB_Metadata_Export_Converter
    {

        protected $xsl_urls = array(
            "MARC21slimUtils.xsl" => "https://www.loc.gov/standards/marcxml/xslt/MARC21slimUtils.xsl",
            "MARC21slim2MODS3-7.xsl" => "https://www.loc.gov/standards/mods/v3/MARC21slim2MODS3-7.xsl",
            "MARC21slim2RDFDC.xsl" => "https://www.loc.gov/standards/marcxml/xslt/MARC21slim2RDFDC.xsl",
            "MARC21slim2OAIDC.xsl" => "https://www.loc.gov/standards/marcxml/xslt/MARC21slim2OAIDC.xsl",
            "MODS3-7_DC_XSLT1-0.xsl" => "https://www.loc.gov/standards/mods/v3/MODS3-7_DC_XSLT1-0.xsl"
        );

        public function __construct()
        {
            $this->download_all_xsl_files();
        }

        protected function download_all_xsl_files() {
            $sep = DIRECTORY_SEPARATOR;
            $xsl_directory = realpath(plugin_dir_path(__FILE__) . $sep . ".." . $sep . "xsl");
            if (!file_exists($xsl_directory)) {
                mkdir($xsl_directory, 0777, true);
            }

            foreach($this->xsl_urls as $xsl_filename => $xsl_url) {
                $xsl_filepath = join($sep, array($xsl_directory, $xsl_filename));
                if (!file_exists($xsl_filepath)) {
                    file_put_contents($xsl_filepath, file_get_contents($xsl_url));
                }
            }
        }

        protected function convertFromMarc21Xml($input, $xsl_filenames) {

            $xsltproc = new XSLTProcessor();
            foreach ($xsl_filenames as $xsl_filename) {
                $xsl = new DOMDocument;
                $xsl_filepath = realpath(plugin_dir_path(__FILE__) . "/../xsl/" . $xsl_filename);
                if (!$xsl->load($xsl_filepath)) {
                    return;
                };
                if (!$xsltproc->importStylesheet($xsl)) {
                    return;
                }
            }

            $marcxml = new DOMDocument();
            $marcxml->preserveWhiteSpace = false;
            $marcxml->loadXML($input, LIBXML_NOCDATA);

            $mods = $xsltproc->transformToDoc($marcxml);
            $mods->formatOutput = true;
            return $mods->saveXML();
        }

        public function convertMarc21ToMods($marc21xml) {
            return $this->convertFromMarc21Xml($marc21xml, array("MARC21slim2MODS3-7.xsl"));
        }

        public function convertMarc21ToRdfDc($marc21xml) {
            return $this->convertFromMarc21Xml($marc21xml, array("MARC21slimUtils.xsl", "MARC21slim2RDFDC.xsl"));
        }

        public function convertMarc21ToOaiDc($marc21xml) {
            $mods = $this->convertMarc21ToMods($marc21xml);
            return $this->convertFromMarc21Xml($mods, array("MODS3-7_DC_XSLT1-0.xsl"));
            // return $this->convertFromMarc21Xml($marc21xml, array("MARC21slim2OAIDC.xsl"));
        }

    }

}