<?php
/**
 * Fallback template.
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="aitu-default-content">
	<?php $search_term = function_exists( 'aitu_current_search_term' ) ? aitu_current_search_term() : ''; ?>
	<?php if ( '' !== $search_term && function_exists( 'aitu_render_search_results_markup' ) ) : ?>
		<?php echo aitu_render_search_results_markup( $search_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php elseif ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<div><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php echo esc_html( aitu_t( 'NO CONTENT FOUND.', 'NENAŠIEL SA ŽIADNY OBSAH.' ) ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
