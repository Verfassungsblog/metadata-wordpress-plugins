<?php
/**
 * Provides a shortcode to render a link to metadata export formats.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-common.php';
require_once plugin_dir_path( __FILE__ ) . '/vb-metadata-export-template.php';

if ( ! class_exists( 'VB_Metadata_Export_Shortcode' ) ) {

	/**
	 * Registers custom shortcode that renders link to meta data export formats.
	 */
	class VB_Metadata_Export_Shortcode {
		/**
		 * The name of this plugin
		 *
		 * @var string
		 */
		protected $plugin_name;

		/**
		 * Common methods
		 *
		 * @var VB_Metadata_Export_Common
		 */
		protected $common;

		/**
		 * Initialize shortcode register class.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->plugin_name = $plugin_name;
			$this->common      = new VB_Metadata_Export_Common( $plugin_name );
		}

		/**
		 * WordPress plugin init action
		 */
		public function action_init() {
			add_shortcode( $this->plugin_name . '-link', array( $this, 'render_link' ) );
		}

		/**
		 * Render function for shortcode.
		 *
		 * @param array  $atts attributes provided to the shortcode.
		 * @param string $content the inner text of the shortcode.
		 * @param string $shortcode_tag the name of the tag.
		 * @return string the link as html string
		 */
		public function render_link( $atts, $content, $shortcode_tag ) {
			$format = $atts['format'];

			if ( ! $this->common->is_valid_format( $format ) ) {
				// format is mandatory argument.
				return '';
			}

			$attributes = shortcode_atts(
				array(
					'format'      => $format,
					'title'       => $this->common->get_format_labels()[ $format ],
					'unavailable' => '',
					'class'       => '',
				),
				$atts,
			);

			return get_the_vb_metadata_export_link(
				$format,
				$attributes['title'],
				$attributes['class'],
				$attributes['unavailable']
			);
		}

		/**
		 * Run function that is called from main class.
		 */
		public function run() {
			add_action( 'init', array( $this, 'action_init' ) );
		}

	}

}
