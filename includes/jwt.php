<?php

// Recommended to set this to a unique key.
// See https://api.wordpress.org/secret-key/1.1/salt/.
if ( ! function_exists( 'wp_boilerplate_nodes_jwt_secret_key' ) ) {
	function wp_boilerplate_nodes_jwt_secret_key() {
		return SECURE_AUTH_KEY;
	}
}
add_filter(
	'graphql_jwt_auth_secret_key',
	'wp_boilerplate_nodes_jwt_secret_key'
);
