<?php
/**
 * Content template part.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.3.2
 */

$post_id = get_the_ID();
$thumb_id = get_post_thumbnail_id();
$thumbnail = $thumb_id ? adventure_tours_get_the_post_thumbnail( $post_id, 'thumb_single' ) : null;

$post_class = 'blog__item margin-bottom';
if ( ! $thumbnail ) {
	$post_class .= ' blog__item--without-image';
}

$is_single = is_single();
$permalink = get_permalink();
?>
<article id="<?php echo get_post_type() . '-' . $post_id; ?>" <?php post_class( $post_class ); ?> itemscope itemtype="http://schema.org/BlogPosting">
	<div class="blog__item__box">
	<?php if ( is_sticky() ) : ?>
		<div class="blog__item__sticky">
			<div class="blog__item__sticky__bg"><i class="fa fa-bookmark"></i></div>
			<div class="blog__item__sticky__content"><i class="fa fa-star"></i></div>
		</div>
	<?php endif; ?>
		<div class="blog__item__info padding-top">
		<?php if ( $is_single ) : ?>
			<div class="blog__item__title padding-left padding-right entry-title heading-text" itemprop="headline"><?php the_title(); ?></div>
		<?php else : ?>
			<h2 class="blog__item__title padding-left padding-right entry-title" itemprop="headline"><a href="<?php echo esc_url( $permalink ); ?>"><?php the_title(); ?></a></h2>
		<?php endif; ?>

		<?php get_template_part( 'templates/parts/article-info' ); ?>

		<?php if ( $permalink ) {
			printf( '<meta itemprop="url" content="%s">', esc_url( $permalink ) );
		} ?>

		<?php if ( $thumbnail ) {
			$thumb_src = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( $thumb_src ) {
				printf('<span itemprop="image" itemscope itemtype="http://schema.org/ImageObject"><meta itemprop="url" content="%s"><meta itemprop="width" content="%s"><meta itemprop="height" content="%s"></span>',
					$thumb_src[0],
					$thumb_src[1],
					$thumb_src[2]
				);
			}

			printf( '<div class="blog__item__thumbnail">%s</div>',
				$is_single ? $thumbnail : sprintf( '<a href="%s">%s</a>', esc_url( $permalink ), $thumbnail )
			);
		} ?>
		</div>
	<?php if ( $is_single ) : ?>
		<div class="blog-single__content padding-all">
			<div itemprop="articleBody" class="entry-content"><?php the_content(); ?></div>
			<div class="margin-top"><?php adventure_tours_render_post_pagination(); ?></div>
			<?php if ( adventure_tours_get_option( 'post_tags' ) ) {
				get_template_part( 'templates/parts/post-tags' );
			} ?>
		</div>

		<?php if ( adventure_tours_get_option( 'social_sharing_blog_single' ) ) {
			get_template_part( 'templates/parts/share-buttons' );
		} ?>
	<?php else : ?>
		<div itemprop="description" class="entry-summary hidden"><?php echo esc_html( adventure_tours_get_short_description( null, 300 ) ); ?></div>

		<div class="blog__item__content <?php echo get_the_content() ? ' padding-all' : ' padding-top'; ?>">
			<?php adventure_tours_the_content(); ?>
		</div>

		<?php if ( adventure_tours_get_option( 'social_sharing_blog' ) ) {
			get_template_part( 'templates/parts/share-buttons' );
		} ?>
	<?php endif; ?>
	</div>

	<?php if ( $is_single ) : ?>
		<?php if ( adventure_tours_get_option( 'about_author' ) ) {
			get_template_part( 'templates/parts/about-author' );
		} ?>

		<?php get_template_part( 'templates/parts/post-navigation' ); ?>

		<?php comments_template(); ?>
	<?php endif; ?>
</article>
