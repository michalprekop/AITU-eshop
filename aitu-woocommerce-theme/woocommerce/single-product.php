<?php
/**
 * WooCommerce single product wrapper.
 *
 * @package AITU_WooCommerce_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<?php while ( have_posts() ) : ?>
	<?php the_post(); ?>
	<?php wc_get_template_part( 'content', 'single-product' ); ?>
<?php endwhile; ?>

<?php
get_footer( 'shop' );
