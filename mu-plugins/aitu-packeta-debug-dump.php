<?php
/**
 * Plugin Name: AITU Packeta Debug Dump
 * Description: Temporary dump of Packeta carriers/options for diagnostics.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'aitu_packeta_debug_dump_done', '' ) === 'done' ) {
			return;
		}

		global $wpdb;
		if ( ! ( $wpdb instanceof wpdb ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'packetery_carrier';
		$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $exists !== $table_name ) {
			update_option( 'aitu_packeta_debug_dump_done', 'missing_table', false );
			return;
		}

		$rows = $wpdb->get_results(
			"SELECT id, name, is_pickup_points, country, max_weight, available, deleted FROM `{$table_name}` WHERE deleted = 0 ORDER BY country, name",
			ARRAY_A
		);

		$data = array(
			'generated_at' => gmdate( 'c' ),
			'rows'         => $rows,
		);

		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) || ! is_dir( $upload_dir['basedir'] ) ) {
			update_option( 'aitu_packeta_debug_dump_done', 'missing_uploads', false );
			return;
		}

		$path = trailingslashit( $upload_dir['basedir'] ) . 'aitu-packeta-debug-dump.json';
		file_put_contents( $path, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

		update_option( 'aitu_packeta_debug_dump_done', 'done', false );
	},
	5
);
