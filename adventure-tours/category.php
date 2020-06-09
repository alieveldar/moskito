<?php
/**
 * Category details page template.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.2.0
 */

get_header();
?>

<?php if ( is_active_sidebar( 'sidebar' ) ) : ?>
<div class="row">
	<main class="col-md-9" role="main">
	<?php get_template_part( 'loop' ); ?>
	</main>
	<?php get_sidebar(); ?>
</div>
<?php else : ?>
	<?php if ( category_description() ) : ?>
		<div class="post-category__description padding-all margin-bottom"><?php echo category_description(); ?></div>
	<?php endif; ?>

	<?php get_template_part( 'loop' ); ?>
<?php endif; ?>

<?php get_footer(); ?>
