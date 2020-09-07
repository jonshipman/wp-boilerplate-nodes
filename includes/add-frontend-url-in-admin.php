<?php

/**
 * Adds editor-scripts to the admin.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_adds_admin_asset_js' ) ) {
	function wp_boilerplate_nodes_adds_admin_asset_js() {
		if ( ! empty( $_GET['action'] ) ) {
			wp_register_script(
				'wp-boilerplate-nodes/editor-scripts',
				sprintf( '%s%s', plugin_dir_url( WP_BOILERPLATE_NODES_FILE ), '/assets/editor-scripts.js' ),
				array(
					'wp-plugins',
					'wp-edit-post',
					'wp-element',
				),
				filemtime( sprintf( '%s%s%s%s', trailingslashit( plugin_dir_path( WP_BOILERPLATE_NODES_FILE ) ), 'assets', DIRECTORY_SEPARATOR, 'editor-scripts.js' ) )
			);

			wp_localize_script(
				'wp-boilerplate-nodes/editor-scripts',
				'HeadlessWp',
				array(
					'frontend_origin' => get_frontend_origin(),
				)
			);
		}
	}
}

add_action(
	'admin_init',
	'wp_boilerplate_nodes_adds_admin_asset_js'
);

/**
 * Adds the editor-scripts to the block editor.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_enqueue_block_editor_scripts' ) ) {
	function wp_boilerplate_nodes_enqueue_block_editor_scripts() {
		wp_enqueue_script( 'wp-boilerplate-nodes/editor-scripts' );
	}
}

add_action(
	'enqueue_block_editor_assets',
	'wp_boilerplate_nodes_enqueue_block_editor_scripts'
);

/**
 * Changes the post link preview pre hydration.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_prehydration_preview_link' ) ) {
	function wp_boilerplate_nodes_prehydration_preview_link( $preview ) {
		return str_replace( get_site_url(), get_frontend_origin(), $preview );
	}
}

add_filter(
	'preview_post_link',
	'wp_boilerplate_nodes_prehydration_preview_link'
);

/**
 * Changes the sample permlink to the frontend origin.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_changes_sample_permalink' ) ) {
	function wp_boilerplate_nodes_changes_sample_permalink( $permalink ) {
		$permalink[0] = rtrim( str_replace( get_site_url(), get_frontend_origin(), $permalink[0] ), '/' );

		return $permalink;
	}
}

add_filter(
	'get_sample_permalink',
	'wp_boilerplate_nodes_changes_sample_permalink'
);

/**
 * Modifies the rest results to use the frontend origin.
 */
if ( ! function_exists( 'wp_boilerplate_nodes_modify_rest' ) ) {
	function wp_boilerplate_nodes_modify_rest( $res ) {
		if ( isset( $res->data['link'] ) ) {
			$res->data['link'] = str_replace( get_site_url(), get_frontend_origin(), $res->data['link'] );
		}
		return $res;
	}
}

add_filter( 'rest_prepare_post', 'wp_boilerplate_nodes_modify_rest' );
add_filter( 'rest_prepare_page', 'wp_boilerplate_nodes_modify_rest' );

/**
 * Modifies get_permalink.
 */
foreach ( array( 'post', 'page', 'post_type' ) as $type ) {
	add_filter(
		$type . '_link',
		function ( $url, $post_id, $sample ) use ( $type ) {
			if ( is_admin() ) {
				return get_frontend_origin( $url );
			}

			return $url;
		},
		9999,
		3
	);
}
