<?php
/**
 * Login with Cookies
 *
 * GraphQL resolve to login with cookies.
 *
 * @link https://github.com/funkhaus/wp-graphql-cors
 *
 * @package wp_boilerplate_nodes
 * @since 1.0.0
 */

if ( ! function_exists( 'wp_boilerplate_nodes_login_with_cookies' ) ) {
	/**
	 * GraphQL resolve to login with cookies.
	 *
	 * @return void
	 */
	function wp_boilerplate_nodes_login_with_cookies() {
		register_graphql_mutation(
			'loginCookies',
			array(
				'inputFields'         => array(
					'login'      => array(
						'type'        => array( 'non_null' => 'String' ),
						'description' => __( 'Input your user/e-mail.', 'wp-boilerplate-nodes' ),
					),
					'password'   => array(
						'type'        => array( 'non_null' => 'String' ),
						'description' => __( 'Input your password.', 'wp-boilerplate-nodes' ),
					),
					'rememberMe' => array(
						'type'        => 'Boolean',
						'description' => __(
							'Whether to "remember" the user. Increases the time that the cookie will be kept. Default false.',
							'wp-boilerplate-nodes'
						),
					),
				),
				'outputFields'        => array(
					'status' => array(
						'type'        => 'String',
						'description' => 'Login operation status',
						'resolve'     => function( $payload ) {
							return $payload['status'];
						},
					),
				),
				'mutateAndGetPayload' => function( $input ) {
					// Prepare credentials.
					$credential_keys = array(
						'login'      => 'user_login',
						'password'   => 'user_password',
						'rememberMe' => 'remember',
					);

					$credentials     = array();
					foreach ( $input as $key => $value ) {
						if ( in_array( $key, array_keys( $credential_keys ), true ) ) {
							$credentials[ $credential_keys[ $key ] ] = $value;
						}
					}

					// Authenticate User.
					$user = wp_boilerplate_nodes_signon( $credentials, is_ssl() );

					if ( is_wp_error( $user ) ) {
						throw new \GraphQL\Error\UserError( ! empty( $user->get_error_code() ) ? $user->get_error_code() : 'invalid login' );
					}

					return array( 'status' => 'SUCCESS' );
				},
			)
		);
	}
}

add_action( 'graphql_register_types', 'wp_boilerplate_nodes_login_with_cookies' );
