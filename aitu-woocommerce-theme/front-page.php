<?php
/**
 * Homepage template (Figma node 612:3).
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$search_term = function_exists( 'aitu_current_search_term' ) ? aitu_current_search_term() : '';
if ( '' !== $search_term ) {
	echo aitu_render_search_results_markup( $search_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	get_footer();
	return;
}

$homepage_settings_page_id = aitu_homepage_settings_page_id();
$homepage_post             = $homepage_settings_page_id > 0 ? get_post( $homepage_settings_page_id ) : null;
$homepage_post_content     = $homepage_post instanceof WP_Post ? trim( (string) $homepage_post->post_content ) : '';
$has_builder_content       = '' !== $homepage_post_content && has_blocks( $homepage_post_content );

if ( $has_builder_content ) :
	?>
	<section class="aitu-homepage aitu-homepage-builder">
		<?php echo apply_filters( 'the_content', $homepage_post_content ); ?>
	</section>
	<?php
	get_footer();
	return;
endif;

$cards        = aitu_resolve_homepage_cards( 9, $homepage_settings_page_id );
$first_cards  = array_slice( $cards, 0, 6 );
$second_cards = array_slice( $cards, 6, 3 );
$banner_data  = aitu_homepage_banner_data( $homepage_settings_page_id );
$banner_image = ! empty( $banner_data['image'] ) ? (string) $banner_data['image'] : get_theme_file_uri( '/assets/images/banner_home.jpg' );
$banner_link  = ! empty( $banner_data['link'] ) ? (string) $banner_data['link'] : '';
?>
<section class="aitu-homepage">
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

	<?php if ( '' !== $banner_link ) : ?>
		<div class="aitu-banner">
			<a class="aitu-banner-link" href="<?php echo esc_url( $banner_link ); ?>">
				<img src="<?php echo esc_url( $banner_image ); ?>" alt="">
			</a>
		</div>
	<?php else : ?>
		<div class="aitu-banner" aria-hidden="true">
			<img src="<?php echo esc_url( $banner_image ); ?>" alt="">
		</div>
	<?php endif; ?>

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
</section>
<?php
get_footer();
