<?php
/**
 * Class contains methods/helper functions related to Wordpress SEO plugin integration.
 * Fixes canonical urls for tours archive page and tours archive page title.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.5.5
 */

class AtWordpressSEOIntegrationHelper extends TdComponent {
	/**
	 * If canoncal urls for tours archive page should be fixed.
	 *
	 * @var boolean
	 */
	public $fix_tour_archive_canonical_urls = true;

	/**
	 * If page title for tours archive page should be fixed.
	 *
	 * @var boolean
	 */
	public $fix_tour_archive_page_title = true;

	/**
	 * If meta description text should be fixed ( used from tours archive page ) instead of the products post type archive.
	 *
	 * @var boolean
	 */
	public $fix_tour_archive_seo_description = true;

	protected $tours_page_url;

	protected $_wpseo_title;

	protected $_meta_desc_text;

	public function init() {
		if ( ! parent::init() ) {
			return false;
		}

		if ( $this->fix_tour_archive_canonical_urls || $this->fix_tour_archive_page_title || $this->fix_tour_archive_seo_description ) {
			add_action( 'template_redirect', array( &$this, 'on_template_redirect' ) );
		}

		return true;
	}

	public function on_template_redirect() {
		if ( ! adventure_tours_check( 'is_tour_search' ) ) {
			return;
		}

		$tours_page_id = adventure_tours_get_option( 'tours_page' );
		$tours_post = $tours_page_id ? get_post( $tours_page_id ) : null;
		$this->tours_page_url = $tours_post ? get_permalink( $tours_post ) : '';

		if ( $this->fix_tour_archive_page_title ) {
			add_filter( 'wpseo_replacements', array( &$this, 'wpseo_replacements_fix_plular_for_tours_archive' ) );

			$seo_title = null;
			if ( $tours_page_id ) {
				$seo_title = WPSEO_Meta::get_value( 'title', $tours_page_id );
				if ( ! $seo_title ) {
					$seo_title = $this->get_wpseo_option( 'title-page' );
				}
			}
			if ( $seo_title ) {
				$this->_wpseo_title = wpseo_replace_vars( $seo_title, $tours_post );
				add_filter( 'wpseo_title', array( &$this, 'wpseo_title_filter' ) );
			}
		}

		if ( $tours_post && $this->fix_tour_archive_seo_description ) {
			$tours_page_metadesc = WPSEO_Meta::get_value( 'metadesc', $tours_page_id );
			if ( ! $tours_page_metadesc ) {
				$tours_page_metadesc = $this->get_wpseo_option( 'metadesc-page' );
			}
			$this->_meta_desc_text = wpseo_replace_vars( $tours_page_metadesc, $tours_post );
			add_filter( 'wpseo_metadesc', array( &$this, 'wpseo_metadesc_filter' ) );
		}

		if ( $this->fix_tour_archive_canonical_urls && $this->tours_page_url ) {
			add_action( 'wpseo_head', array( &$this, 'activate_tour_type_filter' ), 19 );
			add_action( 'wpseo_head', array( &$this, 'deactivate_tour_type_filter' ), 21 );
		}
	}

	public function wpseo_title_filter( $title ) {
		return $this->_wpseo_title ? $this->_wpseo_title : $title;
	}

	public function wpseo_metadesc_filter( $description ) {
		return $this->_meta_desc_text ? $this->_meta_desc_text : $description;
	}

	public function wpseo_replacements_fix_plular_for_tours_archive( $replacements ){
		if ( isset( $replacements['%%pt_plural%%'] ) ) {
			$tours_page_id = adventure_tours_get_option( 'tours_page' );
			$replacements['%%pt_plural%%'] = $tours_page_id ? get_the_title( $tours_page_id ) : __( 'Tours', 'adventure-tours' );
		}
		return $replacements;
	}

	/**
	 * Returns WPSEO option value by name.
	 * @param  string $op_name
	 * @return string|null
	 */
	protected function get_wpseo_option( $op_name ) {
		static $seo_options;
		if ( null === $seo_options ) {
			$seo_options = WPSEO_Options::get_all();
		}
		return isset( $seo_options[ $op_name ] ) ? $seo_options[ $op_name ] : null;
	}

	// post type filter related methods
	public function activate_tour_type_filter() {
		$this->switch_post_type_filter( true );
	}

	public function deactivate_tour_type_filter() {
		$this->switch_post_type_filter( false );
	}

	protected function switch_post_type_filter( $enable ) {
		$callback = array( &$this, 'post_type_filter' );
		$priority = 20;
		if ( $enable ) {
			add_filter( 'post_type_archive_link', $callback, $priority, 2 );
		} else {
			remove_filter( 'post_type_archive_link', $callback, $priority, 2 );
		}
	}

	public function post_type_filter( $url, $post_type ) {
		if ( 'product' == $post_type && $this->tours_page_url ) {
			return $this->tours_page_url;
		}
		return $url;
	}
}
