<?php
/** Two-step withdrawal notice. It records a declaration; it never changes an order or issues a refund. */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
 register_post_type( 'aitu_withdrawal', array(
  'labels' => array( 'name' => 'AITU – Odstúpenia', 'singular_name' => 'Odstúpenie', 'edit_item' => 'Detail odstúpenia' ),
  'public' => false, 'publicly_queryable' => false, 'show_in_rest' => false,
  'show_ui' => true, 'show_in_menu' => 'woocommerce', 'supports' => false,
  'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ), 'manage_woocommerce' ) + array( 'create_posts' => 'do_not_allow' ),
 ) );
} );
add_action( 'add_meta_boxes_aitu_withdrawal', function () {
 add_meta_box( 'aitu-withdrawal-detail', 'Prijaté oznámenie', function ( $post ) {
  echo '<pre style="white-space:pre-wrap">' . esc_html( $post->post_content ) . '</pre>';
  $mail = (array) get_post_meta( $post->ID, '_aitu_mail_status', true );
  echo '<p>Zákazník – odovzdané e-mailovému systému: ' . ( ! empty( $mail['customer'] ) ? 'áno' : 'nie' ) . '</p>';
  echo '<p>Predajca – odovzdané e-mailovému systému: ' . ( ! empty( $mail['merchant'] ) ? 'áno' : 'nie' ) . '</p>';
  echo '<p>Potvrdenie odoslania nie je potvrdením doručenia. Odstúpenie nemení stav objednávky ani nevracia peniaze automaticky.</p>';
 }, 'aitu_withdrawal', 'normal', 'high' );
} );

function aitu_withdrawal_validate( $input ) {
 $data = array();
 foreach ( array( 'name' => 160, 'email' => 254, 'reference' => 240, 'items' => 1600 ) as $key => $limit ) {
  if ( isset( $input[ $key ] ) && ! is_scalar( $input[ $key ] ) ) { return new WP_Error( 'invalid_input', 'Invalid field.' ); }
  $raw = trim( (string) ( $input[ $key ] ?? '' ) );
  if ( strlen( $raw ) > $limit * 4 ) { return new WP_Error( 'too_long', 'Field too long.' ); }
  $data[ $key ] = 'items' === $key ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
  if ( function_exists( 'mb_strlen' ) && mb_strlen( $data[ $key ] ) > $limit ) { return new WP_Error( 'too_long', 'Field too long.' ); }
 }
 if ( '' === $data['name'] || '' === $data['reference'] || ! is_email( $data['email'] ) ) { return new WP_Error( 'missing_fields', 'Name, email and order identification are required.' ); }
 $data['lang'] = isset( $input['lang'] ) && 'sk' === $input['lang'] ? 'sk' : 'en';
 return $data;
}
function aitu_withdrawal_text( $data, $time = null ) {
 $lang = $data['lang']; $m = aitu_customer_details();
 $t = function ( $sk, $en ) use ( $lang ) { return aitu_customer_t( $sk, $en, $lang ); };
 $text = $t( 'Oznámenie o odstúpení od zmluvy', 'Notice of withdrawal from contract' ) . "\n\n";
 $text .= $t( 'Adresát: ', 'To: ' ) . ( $m['seller'] ?? '' ) . ' (AITU), ' . ( $m['return_address'] ?? '' ) . ', ' . ( $m['email'] ?? '' ) . "\n\n";
 $text .= $t( 'Týmto odstupujem od kúpnej zmluvy označenej nižšie.', 'I hereby withdraw from the contract of sale identified below.' ) . "\n";
 foreach ( array( 'name' => array( 'Meno', 'Name' ), 'email' => array( 'E-mail pre potvrdenie', 'Email for acknowledgement' ), 'reference' => array( 'Objednávka / identifikácia zmluvy', 'Order / contract identification' ), 'items' => array( 'Rozsah odstúpenia', 'Scope of withdrawal' ) ) as $key => $labels ) {
  $value = $data[ $key ] ?: $t( 'Celá objednávka', 'Entire order' );
  $text .= $t( $labels[0], $labels[1] ) . ': ' . $value . "\n";
 }
 if ( null !== $time ) { $text .= "\n" . $t( 'Dátum a čas podania: ', 'Submission date and time: ' ) . wp_date( 'Y-m-d H:i:s T', $time, new DateTimeZone( 'Europe/Bratislava' ) ) . ' / ' . gmdate( 'Y-m-d H:i:s', $time ) . " UTC\n"; }
 return $text;
}
function aitu_withdrawal_receipt( $token ) {
 if ( ! is_string( $token ) || ! preg_match( '/^[a-zA-Z0-9]{48}$/D', $token ) ) { return null; }
 $posts = get_posts( array( 'post_type' => 'aitu_withdrawal', 'post_status' => 'private', 'numberposts' => 1, 'suppress_filters' => true, 'lang' => '', 'meta_key' => '_aitu_receipt_hash', 'meta_value' => hash( 'sha256', $token ) ) );
 return $posts ? $posts[0] : null;
}
function aitu_withdrawal_send( $id, $attempt = 0 ) {
 $post = get_post( $id );
 if ( ! $post || 'aitu_withdrawal' !== $post->post_type || 'private' !== $post->post_status ) { return; }
 $data = get_post_meta( $id, '_aitu_declaration', true );
 if ( ! is_array( $data ) ) { return; }
 $m = aitu_customer_details(); $lang = $data['lang'];
 $status = (array) get_post_meta( $id, '_aitu_mail_status', true );
 $body = $post->post_content . "\n" . aitu_customer_t( 'Tvoje oznámenie sme prijali. Tovar odošli do 14 dní od odstúpenia na adresu: ', 'We have received your notice. Send the goods back within 14 days of withdrawal to: ', $lang ) . ( $m['return_address'] ?? '' ) . "\n";
 $body .= aitu_customer_t( 'Toto je potvrdenie prijatia oznámenia. Refundáciu vybavíme podľa zákonných pravidiel.', 'This acknowledges receipt of your notice. We will handle reimbursement under the applicable statutory rules.', $lang ) . "\n" . aitu_customer_url( 'returns', $lang ) . "\n";
 $headers = array( 'Content-Type: text/plain; charset=UTF-8', 'From: AITU <' . sanitize_email( $m['email'] ?? '' ) . '>' );
 foreach ( array( 'customer' => $data['email'], 'merchant' => $m['email'] ?? '' ) as $kind => $address ) {
  if ( ! empty( $status[ $kind ] ) ) { continue; }
  $subject = 'customer' === $kind ? aitu_customer_t( 'AITU – potvrdenie odstúpenia', 'AITU – withdrawal acknowledgement', $lang ) : 'AITU – nové odstúpenie #' . $id;
  $status[ $kind ] = is_email( $address ) && wp_mail( $address, $subject, $body, $headers );
  update_post_meta( $id, '_aitu_mail_status', $status );
 }
 if ( ( empty( $status['customer'] ) || empty( $status['merchant'] ) ) && $attempt < 3 ) {
  $args = array( (int) $id, $attempt + 1 );
  if ( ! wp_next_scheduled( 'aitu_withdrawal_retry_mail', $args ) ) { wp_schedule_single_event( time() + 900, 'aitu_withdrawal_retry_mail', $args ); }
 }
}
add_action( 'aitu_withdrawal_retry_mail', 'aitu_withdrawal_send', 10, 2 );

function aitu_withdrawal_accept( $token ) {
 if ( ! is_string( $token ) || ! preg_match( '/^[a-zA-Z0-9]{48}$/D', $token ) ) { return new WP_Error( 'invalid_token', 'Invalid confirmation.' ); }
 $existing = aitu_withdrawal_receipt( $token );
 if ( $existing ) { return $existing->ID; }
 $hash = hash( 'sha256', $token );
 $data = get_transient( 'aitu_withdrawal_' . $hash );
 if ( ! is_array( $data ) ) { return new WP_Error( 'expired', 'Please review the form again.' ); }
 $checked = aitu_withdrawal_validate( $data );
 if ( is_wp_error( $checked ) ) { return $checked; }
 $lock = 'aitu_withdrawal_lock_' . $hash;
 $old = (int) get_option( $lock );
 if ( $old && $old < time() - 120 ) { delete_option( $lock ); }
 if ( ! add_option( $lock, time(), '', false ) ) { return new WP_Error( 'busy', 'The notice is being recorded. Please retry shortly.' ); }
 try {
  $existing = aitu_withdrawal_receipt( $token );
  if ( $existing ) { return $existing->ID; }
  $timestamp = time();
  $id = wp_insert_post( wp_slash( array( 'post_type' => 'aitu_withdrawal', 'post_status' => 'private', 'post_title' => 'AITU – ' . $data['reference'], 'post_content' => aitu_withdrawal_text( $data, $timestamp ), 'meta_input' => array( '_aitu_receipt_hash' => $hash, '_aitu_declaration' => $data, '_aitu_submitted_at' => $timestamp, '_aitu_mail_status' => array() ) ) ), true );
  if ( is_wp_error( $id ) ) { return $id; }
  delete_transient( 'aitu_withdrawal_' . $hash );
  // Saving the notice precedes email delivery, so a mail failure cannot lose it.
  aitu_withdrawal_send( $id );
  return $id;
 } finally { delete_option( $lock ); }
}

add_action( 'template_redirect', function () {
 if ( ! is_page() || ! has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'aitu_customer_info' ) ) { return; }
 $map = get_option( 'aitu_info_pages', array() );
 if ( ! in_array( get_queried_object_id(), array_map( 'intval', array_values( $map['withdrawal'] ?? array() ) ), true ) ) { return; }
 if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
 nocache_headers();
 header( 'X-Robots-Tag: noindex, nofollow' ); header( 'Referrer-Policy: no-referrer' );
 $token = isset( $_GET['aitu_receipt'] ) && is_string( $_GET['aitu_receipt'] ) ? wp_unslash( $_GET['aitu_receipt'] ) : '';
 if ( $token ) {
  $receipt = aitu_withdrawal_receipt( $token );
  if ( ! $receipt || (int) get_post_meta( $receipt->ID, '_aitu_submitted_at', true ) < time() - 90 * DAY_IN_SECONDS ) {
   $GLOBALS['aitu_withdrawal_state'] = array( 'error' => aitu_customer_t( 'Odkaz na potvrdenie nie je platný alebo už vypršal. Kópiu si vyžiadaj e-mailom.', 'This acknowledgement link is invalid or expired. Request a copy by email.' ) ); return;
  }
  if ( isset( $_GET['aitu_download'] ) ) {
   header( 'Content-Type: text/plain; charset=UTF-8' ); header( 'Content-Disposition: attachment; filename="AITU-withdrawal-' . $receipt->ID . '.txt"' );
   echo $receipt->post_content; exit;
  }
  $GLOBALS['aitu_withdrawal_state'] = array( 'receipt' => $receipt, 'token' => $token ); return;
 }
 if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
 $nonce = isset( $_POST['aitu_nonce'] ) && is_string( $_POST['aitu_nonce'] ) ? wp_unslash( $_POST['aitu_nonce'] ) : '';
 if ( ! wp_verify_nonce( $nonce, 'aitu_withdrawal' ) ) {
  $GLOBALS['aitu_withdrawal_state'] = array( 'error' => aitu_customer_t( 'Formulár vypršal. Obnov stránku a skús znova.', 'The form expired. Refresh the page and try again.' ) ); return;
 }
 if ( isset( $_POST['aitu_confirm'] ) ) {
  $token = isset( $_POST['aitu_stage'] ) && is_string( $_POST['aitu_stage'] ) ? wp_unslash( $_POST['aitu_stage'] ) : '';
  $result = aitu_withdrawal_accept( $token );
  if ( ! is_wp_error( $result ) ) {
   wp_safe_redirect( add_query_arg( 'aitu_receipt', $token, get_permalink() ), 303 ); exit;
  }
  $GLOBALS['aitu_withdrawal_state'] = array( 'error' => aitu_customer_t( 'Oznámenie sa nepodarilo potvrdiť. Skús obnoviť stránku alebo nám napíš e-mail.', 'The notice could not be confirmed. Refresh the page or contact us by email.' ) ); return;
 }
 $input = isset( $_POST['aitu_notice'] ) && is_array( $_POST['aitu_notice'] ) ? wp_unslash( $_POST['aitu_notice'] ) : array();
 $input['lang'] = aitu_customer_lang();
 $data = aitu_withdrawal_validate( $input );
 if ( is_wp_error( $data ) ) {
  $GLOBALS['aitu_withdrawal_state'] = array( 'error' => aitu_customer_t( 'Vyplň meno, platný e-mail a identifikáciu objednávky. Skontroluj dĺžku údajov.', 'Enter your name, a valid email and order identification. Check field lengths.' ), 'input' => $input ); return;
 }
 // Short-lived hash limits automated email abuse without storing the visitor's IP.
 $rate_key = 'aitu_w_rate_' . hash_hmac( 'sha256', (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ), wp_salt( 'nonce' ) );
 $rate = (int) get_transient( $rate_key );
 if ( $rate >= 20 ) {
  $GLOBALS['aitu_withdrawal_state'] = array( 'error' => aitu_customer_t( 'Bolo odoslaných príliš veľa formulárov. Skús neskôr alebo nám pošli oznámenie e-mailom.', 'Too many forms were submitted. Try later or send your notice by email.' ), 'input' => $data ); return;
 }
 set_transient( $rate_key, $rate + 1, HOUR_IN_SECONDS );
 $token = wp_generate_password( 48, false, false );
 set_transient( 'aitu_withdrawal_' . hash( 'sha256', $token ), $data, DAY_IN_SECONDS );
 $GLOBALS['aitu_withdrawal_state'] = array( 'review' => $data, 'token' => $token );
}, 15 );

function aitu_withdrawal_form() {
 $s = $GLOBALS['aitu_withdrawal_state'] ?? array(); $m = aitu_customer_details();
 $t = function ( $sk, $en ) { return aitu_customer_t( $sk, $en ); };
 ob_start();
 if ( isset( $s['error'] ) ) { echo '<p class="aitu-message aitu-error" role="alert">' . esc_html( $s['error'] ) . '</p>'; }
 if ( isset( $s['receipt'] ) ) {
  $mail = (array) get_post_meta( $s['receipt']->ID, '_aitu_mail_status', true );
  echo '<h2>' . esc_html( $t( 'Odstúpenie sme prijali', 'Your withdrawal notice was received' ) ) . '</h2><p>';
  echo esc_html( ! empty( $mail['customer'] ) ? $t( 'Potvrdenie sme odovzdali na odoslanie e-mailom. Môžeš si ho stiahnuť aj tu.', 'The acknowledgement has been submitted for email delivery. You can also download it here.' ) : $t( 'E-mail sa zatiaľ nepodarilo odoslať. Oznámenie je uložené; stiahni si potvrdenie nižšie.', 'The email could not yet be sent. Your notice is saved; download the acknowledgement below.' ) );
  echo '</p><pre>' . esc_html( $s['receipt']->post_content ) . '</pre>';
  echo '<a class="aitu-action" href="' . esc_url( add_query_arg( array( 'aitu_receipt' => $s['token'], 'aitu_download' => '1' ), get_permalink() ) ) . '">' . esc_html( $t( 'Stiahnuť potvrdenie', 'Download acknowledgement' ) ) . '</a>';
  echo '<p>' . esc_html( $t( 'Tovar pošli do 14 dní od odstúpenia na adresu: ', 'Return the goods within 14 days of withdrawal to: ' ) . ( $m['return_address'] ?? '' ) ) . '</p>';
 } elseif ( isset( $s['review'] ) ) {
  echo '<h2>' . esc_html( $t( 'Skontroluj oznámenie', 'Review your declaration' ) ) . '</h2><p>' . esc_html( $t( 'Odstúpenie odošleš až tlačidlom nižšie.', 'Your withdrawal will be submitted only when you confirm below.' ) ) . '</p>';
  echo '<pre>' . esc_html( aitu_withdrawal_text( $s['review'] ) ) . '</pre><form method="post" action="' . esc_url( get_permalink() ) . '">';
  wp_nonce_field( 'aitu_withdrawal', 'aitu_nonce', false );
  echo '<input type="hidden" name="aitu_stage" value="' . esc_attr( $s['token'] ) . '"><button type="submit" name="aitu_confirm" value="1">' . esc_html( $t( 'Potvrdiť odstúpenie od zmluvy', 'Confirm withdrawal from contract' ) ) . '</button></form>';
  echo '<p><a href="' . esc_url( get_permalink() ) . '">' . esc_html( $t( 'Vyplniť údaje znova', 'Enter details again' ) ) . '</a></p>';
 } else {
  echo '<p>' . esc_html( $t( 'Na odstúpenie nepotrebuješ účet ani dôvod. Vyplň údaje, skontroluj oznámenie a potvrď ho v ďalšom kroku. Potvrdenie s dátumom a časom dostaneš e-mailom aj na stiahnutie.', 'You do not need an account or a reason to withdraw. Enter your details, review the declaration and confirm it in the next step. An acknowledgement with the submission date and time is provided by email and as a download.' ) ) . '</p>';
  echo '<form method="post" action="' . esc_url( get_permalink() ) . '">';
  wp_nonce_field( 'aitu_withdrawal', 'aitu_nonce', false );
  foreach ( array( 'name' => array( 'Meno a priezvisko', 'Full name', 160, 'name' ), 'email' => array( 'E-mail pre potvrdenie', 'Email for acknowledgement', 254, 'email' ), 'reference' => array( 'Číslo objednávky alebo iné označenie zmluvy', 'Order number or other contract identification', 240, 'off' ), 'items' => array( 'Časť objednávky, ktorú vraciaš (nepovinné)', 'Items you are returning (optional)', 1600, 'off' ) ) as $key => $field ) {
   $value = isset( $s['input'][ $key ] ) && is_scalar( $s['input'][ $key ] ) ? (string) $s['input'][ $key ] : '';
   echo '<label for="aitu-' . $key . '">' . esc_html( $t( $field[0], $field[1] ) ) . '</label>';
   if ( 'items' === $key ) { echo '<textarea id="aitu-items" name="aitu_notice[items]" maxlength="1600">' . esc_textarea( $value ) . '</textarea><small>' . esc_html( $t( 'Prázdne pole znamená odstúpenie od celej objednávky.', 'Leave blank to withdraw from the entire order.' ) ) . '</small>'; }
   else { echo '<input id="aitu-' . $key . '" name="aitu_notice[' . $key . ']" type="' . ( 'email' === $key ? 'email' : 'text' ) . '" autocomplete="' . $field[3] . '" maxlength="' . $field[2] . '" value="' . esc_attr( $value ) . '" required>'; }
  }
  echo '<button type="submit" name="aitu_review" value="1">' . esc_html( $t( 'Skontrolovať odstúpenie', 'Review withdrawal' ) ) . '</button></form>';
 }
 echo '<p><a href="' . esc_url( aitu_customer_url( 'returns' ) ) . '">' . esc_html( $t( 'Poučenie o vrátení tovaru', 'Returns instructions' ) ) . '</a> · <a href="' . esc_url( aitu_customer_url( 'privacy' ) ) . '">' . esc_html( $t( 'Ochrana súkromia', 'Privacy policy' ) ) . '</a></p>';
 echo '<p>' . esc_html( $t( 'Odstúpenie môžeš poslať aj e-mailom na ', 'You can also send your withdrawal by email to ' ) ) . '<a href="mailto:' . esc_attr( $m['email'] ?? '' ) . '">' . esc_html( $m['email'] ?? '' ) . '</a>.</p>';
 return ob_get_clean();
}
