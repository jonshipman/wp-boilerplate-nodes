<?php
/**
 * Add ACF options page.
 *
 * @package  Wp_Boilerplate_Nodes
 */

// Hide the ACF menu when not debugging.
if ( ! function_exists( 'wp_boilerplate_nodes_hide_acf_menu' ) ) {
	function wp_boilerplate_nodes_hide_acf_menu( $bool ) {
		if ( defined( 'WP_DEBUG' ) && ( WP_DEBUG || WP_DEBUG === 'false' ) ) {
			return true;
		}

		return false;
	}
}

add_filter(
	'acf/settings/show_admin',
	'wp_boilerplate_nodes_hide_acf_menu',
	PHP_INT_MAX
);
