<?php
/**
 * Custom single product content (Figma 623:310).
 *
 * @package AITU_WooCommerce_Theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product instanceof WC_Product ) {
	return;
}

$main_image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), '2048x2048' ) : '';
if ( ! $main_image ) {
	$main_image = get_theme_file_uri( '/assets/images/single_main.jpg' );
}

$gallery_urls = array();
foreach ( $product->get_gallery_image_ids() as $image_id ) {
	$url = wp_get_attachment_image_url( $image_id, '2048x2048' );
	if ( $url ) {
		$gallery_urls[] = $url;
	}
}

$gallery_urls = array_values( array_unique( $gallery_urls ) );

$description = trim( wp_strip_all_tags( $product->get_description() ) );
if ( '' === $description ) {
	$description = trim( wp_strip_all_tags( $product->get_short_description() ) );
}
if ( '' === $description ) {
	$description = aitu_t(
		'Lorem Ipsum is simply dummy text of the printing and typesetting industry. It has survived not only five centuries, but also the leap into electronic typesetting.',
		'Lorem Ipsum je len ukážkový text tlačiarenského a sadzačského priemyslu. Používa sa na testovanie rozloženia a typografie.'
	);
}

$size_info_content = trim( (string) get_post_meta( $product->get_id(), aitu_product_size_info_meta_key(), true ) );
if ( '' === $size_info_content ) {
	$size_info_content  = '<p>' . esc_html( aitu_t( 'Placeholder: this is a sample size guide block for testing accordion open and close behavior.', 'Ukážka: toto je testovací blok veľkostnej tabuľky na overenie otvárania a zatvárania akordeónu.' ) ) . '</p>';
	$size_info_content .= '<p>' . esc_html( aitu_t( 'Chest: measure around the fullest part. Length: measure from shoulder to hem. If between sizes, choose the larger size for a relaxed fit.', 'Hrudník: merajte v najširšom mieste. Dĺžka: merajte od ramena po spodný lem. Ak ste medzi veľkosťami, zvoľte väčšiu pre voľnejší fit.' ) ) . '</p>';
}

$has_product_variants = $product->is_type( 'variable' ) && ! empty( $product->get_variation_attributes() );

$related_cards = array();
if ( $product instanceof WC_Product ) {
	$related_ids = array_merge(
		(array) $product->get_upsell_ids(),
		(array) $product->get_cross_sell_ids()
	);
	$related_ids = array_values(
		array_unique(
			array_filter(
				array_map(
					function ( $related_id ) {
						return aitu_translate_post_id( absint( $related_id ) );
					},
					$related_ids
				)
			)
		)
	);
	$related_ids = array_values(
		array_filter(
			$related_ids,
			function ( $related_id ) use ( $product ) {
				return $related_id !== aitu_translate_post_id( (int) $product->get_id() );
			}
		)
	);

	foreach ( array_slice( $related_ids, 0, 4 ) as $related_id ) {
		$related_product = wc_get_product( $related_id );
		if ( ! $related_product instanceof WC_Product ) {
			continue;
		}
		if ( 'publish' !== $related_product->get_status() ) {
			continue;
		}

		$image = get_the_post_thumbnail_url( $related_id, 'full' );
		if ( ! $image ) {
			continue;
		}

		$related_price = aitu_format_price_eur( $related_product->get_price() );
		if ( '' === $related_price ) {
			$related_price = '38 EUR';
		}

		$related_cards[] = array(
			'url'         => get_permalink( $related_id ),
			'image'       => $image,
			'title'       => $related_product->get_name(),
			'price'       => $related_price,
			'hover_image' => aitu_product_hover_image( $related_product, $image ),
		);
	}
}
?>
<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
	<div class="aitu-woocommerce-notices" aria-live="polite">
		<?php wc_print_notices(); ?>
	</div>
<?php endif; ?>
<section class="aitu-single-product-page">
	<div class="aitu-single-hero">
		<div class="aitu-product-main-image">
			<img src="<?php echo esc_url( $main_image ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
		</div>

		<div class="aitu-product-info">
			<h1 class="aitu-product-title"><?php echo esc_html( $product->get_name() ); ?></h1>
			<p class="aitu-product-price"><?php echo esc_html( aitu_format_price_eur( $product->get_price() ) ); ?></p>
			<?php if ( $has_product_variants ) : ?>
				<p class="aitu-size-label"><?php echo esc_html( aitu_t( 'size', 'veľkosť' ) ); ?></p>
			<?php endif; ?>

			<div class="aitu-add-to-cart-wrap">
				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>

				<p class="aitu-description-label"><?php echo esc_html( aitu_t( 'description', 'popis' ) ); ?></p>
				<div class="aitu-product-description">
					<p><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="aitu-info-line"></div>
				<details class="aitu-size-guide-accordion">
					<summary class="aitu-muted-link aitu-size-guide-summary"><?php echo esc_html( aitu_t( 'size info', 'info o veľkosti' ) ); ?></summary>
					<div class="aitu-size-guide-content">
						<?php echo wp_kses_post( do_shortcode( $size_info_content ) ); ?>
					</div>
				</details>
				<div class="aitu-info-line"></div>
				<a class="aitu-muted-link" href="<?php echo esc_url( aitu_category_link( 't-shirts' ) ); ?>"><?php echo esc_html( aitu_t( 'all t-shirts', 'všetky tričká' ) ); ?> &#8594;</a>
			</div>
		</div>

	<?php if ( ! empty( $gallery_urls ) ) : ?>
		<div class="aitu-product-gallery-section">
			<div class="aitu-product-gallery-grid">
				<?php foreach ( $gallery_urls as $index => $gallery_url ) : ?>
					<div class="aitu-product-gallery-item item-<?php echo esc_attr( (string) ( $index + 1 ) ); ?>">
						<img src="<?php echo esc_url( $gallery_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

		<?php if ( ! empty( $related_cards ) ) : ?>
			<div class="aitu-related-section">
				<h2 class="aitu-related-title"><?php echo esc_html( aitu_t( 'you may also like', 'mohlo by sa vám páčiť' ) ); ?></h2>
				<div class="aitu-related-grid">
					<?php foreach ( $related_cards as $related_card ) : ?>
						<?php
						$hover_image     = ! empty( $related_card['hover_image'] ) ? (string) $related_card['hover_image'] : '';
						$has_hover_image = '' !== $hover_image && $hover_image !== $related_card['image'];
						?>
						<div class="aitu-related-item">
							<a class="aitu-related-card<?php echo $has_hover_image ? ' has-hover-image' : ''; ?>" href="<?php echo esc_url( $related_card['url'] ); ?>">
								<img class="aitu-related-card-image-primary" src="<?php echo esc_url( $related_card['image'] ); ?>" alt="<?php echo esc_attr( $related_card['title'] ); ?>">
								<?php if ( $has_hover_image ) : ?>
									<img class="aitu-related-card-image-secondary" src="<?php echo esc_url( $hover_image ); ?>" alt="<?php echo esc_attr( $related_card['title'] ); ?>">
								<?php endif; ?>
							</a>
							<div class="aitu-related-meta">
								<h3 class="aitu-related-name"><a href="<?php echo esc_url( $related_card['url'] ); ?>"><?php echo esc_html( $related_card['title'] ); ?></a></h3>
								<p class="aitu-related-price"><?php echo esc_html( $related_card['price'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
</section>
