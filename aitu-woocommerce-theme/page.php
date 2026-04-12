<?php
/**
 * Page template.
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_id' ) ) {
	$shop_page_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_page_id > 0 ) {
		$current_page_id = (int) get_queried_object_id();
		$shop_page_ids   = array( $shop_page_id );

		if ( function_exists( 'pll_get_post' ) ) {
			foreach ( array( 'en', 'sk' ) as $lang_slug ) {
				$translated_shop_id = (int) pll_get_post( $shop_page_id, $lang_slug );
				if ( $translated_shop_id > 0 ) {
					$shop_page_ids[] = $translated_shop_id;
				}
			}
		}

		$shop_page_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $shop_page_ids )
				)
			)
		);

		if ( $current_page_id > 0 && in_array( $current_page_id, $shop_page_ids, true ) ) {
			$archive_template = get_theme_file_path( '/woocommerce/archive-product.php' );
			if ( file_exists( $archive_template ) ) {
				require $archive_template;
				return;
			}
		}
	}
}

get_header();
?>
<div class="aitu-default-content">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php $is_checkout_page = aitu_is_current_wc_page( 'checkout' ); ?>
			<?php $is_cart_page = aitu_is_cart_route(); ?>
			<article <?php post_class(); ?>>
				<?php if ( ! $is_checkout_page ) : ?>
					<?php if ( $is_cart_page ) : ?>
						<h1><?php echo esc_html( aitu_t( 'CART', 'KOŠÍK' ) ); ?></h1>
					<?php else : ?>
						<h1><?php the_title(); ?></h1>
					<?php endif; ?>
				<?php endif; ?>
				<div><?php the_content(); ?></div>
				<?php
				if ( $is_cart_page ) {
					$cart_related_cards = aitu_collect_cart_related_cards( 3 );
					echo aitu_render_related_cards_section( $cart_related_cards, aitu_t( 'new in store', 'novinky v obchode' ), 'aitu-cart-related-section' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			?>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
