<?php
/**
 * Plugin Name: AITU Packeta Home Delivery Title Sync
 * Description: One-time sync of display names for active Packeta home delivery carriers.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync active non-pickup Packeta carrier option names.
 *
 * @return void
 */
function aitu_packeta_sync_home_delivery_titles() {
	$status = get_option( 'aitu_packeta_home_delivery_title_sync_status_v2', '' );
	if ( 'done' === (string) $status ) {
		return;
	}

	if ( ! function_exists( 'get_option' ) ) {
		return;
	}

	global $wpdb;
	if ( ! ( $wpdb instanceof wpdb ) ) {
		return;
	}

	$table_name = $wpdb->prefix . 'packetery_carrier';
	$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	if ( $exists !== $table_name ) {
		update_option( 'aitu_packeta_home_delivery_title_sync_status_v2', 'missing_table', false );
		return;
	}

	$rows = $wpdb->get_results(
		"SELECT id, is_pickup_points, deleted FROM `{$table_name}` WHERE deleted = 0 AND is_pickup_points = 0",
		ARRAY_A
	);

	if ( ! is_array( $rows ) ) {
		update_option( 'aitu_packeta_home_delivery_title_sync_status_v2', 'query_failed', false );
		return;
	}

	$target_name = function_exists( 'aitu_packeta_home_delivery_display_name' )
		? aitu_packeta_home_delivery_display_name()
		: 'Doručenie kuriérom na adresu';

	$updated = 0;
	foreach ( $rows as $row ) {
		$carrier_id = isset( $row['id'] ) ? (string) $row['id'] : '';
		if ( '' === $carrier_id ) {
			continue;
		}

		$option_key = 'packetery_carrier_' . $carrier_id;
		$options    = get_option( $option_key, false );
		if ( ! is_array( $options ) || empty( $options['active'] ) ) {
			continue;
		}

		$options['name']                = $target_name;
		$options['free_shipping_limit'] = '150';
		$options['days_until_shipping'] = '0';
		$options['shipping_time_cut_off'] = '';
		$options['address_validation']  = 'none';
		update_option( $option_key, $options );
		$updated++;
	}

	update_option( 'aitu_packeta_home_delivery_title_sync_status_v2', 'done', false );
	update_option( 'aitu_packeta_home_delivery_title_sync_updated_v2', $updated, false );
}
add_action( 'init', 'aitu_packeta_sync_home_delivery_titles', 55 );
