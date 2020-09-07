<?php
/**
 * Adds extra meta to content nodes.
 *
 * @package Wp_Boilerplate_Nodes
 */

// Adds thumbnails.
if ( ! function_exists( 'wp_boilerplate_nodes_add_featured_image_support' ) ) {
	function wp_boilerplate_nodes_add_featured_image_support() {
		add_theme_support( 'post-thumbnails' );
	}
}

add_action(
	'after_setup_theme',
	'wp_boilerplate_nodes_add_featured_image_support'
);

// Adds extra fields used in the boilerplate.
if ( ! function_exists( 'wp_boilerplate_nodes_gql_content_nodes_register' ) ) {
	function wp_boilerplate_nodes_gql_content_nodes_register() {

		// Adds front_page acf fields to wp-graphql.
		add_filter(
			'acf_wpgraphql_locations',
			function ( $locations ) {
				$locations[] = array(
					'operator' => '==',
					'param'    => 'page_type',
					'value'    => 'front_page',
					'field'    => 'Page',
				);

				return $locations;
			}
		);

		$name   = 'dateFormatted';
		$config = array(
			'type'        => 'String',
			'description' => __( 'Returns the date as formatted in WordPress', 'wp-boilerplate-nodes' ),
			'resolve'     => function ( $post ) {
				return get_the_date( get_option( 'date_format' ), $post->ID );
			},
		);

		register_graphql_field( 'ContentNode', $name, $config );

		$name   = 'pageTemplate';
		$config = array(
			'type'        => 'String',
			'description' => __( 'WordPress Page Template', 'wp-boilerplate-nodes' ),
			'resolve'     => function ( $post ) {
				return get_page_template_slug( $post->ID );
			},
		);

		register_graphql_field( 'Page', $name, $config );
	}
}

add_action(
	'graphql_register_types',
	'wp_boilerplate_nodes_gql_content_nodes_register'
);

// Fixes missing single name on the menuItem.
if ( ! function_exists( 'wp_boilerplate_nodes_missing_single_name_menuitem' ) ) {
	function wp_boilerplate_nodes_missing_single_name_menuitem( $args, $post_type ) {
		if ( 'nav_menu_item' === $post_type ) {
			$args['graphql_single_name'] = 'menuItem';
		}

		return $args;
	}
}

add_filter(
	'register_post_type_args',
	'wp_boilerplate_nodes_missing_single_name_menuitem',
	10,
	2
);
