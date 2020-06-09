<?php
/**
 * Author's page.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.5
 */

get_header();

ob_start();
if ( ! have_posts() ) {
	printf(
		'<section class="no-results section-white-box padding-all">%s</section>',
		__('The user has no posts yet.', 'adventure-tours')
	);
} else {
	get_template_part( 'loop' );
}
$primary_content = ob_get_clean();

ob_start();
get_sidebar();
$sidebar_content = ob_get_clean();

adventure_tours_render_template_part('templates/layout', '', array(
	'content' => $primary_content,
	'sidebar' => $sidebar_content,
)); 

get_footer();

