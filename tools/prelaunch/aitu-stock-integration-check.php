<?php
/** Temporary administrator-only integration check; remove from mu-plugins after verification. */
defined( 'ABSPATH' ) || exit;
add_action( 'rest_api_init', function () {
 register_rest_route( 'aitu/v1', '/stock-integration-check', array(
  'methods' => 'POST',
  'permission_callback' => function () { return current_user_can( 'manage_options' ) && current_user_can( 'manage_woocommerce' ); },
  'callback' => function () {
   if ( ! class_exists( 'AITU_Shared_Stock_Variation' ) ) { return new WP_Error( 'missing_plugin', 'Shared stock class is missing.', array( 'status' => 409 ) ); }
   if ( ! add_option( 'aitu_stock_test_running', time(), '', false ) ) { return new WP_Error( 'test_running', 'A test is already in progress.', array( 'status' => 409 ) ); }
   $previous = get_option( 'aitu_shared_stock_map', array() );
   $created = $parents = $variations = $checks = array();
   $token = wp_generate_uuid4();
   $assert = function ( $condition, $label ) use ( &$checks ) {
    if ( ! $condition ) { throw new RuntimeException( $label ); }
    $checks[] = $label;
   };
   try {
    foreach ( array( 'sk', 'en' ) as $lang ) {
     $p = new WC_Product_Variable();
     $p->set_name( 'AITU temporary stock check ' . $token . ' ' . $lang );
     $p->set_status( 'draft' );
     $p->set_catalog_visibility( 'hidden' );
     $p->update_meta_data( '_aitu_stock_test_token', $token );
     $id = $p->save();
     $created[] = $id; $parents[ $lang ] = $id;
     foreach ( array( 'l' => 3, 's' => 1 ) as $size => $quantity ) {
      $v = new WC_Product_Variation();
      $v->set_parent_id( $id );
      $v->set_status( 'publish' );
      $v->set_attributes( array( 'pa_vellkost' => $size ) );
      $v->set_regular_price( '1' );
      $v->set_manage_stock( true );
      $v->set_stock_quantity( $quantity );
      $v->set_stock_status( 'instock' );
      $v->set_backorders( 'no' );
      $v->update_meta_data( '_aitu_stock_test_token', $token );
      $vid = $v->save();
      $created[] = $vid; $variations[ $lang ][ $size ] = $vid;
     }
    }
    $testmap = $previous;
    foreach ( array( 'l', 's' ) as $size ) {
     foreach ( array( 'sk', 'en' ) as $lang ) { $testmap[ $variations[ $lang ][ $size ] ] = $variations['sk'][ $size ]; }
    }
    update_option( 'aitu_shared_stock_map', $testmap, false );
    $sk = $variations['sk']['l']; $en = $variations['en']['l'];
    $assert( wc_get_product( $en ) instanceof AITU_Shared_Stock_Variation, 'Factory uses the shared variation class' );
    $assert( wc_get_product( $en )->get_stock_managed_by_id() === $sk, 'English reservations use the Slovak stock owner' );
    $assert( wc_get_product( $sk )->get_stock_managed_by_id() === $sk, 'Slovak reservations use the same owner' );
    wc_update_product_stock( $en, 1, 'decrease' );
    $assert( 2 === (int) wc_get_product( $sk )->get_stock_quantity() && 2 === (int) wc_get_product( $en )->get_stock_quantity(), 'English decrease is visible in both languages' );
    $assert( 2 === (int) wc_get_product( $en )->get_stock_quantity( 'edit' ), 'Secondary stored quantity is mirrored' );
    $assert( 1 === (int) wc_get_product( $variations['sk']['s'] )->get_stock_quantity(), 'A different size is unaffected' );
    wc_update_product_stock( $en, 1, 'increase' );
    $assert( 3 === (int) wc_get_product( $sk )->get_stock_quantity(), 'English restock restores the owner' );
    wc_update_product_stock( $sk, 2, 'decrease' );
    $assert( 1 === (int) wc_get_product( $en )->get_stock_quantity(), 'Slovak decrease is visible in English' );
    wc_update_product_stock( $en, 1, 'decrease' );
    $assert( ! wc_get_product( $sk )->is_in_stock() && ! wc_get_product( $en )->is_in_stock(), 'Both languages become out of stock at zero' );
    $assert( ! wc_get_product( $en )->has_enough_stock( 1 ), 'Overselling is rejected at zero' );
    wc_update_product_stock( $sk, 3, 'increase' );
    $assert( wc_get_product( $sk )->is_in_stock() && wc_get_product( $en )->is_in_stock(), 'Restocking restores availability in both languages' );
    $v = wc_get_product( $en ); $v->set_stock_quantity( 5 ); $v->save();
    $assert( 5 === (int) wc_get_product( $sk )->get_stock_quantity(), 'Administrative English stock edit updates the owner' );
    $v = wc_get_product( $sk ); $v->set_stock_quantity( 4 ); $v->save();
    $assert( 4 === (int) wc_get_product( $en )->get_stock_quantity( 'edit' ), 'Administrative Slovak stock edit updates the translation' );
    $v = wc_get_product( $en ); $v->set_backorders( 'notify' ); $v->save();
    $assert( 'notify' === wc_get_product( $sk )->get_backorders(), 'Backorder policy is shared' );
    $assert( 0 === (int) wc_get_held_stock_quantity( wc_get_product( $en ) ), 'Native WooCommerce reservation lookup accepts the shared owner' );
    return array( 'passed' => count( $checks ), 'checks' => $checks, 'real_products_changed' => false, 'orders_created' => 0 );
   } catch ( Throwable $e ) {
    return new WP_Error( 'aitu_stock_test_failed', $e->getMessage(), array( 'status' => 500, 'passed_checks' => $checks ) );
   } finally {
    update_option( 'aitu_shared_stock_map', $previous, false );
    foreach ( array_reverse( $created ) as $id ) {
     $p = wc_get_product( $id );
     if ( $p && $token === $p->get_meta( '_aitu_stock_test_token', true, 'edit' ) ) { $p->delete( true ); }
    }
    delete_option( 'aitu_stock_test_running' );
   }
  },
 ) );
} );
