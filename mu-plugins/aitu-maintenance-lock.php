<?php
/**
 * Plugin Name: AITU Temporary Maintenance Lock
 * Description: Restricts frontend pages to logged-in users and shows a minimal maintenance screen.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize current request path.
 *
 * @return string
 */
function aitu_maintenance_request_path() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$path        = '/' . ltrim( $path, '/' );
	$path        = untrailingslashit( $path );

	return '' === $path ? '/' : $path;
}

add_action(
	'template_redirect',
	function () {
		if ( is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		$login_slug   = defined( 'AITU_LOGIN_SLUG' ) ? (string) AITU_LOGIN_SLUG : 'aitu-login';
		$current_path = aitu_maintenance_request_path();
		$allowed      = array(
			'/' . trim( $login_slug, '/' ),
			'/wp-login.php',
		);

		if ( in_array( $current_path, $allowed, true ) ) {
			return;
		}

		status_header( 503 );
		nocache_headers();

		echo '<!doctype html>';
		echo '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
		echo '<title>Maintenance</title>';
		echo '<style>html,body{height:100%;margin:0}body{display:flex;align-items:center;justify-content:center;background:#fff;color:#000;font:400 16px/1.2 Arial,sans-serif;letter-spacing:0;text-transform:uppercase}</style>';
		echo '</head><body>AITU ESHOP OPENING SOON</body></html>';
		exit;
	},
	0
);
