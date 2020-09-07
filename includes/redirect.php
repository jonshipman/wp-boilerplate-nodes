<?php
/**
 * Handle the redirection on the frontend.
 *
 * @package  Wp_Boilerplate_Nodes
 */

/**
 * Handles the redirection to frontend_origin.
 *
 * @return void
 */

if ( ! function_exists( 'wp_boilerplate_nodes_template_redirect_to_origin' ) ) {
	function wp_boilerplate_nodes_template_redirect_to_origin() {
		if ( is_singular() ) {
			header(
				sprintf(
					'Location: %s',
					get_frontend_origin( get_permalink( get_post() ) )
				),
				true,
				301
			);

				die;
		} else {
			header(
				sprintf(
					'Location: %s',
					get_frontend_origin()
				),
				true,
				301
			);

			die;
		}
	}
}

add_action(
	'template_redirect',
	'wp_boilerplate_nodes_template_redirect_to_origin',
	99
);
