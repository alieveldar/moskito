<?php
/**
 * Template Name: Tours list
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   1.0.0
 */

get_header();

$sidebar_content = '';
if ( adventure_tours_get_option( 'tours_archive_show_sidebar', 1 ) ) {
	ob_start();
	do_action( 'woocommerce_sidebar' );
	$sidebar_content = ob_get_clean();
}
global $wp_query;
$wp_query->set('posty_type','product');
$is_first_page = get_query_var( 'paged' ) < 2;
$cat_term = is_tax() ? get_queried_object() : null;

$display_mode = AtTourHelper::get_tour_archive_page_display_mode( $cat_term ? $cat_term->term_id : 0 );
$categories_render_allowed = $is_first_page && in_array( $display_mode, array( 'both', 'subcategories' ) );
?>

<?php ob_start(); ?>

	<?php if ( have_posts() ) : ?>
		<?php
			$tours_display_style = $need_show_tours
				? apply_filters( 'adventure_tours_get_tours_page_display_style', adventure_tours_get_option( 'tours_archive_display_style' ) )
				: '';
			do_action( 'adventure_tours_before_tours_loop' );

			if ('grid' == $tours_display_style){
				get_template_part( 'templates/tour/loop-grid' );
			} else {
				get_template_part( 'templates/tour/loop-list' );
			}

			do_action( 'adventure_tours_after_tours_loop' );
		endif; ?>
<?php $primary_content = ob_get_clean();  ?>

<?php adventure_tours_render_template_part('templates/layout', '', array(
	'content' => $primary_content,
	'sidebar' => $sidebar_content,
)); ?>

<?php get_footer(); ?>
