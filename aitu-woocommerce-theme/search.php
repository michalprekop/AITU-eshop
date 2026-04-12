<?php
/**
 * Search results template.
 *
 * @package AITU_WooCommerce_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$query_text = function_exists( 'aitu_current_search_term' ) ? aitu_current_search_term() : trim( (string) get_search_query() );

wp_reset_postdata();
?>
<?php echo function_exists( 'aitu_render_search_results_markup' ) ? aitu_render_search_results_markup( $query_text ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
get_footer();
