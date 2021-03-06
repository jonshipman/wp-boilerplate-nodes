<?php
/**
 * Plugin Name: React Boilerplate Node Plugin
 * Description: Facilitates the nessesary WP hooks for https://github.com/jonshipman/wp-boilerplate-nodes
 * Version: 1.0.1
 * Author: Jon Shipman
 * Text Domain: wp-boilerplate-nodes

 * ============================================================================================================
 * This software is provided "as is" and any express or implied warranties, including, but not limited to, the
 * implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
 * the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
 * consequential damages(including, but not limited to, procurement of substitute goods or services; loss of
 * use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
 * contract, strict liability, or tort(including negligence or otherwise) arising in any way out of the use of
 * this software, even if advised of the possibility of such damage.

 * ============================================================================================================
 *
 * @package wp_boilerplate_nodes
 * @since 1.0.0
 */

define( 'WP_BOILERPLATE_NODES_FILE', __FILE__ );

add_action(
	'plugins_loaded',
	function() {

		// Require based on if WP-GraphQL is installed.
		if ( function_exists( 'register_graphql_field' ) ) {
			require_once __DIR__ . '/includes/acf-graphql.php';
			require_once __DIR__ . '/includes/acf-options.php';
			require_once __DIR__ . '/includes/admin.php';
			require_once __DIR__ . '/includes/add-frontend-url-in-admin.php';
			require_once __DIR__ . '/includes/content-nodes.php';
			require_once __DIR__ . '/includes/frontend-origin.php';
			require_once __DIR__ . '/includes/headers.php';
			require_once __DIR__ . '/includes/html-entities.php';
			require_once __DIR__ . '/includes/increase-max-post-limit.php';
			require_once __DIR__ . '/includes/jwt.php';
			require_once __DIR__ . '/includes/log.php';
			require_once __DIR__ . '/includes/menus.php';
			require_once __DIR__ . '/includes/redirect.php';
			require_once __DIR__ . '/includes/yoast.php';
			require_once __DIR__ . '/includes/settings.php';
			require_once __DIR__ . '/includes/signon.php';
			require_once __DIR__ . '/includes/set-auth-cookie.php';
			require_once __DIR__ . '/includes/setcookie-same-site.php';
			require_once __DIR__ . '/includes/is-logged-in.php';
			require_once __DIR__ . '/includes/login-with-cookies.php';
			require_once __DIR__ . '/includes/logout.php';
		}
	},
	11
);
