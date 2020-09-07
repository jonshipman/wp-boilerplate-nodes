<?php
/**
 * Admin filters.
 *
 * @package  Headless_WP
 */

 // Disable the editor.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', apply_filters( 'wp_boilerplate_nodes_disallow_file_edit', true ) );
}

/**
 * By default, in Add/Edit Post, WordPress moves checked categories to the top of the list and unchecked to the bottom.
 * When you have subcategories that you want to keep below their parents at all times, this makes no sense.
 * This function removes automatic reordering so the categories widget retains its order regardless of checked state.
 * Thanks to https://stackoverflow.com/a/12586404
 *
 * @param arr $args Array of arguments.
 * @return arr
 */
if ( ! function_exists( 'wp_boilerplate_nodes_taxonomy_checklist_checked_ontop_filter' ) ) {
	function wp_boilerplate_nodes_taxonomy_checklist_checked_ontop_filter( $args ) {
		$args['checked_ontop'] = false;
		return $args;
	}
}

add_filter( 'wp_terms_checklist_args', 'wp_boilerplate_nodes_taxonomy_checklist_checked_ontop_filter' );

/**
 * Customize the preview button in the WordPress admin to point to the headless client.
 *
 * @param  str $link The WordPress preview link.
 * @return str The headless WordPress preview link.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_set_headless_preview_link' ) ) {
	function wp_boilerplate_nodes_set_headless_preview_link( $link ) {
		$post = get_post();
		if ( ! $post ) {
			return $link;
		}
		$status      = 'revision';
		$frontend    = get_frontend_origin();
		$parent_id   = $post->post_parent;
		$revision_id = $post->ID;
		$type        = get_post_type( $parent_id );
		$nonce       = wp_create_nonce( 'wp_rest' );
		if ( 0 === $parent_id ) {
			$status = 'draft';
		}
		return "$frontend/_preview/$parent_id/$revision_id/$type/$status/$nonce";
	}
}

add_filter( 'preview_post_link', 'wp_boilerplate_nodes_set_headless_preview_link' );

/**
 * Includes preview link in post data for a response.
 *
 * @param \WP_REST_Response $response The response object.
 * @param \WP_Post          $post     Post object.
 * @return \WP_REST_Response The response object.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_set_preview_link_in_rest_response' ) ) {
	function wp_boilerplate_nodes_set_preview_link_in_rest_response( $response, $post ) {
		if ( 'draft' === $post->post_status ) {
			$response->data['preview_link'] = get_preview_post_link( $post );
		}

		return $response;
	}
}

add_filter( 'rest_prepare_post', 'wp_boilerplate_nodes_set_preview_link_in_rest_response', 10, 2 );
add_filter( 'rest_prepare_page', 'wp_boilerplate_nodes_set_preview_link_in_rest_response', 10, 2 );

// Remove default menu bar items.
if ( ! function_exists( 'wp_boilerplate_nodes_admin_bar_render' ) ) {
	function wp_boilerplate_nodes_admin_bar_render() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'site-name' );
		$wp_admin_bar->remove_menu( 'comments' );
		$wp_admin_bar->remove_menu( 'wp-logo' );
		$wp_admin_bar->remove_menu( 'wpseo-menu' );
	}
}

add_action(
	'wp_before_admin_bar_render',
	'wp_boilerplate_nodes_admin_bar_render'
);

// Remove Admin pages that the client doesn't need.
if ( ! function_exists( 'wp_boilerplate_nodes_removes_backend_endpoints' ) ) {
	function wp_boilerplate_nodes_removes_backend_endpoints() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$customizer_url = add_query_arg( 'return', urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), 'customize.php' );
			remove_submenu_page( 'themes.php', $customizer_url );
		}

		remove_submenu_page( 'index.php', 'index.php' );
		remove_submenu_page( 'index.php', 'update-core.php' );
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'tools.php', 'site-health.php' );
		remove_submenu_page( 'tools.php', 'network.php' );
		remove_submenu_page( 'tools.php', 'import.php' );
		remove_submenu_page( 'tools.php', 'tools.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'edit-comments.php' );
	}
}

add_action(
	'admin_menu',
	'wp_boilerplate_nodes_removes_backend_endpoints'
);

// Disable comments and pingbacks.
if ( apply_filters( 'wp_boilerplate_nodes_disable_comments_and_pings', true ) ) {
	add_filter( 'comments_open', '__return_false' );
	add_filter( 'pings_open', '__return_false' );
}

// Adds a redirect for people already logged in.
if ( ! function_exists( 'wp_boilerplate_nodes_wp_login_php_redirect_loggedin' ) ) {
	function wp_boilerplate_nodes_wp_login_php_redirect_loggedin() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url() );
			die;
		}
	}
}

add_action(
	'login_init',
	'wp_boilerplate_nodes_wp_login_php_redirect_loggedin'
);
