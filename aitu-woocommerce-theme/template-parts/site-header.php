<?php
/**
 * Site header (Figma navbar).
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shop_page_id    = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
$account_page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'myaccount' ) : 0;
$cart_page_id    = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
$has_cart_items  = false;

if ( function_exists( 'WC' ) && WC()->cart ) {
	$has_cart_items = WC()->cart->get_cart_contents_count() > 0;
}
if ( ! $has_cart_items && ! empty( $_COOKIE['woocommerce_items_in_cart'] ) ) {
	$has_cart_items = absint( wp_unslash( $_COOKIE['woocommerce_items_in_cart'] ) ) > 0;
}

$shop_url    = $shop_page_id > 0 ? aitu_localized_post_url( $shop_page_id ) : aitu_language_home_url();
$account_url = $account_page_id > 0 ? aitu_localized_post_url( $account_page_id ) : aitu_language_home_url();
$cart_url    = $cart_page_id > 0 ? aitu_localized_post_url( $cart_page_id ) : aitu_language_home_url();

$search_products = add_query_arg(
	array(
		's'         => '',
		'post_type' => 'product',
	),
	aitu_language_home_url()
);
$search_action         = aitu_language_home_url();
$search_submit_label   = aitu_t( 'Search', 'Hľadať' );
$search_dialog_label   = aitu_t( 'Search products', 'Vyhľadávanie produktov' );
$search_close_label    = aitu_t( 'Close search', 'Zavrieť vyhľadávanie' );
$search_hint_label     = aitu_t( 'SEARCH BY PRODUCT NAME', 'VYHĽADAJTE NÁZOV PRODUKTU' );

$navbar_links = array();
if ( has_nav_menu( 'primary' ) ) {
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;

	if ( $menu_id > 0 ) {
		$menu_items = wp_get_nav_menu_items(
			$menu_id,
			array(
				'update_post_term_cache' => false,
			)
		);

		if ( is_array( $menu_items ) ) {
			foreach ( $menu_items as $menu_item ) {
				if ( (int) $menu_item->menu_item_parent !== 0 ) {
					continue;
				}
				if ( isset( $menu_item->post_status ) && 'publish' !== $menu_item->post_status ) {
					continue;
				}

				$url   = '';
				$label = isset( $menu_item->title ) ? trim( wp_strip_all_tags( (string) $menu_item->title ) ) : '';

				$item_type      = isset( $menu_item->type ) ? (string) $menu_item->type : '';
				$item_object    = isset( $menu_item->object ) ? (string) $menu_item->object : '';
				$item_object_id = isset( $menu_item->object_id ) ? (int) $menu_item->object_id : 0;
				$label_key      = strtolower( remove_accents( $label ) );

				if ( false !== strpos( $label_key, 'trick' ) || false !== strpos( $label_key, 't-shirt' ) ) {
					$url = aitu_category_link( 't-shirts' );
				} elseif ( false !== strpos( $label_key, 'skate' ) ) {
					$url = aitu_category_link( 'skate' );
				} elseif ( false !== strpos( $label_key, 'dopln' ) || false !== strpos( $label_key, 'accessor' ) ) {
					$url = aitu_category_link( 'accessories' );
				} elseif ( 'post_type' === $item_type && $item_object_id > 0 ) {
					$url = aitu_localized_post_url( $item_object_id );
				} elseif ( 'taxonomy' === $item_type && $item_object_id > 0 ) {
					$term_id = $item_object_id;
					$taxonomy = '' !== $item_object ? $item_object : 'product_cat';
					if ( 'product_cat' === $taxonomy ) {
						$term = get_term( $term_id, 'product_cat' );
						if ( $term instanceof WP_Term && ! empty( $term->slug ) ) {
							$url = aitu_category_link( (string) $term->slug );
						}
					} else {
						if ( function_exists( 'pll_get_term' ) ) {
							$translated_term_id = (int) pll_get_term( $item_object_id, aitu_current_lang_slug() );
							if ( $translated_term_id > 0 ) {
								$term_id = $translated_term_id;
							}
						}
						$term_url = get_term_link( $term_id, $taxonomy );
						if ( ! is_wp_error( $term_url ) ) {
							$url = aitu_same_host_url( (string) $term_url );
						}
					}
				}

				if ( '' === $url ) {
					$url = isset( $menu_item->url ) ? trim( (string) $menu_item->url ) : '';
					$url = aitu_same_host_url( $url );
				}

				if ( '' === $url || '' === $label ) {
					continue;
				}

				$navbar_links[] = array(
					'url'   => $url,
					'label' => $label,
				);
			}
		}
	}
}

if ( empty( $navbar_links ) ) {
	$navbar_links = array(
		array(
			'url'   => aitu_category_link( 't-shirts' ),
			'label' => aitu_t( 'T-shirts', 'Tričká' ),
		),
		array(
			'url'   => aitu_category_link( 'skate' ),
			'label' => aitu_t( 'Skate', 'Skate' ),
		),
		array(
			'url'   => aitu_category_link( 'accessories' ),
			'label' => aitu_t( 'Accessories', 'Doplnky' ),
		),
	);
}
?>
<header class="aitu-navbar">
	<div class="aitu-navbar-inner">
		<nav class="aitu-navbar-links" aria-label="<?php esc_attr_e( 'Primary navigation', 'aitu-woocommerce-theme' ); ?>">
			<?php foreach ( $navbar_links as $navbar_link ) : ?>
				<a href="<?php echo esc_url( $navbar_link['url'] ); ?>"><?php echo esc_html( $navbar_link['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<a class="aitu-navbar-logo" href="<?php echo esc_url( aitu_language_home_url() ); ?>" aria-label="<?php esc_attr_e( 'Home', 'aitu-woocommerce-theme' ); ?>">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/logo_nav_orange.svg' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>">
		</a>

		<div class="aitu-navbar-icons" aria-label="<?php esc_attr_e( 'Store links', 'aitu-woocommerce-theme' ); ?>">
			<a class="aitu-navbar-search-toggle" href="<?php echo esc_url( $search_products ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'aitu-woocommerce-theme' ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/icon_search_home.svg' ) ); ?>" alt="">
			</a>
			<a href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'My account', 'aitu-woocommerce-theme' ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/icon_account_home.svg' ) ); ?>" alt="">
			</a>
			<a class="aitu-navbar-cart-link<?php echo $has_cart_items ? ' has-cart-items' : ''; ?>" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'aitu-woocommerce-theme' ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/icon_cart_home.svg' ) ); ?>" alt="">
			</a>
		</div>
	</div>

	<div class="aitu-search-overlay" aria-hidden="true">
		<div class="aitu-search-overlay-inner" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $search_dialog_label ); ?>">
			<a class="aitu-search-logo" href="<?php echo esc_url( aitu_language_home_url() ); ?>" aria-label="<?php esc_attr_e( 'Home', 'aitu-woocommerce-theme' ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/logo_nav_orange.svg' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			</a>
			<button type="button" class="aitu-search-close" aria-label="<?php echo esc_attr( $search_close_label ); ?>">×</button>
			<p class="aitu-search-hint"><?php echo esc_html( $search_hint_label ); ?></p>
			<form class="aitu-search-form" action="<?php echo esc_url( $search_action ); ?>" method="get" role="search">
				<input type="hidden" name="post_type" value="product">
				<div class="aitu-search-input-wrap">
					<input class="aitu-search-input" type="search" name="s" value="" autocomplete="off" required>
				</div>
				<button type="submit" class="aitu-search-submit" aria-label="<?php echo esc_attr( $search_submit_label ); ?>">
					<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/icon_search_home.svg' ) ); ?>" alt="">
				</button>
			</form>
		</div>
	</div>
</header>
