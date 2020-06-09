<?php
/**
 * Tour categories widget.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   2.3.0
 */

class AtWidgetTourCategories extends AtWidgetBase
{
	/**
	 * Category ancestors.
	 *
	 * @var array
	 */
	public $cat_ancestors;

	/**
	 * Current Category.
	 *
	 * @var Object
	 */
	public $current_cat;

	public function __construct() {

		$this->fields_config = array(
			'title' => array(
				'label' => __( 'Title', 'adventure-tours' ),
			),
			'orderby' => array(
				'label' => __( 'Order by', 'adventure-tours' ),
				'type' => 'select',
				'default' => 'order',
				'options' => array(
					'order' => __( 'Category Order', 'adventure-tours' ),
					'name'  => __( 'Name', 'adventure-tours' )
				)
			),
			'dropdown' => array(
				'label' => __( 'Show as dropdown', 'adventure-tours' ),
				'type' => 'checkbox',
			),
			'count' => array(
				'label' => __( 'Show item counts', 'adventure-tours' ),
				'type' => 'checkbox',
			),
			'hierarchical' => array(
				'label' => __( 'Show hierarchy', 'adventure-tours' ),
				'type' => 'checkbox',
			),
			'hide_empty' => array(
				'label' => __( 'Hide empty', 'adventure-tours' ),
				'type' => 'checkbox',
				'default' => true
			),
			/* 'show_current_children_only' => array(
				'label' => __( 'Show children of current category only', 'adventure-tours' ),
				'type' => 'checkbox',
			), */
		);

		parent::__construct(
			'tour_categories_adventure_tours',
			'AdventureTours: ' . esc_html__( 'Tour Categories', 'adventure-tours' ),
			array(
				'description' => '',
			)
		);
	}

	public function widget( $args, $instance ) {
		global $wp_query, $post;

		$taxonomy_name = 'tour_category';

		$instance = $this->merge_instance( $instance );

		$count = $instance['count'];
		$hierarchical = $instance['hierarchical'];
		$dropdown = $instance['dropdown'];
		$orderby = $instance['orderby'];
		$hide_empty = $instance['hide_empty'];
		$show_current_children_only = false; // $show_current_children_only = $instance['show_current_children_only'];

		if ( $show_current_children_only ) {
			$count = false;
		}

		$dropdown_args = array(
			'taxonomy' => $taxonomy_name,
			'hide_empty' => $hide_empty,
			'show_option_none' => __( 'Select category', 'adventure-tours' ),
		);

		$list_args = array(
			'taxonomy' => $taxonomy_name,
			'show_count' => $count,
			'hierarchical' => $hierarchical,
			'hide_empty' => $hide_empty,
			'show_option_none' => __( 'No categories exist.', 'adventure-tours' ),
		);

		if ( $orderby == 'order' ) {
			$list_args['menu_order'] = 'asc';
		} else {
			$list_args['orderby']    = 'title';
			$list_args['menu_order'] = false;
		}

		$this->current_cat = null;
		$this->cat_ancestors = array();
		if ( is_tax( $taxonomy_name ) ) {
			$this->current_cat   = $wp_query->queried_object;
			$this->cat_ancestors = get_ancestors( $this->current_cat->term_id, $taxonomy_name );

		} elseif ( is_singular( 'product' ) ) {
			$current_category = wc_get_product_terms( $post->ID, $taxonomy_name, apply_filters( 'adventure_toures_tour_categories_widget_product_terms_args', array( 'orderby' => 'parent' ) ) );

			if ( $current_category ) {
				$this->current_cat = end( $current_category );
				$this->cat_ancestors = get_ancestors( $this->current_cat->term_id, $taxonomy_name );
			}
		}

		if ( $show_current_children_only ) {
			if ( $this->current_cat ) {
				$top_level = get_terms(
					$taxonomy_name,
					array(
						'fields'       => 'ids',
						'parent'       => 0,
						'hierarchical' => true,
						'hide_empty'   => false
					)
				);

				// Direct children are wanted
				$direct_children = get_terms(
					$taxonomy_name,
					array(
						'fields'       => 'ids',
						'parent'       => $this->current_cat->term_id,
						'hierarchical' => true,
						'hide_empty'   => false
					)
				);

				// Gather siblings of ancestors
				$siblings  = array();
				if ( $this->cat_ancestors ) {
					foreach ( $this->cat_ancestors as $ancestor ) {
						$ancestor_siblings = get_terms(
							$taxonomy_name,
							array(
								'fields'       => 'ids',
								'parent'       => $ancestor,
								'hierarchical' => false,
								'hide_empty'   => false
							)
						);
						$siblings = array_merge( $siblings, $ancestor_siblings );
					}
				}

				if ( $hierarchical ) {
					$include = array_merge( $top_level, $this->cat_ancestors, $siblings, $direct_children, array( $this->current_cat->term_id ) );
				} else {
					$include = array_merge( $direct_children );
				}

				$dropdown_args['include'] = $list_args['include'] = implode( ',', $include );

				if ( empty( $include ) ) {
					return;
				}
			} else {
				$list_args['depth'] = $dropdown_args['depth'] = 1;
				$list_args['child_of'] = $dropdown_args['child_of'] = 0;
				$list_args['hierarchical'] = $dropdown_args['hierarchical'] = 1;
			}
		} // show_current_children_only

		// Rendering
		$this->widget_start( $args, $instance );

		if ( $dropdown ) { // dropdown mode
			$dropdown_args = wp_parse_args( $dropdown_args, array(
				'name' => 'widget_tour_category',
				// 'id' => 'widget_tour_category',
				'class' => 'widget-tour-category',
				'value_field'        => 'slug',
				'show_count'         => $count,
				'hierarchical'       => $hierarchical,
				'show_uncategorized' => 0,
				'orderby'            => $orderby,
				'selected'           => $this->current_cat ? $this->current_cat->slug : ''
			) );

			wp_dropdown_categories( $dropdown_args );

			$home_url = esc_js( home_url( '/' ) );
			TdJsClientScript::addScript(
				'widget_tour_category',
<<<SCRIPT
				jQuery( '.widget-tour-category' ).change( function() {
					if ( jQuery(this).val() != '' ) {
						var home_url = '{$home_url}',
							separator = home_url.indexOf( '?' ) > 0 ? '&' : '?';
						location.href = home_url + separator + '{$taxonomy_name}=' + jQuery(this).val();
					}
				});
SCRIPT
			);

		} else { // list mode
			$list_args['title_li'] = '';
			$list_args['pad_counts'] = 1;
			$list_args['current_category'] = $this->current_cat ? $this->current_cat->term_id : '';
			$list_args['current_category_ancestors'] = $this->cat_ancestors;
			//$list_args['show_option_none'] = __( 'No item exist.', 'adventure-tours' );

			echo '<ul class="product-categories product-categories--tour-categories">';
			wp_list_categories( apply_filters( 'adventure_tours_tour_categories_widget_args', $list_args ) );
			echo '</ul>';
		}

		$this->widget_end( $args );
	}
}
