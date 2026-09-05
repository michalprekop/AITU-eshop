<?php
/**
 * Site footer (Figma footer).
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_lang   = aitu_current_lang_slug();
$language_links = array(
	'sk' => array(
		'label' => 'Slovenčina',
		'url'   => add_query_arg( 'aitu_lang_manual', 'sk', aitu_same_host_url( home_url( '/sk/' ) ) ),
	),
	'en' => array(
		'label' => 'English',
		'url'   => add_query_arg( 'aitu_lang_manual', 'en', aitu_same_host_url( home_url( '/' ) ) ),
	),
);

if ( function_exists( 'pll_the_languages' ) ) {
	$raw_languages = pll_the_languages(
		array(
			'raw'                    => 1,
			'hide_if_no_translation' => 0,
			'hide_if_empty'          => 0,
		)
	);

	if ( is_array( $raw_languages ) ) {
		foreach ( $raw_languages as $raw_language ) {
			$slug = isset( $raw_language['slug'] ) ? strtolower( (string) $raw_language['slug'] ) : '';
			$url  = isset( $raw_language['url'] ) ? (string) $raw_language['url'] : '';
			if ( '' === $slug || '' === $url ) {
				continue;
			}
			if ( ! in_array( $slug, array( 'en', 'sk' ), true ) ) {
				continue;
			}

			$language_links[ $slug ] = array(
				'label' => 'sk' === $slug ? 'Slovenčina' : 'English',
				'url'   => add_query_arg( 'aitu_lang_manual', $slug, aitu_same_host_url( $url ) ),
			);
		}
	}
}
?>
<footer class="aitu-footer">
	<div class="aitu-footer-inner">
		<div class="aitu-footer-logo" aria-hidden="true">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/logo_footer_home.svg' ) ); ?>" alt="">
		</div>

		<div class="aitu-footer-links aitu-footer-links-products">
			<a href="<?php echo esc_url( aitu_category_link( 't-shirts' ) ); ?>"><?php echo esc_html( aitu_t( 'T-shirts', 'Tričká' ) ); ?></a>
			<a href="<?php echo esc_url( aitu_category_link( 'skate' ) ); ?>"><?php echo esc_html( aitu_t( 'Skate', 'Skate' ) ); ?></a>
			<a href="<?php echo esc_url( aitu_category_link( 'accessories' ) ); ?>"><?php echo esc_html( aitu_t( 'Accessories', 'Doplnky' ) ); ?></a>
		</div>

		<div class="aitu-footer-links aitu-footer-links-social">
			<?php
			$social_links = get_option( 'aitu_social_links', array() );
			foreach ( array( 'instagram' => 'Instagram', 'x' => 'X (Twitter)' ) as $key => $label ) {
				if ( ! empty( $social_links[ $key ] ) ) {
					echo '<a href="' . esc_url( $social_links[ $key ], array( 'https' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>';
				}
			}
			foreach ( array( 'about' => aitu_t( 'About', 'O nás' ), 'faq' => aitu_t( 'FAQ', 'Časté otázky' ) ) as $key => $label ) {
				$url = aitu_info_page_url( $key );
				if ( $url ) { echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
			}
			?>
		</div>

		<div class="aitu-footer-links aitu-footer-links-legal">
			<?php
			foreach ( array(
				'shipping' => aitu_t( 'Delivery & Payment', 'Doprava a platba' ),
				'tracking' => aitu_t( 'Order status', 'Stav objednávky' ),
				'terms' => aitu_t( 'Terms & Conditions', 'Obchodné podmienky' ),
				'privacy' => aitu_t( 'Privacy Policy', 'Ochrana súkromia' ),
				'cookies' => aitu_t( 'Cookie Policy', 'Cookies' ),
				'returns' => aitu_t( 'Returns & Refunds', 'Vrátenie a reklamácie' ),
			) as $key => $label ) {
				$url = aitu_info_page_url( $key );
				if ( $url ) { echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
			}
			?>
		</div>

		<p class="aitu-footer-lang">
			<span><?php echo esc_html( aitu_t( 'Language:', 'Jazyk:' ) ); ?></span>
			<?php if ( isset( $language_links['sk'] ) ) : ?>
				<a href="<?php echo esc_url( $language_links['sk']['url'] ); ?>"<?php echo 'sk' === $current_lang ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $language_links['sk']['label'] ); ?></a>
			<?php else : ?>
				<span<?php echo 'sk' === $current_lang ? ' aria-current="true"' : ''; ?>>Slovenčina</span>
			<?php endif; ?>
			<span>/</span>
			<?php if ( isset( $language_links['en'] ) ) : ?>
				<a href="<?php echo esc_url( $language_links['en']['url'] ); ?>"<?php echo 'en' === $current_lang ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $language_links['en']['label'] ); ?></a>
			<?php else : ?>
				<span<?php echo 'en' === $current_lang ? ' aria-current="true"' : ''; ?>>English</span>
			<?php endif; ?>
		</p>
		<div class="aitu-footer-bottom">
			<p class="aitu-footer-copy"><?php echo esc_html( aitu_t( 'Copyright', 'Autorské práva' ) ); ?> &copy; AITU <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
			<p class="aitu-footer-credit"><span><?php echo esc_html( aitu_t( 'Website by:', 'Web od:' ) ); ?></span> <a href="https://uncutcorners.com" target="_blank" rel="noopener noreferrer">uncutcorners.com</a></p>
		</div>
	</div>
</footer>
