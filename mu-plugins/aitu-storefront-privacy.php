<?php
/**
 * Plugin Name: AITU Storefront Privacy
 * Description: Keeps optional visitor analytics off until a consent flow is configured.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;

// Jetpack connection and WooPayments remain active; only visitor analytics modules are omitted.
add_filter( 'jetpack_active_modules', function ( $modules ) {
	return array_values( array_diff( (array) $modules, array( 'stats', 'woocommerce-analytics' ) ) );
}, 100 );

// The WooCommerce attribution script respects this filter before creating source-tracking cookies.
add_filter( 'wc_order_attribution_allow_tracking', '__return_false', 100 );
