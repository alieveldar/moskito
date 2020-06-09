<?php

/**
 * Includes 'style.css'.
 * Disable this filter if you don't use child style.css file.
 *
 * @param  assoc $default_set set of styles that will be loaded to the page
 * @return assoc
 */
if(isset($_POST['share_email'])){
	$url=get_permalink($_POST['share_email']);
	$title=get_the_title($_POST['share_email']);
	if(!$url || !$title) die(json_encode(array('type'=>'error')));

	$from=is_email($_POST['email_from']) ? $_POST['email_from'] : '';
	$text=$url;
	if($_POST['comment']!='') $text.="\r\n".$_POST['comment'];
	if($from) $res=wp_mail($_POST['email_to'],$title,$text,'From: '.$from.' <'.$from.'>');
	else $res=wp_mail($_POST['email_to'],$title,$text,'From: MOSKITO Adventures <'.get_option('admin_email').'>');
	if(!$res) die(json_encode(array('type'=>'error')));
	die(json_encode(array('type'=>'success','text'=>'Email wurde erfolgreich versendet')));
}
if(isset($_POST['print_tour'])){
	require_once get_stylesheet_directory().'/inc/dompdf/product_pdf.php';
	add_action('init','getPDFFile',PHP_INT_MAX);
	function getPDFFile(){
		$res=createPDFProductFile($_POST['print_tour'],array('map_img'=>$_POST['map']));
		if($res===false) echo json_encode(array('type'=>'error'));
		else echo json_encode(array('type'=>'success','url'=>$res));
		die();
	}
	//die();
}
if(isset($_POST['get_tour_countries'])){
    function getCountriesList(){
        $args=array(
            'taxonomy'=>'pa_land',
            'hide_empty'=>true,
        );
        if(!empty(trim($_POST['get_tour_countries']))){
            $args['meta_query']=array(array(
                'key'=>'country_continent',
                'value'=>$_POST['get_tour_countries'],
                'compare'=>'=',
            ));
        }
        $land=get_taxonomy('pa_land');
        $result='<option value="">'.$land->labels->singular_name.'</option>';
        $terms=get_terms($args);
        foreach($terms as $term){
            $result.='<option value="'.$term->slug.'">'.$term->name.'</option>';
        }
        echo $result;
        die();
    }
    add_action('init','getCountriesList',999999);
}
function filter_adventure_tours_get_theme_styles( $default_set ) {
    $default_set['child-style'] = get_stylesheet_uri();
    return $default_set;
}
add_filter( 'get-theme-styles', 'filter_adventure_tours_get_theme_styles' );

function custom_set_quantity_expand_attribute( $di, $config ) {
    $di['booking_form']->setConfig( array(
        'expand_quantity_attribute' => 'pa_age',
    ));
}
add_action( 'adventure_tours_init_di', 'custom_set_quantity_expand_attribute', 12, 2 );

function countriesOptions($sel=''){
    $args=array(
        'taxonomy'=>'pa_kontinent',
        'hide_empty'=>false,
    );
    $terms=get_terms($args);
    $options='<option value="">'.__('Not selected').'</option>';
    foreach($terms as $t){
        $options.='<option value="'.$t->slug.'" '.selected($t->slug,$sel).'>'.$t->name.'</option>';
    }
    return $options;
}
function country_add_new_meta_field(){
    ?>
    <div class="form-field">
        <label for="custom_term_meta[country_continent]"><?=__( 'Kontinent', '' ); ?></label>
        <select name="custom_term_meta[country_continent]"><?=countriesOptions()?></select>
        <p class="description"><?=__( 'Choose the continent of the Kontinent','' ); ?></p>
    </div>
    <?php
}
function country_edit_new_meta_field($term){
    $country=get_term_meta($term->term_id,'country_continent',true);
    ?>
    <tr class="form-field">
    <th scope="row" valign="top"><label for="custom_term_meta[country_continent]"><?= __( 'Kontinent', 'pippin' ); ?></label></th>
        <td>
            <select name="custom_term_meta[country_continent]"><?=countriesOptions($country)?></select>
            <p class="description"><?=__( 'Choose the continent of the Kontinent','' ); ?></p>
        </td>
    </tr>
    <?php
}
function save_country($term_id){
    if (isset($_POST['custom_term_meta'])){
        foreach($_POST['custom_term_meta'] as $k=>$v){
            update_term_meta($term_id,$k,$v);
        }
    }
}
add_action('pa_land_add_form_fields','country_add_new_meta_field',10,2);
add_action('pa_land_edit_form_fields','country_edit_new_meta_field',10,2);
add_action( 'edited_pa_land', 'save_country', 10, 2 );
add_action( 'create_pa_land', 'save_country', 10, 2 );

function add_custom_resources(){
	wp_enqueue_script('custom_theme_js',get_stylesheet_directory_uri().'/js/custom_js.js');
	wp_enqueue_script('owl-carousel-js',get_stylesheet_directory_uri().'/js/owl.carousel.min.js',array('jquery'),'1.0',true);
	wp_enqueue_script('owl-carousel-js2',get_stylesheet_directory_uri().'/js/owl.carousel2.thumbs.min.js',array('jquery'),'1.0',true);
	wp_enqueue_style('owl-carousel-css',get_stylesheet_directory_uri().'/css/owl.carousel.min.css');
}
add_action('init','add_custom_resources');
function changeWcVarPriceText($price, $product){
    $prices=$product->get_variation_prices(true);
    $price=wc_price(current($prices['price'])).$product->get_price_suffix();
    return __('From','adventure-tours').' '.$price;
}
function changeWcPriceText($price,$product){
    $price=($product instanceof WC_Product_Variable) ? $price : __('From','adventure-tours').' '.$price;
    return $price;
}
add_filter('woocommerce_variable_price_html', 'changeWcVarPriceText',10,2);
add_filter('woocommerce_get_price_html', 'changeWcPriceText',10,2);
add_filter('woocommerce_sale_price_html', 'changeWcPriceText',10,2);

function add_countries_functions(){
    register_nav_menus(array(
        'countries_menu' => esc_html__( 'Countires Menu', 'adventure-tours'),
    ));
    if(is_admin()){
        new VP_Metabox(array(
            'id'           => 'countires_section_meta',
            'types'        => array('page','post'),
            'title'        => esc_html__( 'Countries options', 'adventure-tours' ),
            'priority'     => 'high',
            'is_dev_mode'  => false,
            'template'     => get_stylesheet_directory().'/templates/countries_tab.php',
        ));
    }
}
function tours_page_query($q){
    if(!$q->is_main_query()) return;
    $obj=get_queried_object();
    $tpl=get_post_meta($obj->ID,'_wp_page_template',true);
    if($tpl=='template-tours_list.php'){
        $meta=get_post_meta($obj->ID,'countires_section_meta',true);
        $q->set('toursearch',1);
        if(!empty($meta['_page_country'])){
            $q->set('tourtax',array('pa_land'=>$meta['_page_country']));
        }elseif(!empty($meta['_page_continent'])){
            $q->set('tourtax',array('pa_kontinent'=>$meta['_page_continent']));
        }
    }
    return $q;
}

function get_archive_ordering_args( $orderby = '', $order = '' ) {
    global $wpdb;

    // Get ordering from query string unless defined
    if ( ! $orderby ) {
        $orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'adventure_tours_default_archive_orderby', adventure_tours_get_option( 'tours_archive_orderby' ) );

        // Get order + orderby args from string
        $orderby_value = explode( '-', $orderby_value );
        $orderby       = esc_attr( $orderby_value[0] );
        $order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
    }

    $orderby = strtolower( $orderby );
    $order   = strtoupper( $order );
    $args    = array();

    // default - menu_order
    $args['orderby']  = 'menu_order title';
    $args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
    $args['meta_key'] = '';

    switch ( $orderby ) {
        case 'rand' :
            $args['orderby']  = 'rand';
        break;
        case 'date' :
            $args['orderby']  = 'date';
            $args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
        break;
        case 'price' :
            $args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
            $args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
            $args['meta_key'] = '_price';
        break;
        case 'popularity' :
            $args['meta_key'] = 'total_sales';

            // Sorting handled later though a hook
            add_filter( 'posts_clauses', array( WC()->query, 'order_by_popularity_post_clauses' ) );
        break;
        case 'rating' :
            if ( version_compare( WC_VERSION, '3.4.0', '>') ) {
                $args['meta_key'] = '_wc_average_rating'; // @codingStandardsIgnoreLine
                $args['orderby']  = array(
                    'meta_value_num' => 'DESC',
                    'ID'             => 'ASC',
                );
            } elseif ( version_compare( WC_VERSION, '3.2.0', '<') ) {
                // Sorting handled later though a hook
                add_filter( 'posts_clauses', array( WC()->query, 'order_by_rating_post_clauses' ) );
            } else {
                add_filter( 'posts_clauses', 'WC_Shortcode_Products::order_by_rating_post_clauses' );
            }
        break;
        case 'title' :
            $args['orderby']  = 'title';
            $args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
        break;
    }

    return apply_filters( 'adventure_tours_get_archive_ordering_args', $args );
}
add_action('pre_get_posts','tours_page_query');
add_action('init','add_countries_functions');
/**
 * Get Post by slug
 * @param $slug
 * @param $post_type
 */
function get_post_by_slug( $slug, $post_type = "post" ) {
    $query = new WP_Query(
        array(
            'post_name'   => $slug,
            'post_type'   => $post_type,
            'numberposts' => 1,
        ) );
    $posts = $query->get_posts();
    return array_shift( $posts );
}

if ( ! function_exists( 'adventure_tours_render_tab_description' ) ) {
    /**
     * Tour details page, tab description rendeing function.
     *
     * @return void
     */
    function adventure_tours_render_tab_description() {
        global $product;

        echo do_shortcode(get_the_content());

        if ( ! empty( $GLOBALS['_tour_additional_attributes'] ) ) {
            adventure_tours_render_template_part( 'templates/tour/additional-attributes', '', array(
                'title' => esc_html__( 'Additional information', 'adventure-tours' ),
                'attributes' => $GLOBALS['_tour_additional_attributes'],
            ) );
        }

        // renders product tags
        /*if ( version_compare( WC_VERSION, '3.0.0', '<') ) {
            echo $product->get_tags(
                ' ', // delimiter
                sprintf( '<div class="post-tags margin-top"><span><i class="fa fa-tags"></i>%s</span>', 'Tags:' ), // before
                '</div>' // after
            );
        } else {
            echo wc_get_product_tag_list(
                $product->get_id(),
                ' ', //delimiter
                sprintf( '<div class="post-tags margin-top"><span><i class="fa fa-tags"></i>%s</span>', 'Tags:' ), // before
                '</div>' // after
            );
        }*/
    }
}
function moskitos($args,$content){
    if(!is_numeric($args['count'])) return '';
    $max=is_numeric($args['max']) ? $args['max'] : 5;
    $cnt=floatval($args['count']);
    $output='<div class="moskitos_list">';

    for($i=0;$i<$max;++$i){
        if($i>=$cnt) $output.='<span class="tours-tabs__info__item__moskit tours-tabs__info__item__moskit_grey"></span>';
        elseif($cnt!=floor($cnt) && $i==floor($cnt)) $output.='<span class="tours-tabs__info__item__moskit tours-tabs__info__item__moskit_half"></span>';
        else $output.='<span class="tours-tabs__info__item__moskit"></span>';
    }
    $output.='</div>';
    return $output;
}
add_shortcode('moskitos','moskitos');


if ( ! function_exists( 'adventure_tours_render_tab_description_top_section' ) ) {
    /**
     * Tour details page, tab description icons/attributes rendeing function.
     *
     * @return void
     */
    function adventure_tours_render_tab_description_top_section() {
        global $product;
        $all_attributes = AtTourHelper::get_tour_details_attributes( $product, null );
        $header_attributes = $all_attributes ? AtTourHelper::get_tour_details_attributes( $product, true ) : array();
        if ( $header_attributes ) {
            $count = count( $header_attributes );
            $count_to_batch_size = $count < 5 ? $count : (
                $count % 3 == 0 ? 3 : ( $count % 5 == 0 ? 5 : 4 )
            );
            /*
            $count_to_batch_size = $count < 4 ? $count : (
                $count % 4 == 0 ? 4 : 3
            );*/
            $attributes_batches = array_chunk( $header_attributes, $count_to_batch_size );
            foreach ( $attributes_batches as $attrib_batch ) {
                echo '<div class="tours-tabs__info">';
                foreach ( $attrib_batch as $attribute ) {
                    echo strtr('<div class="tours-tabs__info__item">
                        <div class="tours-tabs__info__item__content">
                            <div class="tours-tabs__info__item__icon"><i class="{icon_class}"></i></div>
                            <div class="tours-tabs__info__item__text">
                                <div class="tours-tabs__info__item__title">{value}</div>
                                <div class="tours-tabs__info__item__description">{label}</div>
                            </div>
                        </div>
                    </div>', array(
                        '{icon_class}' => esc_attr( $attribute['icon_class'] ),
                        '{value}' => do_shortcode($attribute['text']),
                        '{label}' => $attribute['label'],
                    ));
                }
                echo '</div>';
            }
        }

        $additional_attributes = $all_attributes && $header_attributes ? array_diff_key( $all_attributes, $header_attributes ) : $all_attributes;
        if ( $additional_attributes ) {
            $GLOBALS['_tour_additional_attributes'] = $additional_attributes;
        }
    }
}
function hided_text($args,$content){
	global $ht;
	if(!is_numeric($ht)) $ht=0;
	return '<div class="hidden" id="hidden_text_'.++$ht.'">'.do_shortcode($content).'</div><div class="show_btn"><a href="javascript:void(0);" onclick="showText(this,\'hidden_text_'.$ht.'\');" class="revo-button atbtn">Weiterlesen</a></div>';
}
add_shortcode('hided_text','hided_text');

if ( ! function_exists( 'adventure_tours_render_tour_booking_form_for_location' ) ) {
	/**
	 * Checks if $location equal with settings for booking form location and renders booking form in this case.
	 *
	 * @param  string $location allowed values are: 'sidebar', 'above_tabs', 'under_tabs'
	 * @param  array  $options  assoc that may contains 'before_form' and/or 'after_form' keys (may contain pice of html)
	 * @return string           html
	 */
	function adventure_tours_render_tour_booking_form_for_location( $location, $options = array() ) {
		$product = is_singular( 'product' ) ? wc_get_product() : null;
		$result = '';
		if ( $location && $location == adventure_tours_get_booking_form_location_for_tour( $product ) ) {

			// renders price decoration element
			$result .= adventure_tours_render_tour_booking_form( $product );
			if($result){
				if ( ! empty( $options['before_form'] ) ) {
					$result = $options['before_form'] . $result;
				}

				if ( ! empty( $options['after_form'] ) ) {
					$result = $result . $options['after_form'];
				}
			}
		}


		if($attachment_ids) $result.=getTourRightGallery($attachment_ids);
		return $result;
	}
}
function getTourRightGallery(){
	global $product;
     $number_of_images = get_post_meta($product->get_id(), '_rightBlock_number_of_images', true);
      $current_number_of_images = 0;
	$aids=array();
	if(has_post_thumbnail($product->ID)) $aids=array(get_post_thumbnail_id($product->ID));
	if(version_compare(WC_VERSION,'2.7','>')){
		$aids=array_merge($aids,$product->get_gallery_image_ids());
	}else {
		$aids=array_merge($aids,$product->get_gallery_attachment_ids());
	}
	if(!is_array($aids) || sizeOf($aids)==0) return '';
	$output='<div class="product_right_gallery"><ul>';
	foreach($aids as $aid){
        if ($number_of_images != "") {
            if ($current_number_of_images == $number_of_images)
                break;
            $current_number_of_images++;
        }
		$img=wp_get_attachment_image_src($aid,'large');
		$output.='<li><img src="'.$img[0].'"></li>';
	}
	$output.='</ul></div>';

 if ($number_of_images == "")
    wp_enqueue_script('product_right_gallery',get_stylesheet_directory_uri().'/js/product_right_gallery.js');
    return $output;
}
function getToursGallery($args,$content){
	global $product;
	if(!$product) return '';
	$aids=array();
	if(has_post_thumbnail($product->ID)) $aids=array(get_post_thumbnail_id($product->ID));
	if(version_compare(WC_VERSION,'2.7','>')){
		$aids=array_merge($aids,$product->get_gallery_image_ids());
	}else {
		$aids=array_merge($aids,$product->get_gallery_attachment_ids());
	}
	if(!is_array($aids) || sizeOf($aids)==0) return '';
	global $tour_galleries;
	if(!is_numeric($tour_galleries)) $tour_galleries=0;
	$output='<div class="tour_owl_gallery">';
	$output.='<div class="owl-carousel owl-theme" data-slider-id="owlcarousel_'.++$tour_galleries.'">[:images:]</div>';
	if(!isset($args['hide_thumbs']) || $args['hide_thumbs']!='yes') $output.='<div class="owl-thumbs" data-slider-id="owlcarousel_'.$tour_galleries.'">[:thumbs:]</div>';
	$output.='</div>';
	$images=$thumbs='';
	foreach($aids as $aid){
		$caption=wp_get_attachment_caption($aid);
		$imgf=wp_get_attachment_image_src($aid,'full');
		$imgl=wp_get_attachment_image_src($aid,'large');
		$images.='<div class="owl-slide">';
		if(trim($caption)!='') $images.='<p class="owl-description">'.$caption.'</p>';
		$images.='<div class="owl-slide-img" style="background-image: url('.$imgf[0].')"></div></div>';
		$thumbs.='<button class="owl-thumb-item"><img src="'.$imgl[0].'" alt="thumb"></button>';
	}
	$output=str_replace('[:images:]',$images,$output);
	//if(isset($args['hide_thumbs']) && $args['hide_thumbs']=='yes') $thumbs='';
	if(!isset($args['hide_thumbs']) || $args['hide_thumbs']!='yes') $output=str_replace('[:thumbs:]',$thumbs,$output);
	return $output;
}

add_shortcode('tours_gallery','getToursGallery');
function addGuidesPostType(){
	register_post_type('guide',array(
		'labels'=>array(
			'name'               => 'Guides',
			'singular_name'      => 'Guide',
			'add_new'            => 'Add new',
			'add_new_item'       => 'Add new guide',
			'edit_item'          => 'Edit guide',
			'new_item'           => 'New guide',
			'view_item'          => 'View guide',
			'search_items'       => 'Find guide',
			'not_found'          => 'Guides not found',
			'menu_name'          => 'Guide'

		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array('title','editor','author','thumbnail','excerpt','comments'),
	));
}
add_action('init','addGuidesPostType');

$prefix = 'guide_';
$meta_box_guide = array(
	'id'       => 'meta-box-guide',
	'title'    => 'Setting Testimonials',
	'page'     => 'guide',
	'context'  => 'normal',
	'priority' => 'high',
	'fields'   => array(
		array(
			'name' => '"Guide by" text',
			'id'   => $prefix . 'guide_by',
			'type' => 'text',
			'std'  => ''
		),
	)
);

add_action('admin_menu', 'add_box_guide');


function add_box_guide() {
	global $meta_box_guide;
	add_meta_box($meta_box_guide['id'], $meta_box_guide['title'], 'show_box_guide', $meta_box_guide['page'], $meta_box_guide['context'], $meta_box_guide['priority']);
}
function show_box_guide() {
	global $meta_box_guide, $post;

	echo '<p style="padding:10px 0 0 0;">Please fill in additional field for guide.</p>';
	echo '<input type="hidden" name="meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

	echo '<table class="form-table">';
	foreach($meta_box_guide['fields'] as $field){
		$meta=get_post_meta($post->ID, $field['id'], true);
		switch ($field['type']){
			case 'text':
			echo '<tr style="border-top:1px solid #eeeeee;">',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
				'<td>';
			echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" size="30" style="width:75%; margin-right: 20px; float:left;" />';
			break;
			case 'textarea':
			echo '<tr style="border-top:1px solid #eeeeee;">',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style="line-height:18px; display:block; color:#999; margin:5px 0 0 0;">'. $field['desc'].'</span></label></th>',
				'<td>';
			echo '<textarea name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" rows="8" cols="5" style="width:100%; margin-right: 20px; float:left;">', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '</textarea>';
			break;
			case 'select':
				echo '<tr>',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
				'<td>';

				echo'<select id="' . $field['id'] . '" name="'.$field['id'].'">';

				foreach ($field['options'] as $option) {
					echo '<option', $meta == $option['value'] ? '   selected =" selected "' : '', ' value="'.$option['value'].'">'.$option['label'].'</option>';
				}
				echo'</select>';
			break;
		}
	}
	echo '</table>';
}


add_action('save_post', 'save_data_guide');
function save_data_guide($post_id) {
	global $meta_box_guide;
	if (!isset($_POST['meta_box_nonce']) || !wp_verify_nonce($_POST['meta_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	if('page'==$_POST['post_type']){
		if (!current_user_can('edit_guide', $post_id)) return $post_id;
	}elseif(!current_user_can('edit_post', $post_id)) return $post_id;

	foreach($meta_box_guide['fields'] as $field){
		$new=$_POST[$field['id']];
		update_post_meta($post_id,$field['id'],stripslashes(htmlspecialchars($new)));
	}
}


function addTourGuides(){
	add_meta_box('tour_guide','Reiseleiter','tourGuidesList','product','side');
}
function tourGuidesList(){
	global $post;
	if($post->post_type!='product') return;
	$guides=new WP_Query(array(
		'post_type'=>'guide',
		'posts_per_page'=>-1,
		'orderby'=>'title',
		'order'=>'ASC',
	));
	$gs=explode(',',get_post_meta($post->ID,'_tour_guides',true));
	ob_start(); ?>
	<ul class="categorychecklist form-no-clear">
	<?php foreach($guides->posts as $guide) :?>
			<li class="wpseo-term-unchecked">
				<label class="selectit">
				<input value="<?=$guide->ID?>" type="checkbox" name="tour_guides[]" <?php echo (in_array($guide->ID,$gs) ? 'checked' : '');?>> <?=$guide->post_title?></label>
			</li>
	<?php endforeach; ?>
		</ul>
	<?php echo ob_get_clean();
}

function saveTourGuides($pid,$post,$u){
	//var_dump($_POST['post_type']);
	//die();
	if($_POST['post_type']!='product') return;
	$guides='';
	if(isset($_POST['tour_guides'])){ $guides=is_array($_POST['tour_guides']) ? implode(',',$_POST['tour_guides']) : $_POST['tour_guides']; }
	update_post_meta($pid,'_tour_guides',$guides);
}
add_action("save_post_product","saveTourGuides",10,3);
add_action( 'admin_menu' , 'addTourGuides' );
function getGuidesList(){
	global $post;
	if($post->post_type!='product') return;
	$guides=get_post_meta($post->ID,'_tour_guides',true);
	if(!$guides) return '';
	$gids=explode(',',$guides);
	foreach($gids as $k=>$v) if(!is_numeric($v)) unset($gids[$k]);
	if(sizeOf($gids)==0) return '';
	$guides_query=new WP_Query(array(
		'post_type'=>'guide',
		'posts_per_page'=>-1,
		'orderby'=>'title',
		'order'=>'ASC',
		'post__in'=>$gids,
	));
	$output='<div class="tour_guides_list"><ul>';
	foreach($guides_query->posts as $guide){
		$thumb=get_the_post_thumbnail_url($guide->ID,'thumbnail');
		$text=get_post_meta($guide->ID,'guide_guide_by',true);
		$output.='<li '.(!$thumb ? 'class="no_image"' : '').'>';
		if($thumb) $output.='<div class="image"><img src="'.$thumb.'"></div>';
		$output.='<div class="info"><div class="title">';
		$output.='<strong>'.$guide->post_title.'</strong>';
		if($text) $output.=' <span class="guide_by">'.$text.'</span>';
		$output.='</div>';
		$output.='<div class="text">'.$guide->post_content.'</div></div>';
		$output.='</li>';
	}
	$output.='</ul></div>';
	return $output;
}
add_shortcode('guides_list','getGuidesList');

$prefix = 'tour_';
$meta_box_tour = array(
	'id'       => 'meta-box-tour',
	'title'    => 'Tour organisers',
	'page'     => 'product',
	'context'  => 'normal',
	'priority' => 'high',
	'fields'   => array(
		array(
			'name' => 'Christian',
			'id'   => $prefix . 'christian',
			'type' => 'checkbox',
			'std'  => ''
		),
		array(
			'name' => 'Christof',
			'id'   => $prefix . 'christof',
			'type' => 'checkbox',
			'std'  => ''
		),
	)
);

add_action('admin_menu', 'add_box_tour');


function add_box_tour() {
	global $meta_box_tour;
	add_meta_box($meta_box_tour['id'], $meta_box_tour['title'], 'show_box_tour', $meta_box_tour['page'], $meta_box_tour['context'], $meta_box_tour['priority']);
}

add_action('save_post_product', 'saveTourOrganisers');
function saveTourOrganisers($post_id) {
	global $meta_box_tour;
	//var_dump($_POST);
	//die();
	/*if('page'==$_POST['post_type']){
		if (!current_user_can('edit_guide', $post_id)) return $post_id;
	}elseif(!current_user_can('edit_post', $post_id)) return $post_id;*/

	foreach($meta_box_tour['fields'] as $field){
		$new=$_POST[$field['id']];
		update_post_meta($post_id,$field['id'],$new);
	}
}
function show_box_tour() {
	global $meta_box_tour, $post;

	echo '<input type="hidden" name="meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

	echo '<table class="form-table">';
	foreach($meta_box_tour['fields'] as $field){
		$meta=get_post_meta($post->ID, $field['id'], true);
		switch ($field['type']){
			case 'text':
			echo '<tr style="border-top:1px solid #eeeeee;">',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
				'<td>';
			echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" size="30" style="width:75%; margin-right: 20px; float:left;" />';
			break;
			case 'textarea':
			echo '<tr style="border-top:1px solid #eeeeee;">',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style="line-height:18px; display:block; color:#999; margin:5px 0 0 0;">'. $field['desc'].'</span></label></th>',
				'<td>';
			echo '<textarea name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" rows="8" cols="5" style="width:100%; margin-right: 20px; float:left;">', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '</textarea>';
			break;
			case 'select':
				echo '<tr>',
				'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
				'<td>';

				echo'<select id="' . $field['id'] . '" name="'.$field['id'].'">';

				foreach ($field['options'] as $option) {
					echo '<option', $meta == $option['value'] ? '   selected =" selected "' : '', ' value="'.$option['value'].'">'.$option['label'].'</option>';
				}
				echo'</select>';
			break;
			case 'checkbox':
				echo '<tr style="border-top:1px solid #eeeeee;">',
					'<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
					'<td>';
				echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '" value="1" '.($meta==1 ? 'checked' : '').' />';
			break;
		}
	}
	echo '</table>';
}

function getTourContactsRight(){
	if(!is_product()) return '';
	global $post;
	$christian=get_post_meta($post->ID,'tour_christian',true);
	$christof=get_post_meta($post->ID,'tour_christof',true);
	if(trim($christof)=='' && trim($christian)=='') return '';
	$output='<div class="contact-widget"><div class="title">'.__('Ihr Ansprechpartner für diese Reise:','adventure-tours-child').'</div>';
    if(trim($christian)!=''){
        $output.='<div class="contact-widget__person">
			<div class="contact-widget__photo"><img src="'.get_stylesheet_directory_uri().'/images/chris4.jpg" alt="avatar" /></div>
			<div class="contact-widget__name">Christian Hertel</div>
			<div class="contact-widget__info">
				<div class="contact-widget__telephone"><i class="td-phone-1"></i>
				<a href="tel:034292 44 93 39" class="contact-widget__telephone__value">034292 44 93 39</a></div>
				<div class="contact-widget__email"><i class="td-email-1"></i>
				<a href="mailto:c.hertel@moskito-adventures.de" class="contact-widget__email__value">c.hertel@moskito-adventures.de</a></div>
			</div>
		</div>';
    }
    if(trim($christof)!=''){
        $output.='<div class="contact-widget__person">
			<div class="contact-widget__photo"><img src="'.get_stylesheet_directory_uri().'/images/christof4.jpg" /></div>
			<div class="contact-widget__name">Christof Schor</div>
			<div class="contact-widget__info">
				<div class="contact-widget__telephone"><i class="td-phone-1"></i>
				<a href="tel:0178 47 28 570" class="contact-widget__telephone__value">0178 47 28 570</a></div>
				<div class="contact-widget__email"><i class="td-email-1"></i>
				<a href="mailto:c.schor@moskito-adventures.de" class="contact-widget__email__value">c.schor@moskito-adventures.de</a></div>
			</div>
		</div>';
    }
	$output.='</div>';
	return $output;
}

function getPDFEmailRight(){
	if(!is_product()) return '';
	global $post;
	$output='<div class="right_pdfemail">';
	$output.='<a href="javascript:void(0);" onclick="printTour(this,'.get_the_ID().');" class="pdf">'.__('PDF').'</a><a href="javascript:void(0);" onclick="sentTourByEmail(this,'.get_the_ID().',\''.get_the_title().'\');" class="email">'.__('E-Mail').'</a>';
	$output.='</div>';
	add_action('wp_footer','drawEmailPopup');
	return $output;
}
function drawEmailPopup(){
	$output='<div class="popup" id="email_sharing"><div class="popup_wrapper">
		<div class="top">
			<div class="heading">'.__('Reise weiterempfehlen').'</div><div class="close" onclick="closePopup(this);">&times;</div>
		</div>
		<div class="body">
			<div class="title"></div>
			<form action="/kontakt/#wpcf7-f162-p9-o1" method="post" class="wpcf7-form" novalidate="novalidate">
				<div class="form-contact">
					<div class="form-contact__fields-short">
						<div class="form-contact__item-short">
							<span class="wpcf7-form-control-wrap">
								<input type="text" name="email_to" class="wpcf7-form-control wpcf7-text form-validation-item" placeholder="'.__('E-Mail des Empfängers').' *">
							</span>
						</div>
						<div class="form-contact__item-short">
							<span class="wpcf7-form-control-wrap">
								<input type="text" name="email_from" class="wpcf7-form-control wpcf7-text form-validation-item" placeholder="'.__('E-Mail des Absenders').'">
							</span>
						</div>
					</div>
					<p>
						<span class="wpcf7-form-control-wrap your-message">
							<textarea name="comment" class="wpcf7-form-control wpcf7-textareaform-validation-item" placeholder="Nachricht"></textarea>
						</span>
					</p>
					<div class="submit_group">
						<button type="submit" class="button button--style1 button--with-icon" onclick="sendEmailSharing(this,event);"><i class="fa fa-paper-plane"></i>'.__('Senden').'</button>
						<div class="ajax_loading"></div>
						<div class="ajax_result"></div>
					</div>
				</div>
				<input type="hidden" name="share_email" value="">
			</form>
		</div>
		</div></div>';
	echo $output;
}
function general_admin_notice(){
    global $pagenow;
    if ( $pagenow == 'update-core.php' || $pagenow == 'index.php' ) {
         echo '<div class="notice notice-warning" style="display:flex;"><img src="'.get_stylesheet_directory_uri().'/images/xivato.jpg" style="width: 30%; max-width: 200px; height: 51px; margin: 5px 10px 0 0;"><p><b>Kein Support Paket aktiv oder gebucht.</b><br>Profitieren Sie in Zukunft von unserem technischen (Backups, Monitoring etc.) sowie redaktionellen Support (Beratung, Schulung, Pfelge etc.) via Email, Telefon oder Fernwartung. Mehr erfahren Sie dazu unter <a href="https://ivato.de/wartung/" target="_blank">www.ivato.de/wartung</a> oder wir helfen Ihnen telefonisch unter +49 (0) 341 900 3993 weiter.<br>Wir werden uns schnellstmöglich mit Ihnen in Verbindung setzen.</p></div>';
    }
}
add_action('admin_notices', 'general_admin_notice');

if ( ! function_exists( 'adventure_tours_render_tab_photos' ) ) {
	function adventure_tours_render_tab_photos() {
		echo getToursGallery(array(),'');
	}
}


if ( ! function_exists( 'adventure_tours_render_product_attributes' ) ) {
	/**
	 * Renders product attributes on archive page.
	 *
	 * @param  array $args assoc that contains rendering settings.
	 * @param  int   $postId optional post id.
	 * @return void
	 */
	function adventure_tours_render_product_attributes( $args = array(), $postId = null ) {
		global $product;

		$curProduct = $postId ? wc_get_product( $postId ) : $product;

		$list = AtTourHelper::get_tour_details_attributes( $curProduct, true );
		if ( ! $list ) {
			return;
		}

		$defaults = array(
			'before' => '',
			'after' => '',
			'before_each' => '',
			'after_each' => '',
			'limit' => 5,
			'values_limit' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$values_limit = $args['values_limit'] > 0 ? $args['values_limit'] : 0;

		$items_html = '';
		$restricted_atts=array('pa_moskitos','pa_price');
		foreach ( $list as $attribute ) {
			if(in_array($attribute['name'],$restricted_atts)) continue;
			$values_text = $values_limit > 0 && count( $attribute['values'] ) > $values_limit ? join( ', ', array_slice( $attribute['values'], 0, $values_limit ) ) : $attribute['text'];
			$items_html .= $args['before_each'] .
				'<div class="item-attributes__item__content">' .
					( $attribute['icon_class'] ? '<div class="item-attributes__item__content__item"><i class="' . esc_attr( $attribute['icon_class'] ) . '"></i></div>' : '' ) .
					'<div class="item-attributes__item__content__item item-attributes__item__content__item--text"><span>' . do_shortcode($values_text) . '</span></div>' .
				'</div>' .
			$args['after_each'];

			if ( $args['limit'] > 0 ) {
				$args['limit']--;
				if ( $args['limit'] < 1 ) {
					break;
				}
			}
		}

		printf( '%s%s%s',
			$args['before'],
			$items_html,
			$args['after']
		);
	}
}

function changeWPQueryEventsPage(){
	global $post;
	$tpl=get_post_meta($post->ID,'_wp_page_template',true);
	if($tpl=='page-calendar.php'){
		global $tours_list,$tours_query;
		$tours_list=array();
		$mq=array(
			array(
				'key'=>'tour_booking_periods',
				'value'=>'',
				'compare'=>'!=',
			),
		);
		$tours_query=new WP_Query(array(
			'post_type'=>'product',
			'posts_per_page'=>-1,
			'meta_query'=>$mq,
			'tax_query'=>array(
				array(
					'taxonomy'=>'product_type',
					'field'=>'slug',
					'terms'=>'tour',
					'opeartor'=>'=',
				),
			),
		));
		$ids=array();
		$year=isset($_GET['y']) ? $_GET['y'] : date('Y');
		$days_list=array();
		foreach($tours_query->posts as $p){
			$prices_and_dates=get_post_meta($p->ID,'_prices_values_by_dates');
			if(is_array($prices_and_dates)){
				foreach($prices_and_dates as $row){
					foreach($row as $val){
						$parts=explode('.',$val['date_from']);
						$month_of_start_date=intval($parts[1]);
						$year_of_start_date=$parts[2];
						if($year_of_start_date==$year && !isset($days_list[$val['date_from'].'_'.$p->ID])){
							$tours_list[intval($year_of_start_date).'_'.intval($month_of_start_date)][/*$p->ID*/]=$p;
							$price=str_replace('.','',($val['price']));
							$days_list[$val['date_from'].'_'.$p->ID]=array('date'=>$val['date_from'],'price'=>$price);
							if(isset($_GET['month']) && intval($month_of_start_date)==$_GET['month'] && $val && intval($year_of_start_date)==$year) $ids[]=$p->ID;
						}
					}
				}
			}else{
				$date=get_post_meta($p->ID,'tour_booking_periods',true);
				if(is_array($date)){
					foreach($date as $d) if($d['exact_dates']){
						foreach($d['exact_dates'] as $val){
							$val=explode('-',$val);
							/*if(!isset($tours_list[intval($val[0]).'_'.intval($val[1])][$p->ID])) */$tours_list[intval($val[0]).'_'.intval($val[1])][/*$p->ID*/]=$p;
							if(isset($_GET['month']) && intval($val[1])==$_GET['month'] && $val && intval($val[0])==$year) $ids[]=$p->ID;
						}
					}
				}
			}
		}
		if(sizeOf($ids)>0) $tours_query=new WP_Query(array(
			'post_type'=>'product',
			'posts_per_page'=>-1,
			'post__in'=>$ids,
			'tax_query'=>array(
				array(
					'taxonomy'=>'product_type',
					'field'=>'slug',
					'terms'=>'tour',
					'opeartor'=>'=',
				),
			),
		));
	}
}
add_action('wp','changeWPQueryEventsPage');

require_once get_stylesheet_directory()."/inc/importer/admin.class.php";

require_once get_stylesheet_directory()."/inc/passolution/api.php";
$pass=new Passolution();
require_once get_stylesheet_directory()."/inc/xmlexport/export.class.php";

add_action("save_post_product","savePriceForProductsList");
function savePriceForProductsList($post_id){
    $post = get_post($post_id);
    if($post->post_type!='product') return;
    $content = $post->post_content;
    $tour_info_regexp = '/(\d{2}\.\d{2}\.).+?(\d{2}\.\d{2}\.\d{4}).*?(\d{1,2}).*?(\d+\.\d{3}|\d{3})/s'; /* 1-start date;2-end date;3-days;4-price */
    if (preg_match($tour_info_regexp, $content)) {
        preg_match_all($tour_info_regexp,$content,$tour_info, PREG_SET_ORDER);
        $result=array();
        foreach ($tour_info as $row) {
            /* add year for start date  */
            preg_match('/\d{2}\.(\d{2})/',$row[1],$month_of_start_date);
            preg_match('/\d{2}\.(\d{2})/',$row[2],$month_of_end_date);
            preg_match('/\d{2}/',$row[2],$day_of_end_date);
            preg_match('/\d{4}/',$row[2],$current_year);
            if ($month_of_start_date[1] == 12 && $month_of_end_date[1] == 1 && ($day_of_end_date[0] - $row[3]) < 0) { /* check if the tour in the new year */
                $start_date = $row[1] . ($current_year[0] - 1);
            } else {
                $start_date = $row[1] . $current_year[0];
            }
            $result[]=array(
                'date_from'=>$start_date,
                'date_to'=>$row[2],
                'price'=>$row[4],
            );
        }
        delete_post_meta($post_id,'_prices_values_by_dates');
        update_post_meta($post_id,'_prices_values_by_dates', $result);
    }
}

// include functions for checkout form
require_once get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'functions-form-checkout.php';

add_filter('admin_init', 'addHeaderInjectionSetting');
function addHeaderInjectionSetting(){
    register_setting('general','_header_injection', '');
    add_settings_field('_header_injection', '<label for="_header_injection">'.__('Header injection').'</label>' , 'headerInjectionHTML', 'general');
}
function headerInjectionHTML(){
    $value=get_option('_header_injection','');
    echo '<textarea cols="80" id="_header_injection" name="_header_injection">'.$value.'</textarea>';
}
add_action('wp_head','addHeaderInjection');
function addHeaderInjection(){
    $value=get_option('_header_injection','');
	if(trim($value)!='') echo $value;
}

add_filter('admin_init', 'addFormBlattLinkSetting');
function addFormBlattLinkSetting(){
    register_setting('general','_formblatt_link', '');
    add_settings_field('_formblatt_link', '<label for="_formblatt_link">'.__('Formblatt link','adventure-tours-child').'</label>' , 'formBlattHTML', 'general');
}
function formBlattHTML(){
    $value=get_option('_formblatt_link','');
    echo '<input type="text" id="_formblatt_link" name="_formblatt_link" value="'.esc_attr($value).'">';
}

// add field for file upload on checkout page
add_filter('woocommerce_form_field_file','addFileFieldToForm',10,4);
function addFileFieldToForm($field,$key,$args,$value){
	if($args['type']=='file'){
		if($args['label']){
			$field.='<label for="'.esc_attr($args['id']).'" class="'.esc_attr(implode(' ', $args['label_class'])).'">'.$args['label'].'</label>';
		}
		$field.='<span class="woocommerce-input-wrapper"><input type="file" name="'.$key.'" '.($args['multiple']===true ? 'multiple' : '').'>';
		if($args['description']){
			$field.='<span class="description" id="'.esc_attr($args['id']).'-description" aria-hidden="true">'.wp_kses_post($args['description']).'</span>';
		}
		$field.='</span>';
		$cont='<p class="form-row %1$s" id="%2$s">%3$s</p>';
		$container_class=esc_attr(implode( ' ', $args['class']));
		$container_id=esc_attr($args['id']) . '_field';
		$field=sprintf($cont,$container_class,$container_id,$field);
	}
	return $field;
}

// get links of terms files/pages for woocommerce germanized plugin
add_filter('woocommerce_gzd_legal_page_permalink','changeTermsLinks',10,2);
function changeTermsLinks($link,$type){
	if(!WC()->cart) return $link;
	foreach(WC()->cart->get_cart() as $cart_item_key=>$cart_item){
		$_product=apply_filters('woocommerce_cart_item_product',$cart_item['data'],$cart_item,$cart_item_key);
	}
	if($_product){
		$pid=$_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
		$l=get_post_meta($pid,'_links_'.$type,true);
		if($l && trim($l)!='') $link=$l;
	}
	return $link;
}

$meta_box_tour_terms = array(
	'id'       => 'meta-box-tour-terms',
	'title'    => 'Rechtliche Dokumente für diese Tour', // Tour term links
	'page'     => 'product',
	'context'  => 'normal',
	'priority' => 'default',
	'fields'   => array(
		array(
			'name' => 'Allgem. Geschäftsbedingungen', // Terms & Conditions
			'id'   => '_links_terms',
			'type' => 'button',
			'std'  => ''
		),
		// array(
		// 	'name' => 'Widerrufsbelehrung', // Power of revocation
		// 	'id'   => '_links_revocation',
		// 	'type' => 'button',
		// 	'std'  => ''
		// ),
		// array(
		// 	'name' => 'Datenschutzerklärung', // Privacy Policy
		// 	'id'   => '_links_data_security',
		// 	'type' => 'button',
		// 	'std'  => ''
		// ),
		array(
			'name' => 'Formblatt',
			'id'   => '_links_formblatt',
			'type' => 'button',
			'std'  => ''
		),
	)
);

add_action('admin_menu', 'addBoxTermsLinks');


function addBoxTermsLinks() {
	global $meta_box_tour_terms;
	add_meta_box($meta_box_tour_terms['id'], $meta_box_tour_terms['title'], 'showBoxTermsLinks', $meta_box_tour_terms['page'], $meta_box_tour_terms['context'], $meta_box_tour_terms['priority']);
}

add_action('save_post_product', 'saveTourTermsLinks');
function saveTourTermsLinks($post_id) {
	global $meta_box_tour_terms;
	foreach($meta_box_tour_terms['fields'] as $field){
		$new=$_POST[$field['id']];
		update_post_meta($post_id,$field['id'],$new);
	}
}
function showBoxTermsLinks() {
	global $meta_box_tour_terms, $post;

	echo '<input type="hidden" name="meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

	echo '<table class="form-table">';
	foreach($meta_box_tour_terms['fields'] as $field){
		$meta=get_post_meta($post->ID, $field['id'], true);
		switch ($field['type']){
			case 'text':
                echo '<tr style="border-top:1px solid #eeeeee;">',
                    '<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
                    '<td>';
                echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" size="30" style="width:75%; margin-right: 20px; float:left;" />';
			break;
			case 'button':
                echo '<tr style="border-top:1px solid #eeeeee;">',
                    '<th style="width:25%"><label for="', $field['id'], '"><strong>', $field['name'], '</strong><span style=" display:block; color:#999; margin:5px 0 0 0; line-height: 18px;">'. $field['desc'].'</span></label></th>',
                    '<th>';
                echo '<input type="text" class="tttttt" name="'.$field['id'].'" id="'.$field['id'].'" value="'.($meta ? $meta : '').'" style="width:75%; margin-right: 20px; float:left;" />
                      <a href="#" class="aw_upload_file_button button button-secondary">Auswählen</a></th>';
			break;
		}
	}
	echo '</table>';
}

add_action('add_meta_boxes', 'add_meta_imagesNumber_RightBlock');
function add_meta_imagesNumber_RightBlock()
{
    add_meta_box('woocommerce-product-imagesNumber_RightBlock', __('Anzahl der Bilder auf der rechten Seite (Reiseverlauf)', 'woocommerce'), 'add_meta_imagesNumber_RightBlock_callback', 'product', 'side', 'low');
}

function add_meta_imagesNumber_RightBlock_callback($post, $meta)
{
    woocommerce_wp_text_input(
        array(
            'id' => '_rightBlock_number_of_images',
            'label' => __('Maximale Anzahl der Bilder', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        )
    );
}

add_action('woocommerce_process_product_meta', 'imagesNumber_RightBlock_filed_save');
function imagesNumber_RightBlock_filed_save($post_id)
{
    if (isset($_POST['_rightBlock_number_of_images']))
        update_post_meta($post_id, '_rightBlock_number_of_images', $_POST['_rightBlock_number_of_images']);
}

add_action('adventure_tours_before_tours_loop', 'sort_for_tours');
function sort_for_tours() {
    do_action('woocommerce_before_shop_loop');
}

add_action( 'wp_head', 'metaTag_color' );
function metaTag_color(){
    $color = get_option('_header_meta_tag_color', '');
    if (trim($color) != '') {
        echo '<meta name="theme-color" content="' . $color . '" />';
    }
}

add_filter('admin_init', 'addHeader_metaTagColor');
function addHeader_metaTagColor()
{
    register_setting('general', '_header_meta_tag_color', '');
    add_settings_field('_header_meta_tag_color', '<label for="_header_meta_tag_color">' . __('Browser theme-color') . '</label>', 'headerMetaTagColorHTML', 'general');
}

function headerMetaTagColorHTML()
{
    $value = get_option('_header_meta_tag_color', '');
    echo '<input type="text" id="_header_meta_tag_color" name="_header_meta_tag_color" value="' . $value . '">';
}

add_action('add_meta_boxes', 'add_meta_tour_order');
function add_meta_tour_order()
{
    add_meta_box('woocommerce-product-tour-order', __('Priorität der Reisen', 'woocommerce'), 'add_meta_tour_order_callback', 'product', 'side', 'low');
}

function add_meta_tour_order_callback($post, $meta)
{
    woocommerce_wp_text_input(
        array(
            'id' => '_tour_order',
            'label' => __('Priorität der Sortierung nach Kategorie', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        )
    );
}

add_action('woocommerce_process_product_meta', 'tour_order_filed_save');
function tour_order_filed_save($post_id)
{
    if (isset($_POST['_tour_order']))
        update_post_meta($post_id, '_tour_order', $_POST['_tour_order']);
}

add_filter('auto_update_plugin', function(){ return false; });
add_filter('auto_update_theme', function(){ return false; });

// Remove the result count from WooCommerce
remove_action( 'woocommerce_before_shop_loop' , 'woocommerce_result_count', 20 );



// add "Partners" post-type which will use for tours
function addPartnersPostType() {
    register_post_type('partner',array(
        'labels'=>array(
            'name'               => 'Partners',
            'singular_name'      => 'Partner',
            'add_new'            => 'Add new',
            'add_new_item'       => 'Add new partner',
            'edit_item'          => 'Edit partner',
            'new_item'           => 'New partner',
            'view_item'          => 'View partner',
            'search_items'       => 'Find partner',
            'not_found'          => 'Partner not found',
            'menu_name'          => 'Partner'
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => true,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title','editor','author','thumbnail','comments')
    ));
}
add_action('init','addPartnersPostType');


function addTourPartners(){
    add_meta_box('tour_partner','Reiseveranstalter','tourPartnersList','product','side');
}
function tourPartnersList(){
    global $post;
    if($post->post_type!='product') return;
    $partners=new WP_Query(array(
        'post_type'=>'partner',
        'posts_per_page'=>-1,
        'orderby'=>'title',
        'order'=>'ASC'
    ));
    ob_start(); ?>
    <select name="tour_partner[]">
        <?php foreach($partners->posts as $partner) :?>
            <option value="<?=$partner->ID?>"
                    <?php
                    $partner_meta = get_post_meta( $post->ID, '_tour_partner', true );
                    if (empty($partner_meta) && $partner->post_title == "Moskito Adventures") {
                        echo 'selected';
                    }
                    $selected_partner = get_the_title($partner_meta[0]);
                    if ($selected_partner == $partner->post_title) {
                        echo 'selected';
                    }
                    ?>><?=$partner->post_title?></option>
        <?php endforeach; ?>
    </select>
    <?php echo ob_get_clean();
}

function saveTourPartners($pid){
    if($_POST['post_type']!='product') return;
    $partner='';
    if(isset($_POST['tour_partner'])) { $partner=$_POST['tour_partner']; }
    update_post_meta($pid,'_tour_partner', $partner);
}
add_action( 'save_post_product', 'saveTourPartners', 10, 3 );
add_action( 'admin_menu', 'addTourPartners' );


// add media button for terms links
function add_media_button_script() {
    if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    wp_enqueue_script( 'awscript', get_stylesheet_directory_uri() . '/js/media_button.js', array('jquery'), null, false );
}
add_action( 'admin_enqueue_scripts', 'add_media_button_script' );
