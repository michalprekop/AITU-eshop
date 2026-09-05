<?php
/**
 * WooCommerce archive template.
 *
 * @package AITU_WooCommerce_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$cards = array();

// A translated WooCommerce shop can be queried as a page by Polylang.
// Build its product listing explicitly instead of trying to render that page as a product.
if ( is_shop() || aitu_is_current_wc_page( 'shop' ) ) {
	$cards = aitu_collect_home_cards( 100 );
} elseif ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product && 'publish' === $product->get_status() ) {
			$cards[] = aitu_build_product_card( $product, count( $cards ) );
		}
	}
	wp_reset_postdata();
}

$first_cards  = array_slice( $cards, 0, 6 );
$second_cards = array_slice( $cards, 6 );
?>
<section class="aitu-homepage">
	<h1 class="screen-reader-text"><?php echo esc_html( is_product_taxonomy() ? single_term_title( '', false ) : aitu_t( 'AITU shop', 'AITU obchod' ) ); ?></h1>
	<?php if ( ! empty( $cards ) ) : ?>
		<div class="aitu-filter-row" aria-hidden="true">
			<span><?php echo esc_html( aitu_t( 'Filter', 'Filter' ) ); ?></span>
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/filter_arrow_home.svg' ) ); ?>" alt="">
		</div>

		<div class="aitu-home-grid aitu-home-grid-top">
			<?php foreach ( $first_cards as $card ) : ?>
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

		<?php if ( ! empty( $second_cards ) ) : ?>
			<div class="aitu-home-grid aitu-home-grid-bottom">
				<?php foreach ( $second_cards as $card ) : ?>
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
		<?php endif; ?>
	<?php else : ?>
		<div class="aitu-filter-row" aria-hidden="true">
			<span><?php echo esc_html( aitu_t( 'Filter', 'Filter' ) ); ?></span>
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/filter_arrow_home.svg' ) ); ?>" alt="">
		</div>
		<p class="aitu-catalogue-empty"><?php echo esc_html( aitu_t( 'There are no products in this category yet.', 'V tejto kategórii zatiaľ nie sú produkty.' ) ); ?> <a href="<?php echo esc_url( aitu_category_link( 't-shirts' ) ); ?>"><?php echo esc_html( aitu_t( 'Browse T-shirts', 'Pozrieť tričká' ) ); ?></a></p>
	<?php endif; ?>
</section>

<?php
get_footer( 'shop' );
