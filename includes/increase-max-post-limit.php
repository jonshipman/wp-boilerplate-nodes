<?php

/**
 * Increases the post query ceiling to get all posts for the sitemaps.
 * Limited to specifically 9999 for these calls.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_ssr_max_query_amt' ) ) {
	function wp_boilerplate_nodes_ssr_max_query_amt( $amount, $source, $args, $context, $info ) {
		if ( isset( $args['first'] ) && $args['first'] === 9999 ) {
			return 9999;
		}

		return $amount;
	}
}
add_filter(
	'graphql_connection_max_query_amount',
	'wp_boilerplate_nodes_ssr_max_query_amt',
	10,
	5
);
