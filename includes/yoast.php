<?php
/**
 * Yoast hooks
 *
 * A collection of hooks for modifying Yoast to work with the React frontend.
 *
 * @link url
 *
 * @package wp_boilerplate_nodes
 * @since 1.0
 */

/**
 * Fixes "Undefined index" issue in wp-graphql yoast.
 *
 * @param array $result Array of taxonomy_meta for seo.
 * @return array
 */
function wp_boilerplate_nodes_options_wpseo_taxonomy_meta( $result ) {
	foreach ( $result as $tax => $term ) {
		foreach ( $term as $key => $value ) {

			if ( ! isset( $value['canonical'] ) ) {
				$result[ $tax ][ $key ]['canonical'] = '';
			}

			if ( ! isset( $value['wpseo_metakeywords'] ) ) {
				$result[ $tax ][ $key ]['wpseo_metakeywords'] = '';
			}
		}
	}

	return $result;
}

add_filter( 'option_wpseo_taxonomy_meta', 'wp_boilerplate_nodes_options_wpseo_taxonomy_meta' );

/**
 * Adds the missing wp-graphql-yoast params to prevent "Undefined index" notices.
 *
 * @param array $arr The array of meta_keywords to be filtered.
 * @return array
 */
function wp_boilerplate_nodes_wpseo_add_extra_taxmeta_term_defaults( $arr ) {
	$arr['canonical']          = '';
	$arr['wpseo_metakeywords'] = '';

	return $arr;
}

add_filter( 'wpseo_add_extra_taxmeta_term_defaults', 'wp_boilerplate_nodes_wpseo_add_extra_taxmeta_term_defaults' );

/**
 * Only allows the specified post types into Yoast's sitemap.
 *
 * @param array $post_types The array of post_types from Yoast.
 * @return array
 */
function wp_boilerplate_nodes_wpseo_remove_post_types( $post_types ) {
	$allowed = apply_filters( 'wp_boilerplate_nodes_allowed_sitemap_post_types', array( 'post', 'page' ) );
	foreach ( $post_types as $post_type => $value ) {
		if ( ! in_array( $post_type, $allowed, true ) ) {
			unset( $post_types[ $post_type ] );
		}
	}

	return $post_types;
}

add_filter( 'wpseo_accessible_post_types', 'wp_boilerplate_nodes_wpseo_remove_post_types' );

/**
 * Only allows the passed taxonomies.
 *
 * @param boolean $bool Whether the taxonomy is not allowed.
 * @param string  $taxonomy The taxonomy being filtered.
 * @return boolean
 */
function wp_boilerplate_nodes_wpseo_remove_taxonomy( $bool, $taxonomy ) {
	$allowed = apply_filters( 'wp_boilerplate_nodes_allowed_sitemap_taxonomies', array( 'category' ) );
	return ! in_array( $taxonomy, $allowed, true );
}

add_filter( 'wpseo_sitemap_exclude_taxonomy', 'wp_boilerplate_nodes_wpseo_remove_taxonomy', 10, 2 );

/**
 * Changes the URLs in Yoast to the React frontend.
 *
 * @param string $xml The XML string before being output.
 * @return string
 */
function wp_boilerplate_nodes_wpseo_change_urls_to_frontend_origin( $xml ) {
	$home_url = preg_quote( home_url(), '/' );

	$xml = preg_replace(
		'/<loc>' . $home_url . '(.*?)\/?<\/loc>/',
		'<loc>' . get_frontend_origin() . '${1}</loc>',
		$xml
	);

	return $xml;
}

add_filter( 'wpseo_sitemap_url', 'wp_boilerplate_nodes_wpseo_change_urls_to_frontend_origin' );

add_filter( 'wpseo_stylesheet_url', '__return_empty_string' );
add_filter( 'wpseo_sitemap_exclude_author', '__return_true' );
