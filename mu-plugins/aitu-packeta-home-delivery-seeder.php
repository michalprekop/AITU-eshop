<?php
/**
 * Plugin Name: AITU Packeta Home Delivery Seeder
 * Description: One-time setup of selected Packeta home delivery carriers and prices.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize string for fuzzy comparisons.
 *
 * @param string $value Raw value.
 * @return string
 */
function aitu_packeta_normalize( $value ) {
	$value = strtolower( remove_accents( (string) $value ) );
	$value = preg_replace( '/^[a-z]{2}\s+/i', '', $value );
	$value = preg_replace( '/[^a-z0-9]+/i', ' ', $value );
	$value = trim( preg_replace( '/\s+/', ' ', $value ) );

	return (string) $value;
}

/**
 * Get config rows.
 *
 * @return array<int, array<string, string>>
 */
function aitu_packeta_home_delivery_config() {
	return array(
		array( 'shipping_country' => 'BE', 'carrier_country' => 'BE', 'country_name' => 'Belgicko', 'carrier_name' => 'Holandská pošta HD', 'full_label' => 'BE Holandská pošta HD', 'price' => '12.99' ),
		array( 'shipping_country' => 'BG', 'carrier_country' => 'BG', 'country_name' => 'Bulharsko', 'carrier_name' => 'Sameday HD', 'full_label' => 'BG Sameday HD', 'price' => '8.99' ),
		array( 'shipping_country' => 'DK', 'carrier_country' => 'DK', 'country_name' => 'Dánsko', 'carrier_name' => 'Post Nord HD', 'full_label' => 'DK Post Nord HD', 'price' => '18.49' ),
		array( 'shipping_country' => 'EE', 'carrier_country' => 'EE', 'country_name' => 'Estónsko', 'carrier_name' => 'Omniva HD', 'full_label' => 'EE Omniva HD', 'price' => '11.99' ),
		array( 'shipping_country' => 'FI', 'carrier_country' => 'FI', 'country_name' => 'Fínsko', 'carrier_name' => 'Matkahuolto HD', 'full_label' => 'FI Matkahuolto HD', 'price' => '22.99' ),
		array( 'shipping_country' => 'FR', 'carrier_country' => 'FR', 'country_name' => 'Francúzsko', 'carrier_name' => 'Colis Privé Direct HD', 'full_label' => 'FR Colis Privé Direct HD', 'price' => '13.49' ),
		array( 'shipping_country' => 'GR', 'carrier_country' => 'GR', 'country_name' => 'Grécko', 'carrier_name' => 'Elta HD', 'full_label' => 'GR Elta HD', 'price' => '8.49' ),
		array( 'shipping_country' => 'NL', 'carrier_country' => 'NL', 'country_name' => 'Holandsko', 'carrier_name' => 'Holandská pošta HD', 'full_label' => 'NL Holandská pošta HD', 'price' => '12.99' ),
		array( 'shipping_country' => 'HR', 'carrier_country' => 'HR', 'country_name' => 'Chorvátsko', 'carrier_name' => 'Overseas HD', 'full_label' => 'HR Overseas HD', 'price' => '8.49' ),
		array( 'shipping_country' => 'IE', 'carrier_country' => 'IE', 'country_name' => 'Írsko', 'carrier_name' => 'Fastway HD', 'full_label' => 'IE Fastway HD', 'price' => '19.99' ),
		array( 'shipping_country' => 'IL', 'carrier_country' => 'IL', 'country_name' => 'Izrael', 'carrier_name' => 'FedEx Economy HD', 'full_label' => 'IL FedEx Economy HD', 'price' => '52.99' ),
		array( 'shipping_country' => 'LI', 'carrier_country' => 'CH', 'country_name' => 'Lichtenštajnsko', 'carrier_name' => 'Post Direct HD', 'full_label' => 'CH Post Direct HD', 'price' => '16.99' ),
		array( 'shipping_country' => 'LT', 'carrier_country' => 'LT', 'country_name' => 'Litva', 'carrier_name' => 'Lithuanian Post HD', 'full_label' => 'LT Lithuanian Post HD', 'price' => '10.49' ),
		array( 'shipping_country' => 'LV', 'carrier_country' => 'LV', 'country_name' => 'Lotyšsko', 'carrier_name' => 'Venipak HD', 'full_label' => 'LV Venipak HD', 'price' => '9.99' ),
		array( 'shipping_country' => 'LU', 'carrier_country' => 'LU', 'country_name' => 'Luxembursko', 'carrier_name' => 'Luxemburská pošta HD', 'full_label' => 'LU Luxemburská pošta HD', 'price' => '19.49' ),
		array( 'shipping_country' => 'DE', 'carrier_country' => 'DE', 'country_name' => 'Nemecko', 'carrier_name' => 'Home Delivery HD', 'full_label' => 'DE Home Delivery HD', 'price' => '10.49' ),
		array( 'shipping_country' => 'PL', 'carrier_country' => 'PL', 'country_name' => 'Poľsko', 'carrier_name' => 'Poľská pošta 48 HD', 'full_label' => 'PL Poľská pošta 48 HD', 'price' => '6.99' ),
		array( 'shipping_country' => 'PT', 'carrier_country' => 'PT', 'country_name' => 'Portugalsko', 'carrier_name' => 'MRW HD', 'full_label' => 'PT MRW HD', 'price' => '13.99' ),
		array( 'shipping_country' => 'AT', 'carrier_country' => 'AT', 'country_name' => 'Rakúsko', 'carrier_name' => 'DPD HD', 'full_label' => 'AT DPD HD', 'price' => '8.99' ),
		array( 'shipping_country' => 'SI', 'carrier_country' => 'SI', 'country_name' => 'Slovinsko', 'carrier_name' => 'Post HD', 'full_label' => 'SI Post HD', 'price' => '8.99' ),
		array( 'shipping_country' => 'AE', 'carrier_country' => 'AE', 'country_name' => 'Spojené arabské emiráty', 'carrier_name' => 'Aramex HD', 'full_label' => 'AE Aramex HD', 'price' => '55.49' ),
		array( 'shipping_country' => 'ES', 'carrier_country' => 'ES', 'country_name' => 'Španielsko', 'carrier_name' => 'MRW HD', 'full_label' => 'ES MRW HD', 'price' => '12.49' ),
		array( 'shipping_country' => 'CH', 'carrier_country' => 'CH', 'country_name' => 'Švajčiarsko', 'carrier_name' => 'Post Direct HD', 'full_label' => 'CH Post Direct HD', 'price' => '16.99' ),
		array( 'shipping_country' => 'SE', 'carrier_country' => 'SE', 'country_name' => 'Švédsko', 'carrier_name' => 'Post Nord HD', 'full_label' => 'SE Post Nord HD', 'price' => '24.49' ),
		array( 'shipping_country' => 'IT', 'carrier_country' => 'IT', 'country_name' => 'Taliansko', 'carrier_name' => 'Bartolini HD', 'full_label' => 'IT Bartolini HD', 'price' => '11.99' ),
		array( 'shipping_country' => 'TR', 'carrier_country' => 'TR', 'country_name' => 'Turecko', 'carrier_name' => 'FedEx HD Economy', 'full_label' => 'TR FedEx HD Economy', 'price' => '39.99' ),
		array( 'shipping_country' => 'UA', 'carrier_country' => 'UA', 'country_name' => 'Ukrajina', 'carrier_name' => 'Meest Poshta HD', 'full_label' => 'UA Meest Poshta HD', 'price' => '15.99' ),
		array( 'shipping_country' => 'US', 'carrier_country' => 'US', 'country_name' => 'USA', 'carrier_name' => 'FedEx HD Economy', 'full_label' => 'US FedEx HD Economy', 'price' => '44.49' ),
		array( 'shipping_country' => 'GB', 'carrier_country' => 'GB', 'country_name' => 'Veľká Británia', 'carrier_name' => 'Royal Mail 24 HD', 'full_label' => 'GB Royal Mail 24 HD', 'price' => '24.49' ),
	);
}

/**
 * Try to match a concrete carrier row.
 *
 * @param array<int, array<string, mixed>> $rows Carrier rows.
 * @param array<string, string>            $config Config row.
 * @return array<string, mixed>|null
 */
function aitu_packeta_match_carrier( array $rows, array $config ) {
	$targets = array(
		aitu_packeta_normalize( $config['full_label'] ),
		aitu_packeta_normalize( $config['carrier_name'] ),
	);

	$exact = array();
	foreach ( $rows as $row ) {
		$row_name = aitu_packeta_normalize( (string) $row['name'] );
		if ( in_array( $row_name, $targets, true ) ) {
			$exact[] = $row;
		}
	}
	if ( count( $exact ) === 1 ) {
		return $exact[0];
	}

	foreach ( $rows as $row ) {
		$row_name = aitu_packeta_normalize( (string) $row['name'] );
		foreach ( $targets as $target ) {
			if ( '' !== $target && ( false !== strpos( $row_name, $target ) || false !== strpos( $target, $row_name ) ) ) {
				return $row;
			}
		}
	}

	return null;
}

/**
 * Ensure shipping method exists in a zone.
 *
 * @param WC_Shipping_Zone $zone Zone.
 * @param string           $method_id Method ID.
 * @return int
 */
function aitu_packeta_ensure_shipping_method( WC_Shipping_Zone $zone, $method_id ) {
	$methods = $zone->get_shipping_methods( true );
	foreach ( $methods as $method ) {
		if ( isset( $method->id ) && (string) $method->id === $method_id ) {
			return isset( $method->instance_id ) ? (int) $method->instance_id : 0;
		}
	}

	return (int) $zone->add_shipping_method( $method_id );
}

/**
 * Find or create a Woo shipping zone for one country.
 *
 * @param string $zone_name Zone name.
 * @param string $country_code Country code.
 * @return WC_Shipping_Zone
 */
function aitu_packeta_ensure_zone( $zone_name, $country_code ) {
	$zones = WC_Shipping_Zones::get_zones();
	foreach ( $zones as $zone ) {
		if ( isset( $zone['zone_name'] ) && (string) $zone['zone_name'] === (string) $zone_name ) {
			return new WC_Shipping_Zone( (int) $zone['id'] );
		}
	}

	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( (string) $zone_name );
	$zone_id = (int) $zone->save();
	$zone    = new WC_Shipping_Zone( $zone_id );
	$zone->set_zone_name( (string) $zone_name );
	$zone->set_locations(
		array(
			array(
				'code' => (string) $country_code,
				'type' => 'country',
			),
		)
	);
	$zone->save();

	return new WC_Shipping_Zone( $zone_id );
}

/**
 * Configure free shipping method.
 *
 * @param int $instance_id Instance ID.
 * @return void
 */
function aitu_packeta_configure_free_shipping( $instance_id ) {
	$option_key = 'woocommerce_free_shipping_' . (int) $instance_id . '_settings';
	$settings   = get_option( $option_key, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings['requires']   = 'min_amount';
	$settings['min_amount'] = '150';

	update_option( $option_key, $settings );
}

/**
 * Run Packeta setup once.
 *
 * @return void
 */
function aitu_seed_packeta_home_delivery() {
	$status = get_option( 'aitu_packeta_home_delivery_seed_20260412_status', '' );
	if ( 0 === strpos( (string) $status, 'done' ) ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Shipping_Zone' ) || ! class_exists( 'WC_Shipping_Zones' ) ) {
		return;
	}

	global $wpdb;
	if ( ! ( $wpdb instanceof wpdb ) ) {
		return;
	}

	$table_name = $wpdb->prefix . 'packetery_carrier';
	$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	if ( $exists !== $table_name ) {
		update_option( 'aitu_packeta_home_delivery_seed_20260412_status', 'failed_missing_table', false );
		return;
	}

	$all_rows = $wpdb->get_results(
		"SELECT id, name, is_pickup_points, country, max_weight, available, deleted FROM `{$table_name}` WHERE deleted = 0",
		ARRAY_A
	);

	if ( ! is_array( $all_rows ) ) {
		update_option( 'aitu_packeta_home_delivery_seed_20260412_status', 'failed_query', false );
		return;
	}

	$rows_by_country = array();
	foreach ( $all_rows as $row ) {
		if ( ! empty( $row['country'] ) ) {
			$rows_by_country[ strtoupper( (string) $row['country'] ) ][] = $row;
		}
	}

	$selected_ids = array();
	$log          = array(
		'matched'      => array(),
		'missing'      => array(),
		'deactivated'  => array(),
		'zone_updates' => array(),
	);

	foreach ( aitu_packeta_home_delivery_config() as $config ) {
		$candidate_rows = array();
		foreach ( $rows_by_country[ $config['carrier_country'] ] ?? array() as $row ) {
			if ( ! empty( $row['is_pickup_points'] ) ) {
				continue;
			}
			$candidate_rows[] = $row;
		}

		$matched = aitu_packeta_match_carrier( $candidate_rows, $config );
		if ( null === $matched ) {
			$log['missing'][] = array(
				'config'    => $config,
				'available' => array_values( array_map( static function ( $row ) {
					return $row['name'];
				}, $candidate_rows ) ),
			);
			continue;
		}

		$carrier_id           = (string) $matched['id'];
		$option_id            = 'packetery_carrier_' . $carrier_id;
		$existing             = get_option( $option_id, array() );
		$existing             = is_array( $existing ) ? $existing : array();
		$selected_ids[]       = $carrier_id;
		$weight_ceiling       = isset( $matched['max_weight'] ) && (float) $matched['max_weight'] > 0 ? (string) $matched['max_weight'] : '9999';
		$existing['id']                  = $carrier_id;
		$existing['active']              = true;
		$existing['pricing_type']        = 'byWeight';
		$existing['free_shipping_limit'] = '150';
		$existing['days_until_shipping'] = '0';
		$existing['shipping_time_cut_off'] = '';
		$existing['address_validation']  = 'none';
		$existing['weight_limits']       = array(
			array(
				'weight' => $weight_ceiling,
				'price'  => $config['price'],
			),
		);
		update_option( $option_id, $existing );

		$zone = aitu_packeta_ensure_zone( $config['country_name'], $config['shipping_country'] );
		$packeta_instance = aitu_packeta_ensure_shipping_method( $zone, 'packetery_shipping_method' );
		$free_instance    = aitu_packeta_ensure_shipping_method( $zone, 'free_shipping' );
		aitu_packeta_configure_free_shipping( $free_instance );

		$log['matched'][] = array(
			'config'             => $config,
			'carrier_id'         => $carrier_id,
			'carrier_name'       => $matched['name'],
			'zone_id'            => $zone->get_id(),
			'packeta_instance'   => $packeta_instance,
			'free_shipping_id'   => $free_instance,
		);
		$log['zone_updates'][] = array(
			'zone' => $config['country_name'],
			'country_code' => $config['shipping_country'],
		);
	}

	$selected_ids = array_unique( $selected_ids );

	foreach ( $all_rows as $row ) {
		if ( ! empty( $row['is_pickup_points'] ) ) {
			continue;
		}

		$carrier_id = (string) $row['id'];
		if ( in_array( $carrier_id, $selected_ids, true ) ) {
			continue;
		}

		$option_id = 'packetery_carrier_' . $carrier_id;
		$existing  = get_option( $option_id, false );
		if ( ! is_array( $existing ) || empty( $existing['active'] ) ) {
			continue;
		}

		$existing['active'] = false;
		update_option( $option_id, $existing );
		$log['deactivated'][] = array(
			'carrier_id'   => $carrier_id,
			'carrier_name' => $row['name'],
			'country'      => $row['country'],
		);
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['basedir'] ) && is_dir( $upload_dir['basedir'] ) ) {
		$log_path = trailingslashit( $upload_dir['basedir'] ) . 'aitu-packeta-home-delivery-seed-20260412.json';
		file_put_contents( $log_path, wp_json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}

	$final_status = empty( $log['missing'] ) ? 'done' : 'done_with_missing';
	update_option( 'aitu_packeta_home_delivery_seed_20260412_status', $final_status, false );
}
add_action( 'init', 'aitu_seed_packeta_home_delivery', 50 );
