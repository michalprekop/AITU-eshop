<?php
/**
 * Plugin Name: AITU Packeta Home Delivery Name Reset
 * Description: One-time restore of original Packeta home delivery carrier names.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore original Packeta carrier names from carrier table.
 *
 * @return void
 */
function aitu_packeta_restore_home_delivery_names() {
	$status = get_option( 'aitu_packeta_home_delivery_name_reset_status', '' );
	if ( 'done' === (string) $status ) {
		return;
	}

	if ( ! function_exists( 'aitu_packeta_home_delivery_config' ) || ! function_exists( 'aitu_packeta_match_carrier' ) ) {
		return;
	}

	global $wpdb;
	if ( ! ( $wpdb instanceof wpdb ) ) {
		return;
	}

	$table_name = $wpdb->prefix . 'packetery_carrier';
	$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	if ( $exists !== $table_name ) {
		update_option( 'aitu_packeta_home_delivery_name_reset_status', 'missing_table', false );
		return;
	}

	$all_rows = $wpdb->get_results(
		"SELECT id, name, is_pickup_points, country, deleted FROM `{$table_name}` WHERE deleted = 0",
		ARRAY_A
	);

	if ( ! is_array( $all_rows ) ) {
		update_option( 'aitu_packeta_home_delivery_name_reset_status', 'query_failed', false );
		return;
	}

	$rows_by_country = array();
	foreach ( $all_rows as $row ) {
		if ( empty( $row['country'] ) || ! empty( $row['is_pickup_points'] ) ) {
			continue;
		}

		$rows_by_country[ strtoupper( (string) $row['country'] ) ][] = $row;
	}

	$updated = 0;
	$missing = array();

	foreach ( aitu_packeta_home_delivery_config() as $config ) {
		$candidate_rows = $rows_by_country[ $config['carrier_country'] ] ?? array();
		$matched        = aitu_packeta_match_carrier( $candidate_rows, $config );
		if ( null === $matched ) {
			$missing[] = $config['shipping_country'];
			continue;
		}

		$option_key = 'packetery_carrier_' . (string) $matched['id'];
		$options    = get_option( $option_key, false );
		if ( ! is_array( $options ) ) {
			continue;
		}

		$options['name'] = (string) $matched['name'];
		update_option( $option_key, $options );
		$updated++;
	}

	update_option( 'aitu_packeta_home_delivery_name_reset_status', 'done', false );
	update_option( 'aitu_packeta_home_delivery_name_reset_updated', $updated, false );
	update_option( 'aitu_packeta_home_delivery_name_reset_missing', $missing, false );
}
add_action( 'init', 'aitu_packeta_restore_home_delivery_names', 60 );
