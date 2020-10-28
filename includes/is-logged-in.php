<?php
/**
 * Is Logged In
 *
 * @link https://github.com/funkhaus/wp-graphql-cors
 *
 * @package wp_boilerplate_nodes
 * @since 1.0.0
 */

if ( ! function_exists( 'wp_boilerplate_nodes_is_logged_in' ) ) {
	/**
	 * Query for a boolean is logged in return.
	 *
	 * @return void
	 */
	function wp_boilerplate_nodes_is_logged_in() {
		register_graphql_field(
			'RootQuery',
			'IsLoggedIn',
			array(
				'type'        => array( 'non_null' => 'Boolean' ),
				'description' => __( 'Simple resolve that returns is_user_logged_in', 'wp-boilerplate-nodes' ),
				'resolve'     => function() {
					return is_user_logged_in();
				},
			)
		);
	}
}

add_action( 'graphql_register_types', 'wp_boilerplate_nodes_is_logged_in' );
