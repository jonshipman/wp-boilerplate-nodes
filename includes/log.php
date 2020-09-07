<?php
/**
 * Log helper functions.
 *
 * @package  Wp_Boilerplate_Nodes
 */

// Log to stdout or to the log file.
if ( ! function_exists( '__log' ) ) {
	function __log() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$arguements = func_get_args();

			$output = '';

			foreach ( $arguements as $a ) {
				ob_start();
				var_dump( $a );
				$__ret = ob_get_clean();

				$output = "$__ret\n\n";
			}

			return $output;
		}
		return null;
	}

	if ( ! function_exists( '__write_log' ) ) {
		function __write_log() {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$args   = func_get_args();
				$output = call_user_func_array( '__log', $args );

				error_log( $output );
			}
		}
	}
}
