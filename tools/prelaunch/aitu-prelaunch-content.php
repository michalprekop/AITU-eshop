<?php
/** Temporary authenticated setup for AITU content. Remove from mu-plugins after use. */
defined( 'ABSPATH' ) || exit;
function aitu_prelaunch_content_context() {
 $pages = array();
 foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'suppress_filters' => true, 'lang' => '' ) ) as $page ) {
  $pages[] = array( 'id' => $page->ID, 'slug' => $page->post_name, 'status' => $page->post_status, 'language' => pll_get_post_language( $page->ID ), 'translations' => pll_get_post_translations( $page->ID ) );
 }
 return array( 'front_page' => (int) get_option( 'page_on_front' ), 'home_sk' => pll_home_url( 'sk' ), 'home_en' => pll_home_url( 'en' ), 'pages' => $pages, 'info_pages' => get_option( 'aitu_info_pages', array() ) );
}
function aitu_prelaunch_page( $key, $lang, $title, $slug, $content ) {
 $existing = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => 1, 'suppress_filters' => true, 'lang' => '', 'meta_key' => '_aitu_prelaunch_page', 'meta_value' => $key . ':' . $lang ) );
 if ( $existing ) { return $existing[0]->ID; }
 $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'meta_input' => array( '_aitu_prelaunch_page' => $key . ':' . $lang ) ), true );
 if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
 pll_set_post_language( $id, $lang );
 $result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
 if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
 return $id;
}
function aitu_prelaunch_repair_content() {
 if ( get_option( 'aitu_prelaunch_content_v1' ) ) { return aitu_prelaunch_content_context(); }
 $home = get_post( 68 );
 if ( ! $home || 'page' !== $home->post_type || 'publish' !== $home->post_status || 'sk' !== pll_get_post_language( 68 ) ) { return new WP_Error( 'unexpected_home', 'Homepage baseline differs.', array( 'status' => 409 ) ); }
 $sk_products = array( 53, 61, 109, 101, 146, 168, 186 );
 $en_products = array();
 foreach ( $sk_products as $id ) {
  $p = wc_get_product( $id ); $en = (int) pll_get_post( $id, 'en' );
  if ( ! $p || 'publish' !== $p->get_status() || 'sk' !== pll_get_post_language( $id ) || ! $en || 'publish' !== get_post_status( $en ) ) { return new WP_Error( 'unexpected_product', 'A homepage product or translation is missing.', array( 'status' => 409 ) ); }
  $en_products[] = $en;
 }
 add_option( 'aitu_prelaunch_home_backup', array( 'id' => 68, 'content' => $home->post_content, 'translations' => pll_get_post_translations( 68 ) ), '', false );
 $home_content = function ( $ids ) {
  $row = function ( $products ) { return '<!-- wp:aitu/product-row ' . wp_json_encode( array( 'productIds' => $products ) ) . ' /-->'; };
  $banner = '<!-- wp:aitu/banner ' . wp_json_encode( array( 'imageId' => 75, 'imageUrl' => wp_get_attachment_url( 75 ), 'linkUrl' => get_permalink( 6 ) ) ) . ' /-->';
  return $row( array_slice( $ids, 0, 3 ) ) . "\n\n" . $banner . "\n\n" . $row( array_slice( $ids, 3, 3 ) ) . "\n\n" . $row( array_slice( $ids, 6, 3 ) );
 };
 try {
  $english_home = (int) pll_get_post( 68, 'en' );
  if ( ! $english_home || $english_home === 68 ) { $english_home = aitu_prelaunch_page( 'home', 'en', 'AITU', 'home', $home_content( $en_products ) ); }
  $saved = wp_update_post( array( 'ID' => 68, 'post_content' => $home_content( $sk_products ) ), true );
  if ( is_wp_error( $saved ) ) { throw new RuntimeException( $saved->get_error_message() ); }
  pll_save_post_translations( array( 'sk' => 68, 'en' => $english_home ) );
  $content = array(
   'about' => array(
    'sk' => array( 'O AITU', 'o-aitu', '<p>AITU spája streetwear a originálny art. Prvá kolekcia stojí na výrazných grafikách, čistých základoch a oranžovom podpise AITU.</p><p>Tričká majú voľný oversize strih a gramáž 240 g/m². Každý dizajn si môžeš nosiť po svojom.</p>' ),
    'en' => array( 'About AITU', 'about-aitu', '<p>AITU brings together streetwear and original artwork. The first collection combines distinctive graphics, clean foundations and the orange AITU signature.</p><p>The T-shirts have a relaxed oversize fit and 240 g/m² fabric. Wear each design your way.</p>' ),
   ),
   'faq' => array(
    'sk' => array( 'Časté otázky', 'caste-otazky', '<h2>Ako si vybrať veľkosť?</h2><p>Pri každom tričku otvor sekciu Info o veľkosti. Nájdeš v nej rozmery S–XL v centimetroch aj nákres merania. Porovnaj ich s tričkom, ktoré už nosíš; ide o rozmery odevu naplocho.</p><h2>Je strih oversize?</h2><p>Áno. Tričká sú zámerne širšie a dlhšie, so spustenými ramenami.</p><h2>Ako zistím cenu dopravy?</h2><p>Po zadaní doručovacej adresy sa v košíku a pokladni zobrazia dostupné možnosti a presná cena.</p><h2>Ako môžem zaplatiť?</h2><p>Platbu kartou spracúva WooPayments. Dostupné platobné možnosti uvidíš v pokladni pred objednaním.</p>' ),
    'en' => array( 'Frequently asked questions', 'faq', '<h2>How do I choose a size?</h2><p>Open Size info on any T-shirt page. You will find S–XL measurements in centimetres and a measurement sketch. Compare them with a T-shirt you already wear; the measurements describe the garment laid flat.</p><h2>Is the fit oversized?</h2><p>Yes. The T-shirts are intentionally wider and longer, with dropped shoulders.</p><h2>How much does delivery cost?</h2><p>Enter your delivery address in the cart or checkout to see the available methods and their exact prices.</p><h2>How can I pay?</h2><p>Card payments are processed by WooPayments. The available payment options appear at checkout before you place an order.</p>' ),
   ),
   'shipping' => array(
    'sk' => array( 'Doprava a platba', 'doprava-a-platba', '<h2>Možnosti doručenia</h2><p>V košíku zadaj krajinu a adresu doručenia. Zobrazia sa ti dostupní dopravcovia, výdajné miesta a aktuálne ceny pre danú adresu. Pri doručení na výdajné miesto vyber konkrétne miesto pred dokončením objednávky.</p><h2>Platba</h2><p>Online platbu kartou zabezpečuje WooPayments. Celkovú cenu vrátane dopravy uvidíš v pokladni pred potvrdením objednávky.</p>' ),
    'en' => array( 'Delivery and payment', 'delivery-and-payment', '<h2>Delivery options</h2><p>Enter your delivery country and address in the cart to see the available carriers, pickup options and current prices. For pickup delivery, select a specific pickup location before completing your order.</p><h2>Payment</h2><p>Online card payments are processed by WooPayments. The total including delivery appears at checkout before you confirm your order.</p>' ),
   ),
   'tracking' => array(
    'sk' => array( 'Stav objednávky', 'stav-objednavky', '[woocommerce_order_tracking]' ),
    'en' => array( 'Order status', 'order-status', '[woocommerce_order_tracking]' ),
   ),
  );
  $map = get_option( 'aitu_info_pages', array() );
  foreach ( $content as $key => $languages ) {
   foreach ( $languages as $lang => $data ) { $map[ $key ][ $lang ] = aitu_prelaunch_page( $key, $lang, $data[0], $data[1], $data[2] ); }
   pll_save_post_translations( $map[ $key ] );
  }
  update_option( 'aitu_info_pages', $map, false );
  foreach ( array( 2, 49 ) as $id ) {
   $p = get_post( $id );
   if ( $p && 0 === strpos( $p->post_name, 'ukazkova-stranka' ) && 'publish' === $p->post_status ) { wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) ); }
  }
  $attribute = wc_get_attribute( 1 );
  if ( $attribute && 'pa_vellkost' === $attribute->slug ) {
   $result = wc_update_attribute( 1, array( 'name' => 'Veľkosť' ) );
   if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
   foreach ( array( 's', 'm', 'l', 'xl' ) as $position => $slug ) {
    $term = get_term_by( 'slug', $slug, 'pa_vellkost' );
    if ( $term ) { update_term_meta( $term->term_id, 'order', $position ); }
   }
  }
  update_option( 'aitu_prelaunch_content_v1', time(), false );
  return aitu_prelaunch_content_context();
 } catch ( Throwable $e ) { return new WP_Error( 'aitu_content_setup_failed', $e->getMessage(), array( 'status' => 500 ) ); }
}
add_action( 'rest_api_init', function () {
 $permission = function () { return current_user_can( 'manage_options' ) && current_user_can( 'edit_pages' ); };
 register_rest_route( 'aitu/v1', '/prelaunch-content', array( 'methods' => 'GET', 'permission_callback' => $permission, 'callback' => 'aitu_prelaunch_content_context' ) );
 register_rest_route( 'aitu/v1', '/prelaunch-content/repair', array( 'methods' => 'POST', 'permission_callback' => $permission, 'callback' => 'aitu_prelaunch_repair_content' ) );
} );
