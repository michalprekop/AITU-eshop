<?php
/**
 * AITU WooCommerce theme functions.
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup.
 */
function aitu_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'woocommerce' );
	load_theme_textdomain( 'aitu-woocommerce-theme', get_template_directory() . '/languages' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'aitu-woocommerce-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'aitu_theme_setup' );

/**
 * Resolve current language slug.
 *
 * @return string
 */
function aitu_current_lang_slug() {
	$request_path = '';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	}
	if ( '' !== $request_path ) {
		$segments = array_values(
			array_filter(
				explode( '/', trim( $request_path, '/' ) ),
				'strlen'
			)
		);
		if ( ! empty( $segments ) ) {
			$path_lang = strtolower( (string) $segments[0] );
			if ( in_array( $path_lang, array( 'en', 'sk' ), true ) ) {
				return $path_lang;
			}
		}
	}

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = (string) pll_current_language( 'slug' );
		if ( '' !== $lang ) {
			return strtolower( $lang );
		}
	}

	if ( ! empty( $_COOKIE['pll_language'] ) ) {
		$cookie_lang = strtolower( sanitize_text_field( wp_unslash( $_COOKIE['pll_language'] ) ) );
		if ( in_array( $cookie_lang, array( 'en', 'sk' ), true ) ) {
			return $cookie_lang;
		}
	}

	$locale = strtolower( (string) get_locale() );
	if ( 0 === strpos( $locale, 'sk' ) || 0 === strpos( $locale, 'cs' ) ) {
		return 'sk';
	}

	return 'en';
}

/**
 * Determine whether current language context is Slovak.
 *
 * @return bool
 */
function aitu_is_slovak_context() {
	if ( function_exists( 'pll_current_language' ) ) {
		$current = (string) pll_current_language( 'slug' );
		if ( '' !== $current ) {
			return 'sk' === strtolower( $current );
		}
	}

	return 'sk' === aitu_current_lang_slug();
}

/**
 * Resolve language strictly from current URL path.
 *
 * /sk/... => sk
 * everything else => en
 *
 * @return string
 */
function aitu_request_path_lang_slug() {
	$request_path = '';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	}
	if ( '' !== $request_path ) {
		$segments = array_values(
			array_filter(
				explode( '/', trim( $request_path, '/' ) ),
				'strlen'
			)
		);
		if ( ! empty( $segments ) ) {
			$path_lang = strtolower( (string) $segments[0] );
			if ( in_array( $path_lang, array( 'en', 'sk' ), true ) ) {
				return $path_lang;
			}
		}
	}

	return 'en';
}

/**
 * Return string by active language (EN/SK).
 *
 * @param string $en English value.
 * @param string $sk Slovak value.
 * @return string
 */
function aitu_t( $en, $sk ) {
	return 'sk' === aitu_current_lang_slug() ? $sk : $en;
}

/**
 * Normalize URL host to current request host to keep cookies on one domain.
 *
 * @param string $url Source URL.
 * @return string
 */
function aitu_same_host_url( $url ) {
	$url = (string) $url;
	if ( '' === $url || empty( $_SERVER['HTTP_HOST'] ) ) {
		return $url;
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return $url;
	}

	$current_host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
	$current_host = preg_replace( '/:\d+$/', '', $current_host );
	$scheme       = is_ssl() ? 'https' : 'http';
	$path         = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
	$query        = isset( $parts['query'] ) && '' !== (string) $parts['query'] ? '?' . (string) $parts['query'] : '';
	$fragment     = isset( $parts['fragment'] ) && '' !== (string) $parts['fragment'] ? '#' . (string) $parts['fragment'] : '';

	return $scheme . '://' . $current_host . $path . $query . $fragment;
}

/**
 * Force REST URLs to current host to avoid cross-domain Store API issues.
 *
 * @param string $url REST URL.
 * @return string
 */
function aitu_rest_url_same_host( $url ) {
	return aitu_same_host_url( (string) $url );
}
add_filter( 'rest_url', 'aitu_rest_url_same_host', 20, 1 );

/**
 * Disable WooCommerce Coming Soon template interception.
 * We already use a dedicated maintenance lock plugin for public visitors.
 */
add_filter( 'woocommerce_coming_soon_exclude', '__return_true', 20 );

/**
 * Resolve post ID in current language when translation exists.
 *
 * @param int         $post_id Original post ID.
 * @param string|null $lang Target language slug.
 * @return int
 */
function aitu_translate_post_id( $post_id, $lang = null ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return 0;
	}

	$target_lang = is_string( $lang ) && '' !== $lang ? strtolower( $lang ) : aitu_current_lang_slug();

	if ( function_exists( 'pll_get_post' ) ) {
		$translated  = (int) pll_get_post( $post_id, $target_lang );
		if ( $translated > 0 && ( 'en' === $target_lang || $translated !== $post_id ) ) {
			return $translated;
		}
	}

	// Fallback for installations where Polylang relations are missing,
	// but translated pages follow the "<slug>-2" naming convention.
	if ( 'sk' === $target_lang ) {
		$post_object = get_post( $post_id );
		if ( $post_object instanceof WP_Post && 'page' === $post_object->post_type ) {
			$fallback_slugs = array(
				(string) $post_object->post_name . '-2',
				(string) $post_object->post_name . '-sk',
			);

			foreach ( $fallback_slugs as $fallback_slug ) {
				$fallback_page = get_page_by_path( sanitize_title( $fallback_slug ), OBJECT, 'page' );
				if ( $fallback_page instanceof WP_Post ) {
					return (int) $fallback_page->ID;
				}
			}
		}
	}

	return $post_id;
}

/**
 * Resolve cart product family key for language-independent item grouping.
 *
 * @param int $post_id Source post ID.
 * @return string
 */
function aitu_cart_product_family_key( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}

	$post_type = get_post_type( $post_id );
	if ( 'product_variation' === $post_type ) {
		$parent_id = (int) wp_get_post_parent_id( $post_id );
		if ( $parent_id > 0 ) {
			$post_id = $parent_id;
		}
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$en_id = (int) pll_get_post( $post_id, 'en' );
		if ( $en_id > 0 ) {
			$post_id = $en_id;
		}
	}

	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( $post_id );
		if ( is_array( $translations ) && ! empty( $translations ) ) {
			$ids = array_values(
				array_unique(
					array_filter(
						array_map( 'absint', $translations )
					)
				)
			);
			if ( ! empty( $ids ) ) {
				sort( $ids );
				return 'pll:' . implode( '-', $ids );
			}
		}
	}

	$product = wc_get_product( $post_id );
	if ( $product instanceof WC_Product_Variation ) {
		$parent_id = (int) $product->get_parent_id();
		if ( $parent_id > 0 ) {
			$parent_product = wc_get_product( $parent_id );
			if ( $parent_product instanceof WC_Product ) {
				$product = $parent_product;
			}
		}
	}

	if ( $product instanceof WC_Product ) {
		$sku = trim( (string) $product->get_sku() );
		if ( '' !== $sku ) {
			return 'sku:' . strtolower( $sku );
		}
	}

	$post = get_post( $post_id );
	if ( $post instanceof WP_Post ) {
		$slug = sanitize_title( $post->post_name );
		if ( '' !== $slug ) {
			$base_slug = preg_replace( '/(?:-2|-sk|-en)+$/', '', $slug );
			if ( ! is_string( $base_slug ) || '' === $base_slug ) {
				$base_slug = $slug;
			}
			return 'slug:' . $base_slug;
		}
	}

	if ( $product instanceof WC_Product ) {
		$name = strtolower( trim( wp_strip_all_tags( (string) $product->get_name() ) ) );
		$name = preg_replace( '/\s+/', ' ', $name );
		if ( is_string( $name ) && '' !== $name ) {
			return 'name:' . $name;
		}
	}

	return 'id:' . (string) $post_id;
}

/**
 * Normalize variation attributes for stable cart-item comparison.
 *
 * @param mixed $variation Variation attributes.
 * @return array<string, string>
 */
function aitu_normalize_cart_variation_attributes( $variation ) {
	if ( ! is_array( $variation ) ) {
		return array();
	}

	$normalized = array();
	foreach ( $variation as $key => $value ) {
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$attr_key                 = sanitize_key( (string) $key );
		$normalized[ $attr_key ] = strtolower( trim( (string) $value ) );
	}

	ksort( $normalized );
	return $normalized;
}

/**
 * Resolve variation attributes for cart row grouping.
 *
 * @param array<string,mixed> $cart_item Cart item data.
 * @return array<string,string>
 */
function aitu_cart_item_variation_attributes( $cart_item ) {
	$variation_attrs = aitu_normalize_cart_variation_attributes(
		isset( $cart_item['variation'] ) ? $cart_item['variation'] : array()
	);

	if ( ! empty( $variation_attrs ) ) {
		return $variation_attrs;
	}

	$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
	if ( $variation_id <= 0 ) {
		return $variation_attrs;
	}

	$variation_product = wc_get_product( $variation_id );
	if ( ! $variation_product instanceof WC_Product_Variation ) {
		return $variation_attrs;
	}

	return aitu_normalize_cart_variation_attributes( $variation_product->get_variation_attributes() );
}

/**
 * Merge duplicate cart rows that represent EN/SK translations of the same item.
 *
 * @return void
 */
function aitu_merge_translated_cart_items() {
	static $running = false;
	if ( $running ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$running       = true;
	$changed       = false;
	$group_targets = array();
	$cart_contents = WC()->cart->get_cart();

	foreach ( $cart_contents as $item_key => $cart_item ) {
		if ( ! is_array( $cart_item ) ) {
			continue;
		}

		$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$quantity   = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
		if ( $product_id <= 0 || $quantity <= 0 ) {
			continue;
		}

		$family_key      = aitu_cart_product_family_key( $product_id );
		$variation_attrs = aitu_cart_item_variation_attributes( $cart_item );

		$group_fingerprint = md5(
			wp_json_encode(
				array(
					'family'    => $family_key,
					'attrs'     => $variation_attrs,
				)
			)
		);

		if ( ! isset( $group_targets[ $group_fingerprint ] ) ) {
			$group_targets[ $group_fingerprint ] = $item_key;
			continue;
		}

		$target_key = (string) $group_targets[ $group_fingerprint ];
		if ( $target_key === $item_key ) {
			continue;
		}
		if ( ! isset( WC()->cart->cart_contents[ $target_key ] ) ) {
			$group_targets[ $group_fingerprint ] = $item_key;
			continue;
		}

		$target_qty = isset( WC()->cart->cart_contents[ $target_key ]['quantity'] ) ? (int) WC()->cart->cart_contents[ $target_key ]['quantity'] : 0;
		$new_qty    = max( 0, $target_qty + $quantity );

		WC()->cart->set_quantity( $target_key, $new_qty, false );
		WC()->cart->remove_cart_item( $item_key );
		$changed = true;
	}

	if ( $changed ) {
		WC()->cart->set_session();

		if ( 'woocommerce_before_calculate_totals' !== current_filter() ) {
			WC()->cart->calculate_totals();
		}
	}

	$running = false;
}
add_action( 'woocommerce_cart_loaded_from_session', 'aitu_merge_translated_cart_items', 30 );
add_action( 'woocommerce_add_to_cart', 'aitu_merge_translated_cart_items', 30 );
add_action( 'woocommerce_before_calculate_totals', 'aitu_merge_translated_cart_items', 1 );

add_filter(
	'woocommerce_cart_id',
	function ( $cart_id, $product_id, $variation_id, $variation, $cart_item_data ) {
		$family_source_id = $variation_id > 0 ? (int) $variation_id : (int) $product_id;
		$family_key       = aitu_cart_product_family_key( $family_source_id );
		$variation_attrs  = aitu_normalize_cart_variation_attributes( $variation );

		return md5(
			wp_json_encode(
				array(
					'family' => $family_key,
					'attrs'  => $variation_attrs,
				)
			)
		);
	},
	50,
	5
);

/**
 * Check whether current queried page matches WooCommerce page (including translations).
 *
 * @param string $page_key WooCommerce page key (e.g. checkout, cart, shop).
 * @return bool
 */
function aitu_is_current_wc_page( $page_key ) {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}

	$current_page_id = (int) get_queried_object_id();
	if ( $current_page_id <= 0 ) {
		return false;
	}

	$base_page_id = (int) wc_get_page_id( $page_key );
	if ( $base_page_id <= 0 ) {
		return false;
	}

	$page_ids = array( $base_page_id );
	if ( function_exists( 'pll_get_post' ) ) {
		foreach ( array( 'en', 'sk' ) as $lang_slug ) {
			$translated_id = (int) pll_get_post( $base_page_id, $lang_slug );
			if ( $translated_id > 0 ) {
				$page_ids[] = $translated_id;
			}
		}
	}

	$page_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $page_ids )
			)
		)
	);

	return in_array( $current_page_id, $page_ids, true );
}

/**
 * Map WooCommerce page options to translated page IDs by URL language.
 *
 * @param mixed  $value Option value.
 * @param string $option Option key.
 * @return mixed
 */
function aitu_localize_wc_page_option_id( $value, $option = '' ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $value;
	}

	if ( ! function_exists( 'pll_get_post' ) ) {
		return $value;
	}

	$base_page_id = absint( $value );
	if ( $base_page_id <= 0 ) {
		return $value;
	}

	$lang_slug     = aitu_request_path_lang_slug();
	$translated_id = aitu_translate_post_id( $base_page_id, $lang_slug );
	if ( $translated_id > 0 ) {
		return (string) $translated_id;
	}

	return $value;
}
add_filter( 'option_woocommerce_shop_page_id', 'aitu_localize_wc_page_option_id', 20, 2 );
add_filter( 'option_woocommerce_cart_page_id', 'aitu_localize_wc_page_option_id', 20, 2 );
add_filter( 'option_woocommerce_checkout_page_id', 'aitu_localize_wc_page_option_id', 20, 2 );
add_filter( 'option_woocommerce_myaccount_page_id', 'aitu_localize_wc_page_option_id', 20, 2 );

/**
 * Apply language-localized cart/checkout/store page URLs to Woo settings arrays.
 *
 * @param array  $data Source settings.
 * @param string $lang_slug Language slug.
 * @return array
 */
function aitu_localize_wc_settings_array( $data, $lang_slug ) {
	$url_keys = array(
		'cartUrl'      => 'cart',
		'checkoutUrl'  => 'checkout',
		'cart_url'     => 'cart',
		'checkout_url' => 'checkout',
	);

	foreach ( $url_keys as $data_key => $page_key ) {
		if ( isset( $data[ $data_key ] ) && is_string( $data[ $data_key ] ) ) {
			$data[ $data_key ] = aitu_wc_page_url_by_lang( $page_key, $lang_slug );
		}
	}

	if ( isset( $data['storePages'] ) && is_array( $data['storePages'] ) ) {
		$store_page_map = array(
			'shop'      => 'shop',
			'cart'      => 'cart',
			'checkout'  => 'checkout',
			'myaccount' => 'myaccount',
		);

		foreach ( $store_page_map as $store_key => $page_key ) {
			if ( ! isset( $data['storePages'][ $store_key ] ) || ! is_array( $data['storePages'][ $store_key ] ) ) {
				continue;
			}

			$localized_permalink = aitu_wc_page_url_by_lang( $page_key, $lang_slug );
			$translated_id       = (int) url_to_postid( $localized_permalink );
			if ( $translated_id <= 0 && function_exists( 'wc_get_page_id' ) ) {
				$translated_id = (int) wc_get_page_id( $page_key );
			}

			$data['storePages'][ $store_key ]['id']        = max( 0, (int) $translated_id );
			$data['storePages'][ $store_key ]['title']     = $translated_id > 0 ? wp_strip_all_tags( get_the_title( $translated_id ) ) : '';
			$data['storePages'][ $store_key ]['permalink'] = aitu_same_host_url( (string) $localized_permalink );
		}
	}

	return $data;
}

/**
 * Localize WooCommerce frontend script-data links.
 *
 * @param mixed  $data Script data.
 * @param string $handle Script handle.
 * @return mixed
 */
function aitu_localize_woocommerce_script_data( $data, $handle ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $data;
	}

	if ( ! is_array( $data ) ) {
		return $data;
	}

	$lang_slug = aitu_request_path_lang_slug();
	return aitu_localize_wc_settings_array( $data, $lang_slug );
}
add_filter( 'woocommerce_get_script_data', 'aitu_localize_woocommerce_script_data', 25, 2 );

add_filter(
	'woocommerce_shared_settings',
	function ( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$lang_slug = aitu_request_path_lang_slug();
		return aitu_localize_wc_settings_array( $settings, $lang_slug );
	},
	25
);

add_filter(
	'woocommerce_is_cart',
	function ( $is_cart ) {
		if ( $is_cart ) {
			return true;
		}

		return aitu_is_current_wc_page( 'cart' );
	},
	20
);

add_filter(
	'woocommerce_is_checkout',
	function ( $is_checkout ) {
		if ( $is_checkout ) {
			return true;
		}

		return aitu_is_current_wc_page( 'checkout' );
	},
	20
);

/**
 * Resolve language-aware homepage URL.
 *
 * @param string|null $lang Target language slug.
 * @return string
 */
function aitu_language_home_url( $lang = null ) {
	$target_lang = is_string( $lang ) && '' !== $lang ? strtolower( $lang ) : aitu_current_lang_slug();

	if ( function_exists( 'pll_home_url' ) ) {
		$url = pll_home_url( $target_lang );
		if ( $url ) {
			return aitu_same_host_url( (string) $url );
		}
	}

	if ( 'sk' === $target_lang ) {
		return aitu_same_host_url( home_url( '/sk/' ) );
	}

	return aitu_same_host_url( home_url( '/' ) );
}

/**
 * Resolve post permalink in current language.
 *
 * @param int         $post_id Source post ID.
 * @param string|null $lang Target language slug.
 * @return string
 */
function aitu_localized_post_url( $post_id, $lang = null ) {
	$translated_post_id = aitu_translate_post_id( (int) $post_id, $lang );
	if ( $translated_post_id > 0 ) {
		$link = get_permalink( $translated_post_id );
		if ( $link ) {
			return aitu_same_host_url( (string) $link );
		}
	}

	return aitu_language_home_url( $lang );
}

/**
 * Persist manual language switch from footer links.
 */
function aitu_capture_manual_language_choice() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( empty( $_GET['aitu_lang_manual'] ) ) {
		return;
	}

	$manual_lang = strtolower( sanitize_text_field( wp_unslash( $_GET['aitu_lang_manual'] ) ) );
	if ( ! in_array( $manual_lang, array( 'en', 'sk' ), true ) ) {
		$manual_lang = aitu_current_lang_slug();
	}

	setcookie( 'aitu_lang_lock', $manual_lang, time() + MONTH_IN_SECONDS * 12, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE['aitu_lang_lock'] = $manual_lang;

	if ( ! headers_sent() ) {
		wp_safe_redirect( remove_query_arg( 'aitu_lang_manual' ) );
		exit;
	}
}
add_action( 'template_redirect', 'aitu_capture_manual_language_choice', 0 );

/**
 * Auto-select language by country on homepage.
 * SK/CZ => Slovak, other countries => English.
 */
function aitu_geo_language_redirect() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! function_exists( 'pll_home_url' ) || ! function_exists( 'pll_current_language' ) ) {
		return;
	}

	if ( ! is_front_page() ) {
		return;
	}

	$current_lang = (string) pll_current_language( 'slug' );

	if ( ! empty( $_COOKIE['aitu_lang_lock'] ) ) {
		$locked_lang = strtolower( sanitize_text_field( wp_unslash( $_COOKIE['aitu_lang_lock'] ) ) );
		if ( in_array( $locked_lang, array( 'en', 'sk' ), true ) ) {
			if ( '' === $current_lang || $current_lang !== $locked_lang ) {
				$locked_url = pll_home_url( $locked_lang );
				if ( $locked_url ) {
					$locked_url = aitu_same_host_url( $locked_url );
					wp_safe_redirect( $locked_url, 302 );
					exit;
				}
			}
		}
		return;
	}

	$country = '';
	if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
		$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
	} elseif ( ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) {
		$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) );
	}

	$target_lang = in_array( $country, array( 'SK', 'CZ' ), true ) ? 'sk' : 'en';
	if ( '' === $current_lang || $current_lang === $target_lang ) {
		return;
	}

	$target_url = pll_home_url( $target_lang );
	if ( ! $target_url ) {
		return;
	}
	$target_url = aitu_same_host_url( $target_url );

	setcookie( 'aitu_lang_geo', $target_lang, time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	wp_safe_redirect( $target_url, 302 );
	exit;
}
add_action( 'template_redirect', 'aitu_geo_language_redirect', 1 );

/**
 * Enqueue styles and scripts.
 */
function aitu_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );
	$style_path = get_stylesheet_directory() . '/style.css';
	$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $version;

	wp_enqueue_style(
		'aitu-fonts',
		get_theme_file_uri( '/assets/css/fonts.css' ),
		array(),
		null
	);

	wp_enqueue_style(
		'aitu-style',
		get_stylesheet_uri(),
		array( 'aitu-fonts' ),
		$style_version
	);

	// Shared navigation and localized labels are also needed at checkout.
	$script_path    = get_template_directory() . '/assets/js/theme.js';
	$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $version;
	$script_deps    = array( 'jquery' );

	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
		$script_deps[] = 'wc-add-to-cart-variation';
	}

	wp_enqueue_script(
		'aitu-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		$script_deps,
		$script_version,
		true
	);

	wp_localize_script(
		'aitu-theme',
		'aituThemeI18n',
			array(
				'addToBasket' => aitu_t( 'add to basket', 'pridať do košíka' ),
				'sizeOptions' => aitu_t( 'Size options', 'Výber veľkosti' ),
				'addedToCart' => aitu_t( 'Product added to cart.', 'Produkt bol pridaný do košíka.' ),
				'viewCart' => aitu_t( 'View cart →', 'Zobraziť košík →' ),
				'cartTotalLabel' => aitu_t( 'TOTAL:', 'SPOLU' ),
				'cartHeadingLabel' => aitu_t( 'CART', 'KOŠÍK' ),
				'cartEmptyLabel' => aitu_t( 'YOUR CART IS CURRENTLY EMPTY!', 'VÁŠ KOŠÍK JE MOMENTÁLNE PRÁZDNY!' ),
				'checkoutButtonLabel' => aitu_t( 'CHECKOUT', 'POKLADŇA' ),
				'checkoutHeadingLabel' => aitu_t( 'CHECKOUT', 'POKLADŇA' ),
				'cartUrl' => aitu_wc_page_url_by_lang( 'cart', aitu_request_path_lang_slug() ),
				'checkoutUrl' => aitu_wc_page_url_by_lang( 'checkout', aitu_request_path_lang_slug() ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'aitu_enqueue_assets' );

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Detect WooCommerce Store API request (used by Cart/Checkout blocks).
 *
 * @return bool
 */
function aitu_is_store_api_request() {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
		return false;
	}

	$request_uri = '';
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri = strtolower( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	return false !== strpos( $request_uri, '/wc/store/' );
}

/**
 * Build localized WooCommerce page URL for current language and host.
 *
 * @param string $page_key WooCommerce page key.
 * @param string      $fallback_url Original WooCommerce URL (optional).
 * @param string|null $lang Target language slug.
 * @return string
 */
function aitu_localized_wc_page_url( $page_key, $fallback_url = '', $lang = null ) {
	$fallback_url = (string) $fallback_url;
	$localized_url = '';

	if ( function_exists( 'wc_get_page_id' ) ) {
		$page_id = (int) wc_get_page_id( $page_key );
		if ( $page_id > 0 ) {
			$localized_url = aitu_localized_post_url( $page_id, $lang );
		}
	}

	if ( '' === $localized_url ) {
		$localized_url = '' !== $fallback_url ? $fallback_url : aitu_language_home_url( $lang );
	}

	$parts    = wp_parse_url( $fallback_url );
	$query    = ( is_array( $parts ) && ! empty( $parts['query'] ) ) ? (string) $parts['query'] : '';
	$fragment = ( is_array( $parts ) && ! empty( $parts['fragment'] ) ) ? (string) $parts['fragment'] : '';

	if ( '' !== $query ) {
		$localized_url = add_query_arg( wp_parse_args( $query ), $localized_url );
	}
	if ( '' !== $fragment ) {
		$localized_url .= '#' . $fragment;
	}

	return aitu_same_host_url( $localized_url );
}

/**
 * Resolve localized Woo page URL with explicit per-language slug fallback.
 *
 * @param string      $page_key WooCommerce page key.
 * @param string|null $lang Target language slug.
 * @return string
 */
function aitu_wc_page_url_by_lang( $page_key, $lang = null ) {
	$lang_slug = is_string( $lang ) && '' !== $lang ? strtolower( $lang ) : aitu_request_path_lang_slug();

	$slug_map = array(
		'en' => array(
			'shop'      => 'shop',
			'cart'      => 'cart',
			'checkout'  => 'checkout',
			'myaccount' => 'my-account',
		),
		'sk' => array(
			'shop'      => 'shop-2',
			'cart'      => 'cart-2',
			'checkout'  => 'checkout-2',
			'myaccount' => 'my-account-2',
		),
	);

	if ( isset( $slug_map[ $lang_slug ][ $page_key ] ) ) {
		$slug = (string) $slug_map[ $lang_slug ][ $page_key ];
		$path = ( 'sk' === $lang_slug ? '/sk/' : '/' ) . $slug;
		return aitu_same_host_url( home_url( $path ) );
	}

	return aitu_localized_wc_page_url( $page_key, '', $lang_slug );
}

add_filter(
	'woocommerce_get_cart_url',
	function ( $url ) {
		return aitu_localized_wc_page_url( 'cart', (string) $url );
	},
	20
);

add_filter(
	'woocommerce_get_checkout_url',
	function ( $url ) {
		return aitu_localized_wc_page_url( 'checkout', (string) $url );
	},
	20
);

/**
 * Force Cart/Checkout to preferred language URL to prevent EN fallback from SK flow.
 */
function aitu_force_localized_cart_checkout_url() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$is_cart_page     = aitu_is_current_wc_page( 'cart' ) || ( function_exists( 'is_cart' ) && is_cart() );
	$is_checkout_page = aitu_is_current_wc_page( 'checkout' ) || ( function_exists( 'is_checkout' ) && is_checkout() );
	if ( ! $is_cart_page && ! $is_checkout_page ) {
		return;
	}

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return;
	}
	if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
		return;
	}

	$preferred_lang = '';
	if ( ! empty( $_COOKIE['aitu_lang_lock'] ) ) {
		$preferred_lang = strtolower( sanitize_text_field( wp_unslash( $_COOKIE['aitu_lang_lock'] ) ) );
	}
	if ( '' === $preferred_lang ) {
		$preferred_lang = aitu_current_lang_slug();
	}

	if ( ! in_array( $preferred_lang, array( 'en', 'sk' ), true ) ) {
		return;
	}

	$page_key   = $is_checkout_page ? 'checkout' : 'cart';
	$target_url = aitu_localized_wc_page_url( $page_key, '', $preferred_lang );
	if ( '' === $target_url ) {
		return;
	}

	if ( empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$current_url  = ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . (string) wp_unslash( $_SERVER['REQUEST_URI'] );
	$current_path = untrailingslashit( (string) wp_parse_url( $current_url, PHP_URL_PATH ) );
	$target_path  = untrailingslashit( (string) wp_parse_url( $target_url, PHP_URL_PATH ) );

	if ( '' !== $target_path && $current_path !== $target_path ) {
		wp_safe_redirect( $target_url, 302 );
		exit;
	}
}
// Disabled: this redirect can create SK/EN ping-pong loops on checkout routes.
// add_action( 'template_redirect', 'aitu_force_localized_cart_checkout_url', 2 );

add_filter(
	'woocommerce_currency_symbol',
	function ( $currency_symbol, $currency ) {
		if ( 'EUR' === $currency ) {
			return 'EUR';
		}
		return $currency_symbol;
	},
	10,
	2
);

add_filter(
	'woocommerce_price_format',
	function () {
		return '%1$s %2$s';
	},
	10
);

// Preserve WooCommerce currency precision for shipping, taxes and refunds.

add_filter(
	'woocommerce_get_price_html',
	function ( $price_html, $product ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $price_html;
		}

		if ( ! $product instanceof WC_Product ) {
			return $price_html;
		}

		$price = $product->get_price();
		if ( '' === (string) $price ) {
			return $price_html;
		}

		return esc_html( aitu_format_price_eur( $price ) );
	},
	99,
	2
);

add_filter(
	'woocommerce_product_single_add_to_cart_text',
	function () {
		return aitu_t( 'add to basket', 'pridať do košíka' );
	},
	10
);

add_filter(
	'woocommerce_order_button_text',
	function () {
		return aitu_t( 'Place order and pay', 'Objednať s povinnosťou platby' );
	},
	10
);

/**
 * Force WooPayments/Stripe card placeholders to use AITU grey.
 *
 * @param array<string,mixed> $appearance Appearance config.
 * @return array<string,mixed>
 */
function aitu_apply_wcpay_placeholder_grey( array $appearance ) {
	$grey = '#868686';

	if ( ! isset( $appearance['variables'] ) || ! is_array( $appearance['variables'] ) ) {
		$appearance['variables'] = array();
	}
	$appearance['variables']['colorTextSecondary']   = $grey;
	$appearance['variables']['colorTextPlaceholder'] = $grey;

	if ( ! isset( $appearance['rules'] ) || ! is_array( $appearance['rules'] ) ) {
		$appearance['rules'] = array();
	}

	$placeholder_selectors = array(
		'.Input::placeholder',
		'.Input--empty',
		'.Label',
		'.Label--floating',
	);

	foreach ( $placeholder_selectors as $selector ) {
		if ( ! isset( $appearance['rules'][ $selector ] ) || ! is_array( $appearance['rules'][ $selector ] ) ) {
			$appearance['rules'][ $selector ] = array();
		}
		$appearance['rules'][ $selector ]['color'] = $grey;
	}

	return $appearance;
}

add_filter(
	'wcpay_payment_fields_js_config',
	function ( $config ) {
		if ( ! is_array( $config ) ) {
			return $config;
		}

		$appearance_keys = array(
			'upeAppearance',
			'wcBlocksUPEAppearance',
			'upeAddPaymentMethodAppearance',
			'upeBnplProductPageAppearance',
			'upeBnplClassicCartAppearance',
			'upeBnplCartBlockAppearance',
		);

		foreach ( $appearance_keys as $appearance_key ) {
			if ( empty( $config[ $appearance_key ] ) ) {
				continue;
			}

			$appearance_value = $config[ $appearance_key ];
			if ( is_object( $appearance_value ) ) {
				$appearance_value = json_decode( wp_json_encode( $appearance_value ), true );
			}

			if ( is_array( $appearance_value ) ) {
				$config[ $appearance_key ] = aitu_apply_wcpay_placeholder_grey( $appearance_value );
			}
		}

		return $config;
	},
	30
);

add_filter(
	'woocommerce_page_title',
	function ( $title ) {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return aitu_t( 'CART', 'KOŠÍK' );
		}
		return $title;
	},
	10
);

add_filter(
	'loop_shop_columns',
	function () {
		return 3;
	},
	20
);

add_filter(
	'loop_shop_per_page',
	function ( $per_page ) {
		return 9;
	},
	20
);

add_filter(
	'woocommerce_ajax_variation_threshold',
	function ( $threshold ) {
		return max( 9999, (int) $threshold );
	},
	10
);

/**
 * Resolve product category URL by slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function aitu_category_link( $slug ) {
	$shop_url = aitu_language_home_url();
	if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_id' ) ) {
		$shop_page_id = (int) wc_get_page_id( 'shop' );
		if ( $shop_page_id > 0 ) {
			$shop_url = aitu_localized_post_url( $shop_page_id );
		}
	}

	$current_lang = aitu_current_lang_slug();
	$slug_map     = array(
		't-shirts' => array(
			'en' => 't-shirts',
			'sk' => 'tricka',
		),
		'tricka'   => array(
			'en' => 't-shirts',
			'sk' => 'tricka',
		),
	);
	$candidate_slugs = array( (string) $slug );
	if ( isset( $slug_map[ $slug ][ $current_lang ] ) ) {
		array_unshift( $candidate_slugs, $slug_map[ $slug ][ $current_lang ] );
	}
	$candidate_slugs = array_values( array_unique( array_filter( array_map( 'strval', $candidate_slugs ) ) ) );

	if ( taxonomy_exists( 'product_cat' ) ) {
		foreach ( $candidate_slugs as $candidate_slug ) {
			$term = get_term_by( 'slug', $candidate_slug, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$term_id = (int) $term->term_id;
			if ( function_exists( 'pll_get_term' ) ) {
				$translated_term_id = (int) pll_get_term( $term_id, aitu_current_lang_slug() );
				if ( $translated_term_id > 0 ) {
					$term_id = $translated_term_id;
				}
			}

			$has_term_language = true;
			if ( function_exists( 'pll_get_term_language' ) ) {
				$term_language = pll_get_term_language( $term_id, 'slug' );
				if ( ! is_string( $term_language ) || '' === $term_language ) {
					$has_term_language = false;
				}
			}

			if ( $has_term_language ) {
				$link = get_term_link( $term_id, 'product_cat' );
				if ( ! is_wp_error( $link ) ) {
					return aitu_same_host_url( (string) $link );
				}
			}
		}
	}

	return $shop_url;
}

/**
 * Build product special label text.
 *
 * Priority:
 * 1) custom field `_aitu_label`
 * 2) first product tag
 * 3) default NEW
 *
 * @param int $product_id Product ID.
 * @return string
 */
function aitu_product_label( $product_id ) {
	$custom_label = trim( (string) get_post_meta( $product_id, '_aitu_label', true ) );
	if ( '' !== $custom_label ) {
		return strtoupper( $custom_label );
	}

	if ( taxonomy_exists( 'product_tag' ) ) {
		$terms = get_the_terms( $product_id, 'product_tag' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return strtoupper( $terms[0]->name );
		}
	}

	return 'NEW';
}

/**
 * Product meta key for size-info accordion content.
 *
 * @return string
 */
function aitu_product_size_info_meta_key() {
	return '_aitu_size_info_content';
}

/**
 * Render product size-info metabox.
 *
 * @param WP_Post $post Current product post.
 * @return void
 */
function aitu_render_product_size_info_metabox( $post ) {
	$value = (string) get_post_meta( $post->ID, aitu_product_size_info_meta_key(), true );
	wp_nonce_field( 'aitu_save_product_size_info', 'aitu_product_size_info_nonce' );
	?>
	<p style="margin-top:0;">
		<?php echo esc_html( 'Text/images shown in single product accordion (SIZE INFO / INFO O VEĽKOSTI).' ); ?>
	</p>
	<?php
	wp_editor(
		$value,
		'aitu_product_size_info_editor',
		array(
			'textarea_name' => 'aitu_product_size_info_content',
			'textarea_rows' => 8,
			'media_buttons' => true,
			'teeny'         => false,
			'quicktags'     => true,
		)
	);
}

/**
 * Register product metabox for size-info content.
 *
 * @return void
 */
function aitu_register_product_size_info_metabox() {
	add_meta_box(
		'aitu-product-size-info',
		'AITU - Size info / Info o veľkosti',
		'aitu_render_product_size_info_metabox',
		'product',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_product', 'aitu_register_product_size_info_metabox' );

/**
 * Save product size-info metabox content.
 *
 * @param int $post_id Product ID.
 * @return void
 */
function aitu_save_product_size_info_metabox( $post_id ) {
	if ( ! isset( $_POST['aitu_product_size_info_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aitu_product_size_info_nonce'] ) ), 'aitu_save_product_size_info' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$value = isset( $_POST['aitu_product_size_info_content'] ) ? wp_kses_post( wp_unslash( $_POST['aitu_product_size_info_content'] ) ) : '';

	if ( '' === trim( $value ) ) {
		delete_post_meta( $post_id, aitu_product_size_info_meta_key() );
		return;
	}

	update_post_meta( $post_id, aitu_product_size_info_meta_key(), $value );
}
add_action( 'save_post_product', 'aitu_save_product_size_info_metabox' );

/**
 * Format numeric price as "XX EUR".
 *
 * @param string|float|int $raw_price Raw product price.
 * @return string
 */
function aitu_format_price_eur( $raw_price ) {
	if ( '' === (string) $raw_price ) {
		return '';
	}

	$amount = (float) wc_format_decimal( $raw_price );
	// Keep whole product prices compact without rounding fractional prices.
	$decimals = abs( $amount - round( $amount ) ) < 0.000001 ? 0 : wc_get_price_decimals();
	return number_format_i18n( $amount, $decimals ) . ' EUR';
}

/**
 * Fallback product cards for homepage.
 *
 * @return array<int, array<string, string>>
 */
function aitu_home_fallback_cards() {
	return array(
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_1.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_2.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_3.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_4.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_5.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_6.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_4.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_5.jpg' ),
			'url'   => '#',
		),
		array(
			'title' => 'AITU Black-on-Black logo T-shirt',
			'price' => '38 EUR',
			'label' => 'NEW',
			'image' => get_theme_file_uri( '/assets/images/home_product_6.jpg' ),
			'url'   => '#',
		),
	);
}

/**
 * Get secondary card image from product gallery.
 *
 * @param WC_Product $product Product object.
 * @param string     $primary_image Primary image URL.
 * @return string
 */
function aitu_product_hover_image( $product, $primary_image = '' ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$gallery_ids = $product->get_gallery_image_ids();
	foreach ( $gallery_ids as $gallery_id ) {
		$gallery_url = wp_get_attachment_image_url( $gallery_id, '2048x2048' );
		if ( $gallery_url && $gallery_url !== $primary_image ) {
			return $gallery_url;
		}
	}

	return '';
}

/**
 * Normalize WooCommerce product to card data.
 *
 * @param WC_Product $product Product object.
 * @param int        $index Fallback index.
 * @return array<string, string>
 */
function aitu_build_product_card( $product, $index = 0 ) {
	$fallback_cards = aitu_home_fallback_cards();
	$fallback       = $fallback_cards[ $index % count( $fallback_cards ) ];

	if ( ! $product instanceof WC_Product ) {
		return $fallback;
	}

	$display_product = $product;
	$translated_id   = aitu_translate_post_id( $product->get_id() );
	if ( $translated_id > 0 && $translated_id !== (int) $product->get_id() ) {
		$translated_product = wc_get_product( $translated_id );
		if ( $translated_product instanceof WC_Product ) {
			$display_product = $translated_product;
		}
	}

	$image = '';
	if ( $display_product->get_image_id() ) {
		$image = wp_get_attachment_image_url( $display_product->get_image_id(), '2048x2048' );
	}
	if ( ! $image ) {
		$image = $fallback['image'];
	}
	$hover_image = aitu_product_hover_image( $display_product, $image );
	if ( ! $hover_image && ! empty( $fallback['hover_image'] ) ) {
		$hover_image = $fallback['hover_image'];
	}

	$label = aitu_product_label( $display_product->get_id() );

	$price = '';
	if ( '' !== $display_product->get_price() ) {
		$price = aitu_format_price_eur( $display_product->get_price() );
	}

	return array(
		'title'       => $display_product->get_name() ? $display_product->get_name() : $fallback['title'],
		'price'       => $price ? $price : $fallback['price'],
		'label'       => $label ? $label : $fallback['label'],
		'image'       => $image,
		'hover_image' => $hover_image,
		'url'         => get_permalink( $display_product->get_id() ),
	);
}

/**
 * Collect cards for homepage grid.
 *
 * @param int $limit Number of cards.
 * @return array<int, array<string, string>>
 */
function aitu_collect_home_cards( $limit = 9 ) {
	$cards         = array();
	$seen_products = array();
	$query_lang    = aitu_current_lang_slug();

	if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' ) ) {
		$query_args = array(
			'status' => 'publish',
			'limit'  => $limit * 3,
			'order'  => 'DESC',
		);
		if ( function_exists( 'pll_current_language' ) ) {
			$query_args['lang'] = $query_lang;
		}

		$products = wc_get_products( $query_args );

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$translated_id = aitu_translate_post_id( $product->get_id(), $query_lang );
			if ( $translated_id <= 0 || isset( $seen_products[ $translated_id ] ) ) {
				continue;
			}

			$translated_product = wc_get_product( $translated_id );
			if ( ! $translated_product instanceof WC_Product ) {
				$translated_product = $product;
			}
			if ( 'publish' !== $translated_product->get_status() || ! $translated_product->is_visible() ) {
				continue;
			}

			$cards[]                          = aitu_build_product_card( $translated_product, count( $cards ) );
			$seen_products[ $translated_id ] = true;

			if ( count( $cards ) >= $limit ) {
				break;
			}
		}
	}

	// Show only published products; never fill the shop with demo cards.

	return $cards;
}

/**
 * Homepage settings meta keys.
 *
 * @return array<string, string>
 */
function aitu_homepage_meta_keys() {
	return array(
		'product_ids'      => '_aitu_home_products',
		'banner_image_id'  => '_aitu_home_banner_image_id',
		'banner_link_url'  => '_aitu_home_banner_link_url',
	);
}

/**
 * Normalize homepage product slots and preserve slot positions.
 *
 * @param mixed $raw_products Raw slots payload.
 * @param int   $max_slots Maximum slot count.
 * @return array<int, int>
 */
function aitu_homepage_normalize_product_slots( $raw_products, $max_slots = 9 ) {
	$max_slots = max( 1, (int) $max_slots );

	if ( is_string( $raw_products ) && '' !== trim( $raw_products ) ) {
		$raw_products = preg_split( '/\s*,\s*/', $raw_products );
	}
	if ( ! is_array( $raw_products ) ) {
		$raw_products = array();
	}

	$slots = array();
	for ( $slot = 0; $slot < $max_slots; $slot++ ) {
		$value = isset( $raw_products[ $slot ] ) ? $raw_products[ $slot ] : 0;
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = 0;
		}
		$slots[ $slot ] = absint( $value );
	}

	return $slots;
}

/**
 * Resolve all page IDs where homepage settings should be available.
 *
 * @return array<int, int>
 */
function aitu_homepage_settings_page_ids() {
	$front_page_id = (int) get_option( 'page_on_front' );
	if ( $front_page_id <= 0 ) {
		return array();
	}

	$page_ids = array( $front_page_id );

	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( $front_page_id );
		if ( is_array( $translations ) ) {
			foreach ( $translations as $translated_id ) {
				$translated_id = (int) $translated_id;
				if ( $translated_id > 0 ) {
					$page_ids[] = $translated_id;
				}
			}
		}
	} elseif ( function_exists( 'pll_get_post' ) ) {
		foreach ( array( 'en', 'sk' ) as $lang_slug ) {
			$translated_id = (int) pll_get_post( $front_page_id, $lang_slug );
			if ( $translated_id > 0 ) {
				$page_ids[] = $translated_id;
			}
		}
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $page_ids )
			)
		)
	);
}

/**
 * Check whether current page is homepage settings page.
 *
 * @param int $post_id Page ID.
 * @return bool
 */
function aitu_is_homepage_settings_page( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}

	return in_array( $post_id, aitu_homepage_settings_page_ids(), true );
}

/**
 * Resolve homepage settings page ID in current language context.
 *
 * @return int
 */
function aitu_homepage_settings_page_id() {
	$queried_id = (int) get_queried_object_id();
	if ( $queried_id > 0 && aitu_is_homepage_settings_page( $queried_id ) ) {
		return $queried_id;
	}

	$front_page_id = (int) get_option( 'page_on_front' );
	if ( $front_page_id <= 0 ) {
		return 0;
	}

	$translated_front = aitu_translate_post_id( $front_page_id, aitu_current_lang_slug() );
	if ( $translated_front > 0 ) {
		return $translated_front;
	}

	return $front_page_id;
}

/**
 * Get manually selected homepage product IDs.
 *
 * @param int $post_id Optional page ID.
 * @return array<int, int>
 */
function aitu_homepage_selected_product_ids( $post_id = 0 ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		$post_id = aitu_homepage_settings_page_id();
	}

	$meta_keys       = aitu_homepage_meta_keys();
	$raw_product_ids = $post_id > 0 ? get_post_meta( $post_id, $meta_keys['product_ids'], true ) : array();
	return aitu_homepage_normalize_product_slots( $raw_product_ids );
}

/**
 * Build homepage cards from manually selected product IDs.
 *
 * @param array<int, int> $product_ids Product IDs.
 * @param int             $limit Number of cards.
 * @return array<int, array<string, string>>
 */
function aitu_collect_home_cards_from_ids( $product_ids, $limit = 9 ) {
	$limit      = max( 1, (int) $limit );
	$query_lang = aitu_current_lang_slug();
	$cards      = array();
	$seen_ids   = array();
	$seen_urls  = array();

	foreach ( (array) $product_ids as $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			continue;
		}

		$mapped_id = aitu_translate_post_id( $product_id, $query_lang );
		if ( $mapped_id <= 0 ) {
			$mapped_id = $product_id;
		}
		if ( isset( $seen_ids[ $mapped_id ] ) ) {
			continue;
		}

		$product = wc_get_product( $mapped_id );
		if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			continue;
		}

		$seen_ids[ $mapped_id ] = true;
		$card                   = aitu_build_product_card( $product, count( $cards ) );
		$card_url               = isset( $card['url'] ) ? (string) $card['url'] : '';
		if ( '' !== $card_url ) {
			$seen_urls[ $card_url ] = true;
		}
		$cards[] = $card;

		if ( count( $cards ) >= $limit ) {
			return $cards;
		}
	}

	$fallback_cards = aitu_collect_home_cards( $limit );
	foreach ( $fallback_cards as $fallback_card ) {
		if ( count( $cards ) >= $limit ) {
			break;
		}

		$fallback_url = isset( $fallback_card['url'] ) ? (string) $fallback_card['url'] : '';
		if ( '' !== $fallback_url && isset( $seen_urls[ $fallback_url ] ) ) {
			continue;
		}

		if ( '' !== $fallback_url ) {
			$seen_urls[ $fallback_url ] = true;
		}
		$cards[] = $fallback_card;
	}

	return array_slice( $cards, 0, $limit );
}

/**
 * Normalize text for case-insensitive partial search matching.
 *
 * @param string $text Raw text.
 * @return string
 */
function aitu_normalize_search_text( $text ) {
	$normalized = strtolower( remove_accents( (string) $text ) );
	$normalized = preg_replace( '/\s+/', ' ', $normalized );
	return trim( (string) $normalized );
}

/**
 * Collect product cards for search term with robust partial matching.
 *
 * @param string $search_term Search term.
 * @param int    $limit Max cards.
 * @return array<int, array<string, string>>
 */
function aitu_collect_search_cards( $search_term, $limit = 60 ) {
	$term = trim( (string) $search_term );
	if ( '' === $term || ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$needle        = aitu_normalize_search_text( $term );
	$seen_products = array();
	$cards         = array();
	$limit         = max( 1, absint( $limit ) );

	$append_matches = static function ( $products ) use ( &$cards, &$seen_products, $needle, $limit ) {
		if ( ! is_array( $products ) ) {
			return;
		}

		foreach ( $products as $product ) {
			if ( count( $cards ) >= $limit ) {
				return;
			}

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$product_id = (int) $product->get_id();
			if ( $product_id <= 0 || isset( $seen_products[ $product_id ] ) ) {
				continue;
			}

			$display_product = $product;
			$translated_id   = aitu_translate_post_id( $product_id );
			if ( $translated_id > 0 && $translated_id !== $product_id ) {
				$translated_product = wc_get_product( $translated_id );
				if ( $translated_product instanceof WC_Product ) {
					$display_product = $translated_product;
				}
			}

			$display_id = (int) $display_product->get_id();
			if ( $display_id <= 0 || isset( $seen_products[ $display_id ] ) ) {
				continue;
			}

			$post_content = '';
			$post_obj     = get_post( $display_id );
			if ( $post_obj instanceof WP_Post ) {
				$post_content = wp_strip_all_tags( (string) $post_obj->post_content );
			}

			$haystack = aitu_normalize_search_text(
				$display_product->get_name() . ' ' . $display_product->get_slug() . ' ' . $post_content
			);

			if ( '' === $haystack || false === strpos( $haystack, $needle ) ) {
				continue;
			}

			$cards[]                    = aitu_build_product_card( $display_product, count( $cards ) );
			$seen_products[ $display_id ] = true;
			$seen_products[ $product_id ] = true;
		}
	};

	$query_args = array(
		'status' => 'publish',
		'limit'  => max( $limit * 8, 120 ),
		'order'  => 'DESC',
	);
	if ( function_exists( 'pll_current_language' ) ) {
		$query_args['lang'] = aitu_current_lang_slug();
	}
	$append_matches( wc_get_products( $query_args ) );

	// Fallback: if nothing matched in current language, scan all languages.
	if ( empty( $cards ) && function_exists( 'pll_current_language' ) ) {
		unset( $query_args['lang'] );
		$append_matches( wc_get_products( $query_args ) );
	}

	return $cards;
}

/**
 * Resolve current search term from query vars/URL.
 *
 * @return string
 */
function aitu_current_search_term() {
	$term = get_query_var( 's' );
	if ( ! is_string( $term ) || '' === trim( $term ) ) {
		$term = isset( $_GET['s'] ) ? (string) wp_unslash( $_GET['s'] ) : '';
	}

	return trim( (string) $term );
}

/**
 * Render search results markup with AITU product card design.
 *
 * @param string $search_term Search term.
 * @return string
 */
function aitu_render_search_results_markup( $search_term ) {
	$query_text = trim( (string) $search_term );
	$cards      = aitu_collect_search_cards( $query_text, 120 );

	ob_start();
	?>
	<div class="aitu-search-results">
		<header class="aitu-search-results-head">
			<h1><?php echo esc_html( aitu_t( 'SEARCH RESULTS', 'VÝSLEDKY VYHĽADÁVANIA' ) ); ?></h1>
			<?php if ( '' !== $query_text ) : ?>
				<p class="aitu-search-results-query"><?php echo esc_html( sprintf( (string) aitu_t( 'FOR: %s', 'PRE: %s' ), $query_text ) ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $cards ) ) : ?>
			<div class="aitu-home-grid aitu-search-grid">
				<?php foreach ( $cards as $card ) : ?>
					<?php
					$hover_image     = ! empty( $card['hover_image'] ) ? (string) $card['hover_image'] : '';
					$has_hover_image = '' !== $hover_image && $hover_image !== $card['image'];
					?>
					<article class="aitu-product-card">
						<a class="aitu-product-card-image<?php echo $has_hover_image ? ' has-hover-image' : ''; ?>" href="<?php echo esc_url( $card['url'] ); ?>">
							<img class="aitu-product-card-image-primary" src="<?php echo esc_url( $card['image'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
							<?php if ( $has_hover_image ) : ?>
								<img class="aitu-product-card-image-secondary" src="<?php echo esc_url( $hover_image ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
							<?php endif; ?>
						</a>
						<div class="aitu-product-card-meta">
							<h2 class="aitu-product-card-title"><a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h2>
							<p class="aitu-product-card-price"><?php echo esc_html( $card['price'] ); ?></p>
							<?php if ( ! empty( $card['label'] ) ) : ?>
								<p class="aitu-product-card-label"><?php echo esc_html( $card['label'] ); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="aitu-search-empty"><?php echo esc_html( aitu_t( 'NO PRODUCTS FOUND.', 'NENAŠLI SA ŽIADNE PRODUKTY.' ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Resolve homepage cards with admin-selected products fallback.
 *
 * @param int $limit Number of cards.
 * @param int $post_id Optional homepage page ID.
 * @return array<int, array<string, string>>
 */
function aitu_resolve_homepage_cards( $limit = 9, $post_id = 0 ) {
	$selected_ids = aitu_homepage_selected_product_ids( $post_id );
	if ( empty( array_filter( $selected_ids ) ) ) {
		return aitu_collect_home_cards( $limit );
	}

	return aitu_collect_home_cards_from_ids( $selected_ids, $limit );
}

/**
 * Resolve homepage banner data from admin settings.
 *
 * @param int $post_id Optional homepage page ID.
 * @return array<string, string>
 */
function aitu_homepage_banner_data( $post_id = 0 ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		$post_id = aitu_homepage_settings_page_id();
	}

	$meta_keys  = aitu_homepage_meta_keys();
	$banner_url = get_theme_file_uri( '/assets/images/banner_home.jpg' );
	$link_url   = '';

	if ( $post_id > 0 ) {
		$banner_image_id = absint( get_post_meta( $post_id, $meta_keys['banner_image_id'], true ) );
		$custom_banner   = $banner_image_id > 0 ? wp_get_attachment_image_url( $banner_image_id, 'full' ) : '';
		if ( $custom_banner ) {
			$banner_url = (string) $custom_banner;
		}

		$raw_link = trim( (string) get_post_meta( $post_id, $meta_keys['banner_link_url'], true ) );
		if ( '' !== $raw_link ) {
			$link_url = esc_url( $raw_link );
		}
	}

	return array(
		'image' => $banner_url,
		'link'  => $link_url,
	);
}

/**
 * Collect cards from explicit product IDs without auto-fallback fill.
 *
 * @param array<int, int> $product_ids Product IDs.
 * @param int             $limit Maximum cards.
 * @return array<int, array<string, string>>
 */
function aitu_collect_home_cards_exact_ids( $product_ids, $limit = 3 ) {
	$limit      = max( 1, (int) $limit );
	$query_lang = aitu_current_lang_slug();
	$cards      = array();
	$seen_ids   = array();

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $cards;
	}

	foreach ( (array) $product_ids as $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			continue;
		}

		$mapped_id = aitu_translate_post_id( $product_id, $query_lang );
		if ( $mapped_id <= 0 ) {
			$mapped_id = $product_id;
		}
		if ( isset( $seen_ids[ $mapped_id ] ) ) {
			continue;
		}

		$product = wc_get_product( $mapped_id );
		if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			continue;
		}

		$seen_ids[ $mapped_id ] = true;
		$cards[]                = aitu_build_product_card( $product, count( $cards ) );

		if ( count( $cards ) >= $limit ) {
			break;
		}
	}

	return $cards;
}

/**
 * Render compact product-row preview markup for Gutenberg editor.
 *
 * @param array<int, array<string, string>> $cards Product cards.
 * @return string
 */
function aitu_render_homepage_product_row_editor_preview( $cards ) {
	if ( empty( $cards ) ) {
		return '';
	}

	ob_start();
	?>
	<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;width:100%;">
		<?php foreach ( $cards as $card ) : ?>
			<div style="min-width:0;">
				<div style="aspect-ratio:1/1;overflow:hidden;background:#ffffff;">
					<img src="<?php echo esc_url( $card['image'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>" style="display:block;width:100%;height:100%;object-fit:cover;">
				</div>
				<div style="margin-top:8px;">
					<p style="margin:0;font-family:'IBM Plex Mono',monospace;font-size:13px;font-weight:500;text-transform:uppercase;line-height:1.3;"><?php echo esc_html( $card['title'] ); ?></p>
					<p style="margin:2px 0 0 0;font-family:'IBM Plex Mono',monospace;font-size:12px;color:#868686;"><?php echo esc_html( $card['price'] ); ?></p>
					<?php if ( ! empty( $card['label'] ) ) : ?>
						<p style="margin:2px 0 0 0;font-family:'IBM Plex Mono',monospace;font-size:12px;color:#868686;text-transform:uppercase;"><?php echo esc_html( $card['label'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render AITU Product Row Gutenberg block.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function aitu_render_homepage_product_row_block( $attributes ) {
	$raw_product_ids = isset( $attributes['productIds'] ) ? $attributes['productIds'] : array();
	$product_ids     = aitu_homepage_normalize_product_slots( $raw_product_ids, 3 );
	$cards           = aitu_collect_home_cards_exact_ids( $product_ids, 3 );
	$is_editor_preview = ! empty( $attributes['isEditorPreview'] );

	if ( empty( $cards ) ) {
		return '';
	}

	if ( $is_editor_preview ) {
		return aitu_render_homepage_product_row_editor_preview( $cards );
	}

	ob_start();
	?>
	<div class="aitu-home-grid aitu-home-grid-module">
		<?php foreach ( $cards as $card ) : ?>
			<?php
			$hover_image     = ! empty( $card['hover_image'] ) ? (string) $card['hover_image'] : '';
			$has_hover_image = '' !== $hover_image && $hover_image !== $card['image'];
			?>
			<article class="aitu-product-card">
				<a class="aitu-product-card-image<?php echo $has_hover_image ? ' has-hover-image' : ''; ?>" href="<?php echo esc_url( $card['url'] ); ?>">
					<img class="aitu-product-card-image-primary" src="<?php echo esc_url( $card['image'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
					<?php if ( $has_hover_image ) : ?>
						<img class="aitu-product-card-image-secondary" src="<?php echo esc_url( $hover_image ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
					<?php endif; ?>
				</a>
				<div class="aitu-product-card-meta">
					<h2 class="aitu-product-card-title"><a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h2>
					<p class="aitu-product-card-price"><?php echo esc_html( $card['price'] ); ?></p>
					<?php if ( ! empty( $card['label'] ) ) : ?>
						<p class="aitu-product-card-label"><?php echo esc_html( $card['label'] ); ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render AITU Banner Gutenberg block.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function aitu_render_homepage_banner_block( $attributes ) {
	$image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
	$image_url = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

	if ( ! $image_url && ! empty( $attributes['imageUrl'] ) ) {
		$image_url = esc_url_raw( (string) $attributes['imageUrl'] );
	}
	if ( ! $image_url ) {
		return '';
	}

	$link_url = ! empty( $attributes['linkUrl'] ) ? esc_url( (string) $attributes['linkUrl'] ) : '';
	if ( $link_url && wp_parse_url( $link_url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		$link_id = url_to_postid( $link_url );
		if ( $link_id > 0 && 'publish' === get_post_status( $link_id ) ) {
			$link_url = aitu_localized_post_url( $link_id );
		}
	}

	ob_start();
	if ( '' !== $link_url ) :
		?>
		<div class="aitu-banner aitu-banner-module">
			<a class="aitu-banner-link" href="<?php echo esc_url( $link_url ); ?>" aria-label="<?php echo esc_attr( aitu_t( 'Explore the AITU collection', 'Pozrieť kolekciu AITU' ) ); ?>">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="">
			</a>
		</div>
		<?php
	else :
		?>
		<div class="aitu-banner aitu-banner-module" aria-hidden="true">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="">
		</div>
		<?php
	endif;

	return (string) ob_get_clean();
}

/**
 * Register Gutenberg homepage modules.
 *
 * @return void
 */
function aitu_register_homepage_gutenberg_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$script_path    = get_template_directory() . '/assets/js/homepage-blocks.js';
	$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );
	$editor_style_path    = get_template_directory() . '/assets/css/homepage-blocks-editor.css';
	$editor_style_version = file_exists( $editor_style_path ) ? (string) filemtime( $editor_style_path ) : wp_get_theme()->get( 'Version' );

	wp_register_script(
		'aitu-homepage-blocks',
		get_template_directory_uri() . '/assets/js/homepage-blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
		$script_version,
		true
	);
	wp_register_style(
		'aitu-homepage-blocks-editor',
		get_template_directory_uri() . '/assets/css/homepage-blocks-editor.css',
		array(),
		$editor_style_version
	);

	$product_options = array();
	foreach ( aitu_homepage_product_selector_options() as $product_id => $label ) {
		$product_options[] = array(
			'value' => (int) $product_id,
			'label' => (string) $label,
		);
	}

	wp_localize_script(
		'aitu-homepage-blocks',
		'aituHomepageBlocksData',
		array(
			'products' => $product_options,
		)
	);

	register_block_type(
		'aitu/product-row',
		array(
			'api_version'     => 2,
			'editor_script'   => 'aitu-homepage-blocks',
			'editor_style'    => 'aitu-homepage-blocks-editor',
			'render_callback' => 'aitu_render_homepage_product_row_block',
			'attributes'      => array(
				'productIds' => array(
					'type'    => 'array',
					'default' => array( 0, 0, 0 ),
					'items'   => array(
						'type' => 'number',
					),
				),
				'isEditorPreview' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'supports'        => array(
				'html' => false,
			),
		)
	);

	register_block_type(
		'aitu/banner',
		array(
			'api_version'     => 2,
			'editor_script'   => 'aitu-homepage-blocks',
			'editor_style'    => 'aitu-homepage-blocks-editor',
			'render_callback' => 'aitu_render_homepage_banner_block',
			'attributes'      => array(
				'imageId'  => array(
					'type'    => 'number',
					'default' => 0,
				),
				'imageUrl' => array(
					'type'    => 'string',
					'default' => '',
				),
				'linkUrl'  => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'        => array(
				'html' => false,
			),
		)
	);
}
add_action( 'init', 'aitu_register_homepage_gutenberg_blocks' );

/**
 * Ensure AITU block editor styles are always loaded in Gutenberg.
 *
 * @return void
 */
function aitu_enqueue_homepage_gutenberg_editor_assets() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! wp_style_is( 'aitu-homepage-blocks-editor', 'registered' ) ) {
		$editor_style_path    = get_template_directory() . '/assets/css/homepage-blocks-editor.css';
		$editor_style_version = file_exists( $editor_style_path ) ? (string) filemtime( $editor_style_path ) : wp_get_theme()->get( 'Version' );
		wp_register_style(
			'aitu-homepage-blocks-editor',
			get_template_directory_uri() . '/assets/css/homepage-blocks-editor.css',
			array(),
			$editor_style_version
		);
	}

	wp_enqueue_style( 'aitu-homepage-blocks-editor' );
}
add_action( 'enqueue_block_editor_assets', 'aitu_enqueue_homepage_gutenberg_editor_assets' );

/**
 * Build options list for homepage product selectors.
 *
 * @return array<int, string>
 */
function aitu_homepage_product_selector_options() {
	$product_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$options = array();

	foreach ( $product_ids as $product_id ) {
		$product_id = (int) $product_id;
		$title      = trim( wp_strip_all_tags( get_the_title( $product_id ) ) );
		if ( '' === $title ) {
			$title = sprintf( 'Product #%d', $product_id );
		}

		$lang_suffix = '';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang_slug = (string) pll_get_post_language( $product_id, 'slug' );
			if ( '' !== $lang_slug ) {
				$lang_suffix = ' [' . strtoupper( $lang_slug ) . ']';
			}
		}

		$status = get_post_status( $product_id );
		if ( is_string( $status ) && 'publish' !== $status ) {
			$status_label = strtoupper( $status );
			$lang_suffix .= ' {' . $status_label . '}';
		}

		$options[ $product_id ] = $title . ' (#' . $product_id . ')' . $lang_suffix;
	}

	return $options;
}

/**
 * Fallback gallery images for single product page.
 *
 * @return array<int, string>
 */
function aitu_single_gallery_fallback() {
	return array(
		get_theme_file_uri( '/assets/images/single_gallery_1.jpg' ),
		get_theme_file_uri( '/assets/images/single_gallery_2.jpg' ),
		get_theme_file_uri( '/assets/images/single_gallery_3.jpg' ),
		get_theme_file_uri( '/assets/images/single_gallery_4.jpg' ),
		get_theme_file_uri( '/assets/images/single_gallery_5.png' ),
	);
}

/**
 * Fallback related images for single product page.
 *
 * @return array<int, string>
 */
function aitu_related_fallback_images() {
	return array(
		get_theme_file_uri( '/assets/images/single_related_1.jpg' ),
		get_theme_file_uri( '/assets/images/single_related_2.jpg' ),
		get_theme_file_uri( '/assets/images/single_related_3.jpg' ),
		get_theme_file_uri( '/assets/images/single_related_4.jpg' ),
		get_theme_file_uri( '/assets/images/single_related_5.jpg' ),
	);
}

/**
 * Build related cards for cart recommendations.
 *
 * @param int $limit Maximum number of cards.
 * @return array<int, array<string, string>>
 */
function aitu_collect_cart_related_cards( $limit = 3 ) {
	$limit = max( 1, (int) $limit );
	$cards      = array();
	$seen       = array();
	$query_lang = aitu_current_lang_slug();

	if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			foreach ( (array) $product->get_cross_sell_ids() as $cross_sell_id ) {
				$cross_sell_id = aitu_translate_post_id( (int) $cross_sell_id, $query_lang );
				if ( $cross_sell_id <= 0 || isset( $seen[ $cross_sell_id ] ) ) {
					continue;
				}

				$cross_sell_product = wc_get_product( $cross_sell_id );
				if ( ! $cross_sell_product instanceof WC_Product ) {
					continue;
				}
				if ( ! $cross_sell_product->is_visible() || 'publish' !== $cross_sell_product->get_status() ) {
					continue;
				}

				$seen[ $cross_sell_id ] = true;
				$cards[]                = aitu_build_product_card( $cross_sell_product, count( $cards ) );

				if ( count( $cards ) >= $limit ) {
					break 2;
				}
			}
		}
	}

	if ( count( $cards ) < $limit && class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' ) ) {
		$recent_query_args = array(
			'status' => 'publish',
			'limit'  => $limit * 6,
			'order'  => 'DESC',
		);
		if ( function_exists( 'pll_current_language' ) ) {
			$recent_query_args['lang'] = $query_lang;
		}

		$recent_products = wc_get_products( $recent_query_args );

		foreach ( $recent_products as $recent_product ) {
			if ( ! $recent_product instanceof WC_Product ) {
				continue;
			}

			$recent_id = aitu_translate_post_id( (int) $recent_product->get_id(), $query_lang );
			if ( $recent_id <= 0 || isset( $seen[ $recent_id ] ) ) {
				continue;
			}
			$mapped_recent_product = wc_get_product( $recent_id );
			if ( ! $mapped_recent_product instanceof WC_Product ) {
				$mapped_recent_product = $recent_product;
			}
			if ( ! $mapped_recent_product->is_visible() || 'publish' !== $mapped_recent_product->get_status() ) {
				continue;
			}

			$seen[ $recent_id ] = true;
			$cards[]            = aitu_build_product_card( $mapped_recent_product, count( $cards ) );

			if ( count( $cards ) >= $limit ) {
				break;
			}
		}
	}

	if ( empty( $cards ) ) {
		$fallback_cards = aitu_collect_home_cards( $limit );
		return array_slice( $fallback_cards, 0, $limit );
	}

	return array_slice( $cards, 0, $limit );
}

/**
 * Render AITU related cards section markup.
 *
 * @param array<int, array<string, string>> $cards Product cards.
 * @param string                             $title Section heading.
 * @param string                             $extra_class Extra wrapper class.
 * @return string
 */
function aitu_render_related_cards_section( $cards, $title = '', $extra_class = '' ) {
	if ( empty( $cards ) ) {
		return '';
	}

	if ( '' === trim( (string) $title ) ) {
		$title = aitu_t( 'new in store', 'novinky v obchode' );
	}

	ob_start();
	?>
	<div class="aitu-related-section <?php echo esc_attr( trim( (string) $extra_class ) ); ?>">
		<h2 class="aitu-related-title"><?php echo esc_html( $title ); ?></h2>
		<div class="aitu-related-grid">
			<?php foreach ( $cards as $card ) : ?>
				<?php
				$hover_image     = ! empty( $card['hover_image'] ) ? (string) $card['hover_image'] : '';
				$has_hover_image = '' !== $hover_image && $hover_image !== $card['image'];
				?>
				<div class="aitu-related-item">
					<a class="aitu-related-card<?php echo $has_hover_image ? ' has-hover-image' : ''; ?>" href="<?php echo esc_url( $card['url'] ); ?>">
						<img class="aitu-related-card-image-primary" src="<?php echo esc_url( $card['image'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
						<?php if ( $has_hover_image ) : ?>
							<img class="aitu-related-card-image-secondary" src="<?php echo esc_url( $hover_image ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
						<?php endif; ?>
					</a>
					<div class="aitu-related-meta">
						<h3 class="aitu-related-name"><a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h3>
						<p class="aitu-related-price"><?php echo esc_html( $card['price'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}
/**
 * Detect current frontend path (normalized, no trailing slash except root).
 *
 * @return string
 */
function aitu_current_request_path() {
	$request_path = '';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	}
	if ( '' === $request_path ) {
		return '/';
	}

	$normalized = untrailingslashit( $request_path );
	return '' === $normalized ? '/' : $normalized;
}

/**
 * Determine if current route is checkout page using multiple safe checks.
 *
 * @return bool
 */
function aitu_is_checkout_route() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return false;
		}

		return true;
	}

	return aitu_is_current_wc_page( 'checkout' );
}

/**
 * Determine if current route is cart page using multiple safe checks.
 *
 * @return bool
 */
function aitu_is_cart_route() {
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( aitu_is_current_wc_page( 'cart' ) ) {
		return true;
	}

	$current_path = aitu_current_request_path();
	foreach ( array( 'en', 'sk' ) as $lang_slug ) {
		$cart_url  = aitu_localized_wc_page_url( 'cart', '', $lang_slug );
		$cart_path = (string) wp_parse_url( $cart_url, PHP_URL_PATH );
		$cart_path = '' === $cart_path ? '/' : untrailingslashit( $cart_path );
		if ( '' === $cart_path ) {
			$cart_path = '/';
		}

		if ( $current_path === $cart_path ) {
			return true;
		}
	}

	return false;
}

/**
 * Localize cart empty text and cart headings when Woo outputs hardcoded EN strings.
 *
 * @param string $translated_text Translated text.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return string
 */
function aitu_localize_woocommerce_gettext( $translated_text, $text, $domain ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $translated_text;
	}

	if ( 'woocommerce' !== $domain ) {
		return $translated_text;
	}

	if ( ! aitu_is_slovak_context() ) {
		return $translated_text;
	}

	$normalized_text = strtolower( trim( (string) $text ) );
	$map             = array(
		'cart'                           => 'KOŠÍK',
		'your cart is currently empty!'  => 'VÁŠ KOŠÍK JE MOMENTÁLNE PRÁZDNY!',
		'your cart is currently empty.'  => 'VÁŠ KOŠÍK JE MOMENTÁLNE PRÁZDNY!',
		'new in store'                   => 'NOVINKY V OBCHODE',
	);

	if ( isset( $map[ $normalized_text ] ) ) {
		return $map[ $normalized_text ];
	}

	return $translated_text;
}
add_filter( 'gettext', 'aitu_localize_woocommerce_gettext', 30, 3 );

/**
 * Remove default Woo "New in store" blocks from cart page.
 * Keep only the custom AITU related section rendered in page.php.
 *
 * @param string               $block_content Rendered block HTML.
 * @param array<string, mixed> $parsed_block Block data.
 * @return string
 */
function aitu_remove_default_cart_new_in_store_blocks( $block_content, $parsed_block ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $block_content;
	}

	if ( ! aitu_is_cart_route() ) {
		return $block_content;
	}

	$block_name = isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';
	$remove     = array(
		'woocommerce/cart-cross-sells-block',
		'woocommerce/cart-cross-sells',
		'woocommerce/cart-cross-sells-products-block',
		'woocommerce/product-collection',
		'woocommerce/product-new',
	);

	if (
		in_array( $block_name, $remove, true ) ||
		false !== strpos( $block_name, 'cross-sells' ) ||
		0 === strpos( $block_name, 'woocommerce/product-collection' ) ||
		0 === strpos( $block_name, 'woocommerce/product-new' )
	) {
		return '';
	}

	if ( 'core/heading' === $block_name ) {
		$heading_text = '';
		if ( isset( $parsed_block['attrs']['content'] ) && is_string( $parsed_block['attrs']['content'] ) ) {
			$heading_text = strtolower( trim( wp_strip_all_tags( $parsed_block['attrs']['content'] ) ) );
		}

		if ( in_array( $heading_text, array( 'new in store', 'novinky v obchode' ), true ) ) {
			return '';
		}
	}

	if ( 'core/separator' === $block_name ) {
		$class_name = '';
		if ( isset( $parsed_block['attrs']['className'] ) && is_string( $parsed_block['attrs']['className'] ) ) {
			$class_name = strtolower( $parsed_block['attrs']['className'] );
		}

		if ( false !== strpos( $class_name, 'is-style-dots' ) ) {
			return '';
		}
	}

	if ( false !== stripos( (string) $block_content, 'wc-block-cart__cross-sells' ) ) {
		return '';
	}

	if (
		( false !== stripos( (string) $block_content, 'new in store' ) || false !== stripos( (string) $block_content, 'novinky v obchode' ) ) &&
		false === stripos( (string) $block_content, 'aitu-cart-related-section' )
	) {
		return '';
	}

	if ( aitu_is_slovak_context() && is_string( $block_content ) && '' !== $block_content ) {
		$block_content = str_ireplace( '>CART<', '>KOŠÍK<', $block_content );
		$block_content = str_ireplace( 'YOUR CART IS CURRENTLY EMPTY!', 'VÁŠ KOŠÍK JE MOMENTÁLNE PRÁZDNY!', $block_content );
		$block_content = str_ireplace( 'YOUR CART IS CURRENTLY EMPTY.', 'VÁŠ KOŠÍK JE MOMENTÁLNE PRÁZDNY!', $block_content );
	}

	return $block_content;
}
add_filter( 'render_block', 'aitu_remove_default_cart_new_in_store_blocks', 30, 2 );

/**
 * Hard-disable legacy Woo product recommendation blocks on Cart page.
 *
 * @param string $block_content Rendered block HTML.
 * @return string
 */
function aitu_disable_legacy_cart_product_block_render( $block_content ) {
	if ( ! aitu_is_cart_route() ) {
		return $block_content;
	}

	return '';
}
add_filter( 'render_block_woocommerce/product-new', 'aitu_disable_legacy_cart_product_block_render', 30, 1 );
add_filter( 'render_block_woocommerce/product-collection', 'aitu_disable_legacy_cart_product_block_render', 30, 1 );
add_filter( 'render_block_woocommerce/cart-cross-sells', 'aitu_disable_legacy_cart_product_block_render', 30, 1 );
add_filter( 'render_block_woocommerce/cart-cross-sells-block', 'aitu_disable_legacy_cart_product_block_render', 30, 1 );
add_filter( 'render_block_woocommerce/cart-cross-sells-products-block', 'aitu_disable_legacy_cart_product_block_render', 30, 1 );

/**
 * Remove legacy cart product blocks directly from cart page content.
 * This ensures editor-inserted blocks (e.g. woocommerce/product-new) never render.
 *
 * @param string $content Raw post content.
 * @return string
 */
function aitu_strip_legacy_cart_product_blocks_from_content( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $content;
	}

	if ( ! aitu_is_cart_route() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_blocks' ) ) {
		return $content;
	}

	$blocks = parse_blocks( $content );
	if ( empty( $blocks ) || ! is_array( $blocks ) ) {
		return $content;
	}

	$remove_names = array(
		'woocommerce/product-new',
		'woocommerce/product-collection',
		'woocommerce/cart-cross-sells-block',
		'woocommerce/cart-cross-sells-products-block',
		'woocommerce/cart-cross-sells',
	);

	$filtered = array();
	foreach ( $blocks as $block ) {
		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( in_array( $block_name, $remove_names, true ) ) {
			continue;
		}

		if ( 'core/heading' === $block_name ) {
			$heading_text = '';
			if ( isset( $block['attrs']['content'] ) && is_string( $block['attrs']['content'] ) ) {
				$heading_text = strtolower( trim( wp_strip_all_tags( $block['attrs']['content'] ) ) );
			}

			if ( in_array( $heading_text, array( 'new in store', 'novinky v obchode' ), true ) ) {
				continue;
			}
		}

		if ( 'core/separator' === $block_name ) {
			$class_name = '';
			if ( isset( $block['attrs']['className'] ) && is_string( $block['attrs']['className'] ) ) {
				$class_name = strtolower( $block['attrs']['className'] );
			}

			if ( false !== strpos( $class_name, 'is-style-dots' ) ) {
				continue;
			}
		}

		$filtered[] = $block;
	}

	return serialize_blocks( $filtered );
}
add_filter( 'the_content', 'aitu_strip_legacy_cart_product_blocks_from_content', 8 );


/** Keep the stored attribute slug stable for existing variations and orders. */
add_filter( 'woocommerce_attribute_label', function ( $label, $name ) {
	return in_array( $name, array( 'pa_vellkost', 'vellkost' ), true ) ? aitu_t( 'Size', 'Veľkosť' ) : $label;
}, 20, 2 );

/** Correct legacy /en/ links when English uses the site's root URL. */
add_action( 'template_redirect', function () {
	if ( ! is_admin() && '/en' === untrailingslashit( aitu_current_request_path() ) ) {
		$home = aitu_language_home_url( 'en' );
		if ( '/en' !== untrailingslashit( (string) wp_parse_url( $home, PHP_URL_PATH ) ) ) {
			wp_safe_redirect( $home, 301 );
			exit;
		}
	}
}, 3 );

/** Basic metadata for the custom storefront; product schema comes from WooCommerce. */
add_action( 'wp_head', function () {
	if ( is_404() || is_search() || is_feed() || aitu_is_cart_route() || aitu_is_checkout_route() || aitu_is_current_wc_page( 'myaccount' ) ) {
		return;
	}
	$description = '';
	$image = '';
	$url = '';
	if ( is_singular() ) {
		$url = get_permalink();
		$description = has_excerpt() ? get_the_excerpt() : get_post_field( 'post_content', get_queried_object_id() );
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	} elseif ( is_product_taxonomy() ) {
		$url = get_term_link( get_queried_object() );
		$description = term_description();
	}
	if ( is_front_page() || is_shop() || aitu_is_current_wc_page( 'shop' ) || ! trim( wp_strip_all_tags( (string) $description ) ) ) {
		$description = aitu_t(
			'Original AITU T-shirts with an oversize fit. Discover AITU artwork. Explore the first collection and wear it your way.',
			'Originálne AITU tričká s oversize strihom. S originálnym dizajnom AITU. Objav prvú kolekciu a nos ju po svojom.'
		);
	}
	if ( is_front_page() ) {
		$url = aitu_language_home_url();
	} elseif ( is_shop() ) {
		$url = aitu_localized_post_url( wc_get_page_id( 'shop' ) );
	}
	$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $description ) ) ) );
	$description = wp_html_excerpt( $description, 165, '…' );
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return;
	}
	// WordPress already emits the canonical for singular pages.
	if ( ! is_singular() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	echo '<meta property="og:type" content="' . ( is_product() ? 'product' : 'website' ) . '">' . "\n";
	echo '<meta property="og:site_name" content="AITU">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
}, 6 );

add_filter( 'get_canonical_url', function ( $url, $post ) {
	return is_front_page() ? aitu_language_home_url() : $url;
}, 20, 2 );


/** Link only to completed, published information pages in the selected language. */
function aitu_info_page_url( $key ) {
	$pages = get_option( 'aitu_info_pages', array() );
	$id = isset( $pages[ $key ][ aitu_current_lang_slug() ] ) ? absint( $pages[ $key ][ aitu_current_lang_slug() ] ) : 0;
	if ( ! $id && 'terms' === $key ) { $id = (int) get_option( 'woocommerce_terms_page_id' ); }
	if ( ! $id && 'privacy' === $key ) { $id = (int) get_option( 'wp_page_for_privacy_policy' ); }
	if ( $id ) { $id = aitu_translate_post_id( $id ); }
	return $id > 0 && 'publish' === get_post_status( $id ) ? aitu_same_host_url( get_permalink( $id ) ) : '';
}
