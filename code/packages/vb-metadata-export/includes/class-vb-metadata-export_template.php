<?php
/**
 * Template function that allow to generate links to the meta data in themes or other plugins.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export_common.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export_marc21xml.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export_converter.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export_oai_pmh.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export_dc.php';

if ( ! function_exists( 'get_the_vb_metadata_export_permalink' ) ) {
	/**
	 * Return the permalink to the meta data export format.
	 *
	 * @param string $format the format to which the link is pointing to, either "marc21xml", "mods", "dc" or "oaipmh".
	 */
	function get_the_vb_metadata_export_permalink( $format ) {
		global $post;
		$common = new VB_Metadata_Export_Common( 'vb-metadata-export' );
		return $common->get_the_permalink( $format, $post );
	}
}

if ( ! function_exists( 'get_the_vb_metadata_export_link' ) ) {
	/**
	 * Return a a-tag link to the meta data export format as string.
	 *
	 * @param string $format the format to which the link is pointing to, either "marc21xml", "mods", "dc" or "oaipmh".
	 * @param string $title the link text.
	 * @param string $extra_class any additional css class tag.
	 * @param string $unavailable the link text in case the format is not available (e.g. disabled in settings).
	 */
	function get_the_vb_metadata_export_link( $format, $title = '', $extra_class = '', $unavailable = '' ) {
		global $post;
		$common = new VB_Metadata_Export_Common( 'vb-metadata-export' );

		if ( ! $common->is_valid_format( $format ) ) {
			return 'invalid format';
		}

		$permalink = $common->get_the_permalink( $format, $post );
		$title     = empty( $title ) ? $common->get_format_labels()[ $format ] : $title;

		$classes = implode(
			' ',
			array(
				$common->plugin_name . '-link',
				$common->plugin_name . '-' . $format . '-link',
				empty( $marc21_permalink ) ? $common->plugin_name . '-unavailable' : '',
				$extra_class,
			)
		);

		if ( empty( $permalink ) ) {
			return '<a class=\"' . esc_attr( $classes ) . '\">' . esc_html( $unavailable ) . '</a>';
		}
		return '<a class=\"' . esc_attr( $classes ) . '\" href=\"' . esc_url( $permalink ) . '\">' . esc_html( $title ) . '</a>';
	}
}

if ( ! function_exists( 'the_vb_metadata_export_link' ) ) {
	/**
	 * Renders a link to the meta data export format.
	 *
	 * @param string $format the format to which the link is pointing to, either "marc21xml", "mods", "dc" or "oaipmh".
	 * @param string $title the link text.
	 * @param string $extra_class any additional css class tag.
	 * @param string $unavailable the link text in case the format is not available (e.g. disabled in settings).
	 */
	function the_vb_metadata_export_link( $format, $title = '', $extra_class = '', $unavailable = '' ) {
		echo get_the_vb_metadata_export_link( $format, $title, $extra_class, $unavailable ); // phpcs:ignore
	}
}

if ( ! function_exists( 'vb_metadata_export_render_format' ) ) {
	/**
	 * Render the actual meta data export format as xml output.
	 *
	 * @param string $format the format to which the link is pointing to, either "marc21xml", "mods" or "dc".
	 */
	function vb_metadata_export_render_format( $format ) {
		global $post;

		$common = new VB_Metadata_Export_Common( 'vb-metadata-export' );

		if ( ! $common->is_metadata_available( $format, $post ) || ! is_single() ) {
			return;
		}

		header( 'Content-Type: application/xml' );

		if ( 'marc21xml' === $format ) {
			$marc21xml = new VB_Metadata_Export_Marc21Xml( $common->plugin_name );
			echo $marc21xml->render( $post ); // phpcs:ignore
			return;
		}

		if ( 'mods' === $format ) {
			$converter = new VB_Metadata_Export_Converter();
			$marc21xml = new VB_Metadata_Export_Marc21Xml( $common->plugin_name );
			$mods      = $converter->convertMarc21ToMods( $marc21xml->render( $post ) );
			echo $mods; // phpcs:ignore
			return;
		}

		if ( 'dc' === $format ) {
			$dc = new VB_Metadata_Export_DC( $common->plugin_name );
			echo $dc->render( $post ); // phpcs:ignore
			return;
		}

		// should not happen.
		return 'unkown format';
	}
}

if ( ! function_exists( 'vb_metadata_export_render_oaipmh' ) ) {
	/**
	 * Render the OAI-PMH 2.0 xml. Requires appropriate URL options (e.g. verb=Identify, etc.).
	 */
	function vb_metadata_export_render_oaipmh() {

		$common = new VB_Metadata_Export_Common( 'vb-metadata-export' );
		if ( $common->get_settings_field_value( 'oai-pmh_enabled' ) ) {
			header( 'Content-Type: application/xml' );
			$oaipmh = new VB_Metadata_Export_OAI_PMH( $common->plugin_name );
			echo $oaipmh->render(); // phpcs:ignore
		} else {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}
	}
}
