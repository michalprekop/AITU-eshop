<?php
/** Temporary, admin-only integration tests. Intercept ALL wp_mail calls and clean only self-created notices. */
defined( 'ABSPATH' ) || exit;
add_action( 'rest_api_init', function () {
 register_rest_route( 'aitu/v1', '/customer-check', array( 'methods' => 'POST', 'permission_callback' => function () { return current_user_can( 'manage_options' ) && current_user_can( 'manage_woocommerce' ); }, 'callback' => function () {
  $checks = array(); $created = array(); $tokens = array(); $mails = array(); $fail_customer = false; $user = get_current_user_id();
  $assert = function ( $ok, $label ) use ( &$checks ) { if ( ! $ok ) { throw new RuntimeException( $label ); } $checks[] = $label; };
  $intercept = function ( $value, $args ) use ( &$mails, &$fail_customer ) {
   $mails[] = array( 'to' => $args['to'], 'subject' => $args['subject'], 'message' => $args['message'] );
   return ! ( $fail_customer && 'aitu-test@example.invalid' === $args['to'] );
  };
  add_filter( 'pre_wp_mail', $intercept, PHP_INT_MAX, 2 );
  try {
   $data = array( 'name' => "AITU O'Connor test", 'email' => 'aitu-test@example.invalid', 'reference' => 'AITU-SELFTEST-' . wp_generate_password( 12, false ), 'items' => 'Tričko L – 1 kus', 'lang' => 'sk' );
   $assert( ! is_wp_error( aitu_withdrawal_validate( $data ) ), 'Valid partial withdrawal accepted' );
   foreach ( array( 'email' => 'bad-email', 'name' => '', 'reference' => '', 'items' => array( 'bad' ), 'lang' => array() ) as $key => $value ) {
    $input = $data; $input[ $key ] = $value;
    if ( 'lang' === $key ) { $assert( 'en' === aitu_withdrawal_validate( $input )['lang'], 'Invalid language falls back safely' ); }
    else { $assert( is_wp_error( aitu_withdrawal_validate( $input ) ), 'Reject invalid ' . $key ); }
   }
   $token = wp_generate_password( 48, false, false ); $tokens[] = $token;
   set_transient( 'aitu_withdrawal_' . hash( 'sha256', $token ), $data, DAY_IN_SECONDS );
   $id = aitu_withdrawal_accept( $token );
   if ( ! is_wp_error( $id ) ) { $created[] = $id; }
   $assert( ! is_wp_error( $id ) && 'private' === get_post_status( $id ), 'Notice saved privately before email' );
   $assert( 2 === count( $mails ), 'One customer acknowledgement and one merchant notification intercepted' );
   $assert( false !== strpos( $mails[0]['message'], $data['reference'] ) && false !== strpos( $mails[0]['message'], $data['name'] ) && false !== strpos( $mails[0]['message'], 'UTC' ), 'Acknowledgement preserves declaration and timestamp' );
   $assert( $id === aitu_withdrawal_accept( $token ) && 2 === count( $mails ), 'Repeated confirmation creates no duplicate or email' );
   wp_set_current_user( 0 );
   $receipt = aitu_withdrawal_receipt( $token );
   $assert( $receipt && $id === $receipt->ID, 'Guest with secret receipt token can read their confirmation' );
   $assert( null === aitu_withdrawal_receipt( str_repeat( 'x', 48 ) ) && null === aitu_withdrawal_receipt( array() ), 'Invalid receipt tokens reveal no notice' );
   wp_set_current_user( $user );
   $assert( is_wp_error( aitu_withdrawal_accept( wp_generate_password( 48, false, false ) ) ), 'Missing review cannot be confirmed' );
   $token2 = wp_generate_password( 48, false, false ); $tokens[] = $token2;
   $data['lang'] = 'en'; $data['items'] = ''; $data['reference'] .= '-MAIL-FAIL';
   set_transient( 'aitu_withdrawal_' . hash( 'sha256', $token2 ), $data, DAY_IN_SECONDS );
   $fail_customer = true;
   $id2 = aitu_withdrawal_accept( $token2 );
   if ( ! is_wp_error( $id2 ) ) { $created[] = $id2; }
   $assert( ! is_wp_error( $id2 ) && aitu_withdrawal_receipt( $token2 ), 'Mail failure does not lose accepted notice' );
   $status = get_post_meta( $id2, '_aitu_mail_status', true );
   $assert( empty( $status['customer'] ) && ! empty( $status['merchant'] ) && wp_next_scheduled( 'aitu_withdrawal_retry_mail', array( $id2, 1 ) ), 'Only failed acknowledgement is queued for retry' );
   $count = count( $mails ); $fail_customer = false;
   aitu_withdrawal_send( $id2, 1 );
   $assert( count( $mails ) === $count + 1 && 'aitu-test@example.invalid' === end( $mails )['to'], 'Retry does not resend successful merchant notification' );
   $assert( false !== strpos( end( $mails )['message'], 'Entire order' ), 'English full-order declaration is explicit' );
   $GLOBALS['aitu_withdrawal_state'] = array( 'review' => array_merge( $data, array( 'name' => '<script>alert(1)</script>' ) ), 'token' => $token2 );
   $html = aitu_withdrawal_form();
   $assert( false === strpos( $html, '<script>' ) && false !== strpos( $html, '&lt;script&gt;' ), 'Review escapes customer input' );
   foreach ( array( 'terms', 'privacy', 'returns', 'contact', 'cookies', 'shipping', 'faq' ) as $kind ) {
    foreach ( array( 'sk', 'en' ) as $lang ) {
     $html = aitu_customer_content( $kind, $lang );
     $assert( strlen( $html ) > 300 && false === strpos( $html, '{{' ) && false === strpos( $html, 'href=""' ), 'Complete ' . $kind . ' ' . $lang . ' content and links' );
    }
   }
   $map = get_option( 'aitu_info_pages', array() );
   foreach ( array( 46 => 'sk', 8 => 'en' ) as $id => $lang ) {
    $raw = get_post_field( 'post_content', $id );
    preg_match( '/<!-- wp:woocommerce\/checkout-terms-block (.*?) -->/', $raw, $match );
    $attrs = json_decode( $match[1] ?? '{}', true );
    $assert( true === ( $attrs['checkbox'] ?? false ) && false !== strpos( $attrs['text'], get_permalink( $map['terms'][ $lang ] ) ), 'Checkout has required terms checkbox in ' . $lang );
   }
   // An in-memory order object verifies the email path without creating an order in the database.
   $order = new class extends WC_Order { public function save_meta_data() {} };
   aitu_capture_contract_copy( $order );
   $copy = $order->get_meta( '_aitu_contract_copy' );
   $assert( ! empty( $copy['html'] ) && false !== strpos( $copy['html'], aitu_customer_details()['dic'] ), 'Contract snapshot includes confirmed seller identity' );
   $order->update_meta_data( '_aitu_contract_copy', array_merge( $copy, array( 'version' => 'preserved-test-version' ) ) );
   aitu_capture_contract_copy( $order );
   $assert( 'preserved-test-version' === $order->get_meta( '_aitu_contract_copy' )['version'], 'Resend preserves the terms originally saved with the order' );
   ob_start(); do_action( 'woocommerce_email_after_order_table', $order, false, false, (object) array( 'id' => 'customer_processing_order' ) ); $html = ob_get_clean();
   $assert( false !== strpos( $html, aitu_customer_details()['dic'] ) && ( false !== strpos( $html, '/withdraw' ) || false !== strpos( $html, '/sk/odstupenie' ) ), 'Customer email embeds durable terms and withdrawal information' );
   ob_start(); do_action( 'woocommerce_email_after_order_table', $order, false, true, (object) array( 'id' => 'customer_processing_order' ) ); $plain = ob_get_clean();
   $assert( false !== strpos( $plain, aitu_customer_details()['dic'] ) && false === strpos( $plain, '<h2>' ), 'Plain-text email contains readable terms' );
   $weights = 0;
   foreach ( array( 53, 99, 61, 100, 109, 117, 101, 108, 146, 151, 168, 173, 186, 191 ) as $id ) {
    $p = wc_get_product( $id );
    if ( '0.31' !== $p->get_weight() ) { throw new RuntimeException( 'Parent weight: ' . $id ); }
    foreach ( get_posts( array( 'post_type' => 'product_variation', 'post_parent' => $id, 'post_status' => array( 'publish', 'private' ), 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'lang' => '' ) ) as $child ) { if ( '0.31' !== wc_get_product( $child )->get_weight() ) { throw new RuntimeException( 'Variant weight inheritance: ' . $child ); } $weights++; }
   }
   $assert( 60 === $weights, 'All 60 stored variations, including four inactive legacy variations, inherit 0.31 kg' );
   return array( 'passed' => count( $checks ), 'checks' => $checks, 'real_emails_sent' => 0, 'real_orders_created' => 0, 'temporary_notices' => count( $created ) );
  } catch ( Throwable $e ) { return new WP_Error( 'integration_failed', $e->getMessage(), array( 'status' => 500, 'passed' => $checks ) ); }
  finally {
   wp_set_current_user( $user ); unset( $GLOBALS['aitu_withdrawal_state'] );
   foreach ( $created as $id ) {
    for ( $attempt = 1; $attempt <= 3; $attempt++ ) { wp_clear_scheduled_hook( 'aitu_withdrawal_retry_mail', array( (int) $id, $attempt ) ); }
    $post = get_post( $id );
    if ( $post && 'aitu_withdrawal' === $post->post_type && 0 === strpos( $post->post_title, 'AITU – AITU-SELFTEST-' ) ) { wp_delete_post( $id, true ); }
   }
   foreach ( $tokens as $token ) { delete_transient( 'aitu_withdrawal_' . hash( 'sha256', $token ) ); delete_option( 'aitu_withdrawal_lock_' . hash( 'sha256', $token ) ); }
   remove_filter( 'pre_wp_mail', $intercept, PHP_INT_MAX );
  }
 } ) );
} );
