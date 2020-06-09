<?php
/**
 * Theme dependency plugins integration.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.7
 */

// Including the TGM_Plugin_Activation class.
require PARENT_DIR . '/vendor/tgm-plugin-activation/class-tgm-plugin-activation.php';

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register two plugins - one included with the TGMPA library
 * and one from the .org repo.
 *
 * The variable passed to tgmpa_register_plugins() should be an array of plugin
 * arrays.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
function adventure_tours_register_required_plugins()
{
	$base_path = get_template_directory() . '/vendor/plugins/';

	/**
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		array(
			'name'      => 'Data types for Adventure Tours theme',
			'slug'      => 'adventure-tours-data-types',
			'source'    => $base_path . 'adventure-tours-data-types.zip',
			'version'   => '2.3.1',
			'required'  => true,
		),
		array(
			'name'      => 'WooCommerce',
			'slug'      => 'woocommerce',
			'required'  => true,
		),
		array(
			'name'      => 'Classic Editor',
			'slug'      => 'classic-editor',
			'required'  => true,
		),
		array(
			'name'      => 'WPBakery Page Builder',
			'slug'      => 'js_composer',
			'source'    => $base_path . 'js_composer.zip',
			'version'   => '5.6',
			'required'  => true,
		),
		array(
			'name'      => 'Contact Form 7',
			'slug'      => 'contact-form-7',
			'required'  => false,
		),
		array(
			'name'      => 'Slider Revolution',
			'slug'      => 'revslider',
			'source'    => $base_path . 'revslider.zip',
			'version'   => '5.4.8.2',
			'required'  => false,
		),
		array(
			'name'      => 'Easy MailChimp Forms',
			'slug'      => 'yikes-inc-easy-mailchimp-extender',
			'required'  => false,
		),
		array(
			'name'      => 'TinyMCE Advanced',
			'slug'      => 'tinymce-advanced',
			'required'  => false,
		),
		array(
			'name'      => 'WordPress Importer',
			'slug'      => 'wordpress-importer',
			'required'  => false,
		),
		array(
			'name'      => 'Widget Importer & Exporter',
			'slug'      => 'widget-importer-exporter',
			'required'  => false,
		),
		/*array(
			'name'      => 'WordPress SEO by Yoast',
			'slug'      => 'wordpress-seo',
			'required'  => false,
		),*/
	);

	tgmpa( $plugins, array(
		'domain'            => 'adventure-tours',           // Text domain - likely want to be the same as your theme.
		'default_path'      => '',                          // Default absolute path to pre-packaged plugins
		'menu'              => 'install-required-plugins',  // Menu slug
		'has_notices'       => true,                        // Show admin notices or not
		'is_automatic'      => true,                        // Automatically activate plugins after installation or not
	) );
}

add_action( 'tgmpa_register', 'adventure_tours_register_required_plugins' );

// Disables Visual Composer plugin updater for users that has not personal license, otherwise updater blocks TGMPA update process.
function adventure_tours_vc_disable_updater() {
	if ( ! vc_license()->isActivated() ) {
		// vc_set_as_theme();
		vc_manager()->disableUpdater();
	}
}
add_action( 'vc_before_init', 'adventure_tours_vc_disable_updater' );
