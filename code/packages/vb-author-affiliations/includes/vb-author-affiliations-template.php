<?php
/**
 * Template functions
 *
 * @package vb-author-affiliations
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-author-affiliations-common.php';

if ( ! function_exists( 'get_the_vb_author_affiliations' ) ) {
	/**
	 * Return the stored author affiliations for a post as array (user id => affiliations).
	 * Each affiliation conists of the name and rorid as string.
	 *
	 * @param WP_Post $post the post from which author affiliations are retrieved.
	 * @return array the array of author affiliations in the form of:
	 * [
	 *   '[user id]' => [
	 *     'name' => (string) the name of the affiliation,
	 *     'rorid' => (string) the ROR-ID of the affiliation (only ID, not the URI)
	 *   ]
	 * ]
	 */
	function get_the_vb_author_affiliations( $post ) {
		$common              = new VB_Author_Affiliations_Common( 'vb-author-affiliations' );
		$json                = $common->get_post_meta_field_value( 'author_affiliations_meta_key', $post );
		$author_affiliations = json_decode( $json, true );
		if ( empty( $author_affiliations ) ) {
			$author_affiliations = array();
		}
		return $author_affiliations;
	}
}
