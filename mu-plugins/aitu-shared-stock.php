<?php
/**
 * Plugin Name: AITU Shared Stock
 * Description: One WooCommerce stock owner for each size across linked SK/EN products.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;

function aitu_stock_owner_id( $id ) {
	$map = get_option( 'aitu_shared_stock_map', array() );
	return isset( $map[ $id ] ) ? absint( $map[ $id ] ) : 0;
}

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WC_Product_Variation' ) ) {
		return;
	}
	class AITU_Shared_Stock_Variation extends WC_Product_Variation {
		public function get_stock_managed_by_id() {
			return aitu_stock_owner_id( $this->get_id() ) ?: parent::get_stock_managed_by_id();
		}
		private function stock_owner( $context ) {
			$id = aitu_stock_owner_id( $this->get_id() );
			return 'view' === $context && $id && $id !== $this->get_id() ? wc_get_product( $id ) : null;
		}
		public function get_stock_quantity( $context = 'view' ) {
			$owner = $this->stock_owner( $context );
			return $owner ? $owner->get_stock_quantity( $context ) : parent::get_stock_quantity( $context );
		}
		public function get_manage_stock( $context = 'view' ) {
			$owner = $this->stock_owner( $context );
			return $owner ? $owner->get_manage_stock( $context ) : parent::get_manage_stock( $context );
		}
		public function get_stock_status( $context = 'view' ) {
			$owner = $this->stock_owner( $context );
			return $owner ? $owner->get_stock_status( $context ) : parent::get_stock_status( $context );
		}
		public function get_backorders( $context = 'view' ) {
			$owner = $this->stock_owner( $context );
			return $owner ? $owner->get_backorders( $context ) : parent::get_backorders( $context );
		}
	}
	add_filter( 'woocommerce_product_class', function ( $class, $type, $post_type, $id ) {
		return 'variation' === $type && 'WC_Product_Variation' === $class && aitu_stock_owner_id( $id ) ? 'AITU_Shared_Stock_Variation' : $class;
	}, 30, 4 );
}, 30 );

/**
 * Mirror administrative data; stock reservations and atomic quantity updates use
 * the shared owner ID in WooCommerce itself, including refunds and cancellations.
 */
function aitu_stock_mirror( $product ) {
	static $running = false;
	if ( $running || ! $product instanceof WC_Product_Variation ) {
		return;
	}
	$owner_id = aitu_stock_owner_id( $product->get_id() );
	if ( ! $owner_id ) {
		return;
	}
	$running = true;
	try {
		$owner = wc_get_product( $owner_id );
		if ( ! $owner instanceof WC_Product_Variation ) {
			return;
		}
		if ( $owner_id !== $product->get_id() ) {
			// An edit made in either language deliberately updates the same owner.
			$owner->set_manage_stock( $product->get_manage_stock( 'edit' ) );
			$owner->set_backorders( $product->get_backorders( 'edit' ) );
			$owner->set_stock_quantity( $product->get_stock_quantity( 'edit' ) );
			$owner->set_stock_status( $product->get_stock_status( 'edit' ) );
			$owner->save();
		}
		foreach ( get_option( 'aitu_shared_stock_map', array() ) as $id => $mapped_owner ) {
			if ( (int) $mapped_owner !== $owner_id || (int) $id === $owner_id ) {
				continue;
			}
			$peer = wc_get_product( $id );
			if ( ! $peer instanceof WC_Product_Variation ) {
				continue;
			}
			$peer->set_manage_stock( $owner->get_manage_stock( 'edit' ) );
			$peer->set_backorders( $owner->get_backorders( 'edit' ) );
			$peer->set_stock_quantity( $owner->get_stock_quantity( 'edit' ) );
			$peer->set_stock_status( $owner->get_stock_status( 'edit' ) );
			$peer->save();
			wc_delete_product_transients( $peer->get_parent_id() );
		}
		wc_delete_product_transients( $owner->get_parent_id() );
	} finally {
		$running = false;
	}
}
add_action( 'woocommerce_variation_set_stock', 'aitu_stock_mirror', 30 );
add_action( 'woocommerce_product_object_updated_props', function ( $product, $props ) {
	if ( array_intersect( array( 'manage_stock', 'stock_quantity', 'stock_status', 'backorders' ), $props ) ) {
		aitu_stock_mirror( $product );
	}
}, 30, 2 );

function aitu_stock_signature( $variation ) {
	$attributes = $variation->get_attributes( 'edit' );
	$attributes = array_map( function ( $value ) { return strtolower( trim( (string) $value ) ); }, $attributes );
	ksort( $attributes );
	return wp_json_encode( $attributes );
}

/** Build a reviewable plan from actual Polylang relationships; never guess IDs. */
function aitu_stock_plan() {
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		return new WP_Error( 'aitu_missing_polylang', 'Polylang is required.', array( 'status' => 409 ) );
	}
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'lang' => '' ) );
	$map = $groups = $seen = $errors = array();
	$total = 0;
	foreach ( $ids as $id ) {
		$translations = array_map( 'absint', pll_get_post_translations( $id ) );
		if ( empty( $translations['sk'] ) || empty( $translations['en'] ) ) {
			continue;
		}
		$family = array( $translations['sk'], $translations['en'] );
		sort( $family );
		$primary_id = $family[0];
		if ( isset( $seen[ $primary_id ] ) ) { continue; }
		$seen[ $primary_id ] = true;
		$primary = wc_get_product( $primary_id );
		if ( ! $primary || ! $primary->is_type( 'variable' ) ) { continue; }
		$owners = array();
		foreach ( $primary->get_children() as $variation_id ) {
			$v = wc_get_product( $variation_id );
			if ( ! $v || 'publish' !== $v->get_status() ) { continue; }
			$signature = aitu_stock_signature( $v );
			if ( isset( $owners[ $signature ] ) ) { $errors[] = 'Duplicate attributes on product ' . $primary_id; }
			$owners[ $signature ] = $v;
		}
		foreach ( $family as $parent_id ) {
			$parent = wc_get_product( $parent_id );
			if ( ! $parent || ! $parent->is_type( 'variable' ) || 'publish' !== $parent->get_status() ) {
				$errors[] = 'Translation is not a published variable product: ' . $parent_id;
				continue;
			}
			$matched = array();
			foreach ( $parent->get_children() as $variation_id ) {
				$v = wc_get_product( $variation_id );
				if ( ! $v || 'publish' !== $v->get_status() ) { continue; }
				$signature = aitu_stock_signature( $v );
				if ( ! isset( $owners[ $signature ] ) || isset( $matched[ $signature ] ) ) {
					$errors[] = 'Unmatched or duplicate variation ' . $variation_id;
					continue;
				}
				$matched[ $signature ] = true;
				$owner = $owners[ $signature ];
				if ( true !== $owner->get_manage_stock( 'edit' ) || true !== $v->get_manage_stock( 'edit' ) || $owner->get_stock_quantity( 'edit' ) !== $v->get_stock_quantity( 'edit' ) || $owner->get_backorders( 'edit' ) !== $v->get_backorders( 'edit' ) || $owner->get_stock_status( 'edit' ) !== $v->get_stock_status( 'edit' ) ) {
					$errors[] = 'Stock settings differ for variations ' . $owner->get_id() . ' and ' . $variation_id;
				}
				$map[ $variation_id ] = $owner->get_id();
			}
			if ( count( $matched ) !== count( $owners ) ) { $errors[] = 'Incomplete size set on product ' . $parent_id; }
		}
		foreach ( $owners as $owner ) {
			$total += (float) $owner->get_stock_quantity( 'edit' );
			$groups[] = array( 'owner' => $owner->get_id(), 'parents' => $family, 'attributes' => $owner->get_attributes(), 'quantity' => $owner->get_stock_quantity( 'edit' ) );
		}
	}
	return array( 'map' => $map, 'groups' => $groups, 'physical_quantity' => $total, 'errors' => array_values( array_unique( $errors ) ) );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'aitu/v1', '/shared-stock', array(
		'permission_callback' => function () { return current_user_can( 'manage_woocommerce' ); },
		'methods' => 'GET',
		'callback' => function () {
			$plan = aitu_stock_plan();
			if ( is_wp_error( $plan ) ) { return $plan; }
			$plan['enabled'] = ! empty( get_option( 'aitu_shared_stock_map', array() ) );
			return $plan;
		},
	) );
	register_rest_route( 'aitu/v1', '/shared-stock/initialize', array(
		'permission_callback' => function () { return current_user_can( 'manage_woocommerce' ) && current_user_can( 'manage_options' ); },
		'methods' => 'POST',
		'callback' => function () {
			$plan = aitu_stock_plan();
			if ( is_wp_error( $plan ) ) { return $plan; }
			if ( ! empty( $plan['errors'] ) || empty( $plan['map'] ) ) {
				return new WP_Error( 'aitu_stock_not_matched', 'Stock needs reconciliation before initialization.', array( 'status' => 409, 'errors' => $plan['errors'] ) );
			}
			global $wpdb;
			$ids = array_map( 'absint', array_keys( $plan['map'] ) );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$active = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_reserved_stock WHERE expires > UTC_TIMESTAMP() AND stock_quantity > 0 AND product_id IN ($placeholders)", $ids ) );
			if ( $wpdb->last_error ) { return new WP_Error( 'aitu_reservation_check_failed', 'Could not verify active reservations.', array( 'status' => 500 ) ); }
			if ( $active ) { return new WP_Error( 'aitu_active_reservations', 'Active reservations must finish first.', array( 'status' => 409 ) ); }
			update_option( 'aitu_shared_stock_map', $plan['map'], false );
			$plan['enabled'] = true;
			return $plan;
		},
	) );
} );


/** Refuse sales of new translated sizes until an administrator maps their stock. */
add_filter( 'woocommerce_variation_is_purchasable', function ( $purchasable, $variation ) {
	if ( ! $purchasable || ! get_option( 'aitu_shared_stock_map' ) || ! function_exists( 'pll_get_post_translations' ) ) {
		return $purchasable;
	}
	$translations = pll_get_post_translations( $variation->get_parent_id() );
	if ( empty( $translations['sk'] ) || empty( $translations['en'] ) ) {
		return $purchasable;
	}
	$owner_id = aitu_stock_owner_id( $variation->get_id() );
	$owner = $owner_id ? wc_get_product( $owner_id ) : false;
	return $owner instanceof WC_Product_Variation && 'publish' === $owner->get_status();
}, 30, 2 );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type || ! current_user_can( 'manage_woocommerce' ) || ! get_option( 'aitu_shared_stock_map' ) ) { return; }
	echo '<div class="notice notice-info"><p>AITU: SK/EN zdieľajú sklad. Po pridaní nového prekladu alebo veľkosti je potrebné aktualizovať mapovanie skladu cez AITU Shared Stock. Nové nespárované preložené veľkosti sa dovtedy nedajú objednať.</p></div>';
} );
