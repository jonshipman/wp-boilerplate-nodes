<?php
/**
 * Logout
 *
 * GraphQL resolve to logout.
 *
 * @link https://github.com/funkhaus/wp-graphql-cors
 *
 * @package wp_boilerplate_nodes
 * @since 1.0.0
 */

if ( ! function_exists( 'wp_boilerplate_nodes_logout_resolve' ) ) {
	/**
	 * Used with wp_boilerplate_nodes_login_with_cookies.
	 *
	 * @return void
	 */
	function wp_boilerplate_nodes_logout_resolve() {
		register_graphql_mutation(
			'logout',
			array(
				'inputFields'         => array(),
				'outputFields'        => array(
					'status' => array(
						'type'        => 'String',
						'description' => 'Logout operation status',
						'resolve'     => function( $payload ) {
							return $payload['status'];
						},
					),
				),
				'mutateAndGetPayload' => function() {
					// Logout and destroy session.
					wp_logout();

					return array( 'status' => 'SUCCESS' );
				},
			)
		);
	}
}

add_action( 'graphql_register_types', 'wp_boilerplate_nodes_logout_resolve' );
