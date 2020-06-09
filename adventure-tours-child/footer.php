<?php
/**
 * Footer template part.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.0.7
 */

$isShowFooterMenu = has_nav_menu( 'footer-menu' );

?>
</div><!-- .container -->
<footer class="footer">
	<?php get_template_part( 'templates/footer/widget-areas' ); ?>
	<div class="footer__bottom">
		<div class="footer__arrow-top"><a href="#"><i class="fa fa-chevron-up"></i></a></div>
		<div class="container">
			<div class="row">
				<div class="<?php echo ( $isShowFooterMenu ? 'col-md-4' : 'col-md-10' ); ?>">
					<div class="footer__copyright"><?php echo adventure_tours_esc_text( adventure_tours_get_option( 'footer_text_note' ), 'option_input', true ); ?></div>
				</div>
				<div class="col-md-2">
					<div class="footer__creator"><a href="https://ivato.de" target="_blank">powered by ivato</a></div>
				</div>
				<?php if ( $isShowFooterMenu ) : ?>
					<div class="col-md-6">
						<div class="footer-nav">
							<?php wp_nav_menu(array(
								'theme_location' => 'footer-menu',
								'container' => 'ul',
								'depth' => 1,
							)); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</footer>
<div id="full_bg"><div class="page_loading"></div></div>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.owl-carousel').owlCarousel({
			loop: true,
			margin: 0,
			nav: true,
			dots: true,
			items: 1,
			thumbs: true,
			thumbsPrerendered: true,
			navText: false
		});
	});
</script>
<?php get_template_part( 'footer','clean' ); ?>
