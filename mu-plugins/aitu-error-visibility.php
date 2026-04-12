<?php
/**
 * Plugin Name: AITU Error Visibility
 * Description: Hide PHP warnings from visitors, allow them for administrators in wp-admin.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default to hidden as early as possible for all public requests.
ini_set( 'display_errors', '0' );
ini_set( 'display_startup_errors', '0' );

/**
 * Re-enable PHP error display for administrators inside wp-admin only.
 *
 * @return void
 */
function aitu_enable_admin_error_visibility() {
	if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	ini_set( 'display_errors', '1' );
	ini_set( 'display_startup_errors', '1' );
	error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE & ~E_STRICT );
}
add_action( 'init', 'aitu_enable_admin_error_visibility', 1 );
