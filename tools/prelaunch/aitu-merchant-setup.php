<?php
/** Temporary admin-only migration. Deploy after the permanent customer-info files and remove after verification. */
defined( 'ABSPATH' ) || exit;
function aitu_merchant_setup_report() {
 $map = get_option( 'aitu_info_pages', array() ); $pages = array();
 foreach ( $map as $key => $langs ) {
  foreach ( $langs as $lang => $id ) { $pages[ $key ][ $lang ] = array( 'id' => $id, 'url' => get_permalink( $id ), 'status' => get_post_status( $id ), 'language' => pll_get_post_language( $id ) ); }
 }
 $retries = 0;
 foreach ( (array) _get_cron_array() as $events ) { if ( isset( $events['aitu_withdrawal_retry_mail'] ) ) { $retries += count( $events['aitu_withdrawal_retry_mail'] ); } }
 return array( 'applied' => (bool) get_option( 'aitu_merchant_setup_v1' ), 'pages' => $pages, 'weight_unit' => get_option( 'woocommerce_weight_unit' ), 'terms_id' => get_option( 'woocommerce_terms_page_id' ), 'privacy_id' => get_option( 'wp_page_for_privacy_policy' ), 'merchant_fields' => array_keys( aitu_customer_details() ), 'checkout_script_registered' => wp_script_is( 'wc-blocks-checkout', 'registered' ), 'withdrawal_records' => array_sum( (array) wp_count_posts( 'aitu_withdrawal' ) ), 'withdrawal_mail_retries' => $retries );
}
function aitu_merchant_setup_tidy() {
 if ( ! get_option( 'aitu_merchant_setup_v1' ) ) { return new WP_Error( 'not_ready', 'Apply the migration first.' ); }
 $old = get_permalink( 50 );
 foreach ( array( 50 => array( 'sk', 'ochrana-sukromia' ), 240 => array( 'en', 'cookie-policy' ) ) as $id => $spec ) {
  if ( $spec[0] !== pll_get_post_language( $id ) || 'page' !== get_post_type( $id ) ) { return new WP_Error( 'identity', 'Unexpected page identity.' ); }
  wp_update_post( array( 'ID' => $id, 'post_name' => $spec[1] ) );
 }
 $new = get_permalink( 50 );
 if ( $old !== $new ) {
  $post = get_post( 46 );
  wp_update_post( wp_slash( array( 'ID' => 46, 'post_content' => str_replace( $old, $new, $post->post_content ) ) ) );
 }
 return aitu_merchant_setup_report();
}
function aitu_merchant_setup_apply( $request ) {
 if ( get_option( 'aitu_merchant_setup_v1' ) ) { return aitu_merchant_setup_report(); }
 $merchant = json_decode( (string) $request->get_param( 'merchant_json' ), true );
 $required = array( 'seller', 'ico', 'dic', 'address', 'register', 'email', 'return_address', 'dispatch_business_days', 'invoice_provider', 'packed_weight_g', 'production', 'instagram' );
 if ( ! is_array( $merchant ) || array_diff( $required, array_keys( $merchant ) ) || ! is_email( $merchant['email'] ) || ! preg_match( '/^\d{10}$/D', $merchant['dic'] ) || ! preg_match( '/^\d{8}$/D', $merchant['ico'] ) || ! isset( $merchant['vat_registered'] ) || false !== $merchant['vat_registered'] || 310 !== (int) $merchant['packed_weight_g'] || 5 !== (int) $merchant['dispatch_business_days'] || 'SK' !== $merchant['production'] ) { return new WP_Error( 'merchant_input', 'Confirmed merchant input is incomplete or inconsistent.', array( 'status' => 400 ) ); }
 if ( 'kg' !== get_option( 'woocommerce_weight_unit' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'aitu_customer_content' ) ) { return new WP_Error( 'environment', 'Required environment differs.', array( 'status' => 409 ) ); }
 $products = array( 53, 99, 61, 100, 109, 117, 101, 108, 146, 151, 168, 173, 186, 191 );
 $baseline = array( 'products' => array(), 'pages' => array(), 'options' => array() );
 foreach ( $products as $id ) {
  $p = wc_get_product( $id ); $lang = pll_get_post_language( $id );
  $phrase = 'sk' === $lang ? 'Navrhnuté a vyrobené na Slovensku' : 'Designed and made in Slovakia';
  if ( ! $p || ! $p->is_type( 'variable' ) || 'publish' !== $p->get_status() || '' !== $p->get_weight( 'edit' ) || 1 !== substr_count( $p->get_description( 'edit' ), $phrase ) ) { return new WP_Error( 'product_baseline', 'Unexpected product baseline: ' . $id, array( 'status' => 409 ) ); }
  $baseline['products'][ $id ] = array( 'weight' => $p->get_weight( 'edit' ), 'description' => $p->get_description( 'edit' ) );
 }
 foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'suppress_filters' => true, 'lang' => '' ) ) as $p ) { $baseline['pages'][ $p->ID ] = get_object_vars( $p ); }
 foreach ( array( 'aitu_merchant_details', 'aitu_info_pages', 'aitu_social_links', 'woocommerce_terms_page_id', 'wp_page_for_privacy_policy' ) as $key ) { $baseline['options'][ $key ] = get_option( $key, null ); }
 // Record a durable rollback baseline before any mutations; never overwrite it on a retry.
 add_option( 'aitu_merchant_setup_backup_v1', $baseline, '', false );
 $saved = array();
 foreach ( $merchant as $key => $value ) { if ( is_scalar( $value ) || null === $value ) { $saved[ sanitize_key( $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value; } }
 update_option( 'aitu_merchant_details', $saved, false );
 $map = get_option( 'aitu_info_pages', array() );
 $specs = array(
  'terms' => array( 'sk' => array( 0, 'Obchodné podmienky', 'obchodne-podmienky' ), 'en' => array( 0, 'Terms and conditions', 'terms-and-conditions' ) ),
  'privacy' => array( 'sk' => array( 50, 'Ochrana súkromia', 'ochrana-sukromia' ), 'en' => array( 3, 'Privacy policy', 'privacy-policy' ) ),
  'returns' => array( 'sk' => array( 48, 'Vrátenie a reklamácie', 'vratenie-a-reklamacie' ), 'en' => array( 10, 'Returns and faulty goods', 'returns-and-refunds' ) ),
  'cookies' => array( 'sk' => array( 0, 'Cookies', 'cookies' ), 'en' => array( 0, 'Cookies', 'cookies' ) ),
  'contact' => array( 'sk' => array( 0, 'Kontakt', 'kontakt' ), 'en' => array( 0, 'Contact', 'contact' ) ),
  'withdrawal' => array( 'sk' => array( 0, 'Odstúpiť od zmluvy tu', 'odstupenie-od-zmluvy' ), 'en' => array( 0, 'Withdraw from contract', 'withdraw-from-contract' ) ),
  'shipping' => array( 'sk' => array( 218, 'Doprava a platba', 'doprava-a-platba' ), 'en' => array( 220, 'Delivery and payment', 'delivery-and-payment' ) ),
  'faq' => array( 'sk' => array( 214, 'Časté otázky', 'caste-otazky' ), 'en' => array( 216, 'Frequently asked questions', 'faq' ) ),
 );
 try {
  foreach ( $specs as $key => $langs ) {
   foreach ( $langs as $lang => $spec ) {
    $id = (int) ( $map[ $key ][ $lang ] ?? $spec[0] );
    if ( $id && ( 'page' !== get_post_type( $id ) || $lang !== pll_get_post_language( $id ) ) ) { throw new RuntimeException( 'Page identity mismatch: ' . $id ); }
    if ( ! $id ) {
     $existing = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'draft', 'publish' ), 'numberposts' => 1, 'suppress_filters' => true, 'lang' => '', 'meta_key' => '_aitu_customer_page', 'meta_value' => $key . ':' . $lang ) );
     if ( $existing ) { $id = $existing[0]->ID; }
    }
    $post = array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $spec[1], 'post_name' => $spec[2], 'post_content' => '[aitu_customer_info kind="' . $key . '"]', 'meta_input' => array( '_aitu_customer_page' => $key . ':' . $lang ) );
    if ( $id ) { $post['ID'] = $id; }
    $id = wp_insert_post( wp_slash( $post ), true );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
    pll_set_post_language( $id, $lang );
    $result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    $map[ $key ][ $lang ] = $id;
    update_option( 'aitu_info_pages', $map, false );
   }
   pll_save_post_translations( $map[ $key ] );
  }
  update_option( 'woocommerce_terms_page_id', $map['terms']['en'] );
  update_option( 'wp_page_for_privacy_policy', $map['privacy']['en'] );
  $social = get_option( 'aitu_social_links', array() ); $social['instagram'] = esc_url_raw( $merchant['instagram'], array( 'https' ) );
  update_option( 'aitu_social_links', $social, false );
  foreach ( array( 46 => 'sk', 8 => 'en' ) as $id => $lang ) {
   $post = get_post( $id ); $old = '<!-- wp:woocommerce/checkout-terms-block -->';
   if ( 1 !== substr_count( $post->post_content, $old ) ) { throw new RuntimeException( 'Checkout terms baseline differs: ' . $id ); }
   $label = 'sk' === $lang ? 'Súhlasím s <a href="%s" target="_blank" rel="noopener">obchodnými podmienkami</a>. Informácie o <a href="%s" target="_blank" rel="noopener">ochrane osobných údajov</a>.' : 'I agree to the <a href="%s" target="_blank" rel="noopener">terms and conditions</a>. Read the <a href="%s" target="_blank" rel="noopener">privacy policy</a>.';
   $attrs = array( 'checkbox' => true, 'text' => sprintf( $label, get_permalink( $map['terms'][ $lang ] ), get_permalink( $map['privacy'][ $lang ] ) ) );
   // Preserve all shipping and payment blocks byte-for-byte.
   $replacement = '<!-- wp:woocommerce/checkout-terms-block ' . wp_json_encode( $attrs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . ' -->';
   $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => str_replace( $old, $replacement, $post->post_content ) ) ), true );
   if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
  }
  foreach ( $products as $id ) {
   $p = wc_get_product( $id ); $sk = 'sk' === pll_get_post_language( $id );
   $p->set_weight( '0.31' );
   $p->set_description( str_replace( $sk ? 'Navrhnuté a vyrobené na Slovensku' : 'Designed and made in Slovakia', $sk ? 'Navrhnuté, potlačené a vyšívané na Slovensku' : 'Designed, printed and embroidered in Slovakia', $p->get_description( 'edit' ) ) );
   $p->save();
  }
  foreach ( array( 210 => '<p>AITU dizajn, potlač a výšivka vznikajú na Slovensku. Základom je Build Your Brand Heavy Oversize Tee BY102.</p>', 212 => '<p>AITU design, printing and embroidery take place in Slovakia. The base garment is the Build Your Brand Heavy Oversize Tee BY102.</p>' ) as $id => $copy ) {
   $post = get_post( $id );
   if ( false === strpos( $post->post_content, 'Build Your Brand Heavy Oversize Tee BY102' ) ) { wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $post->post_content . $copy ) ) ); }
  }
  update_option( 'aitu_merchant_setup_v1', time(), false );
  return aitu_merchant_setup_report();
 } catch ( Throwable $e ) { return new WP_Error( 'setup_failed', $e->getMessage(), array( 'status' => 500 ) ); }
}
add_action( 'rest_api_init', function () {
 $permission = function () { return current_user_can( 'manage_options' ) && current_user_can( 'manage_woocommerce' ) && current_user_can( 'edit_pages' ); };
 register_rest_route( 'aitu/v1', '/merchant-setup', array( 'methods' => 'GET', 'permission_callback' => $permission, 'callback' => 'aitu_merchant_setup_report' ) );
 register_rest_route( 'aitu/v1', '/merchant-setup/apply', array( 'methods' => 'POST', 'permission_callback' => $permission, 'callback' => 'aitu_merchant_setup_apply' ) );
 register_rest_route( 'aitu/v1', '/merchant-setup/tidy', array( 'methods' => 'POST', 'permission_callback' => $permission, 'callback' => 'aitu_merchant_setup_tidy' ) );
 register_rest_route( 'aitu/v1', '/merchant-setup/backup', array( 'methods' => 'GET', 'permission_callback' => $permission, 'callback' => function () { return get_option( 'aitu_merchant_setup_backup_v1' ); } ) );
} );
