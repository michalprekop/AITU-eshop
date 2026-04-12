<?php
/**
 * WooCommerce fallback template.
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_shop_archive = function_exists( 'is_shop' ) && is_shop();
$is_product_tax  = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();

if ( $is_shop_archive || $is_product_tax ) {
	$archive_template = get_theme_file_path( '/woocommerce/archive-product.php' );
	if ( file_exists( $archive_template ) ) {
		require $archive_template;
		return;
	}
}

if ( function_exists( 'is_product' ) && is_product() ) {
	$single_template = get_theme_file_path( '/woocommerce/single-product.php' );
	if ( file_exists( $single_template ) ) {
		require $single_template;
		return;
	}
}

get_header();
?>
<div class="aitu-default-content">
	<?php
	while ( have_posts() ) :
		the_post();

		if ( function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'do_blocks' ) ) {
			$checkout_block_markup = '<!-- wp:woocommerce/checkout {"className":"alignwide"} /-->';
			$checkout_output       = do_blocks( $checkout_block_markup );

			if ( '' !== trim( (string) $checkout_output ) ) {
				echo $checkout_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				the_content();
			}
		} else {
			the_content();
		}
	endwhile;
	?>

	<?php
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		$cart_related_cards = aitu_collect_cart_related_cards( 3 );
		echo aitu_render_related_cards_section( $cart_related_cards, aitu_t( 'new in store', 'novinky v obchode' ), 'aitu-cart-related-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
<?php
get_footer();
