<?php
/**
 * Plugin Name: AITU Customer Information
 * Description: Localised customer information, checkout terms and online withdrawal.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;

function aitu_customer_lang() {
 return function_exists( 'aitu_current_lang_slug' ) && 'sk' === aitu_current_lang_slug() ? 'sk' : 'en';
}
function aitu_customer_t( $sk, $en, $lang = null ) { return 'sk' === ( $lang ?: aitu_customer_lang() ) ? $sk : $en; }
function aitu_customer_details() { return (array) get_option( 'aitu_merchant_details', array() ); }
function aitu_customer_url( $key, $lang = null ) {
 $map = (array) get_option( 'aitu_info_pages', array() );
 $id = (int) ( $map[ $key ][ $lang ?: aitu_customer_lang() ] ?? 0 );
 return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : '';
}
function aitu_customer_content( $kind, $lang = null ) {
 $lang = $lang ?: aitu_customer_lang();
 $all = json_decode( file_get_contents( __DIR__ . '/aitu-customer-info/content.json' ), true );
 if ( ! isset( $all[ $kind ][ $lang ] ) ) { return ''; }
 $merchant = aitu_customer_details();
 $values = array();
 foreach ( $merchant as $key => $value ) { if ( is_scalar( $value ) ) { $values[ '{{' . $key . '}}' ] = esc_html( (string) $value ); } }
 $email = $merchant['email'] ?? '';
 $values['{{email_link}}'] = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
 $phone = $merchant['phone'] ?? '';
 $values['{{phone_line}}'] = $phone ? '<p>' . esc_html( aitu_customer_t( 'Telefón: ', 'Telephone: ', $lang ) . $phone ) . '</p>' : '';
 foreach ( array( 'terms', 'privacy', 'cookies', 'returns', 'withdrawal', 'contact', 'shipping' ) as $key ) { $values[ '{{' . $key . '_url}}' ] = esc_url( aitu_customer_url( $key, $lang ) ); }
 return strtr( $all[ $kind ][ $lang ], $values );
}
add_shortcode( 'aitu_customer_info', function ( $atts ) {
 $atts = shortcode_atts( array( 'kind' => 'contact' ), $atts );
 $kind = sanitize_key( $atts['kind'] );
 $html = 'withdrawal' === $kind ? aitu_withdrawal_form() : aitu_customer_content( $kind );
 return '<div class="aitu-customer-copy">' . $html . '</div>';
} );

foreach ( array( 'woocommerce_terms_page_id' => 'terms', 'wp_page_for_privacy_policy' => 'privacy' ) as $option => $key ) {
 add_filter( 'option_' . $option, function ( $id ) use ( $key ) {
  if ( is_admin() ) { return $id; }
  $map = (array) get_option( 'aitu_info_pages', array() );
  return $map[ $key ][ aitu_customer_lang() ] ?? $id;
 }, 20 );
}
add_action( 'wp_enqueue_scripts', function () {
 $base = plugin_dir_url( __FILE__ ) . 'aitu-customer-info/';
 if ( is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'aitu_customer_info' ) ) {
  wp_enqueue_style( 'aitu-customer-info', $base . 'customer.css', array(), '1.0.0' );
 }
 if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) && ! is_order_received_page() ) {
  wp_enqueue_script( 'aitu-checkout-terms', $base . 'checkout.js', array( 'wc-blocks-checkout' ), '1.0.2', true );
  wp_add_inline_script( 'aitu-checkout-terms', 'window.aituCheckoutTerms=' . wp_json_encode( array( 'label' => aitu_customer_t( 'Objednať s povinnosťou platby', 'Place order and pay' ) ) ) . ';', 'before' );
 }
}, 40 );

/** Preserve the exact terms accepted with the order, also for future email resends. */
function aitu_capture_contract_copy( $order ) {
 if ( ! $order instanceof WC_Order || $order->get_meta( '_aitu_contract_copy' ) ) { return; }
 $lang = aitu_customer_lang();
 $html = aitu_customer_content( 'terms', $lang ) . aitu_customer_content( 'returns', $lang );
 if ( ! $html || ! aitu_customer_details() ) { return; }
 $order->update_meta_data( '_aitu_contract_copy', array( 'lang' => $lang, 'version' => '2026-09-05', 'html' => $html ) );
 $order->save_meta_data();
}
add_action( 'woocommerce_checkout_order_created', 'aitu_capture_contract_copy' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'aitu_capture_contract_copy' );
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin, $plain, $email ) {
 if ( $sent_to_admin || ! $order instanceof WC_Order || ! in_array( $email->id ?? '', array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ), true ) ) { return; }
 $copy = $order->get_meta( '_aitu_contract_copy' );
 if ( empty( $copy['html'] ) ) { return; }
 $heading = aitu_customer_t( 'Obchodné podmienky a vrátenie tovaru', 'Terms and returns information', $copy['lang'] );
 if ( $plain ) { echo "\n" . esc_html( $heading ) . "\n" . aitu_customer_plain_text( $copy['html'] ) . "\n"; }
 else { echo '<h2>' . esc_html( $heading ) . '</h2><div style="font-size:12px;line-height:1.6">' . wp_kses_post( $copy['html'] ) . '</div>'; }
}, 25, 4 );
function aitu_customer_plain_text( $html ) {
 $html = preg_replace( '~<a[^>]+href="([^"]+)"[^>]*>(.*?)</a>~s', '$2 ($1)', $html );
 $html = preg_replace( '~</(?:p|h[1-6]|li|pre)>|<br\s*/?>~i', "\n\n", $html );
 return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ) );
}
require_once __DIR__ . '/aitu-customer-info/withdrawal.php';
