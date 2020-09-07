<?php
/**
 * Frontend origin helper function.
 *
 * @package  Wp_Boilerplate_Nodes
 */

/**
 * Placeholder function for determining the frontend origin.
 *
 * @return str Frontend origin URL, e.g. the React URL.
 */
if ( ! function_exists( 'get_frontend_origin' ) ) {
	function get_frontend_origin( $original_url = '' ) {
		$port   = apply_filters( 'dev_port', '3000' );
		$origin = apply_filters( 'frontend_origin', sprintf( 'http://localhost:%s', $port ) );

		// If we're debugging, allow localhost.
		if (
		defined( 'WP_DEBUG' ) &&
		WP_DEBUG &&
		isset( $_SERVER['HTTP_REFERER'] ) &&
		false !== strpos( $_SERVER['HTTP_REFERER'], ':' . $port )
		) {
			$origin = explode( ':' . $port, $_SERVER['HTTP_REFERER'] )[0] . ':' . $port;
		}

		if ( ! empty( $original_url ) ) {
			return str_replace( get_site_url(), $origin, $original_url );
		}

		return $origin;
	}
}

// Fix breadcrumbs to use the frontend origin.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_breadcrumbs' ) ) {
	function wp_boilerplate_nodes_origin_breadcrumbs( $links ) {
		foreach ( $links as &$link ) {
			$link['url'] = get_frontend_origin( $link['url'] );
		}

		return $links;
	}
}

add_filter(
	'wpseo_breadcrumb_links',
	'wp_boilerplate_nodes_origin_breadcrumbs'
);

// Adds origin to the http_origins list.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_http_origins' ) ) {
	function wp_boilerplate_nodes_origin_http_origins( $origins ) {
		$origins = array_merge( array( get_frontend_origin() ), $origins );
		return $origins;
	}
}

add_filter(
	'allowed_http_origins',
	'wp_boilerplate_nodes_origin_http_origins',
	99
);

// Sets the login url.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_login_url' ) ) {
	function wp_boilerplate_nodes_origin_login_url() {
		return sprintf( '%s/login', get_frontend_origin() );
	}
}

add_filter(
	'login_url',
	'wp_boilerplate_nodes_origin_login_url'
);

// Sets the forgotpassword url.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_forgot_password_url' ) ) {
	function wp_boilerplate_nodes_origin_forgot_password_url() {
		return sprintf( '%s/forgot-password', get_frontend_origin() );
	}
}

add_filter(
	'lostpassword_url',
	'wp_boilerplate_nodes_origin_forgot_password_url'
);

// Sets the registration url.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_register_url' ) ) {
	function wp_boilerplate_nodes_origin_register_url() {
		return sprintf( '%s/register', get_frontend_origin() );
	}
}

add_filter(
	'register_url',
	'wp_boilerplate_nodes_origin_register_url'
);

// Filters the password reset email to change the retrieve password url.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_rp_message' ) ) {
	function wp_boilerplate_nodes_origin_rp_message( $message, $key, $user_login ) {
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
		/* translators: %s: Site name. */
		$message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
		/* translators: %s: User login. */
		$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
		$message .= sprintf( '%s/rp/%s/%s', get_frontend_origin(), $key, rawurlencode( $user_login ) ) . "\r\n";

		return $message;
	}
}

add_filter(
	'retrieve_password_message',
	'wp_boilerplate_nodes_origin_rp_message',
	99,
	3
);

// Filters the user registration email for the same.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_new_user_message' ) ) {
	function wp_boilerplate_nodes_origin_new_user_message( $wp_new_user_notification_email, $user ) {
		$key = get_password_reset_key( $user );

		$message  = sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
		$message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
		$message .= sprintf( '%s/rp/%s/%s', get_frontend_origin(), $key, rawurlencode( $user->user_login ) ) . "\r\n";

		$message .= wp_login_url() . "\r\n";

		$wp_new_user_notification_email['message'] = $message;

		return $wp_new_user_notification_email;
	}
}

add_filter(
	'wp_new_user_notification_email',
	'wp_boilerplate_nodes_origin_new_user_message',
	99,
	2
);

// Modifies links in the content to point to the origin.
if ( ! function_exists( 'wp_boilerplate_nodes_origin_content_filter' ) ) {
	function wp_boilerplate_nodes_origin_content_filter( $content ) {
		$content = str_replace( array( 'href="' . get_site_url() ), sprintf( 'href="%s', get_frontend_origin() ), $content );
		$content = str_replace( 'src="/wp-content', 'src="' . get_site_url() . '/wp-content', $content );

		// Fix links to the images.
		$content = str_replace( sprintf( 'href="%s/wp-content/', get_frontend_origin() ), sprintf( 'href="%s/wp-content/', get_site_url() ), $content );

		return $content;
	}
}

add_filter(
	'the_content',
	'wp_boilerplate_nodes_origin_content_filter',
	PHP_INT_MAX
);
