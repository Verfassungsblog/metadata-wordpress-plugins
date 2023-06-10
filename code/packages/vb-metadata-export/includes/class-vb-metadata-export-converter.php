<?php
/**
 * Class that converts XML to other formats.
 *
 * @package vb-metadata-export
 */

if ( ! class_exists( 'VB_Metadata_Export_Converter' ) ) {

	/**
	 * Class that converts XML to other formats.
	 */
	class VB_Metadata_Export_Converter {

		/**
		 * Array of XSL files that needs to be downloaded before conversion is possible.
		 *
		 * @var array
		 */
		protected $xsl_urls = array(
			'MARC21slim2MODS3-7.xsl' => 'https://www.loc.gov/standards/mods/v3/MARC21slim2MODS3-7.xsl',

			/*
			Additional files that are currently not required:

			'MARC21slimUtils.xsl'    => 'https://www.loc.gov/standards/marcxml/xslt/MARC21slimUtils.xsl',
			'MARC21slim2RDFDC.xsl'   => 'https://www.loc.gov/standards/marcxml/xslt/MARC21slim2RDFDC.xsl',
			'MARC21slim2OAIDC.xsl'   => 'https://www.loc.gov/standards/marcxml/xslt/MARC21slim2OAIDC.xsl',
			'MODS3-7_DC_XSLT1-0.xsl' => 'https://www.loc.gov/standards/mods/v3/MODS3-7_DC_XSLT1-0.xsl',
			*/
		);

		/**
		 * Initialize by downloading all required files.
		 */
		public function __construct() {
			$this->download_all_xsl_files();
		}

		/**
		 * Downloads all required XSL files.
		 */
		protected function download_all_xsl_files() {
			$sep           = DIRECTORY_SEPARATOR;
			$xsl_directory = realpath( plugin_dir_path( __FILE__ ) . $sep . '..' . $sep . 'xsl' );
			if ( ! file_exists( $xsl_directory ) ) {
				mkdir( $xsl_directory, 0777, true );
			}

			foreach ( $this->xsl_urls as $xsl_filename => $xsl_url ) {
				$xsl_filepath = join( $sep, array( $xsl_directory, $xsl_filename ) );
				if ( ! file_exists( $xsl_filepath ) ) {
					file_put_contents( $xsl_filepath, file_get_contents( $xsl_url ) );
				}
			}
		}

		/**
		 * Convert Marc21 xml to some other format using XSL files.
		 *
		 * @param string $input the Marc21 xml as string.
		 * @param array  $xsl_filenames the array of filenames of XSL files that are sequentially applied.
		 * @return string the converted xml as string
		 */
		protected function convert_from_marc21_xml( $input, $xsl_filenames ) {
			$sep      = DIRECTORY_SEPARATOR;
			$xsltproc = new XSLTProcessor();
			foreach ( $xsl_filenames as $xsl_filename ) {
				$xsl          = new DOMDocument();
				$xsl_filepath = realpath( plugin_dir_path( __FILE__ ) . $sep . '..' . $sep . 'xsl' . $sep . $xsl_filename );
				if ( ! $xsl->load( $xsl_filepath ) ) {
					return '';
				}
				if ( ! $xsltproc->importStylesheet( $xsl ) ) {
					return '';
				}
			}

			$marcxml                     = new DOMDocument();
			$marcxml->preserveWhiteSpace = false; // phpcs:ignore
			$marcxml->loadXML( $input, LIBXML_NOCDATA );

			$mods               = $xsltproc->transformToDoc( $marcxml );
			$mods->formatOutput = true; // phpcs:ignore
			return $mods->saveXML();
		}

		/**
		 * Convert Marc21 XML to MODS 3.7 xml.
		 *
		 * @param string $marc21xml marc21 xml as string.
		 * @return string the converted MODS 3.7 xml as string
		 */
		public function convert_marc21_xml_to_mods( $marc21xml ) {
			return $this->convert_from_marc21_xml( $marc21xml, array( 'MARC21slim2MODS3-7.xsl' ) );
		}

		/**
		 * Convert Marc21 XML to RDF DC xml.
		 *
		 * @param string $marc21xml marc21 xml as string.
		 * @return string the converted RDF DC xml as string
		 */
		protected function convert_marc21_xml_to_rdf_dc( $marc21xml ) {
			return $this->convert_from_marc21_xml( $marc21xml, array( 'MARC21slimUtils.xsl', 'MARC21slim2RDFDC.xsl' ) );
		}

		/**
		 * Convert Marc21 XML to OAI DC xml.
		 *
		 * @param string $marc21xml marc21 xml as string.
		 * @return string the converted OAI DC xml as string
		 */
		protected function convert_marc21_xml_to_oai_dc( $marc21xml ) {
			$mods = $this->convert_marc21_xml_to_mods( $marc21xml );
			return $this->convert_from_marc21_xml( $mods, array( 'MODS3-7_DC_XSLT1-0.xsl' ) );
		}

	}

}
