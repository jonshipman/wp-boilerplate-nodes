<?php
/**
 * Register main menu.
 *
 * @package  Wp_Boilerplate_Nodes
 */

/**
 * Register navigation menu.
 *
 * @return void
 */
function wp_boilerplate_nodes_register_menus() {
	register_nav_menu( 'header-menu', __( 'Header Menu', 'wp-boilerplate-nodes' ) );
	register_nav_menu( 'footer-menu', __( 'Footer Menu', 'wp-boilerplate-nodes' ) );
}
add_action( 'after_setup_theme', 'wp_boilerplate_nodes_register_menus' );

/**
 * Update the menu items to remove the site_url so NavLink will work with Router.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_origin_navmenu_replacement' ) ) {
	function wp_boilerplate_nodes_origin_navmenu_replacement( $menu_item ) {
		$menu_item->url = str_replace( get_site_url(), '', $menu_item->url );

		return $menu_item;
	}
}
add_filter(
	'wp_setup_nav_menu_item',
	'wp_boilerplate_nodes_origin_navmenu_replacement'
);
