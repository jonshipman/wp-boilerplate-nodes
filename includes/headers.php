<?php
/**
 * WP-GRAPHQL HEADERS filter.
 *
 * @package  Wp_Boilerplate_Nodes
 */

/**
 * IMPORTANT: Remember to set 'SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1' in APACHE or equiv in nginx.
 */

if ( ! function_exists( 'wp_boilerplate_nodes_gql_headers' ) ) {
	function wp_boilerplate_nodes_gql_headers( $headers ) {
		if ( isset( $headers['X-hacker'] ) ) {
			unset( $headers['X-hacker'] );
		}

		$headers['Access-Control-Allow-Origin']      = get_frontend_origin();
		$headers['Access-Control-Allow-Methods']     = 'GET, POST';
		$headers['Access-Control-Allow-Credentials'] = 'true';
		$headers['Access-Control-Expose-Headers']    = 'Content-Type, X-JWT-Auth, X-JWT-Refresh, HTTP_X_WP_NONCE';

		return $headers;
	}
}

add_filter(
	'graphql_response_headers_to_send',
	'wp_boilerplate_nodes_gql_headers'
);
