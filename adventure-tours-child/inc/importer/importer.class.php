<?php
class ToursImporter{
	var $db=null;
	var $phpexcel=null;
	var $data=null;
	var $result=null;
	var $error='';
	var $ids=array();
	var $skip=0;
	var $skip_exists=true;
	function __construct($file='',$fname='',$params=array()){
		global $wpdb;
		$this->db=$wpdb;
		if(!$file){ $this->error='Incorrect file'; return false; }
		$fext='';
		if($fname!=''){ $fext=$this->getFileExt($fname); }
		if($fext==''){ $fext=$this->getFileExt($name); }
		if(is_numeric($params['skip']))$this->skip=$params['skip'];
		if($fext=='xml') $this->loadXMLData($file);
		else $this->loadXLSData($file);
		if(!is_array($this->data) || sizeOf($this->data)==0){ $this->error='File is not correct';  return false; }
		if(!$this->insertInfo()){ $this->error='Error on data inserting, please try again later'; return false; }
	}
	protected function getFileExt($f=''){		
		$name=basename($f);
		$res='';
		if(strpos($name,'.')!==false){
			preg_match('/\.(xlsx|xml|xls|tmp)/',$name,$ext);
			if($ext && $ext[1]) $res=$ext[1];
		}
		return $res;
	}
	private function trimArray(&$arr){
		foreach($arr as $k=>$v){
			if(!is_array($v)){
				$arr[$k]=is_bool($v) ? $v : trim($v);
			}
			else $this->trimArray($arr[$k]);
		}
	}
	private function loadXLSData($file){
		if(!file_exists(__DIR__ . '/PHPExcel/PHPExcel/IOFactory.php')) return false;
		require_once __DIR__ . '/PHPExcel/PHPExcel/IOFactory.php';
		if(!class_exists('PHPExcel_IOFactory') || !method_exists('PHPExcel_IOFactory','load')) return false;
		$xls = PHPExcel_IOFactory::load($file);
		$xls->setActiveSheetIndex(0);
		$sheet=$xls->getActiveSheet();
		if(!$sheet) return false;
		$i=0;
		foreach ($sheet->toArray() as $row){
			$this->trimArray($row);
			if($this->skip>0 && ++$i<=$this->skip) continue;
			if($row[0]!=''){ $k=is_numeric($k) ? ++$k : 0;	}
			if($row[0]!='') $this->data[$k]['code']=$row[0];
			if($row[1]!='') $this->data[$k]['name']=$row[1];
			if($row[2]!='') $this->data[$k]['subtitle']=$row[2];
			if($row[3]!='') $this->data[$k]['content']=$row[3];
			if($row[4]!='') $this->data[$k]['highlight']=$row[4];
			if($row[5]!='') $this->data[$k]['duration']=$row[5];
			if($row[6]!='') $this->data[$k]['extension']=$row[6];
			if($row[7]!='') $this->data[$k]['group']=$row[7];
			if($row[8]!='') $this->data[$k]['price']=$row[8];
			if($row[9]!='') $this->data[$k]['map']=$row[9];
			if($row[10]!='') $this->data[$k]['rating']=$row[10];
			if($row[11]!='') $this->data[$k]['requirement']=$row[11];
			if($row[12]!='') $this->data[$k]['courier']=$row[12];
			if(!is_array($this->data[$k]['steps'])) $this->data[$k]['steps']=array();
			if($row[13]!='') $this->data[$k]['steps'][]=$row[13];
			if(!is_array($this->data[$k]['prices'])) $this->data[$k]['prices']=array();
			if($row[14]!='') $this->data[$k]['prices'][]=$row[14];
			if($row[15]!='') $this->data[$k]['service']=$row[15];
			if($row[16]!='') $this->data[$k]['extras']=$row[16];
			if($row[17]!='') $this->data[$k]['additional'][]=$row[17];
			if($row[18]!='') $this->data[$k]['operator']=$row[18];
			if(!is_array($this->data[$k]['dates'])) $this->data[$k]['dates']=array();
			if($row[19]!='') $this->data[$k]['dates'][]=$row[19];
			if(!is_array($this->data[$k]['images'])) $this->data[$k]['images']=array();
			if($row[20]!='') $this->data[$k]['images'][]=$row[20];
			if(!is_array($this->data[$k]['guides'])) $this->data[$k]['guides']=array();
			if($row[21]!='') $this->data[$k]['guides'][]=$row[21];
			if($row[22]!='') $this->data[$k]['pricetext']=$row[22];
			if($row[23]!='') $this->data[$k]['destco']=$row[23];
			if($row[24]!='') $this->data[$k]['tickets_number']=$row[24];
			if($row[25]!='') $this->data[$k]['organizator'][]=$row[25];
		}
	}
	
	private function loadXMLData($file){
		$xml=file_get_contents($file);
		if(!$xml) return false;
		$xml=new SimpleXMLElement($xml);
		if(!$xml) return false;
		$k=0;
		foreach($xml->tour as $tour){
			$this->data[$k]['code']=(string)$tour->code;
			$this->data[$k]['name']=(string)$tour->name;
			$this->data[$k]['subtitle']=(string)$tour->excerpt;
			$this->data[$k]['content']=(string)$tour->description;
			$this->data[$k]['highlight']='';
			if($tour->highlights) foreach($tour->highlights->item as $hl) $this->data[$k]['highlight'].=(string)$hl.';';
			$this->data[$k]['duration']=(string)$tour->duration;
			$this->data[$k]['extension']=(string)$tour->extension;
			$this->data[$k]['group']=(string)$tour->group;
			$this->data[$k]['price']=(string)$tour->price;
			$this->data[$k]['map']=(string)$tour->map;
			$this->data[$k]['rating']=(string)$tour->rating;
			$this->data[$k]['requirement']=(string)$tour->requirement;
			$this->data[$k]['courier']=(string)$tour->courier;
			$this->data[$k]['steps']=array();
			if($tour->individual){
				$st=0;
				foreach($tour->individual->item as $ind){
					if($ind->num) $this->data[$k]['steps'][$st]['num']=(string)$ind->num;
					if($ind->title) $this->data[$k]['steps'][$st]['name']=(string)$ind->title;
					if($ind->text) $this->data[$k]['steps'][$st]['content']=(string)$ind->text;
					++$st;
				}
			}
			if($tour->dates){
				$this->data[$k]['dates']=array();
				foreach($tour->dates->date as $date){
					$this->data[$k]['dates'][]=(string)$date;
				}
			}
			if($tour->guides){
				$this->data[$k]['guides']=array();
				foreach($tour->guides->guide as $guide){
					$this->data[$k]['guides'][]=(string)$guide->name;
				}
			}
			if($tour->images){
				$this->data[$k]['images']=array();
				foreach($tour->images->image as $image){
					$this->data[$k]['images'][]=(string)$image;
				}
			}
			$this->data[$k]['service']=(string)$tour->service;
			$this->data[$k]['extras']=(string)$tour->extras;
			$this->data[$k]['additional']=(string)$tour->additional;
			$this->data[$k]['operator']=(string)$tour->operator;		
			$this->data[$k]['pricetext']=(string)$tour->pricetext;
			$this->data[$k]['destco']=(string)$tour->destco;
			$this->data[$k]['prices']=array();
			if($tour->pricetermine){
				foreach($tour->pricetermine->item as $item){
					$cells=(array)$item->cell;
					$this->data[$k]['prices'][]=array($cells[0],$cells[1],$cells[2],$cells[3]);
				}
			}
			++$k;
		}
	}
	function addWCTaxonomies(){
		if(!taxonomy_exists('product_type')){
			register_taxonomy(
				'product_type',
				apply_filters( 'woocommerce_taxonomy_objects_product_type', array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_product_type', array(
						'hierarchical'      => false,
						'show_ui'           => false,
						'show_in_nav_menus' => false,
						'query_var'         => is_admin(),
						'rewrite'           => false,
						'public'            => false,
					)
				)
			);
		}
		
		register_taxonomy(
			'product_visibility',
			apply_filters( 'woocommerce_taxonomy_objects_product_visibility', array( 'product', 'product_variation' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_visibility', array(
					'hierarchical'      => false,
					'show_ui'           => false,
					'show_in_nav_menus' => false,
					'query_var'         => is_admin(),
					'rewrite'           => false,
					'public'            => false,
				)
			)
		);

		register_taxonomy(
			'product_cat',
			apply_filters( 'woocommerce_taxonomy_objects_product_cat', array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_cat', array(
					'hierarchical'          => true,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Categories', 'woocommerce' ),
					'labels'                => array(
						'name'              => __( 'Product categories', 'woocommerce' ),
						'singular_name'     => __( 'Category', 'woocommerce' ),
						'menu_name'         => _x( 'Categories', 'Admin menu name', 'woocommerce' ),
						'search_items'      => __( 'Search categories', 'woocommerce' ),
						'all_items'         => __( 'All categories', 'woocommerce' ),
						'parent_item'       => __( 'Parent category', 'woocommerce' ),
						'parent_item_colon' => __( 'Parent category:', 'woocommerce' ),
						'edit_item'         => __( 'Edit category', 'woocommerce' ),
						'update_item'       => __( 'Update category', 'woocommerce' ),
						'add_new_item'      => __( 'Add new category', 'woocommerce' ),
						'new_item_name'     => __( 'New category name', 'woocommerce' ),
						'not_found'         => __( 'No categories found', 'woocommerce' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => array(
						'slug'         => $permalinks['category_rewrite_slug'],
						'with_front'   => false,
						'hierarchical' => true,
					),
				)
			)
		);

		register_taxonomy(
			'product_tag',
			apply_filters( 'woocommerce_taxonomy_objects_product_tag', array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_tag', array(
					'hierarchical'          => false,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Product tags', 'woocommerce' ),
					'labels'                => array(
						'name'                       => __( 'Product tags', 'woocommerce' ),
						'singular_name'              => __( 'Tag', 'woocommerce' ),
						'menu_name'                  => _x( 'Tags', 'Admin menu name', 'woocommerce' ),
						'search_items'               => __( 'Search tags', 'woocommerce' ),
						'all_items'                  => __( 'All tags', 'woocommerce' ),
						'edit_item'                  => __( 'Edit tag', 'woocommerce' ),
						'update_item'                => __( 'Update tag', 'woocommerce' ),
						'add_new_item'               => __( 'Add new tag', 'woocommerce' ),
						'new_item_name'              => __( 'New tag name', 'woocommerce' ),
						'popular_items'              => __( 'Popular tags', 'woocommerce' ),
						'separate_items_with_commas' => __( 'Separate tags with commas', 'woocommerce' ),
						'add_or_remove_items'        => __( 'Add or remove tags', 'woocommerce' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags', 'woocommerce' ),
						'not_found'                  => __( 'No tags found', 'woocommerce' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => array(
						'slug'       => $permalinks['tag_rewrite_slug'],
						'with_front' => false,
					),
				)
			)
		);

		register_taxonomy(
			'product_shipping_class',
			apply_filters( 'woocommerce_taxonomy_objects_product_shipping_class', array( 'product', 'product_variation' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_shipping_class', array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => __( 'Shipping classes', 'woocommerce' ),
					'labels'                => array(
						'name'              => __( 'Product shipping classes', 'woocommerce' ),
						'singular_name'     => __( 'Shipping class', 'woocommerce' ),
						'menu_name'         => _x( 'Shipping classes', 'Admin menu name', 'woocommerce' ),
						'search_items'      => __( 'Search shipping classes', 'woocommerce' ),
						'all_items'         => __( 'All shipping classes', 'woocommerce' ),
						'parent_item'       => __( 'Parent shipping class', 'woocommerce' ),
						'parent_item_colon' => __( 'Parent shipping class:', 'woocommerce' ),
						'edit_item'         => __( 'Edit shipping class', 'woocommerce' ),
						'update_item'       => __( 'Update shipping class', 'woocommerce' ),
						'add_new_item'      => __( 'Add new shipping class', 'woocommerce' ),
						'new_item_name'     => __( 'New shipping class Name', 'woocommerce' ),
					),
					'show_ui'               => false,
					'show_in_quick_edit'    => false,
					'show_in_nav_menus'     => false,
					'query_var'             => is_admin(),
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => false,
				)
			)
		);

		global $wc_product_attributes;

		$wc_product_attributes = array();
		$attribute_taxonomies  = wc_get_attribute_taxonomies();

		if($attribute_taxonomies){
			foreach ( $attribute_taxonomies as $tax ) {
				$name = wc_attribute_taxonomy_name( $tax->attribute_name );

				if ( $name ) {
					$tax->attribute_public          = absint( isset( $tax->attribute_public ) ? $tax->attribute_public : 1 );
					$label                          = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;
					$wc_product_attributes[ $name ] = $tax;
					$taxonomy_data                  = array(
						'hierarchical'          => false,
						'update_count_callback' => '_update_post_term_count',
						'labels'                => array(
							'name'              => sprintf( _x( 'Product %s', 'Product Attribute', 'woocommerce' ), $label ),
							'singular_name'     => $label,
							'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
							'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
							'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
							'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
							'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
							'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
							'add_new_item'      => sprintf( __( 'Add new %s', 'woocommerce' ), $label ),
							'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label ),
							'not_found'         => sprintf( __( 'No &quot;%s&quot; found', 'woocommerce' ), $label ),
						),
						'show_ui'               => true,
						'show_in_quick_edit'    => false,
						'show_in_menu'          => false,
						'meta_box_cb'           => false,
						'query_var'             => 1 === $tax->attribute_public,
						'rewrite'               => false,
						'sort'                  => false,
						'public'                => 1 === $tax->attribute_public,
						'show_in_nav_menus'     => 1 === $tax->attribute_public && apply_filters( 'woocommerce_attribute_show_in_nav_menus', false, $name ),
						'capabilities'          => array(
							'manage_terms' => 'manage_product_terms',
							'edit_terms'   => 'edit_product_terms',
							'delete_terms' => 'delete_product_terms',
							'assign_terms' => 'assign_product_terms',
						),
					);

					if ( 1 === $tax->attribute_public && sanitize_title( $tax->attribute_name ) ) {
						$taxonomy_data['rewrite'] = array(
							'slug'         => trailingslashit( $permalinks['attribute_rewrite_slug'] ) . sanitize_title( $tax->attribute_name ),
							'with_front'   => false,
							'hierarchical' => true,
						);
					}

					register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", array( 'product' ) ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );
				}
			}
		}
	}
	private function removeActions(){
		if(class_exists('WC_Germanized_Meta_Box_Product_Data')){
			remove_action( 'woocommerce_update_product', array('WC_Germanized_Meta_Box_Product_Data', 'update_after_save' ),10);
			remove_action( 'woocommerce_create_product', array('WC_Germanized_Meta_Box_Product_Data', 'update_after_save' ), 10);
			remove_action( 'woocommerce_update_product_variation', array( 'WC_Germanized_Meta_Box_Product_Data', 'update_after_save' ), 10);

			if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
				remove_action( 'woocommerce_create_product_variation', array( 'WC_Germanized_Meta_Box_Product_Data', 'update_after_save' ), 10);
			} else {
				remove_action( 'woocommerce_new_product_variation', array( 'WC_Germanized_Meta_Box_Product_Data', 'update_after_save' ), 10);
			}
		}
	}
	private function insertInfo(){
		$this->addWCTaxonomies();
		$this->removeActions();
		if(!$this->data || sizeOf($this->data)==0) return false;
		$guides_cache=$orgs_cache=array();
		foreach($this->data as $data){
			if($this->skip_exists){
				$exists=new WP_Query(array(
					'post_type'=>'product',
					'meta_query'=>array(
						'AND',
						array(
							'key'=>'_sku',
							'value'=>$data['code'],
							'compare'=>'=',
						),
					),
					'posts_per_page'=>1,
				));
				if(sizeOf($exists->posts)){
					if(!is_array($this->exists)) $this->exists=array();
					$this->exists=array_merge($this->exists,$exists->posts);
					continue;
				}
			}
			$attributes=array();	
			$ins=array(
				'post_title'=>$data['name'],
				'post_content'=>$data['content'],
				'post_status'=>'publish',
				'post_type'=>'product',
				'post_name'=>sanitize_title($data['name']),
				'meta_input'=>array(
					'_visibility'=>'visible',
					'_stock_status'=>'instock',
					'_product_version'=>'3.5.4',
					'_variable_tour'=>'yes',
					'total_sales'=>'0',
					'_downloadable'=>'no',
					'_tax_status'=>'taxable',
					'_virtual'=>'no',
					'_regular_price'=>$data['price'],
					'_price'=>$data['price'],
					'_sale_price'=>"0",
					'_purchase_note'=>"",
					'_featured'=>"no",
					'_weight'=>"",
					'_length'=>"",
					'_length'=>"",
					'_width'=>"" ,
					'_height'=>"",
					'_sku'=>$data['code'],
					'_product_attributes', array(),
					'_sale_price_dates_from'=>"",
					'_sale_price_dates_to'=>"",
					'tour_booking_periods'=>'',
					'_price'=>"0",
					'_sold_individually'=>"no",
					'_manage_stock'=>"no",
					'_backorders'=>"no",
					'_stock'=>"",
					'_thumbnail_id'=>'',
					'_product_image_gallery'=>'',
					'_tour_order'=>'',
				),
				'tax_input'=>array(
					'product_type'=>array('tour'),
				),
			);
			if($data['duration']!=''){
				$ins['tax_input']['pa_duration']=$data['duration'];
				$attributes['pa_duration']=array(
					'name'=>'pa_duration',
					'value'=>$data['duration'],
					'position'=>0,
					'is_visible'=>1,
					'is_variation'=>0,
					'is_taxonomy'=>1,
				);
			}
			if($data['operator']!=''){
				$ins['tax_input']['pa_agency']=$data['operator'];
				$attributes["pa_agency"]=array(
					"name"=>"pa_agency",
					"value"=>$data['operator'],
					"position"=>3,
					"is_visible"=>0,
					"is_variation"=>0,
					"is_taxonomy"=>1,
				);
			}
			if($data['group']!=''){
				$ins['tax_input']['pa_hardness']=$data['group'];
				$attributes["pa_hardness"]=array(
					"name"=>"pa_hardness",
					"value"=>$data['group'],
					"position"=>4,
					"is_visible"=>0,
					"is_variation"=>0,
					"is_taxonomy"=>1,
				);				
			}
			if($data['rating']!=''){
				$ins['tax_input']['pa_moskitos']='[moskitos count="'.$data['rating'].']';
				$attributes["pa_moskitos"]=array(
					"name"=>"pa_moskitos",
					"value"=>'[moskitos count="'.$data['rating'].']',
					"position"=>4,
					"is_visible"=>0,
					"is_variation"=>0,
					"is_taxonomy"=>1,
				);	
			}
			if($data['pricetext']!=''){
				$ins['tax_input']['pa_price']=$data['pricetext'];
				$attributes["pa_price"]=array(
					"name"=>"pa_price",
					"value"=>$data['pricetext'],
					"position"=>5,
					"is_visible"=>0,
					"is_variation"=>0,
					"is_taxonomy"=>1,
				);	
			}
			$ins['tax_input']['pa_age']='Erwachsener';
			$attributes["pa_age"]=array(
				"name"=>"pa_age",
				"value"=>'Erwachsener',
				"position"=>5,
				"is_visible"=>0,
				"is_variation"=>1,
				"is_taxonomy"=>1,
			);
			$ins['tax_input']['pa_extension']=$data['extension'];
			$attributes["pa_extension"]=array(
				"name"=>"pa_extension",
				"value"=>$data['extension'],
				"position"=>5,
				"is_visible"=>0,
				"is_variation"=>1,
				"is_taxonomy"=>1,
			);
			$tabs=array();
			$tc=0;
			if(is_array($data['steps'])){
				$tabs[$tc]['title']='Reiseverlauf';
				$tabs[$tc]['content']='[timeline]';
				$sc=0;
				foreach($data['steps'] as $step){
					$num=$name=$content='';
					if(is_array($step)){
						if(trim($step['name'])!='') $name=$step['name'];
						if(trim($step['num'])!='') $num=$step['num'];
						$content=$step['content'];
					}else{
						$num=(++$sc).'. Tag';
						$content=$step;
					}
					$content=preg_replace('/\|([^\|]{1,})\|/','<div class="title">$1</div>',$content);
					$tabs[$tc]['content'].='[timeline_item item_number="'.$num.'" title="'.$name.'"]'.$content.'[/timeline_item]';
				}
				++$tc;
			}
			if($data['extras']!=''){
				$tabs[$tc]['title']='Leistungen';
				if($data['service']!='' || is_array($data['service'])){
					if(!is_array($data['service'])) $data['service']=explode(';',$data['service']);
					$tabs[$tc]['content']='<h3>Leistungen:</h3><ul class="bullet-check">';
					foreach($data['service'] as $s) $tabs[$tc]['content'].='<li>'.$s.'</li>';
					$tabs[$tc]['content'].='</ul>';
				}
				/*$tabs[$tc]['content']='<h3>Unser Leistungspaket für Sie:</h3><ul class="bullet-check">';
				$hs=explode(";",$data['highlight']);
				foreach($hs as $h) if(trim($h)!='') $tabs[$tc]['content'].='<li>'.$h.'</li>';
				$tabs[$tc]['content'].='</ul>';*/
				$tabs[$tc]['content'].='<h3>Extras:</h3>';
				$es=explode(";",$data['extras']);
				$tabs[$tc]['content'].='<ul class="bullet-check">';
				foreach($es as $e) if(trim($e)!='') $tabs[$tc]['content'].='<li>'.$e.'</li>';
				$tabs[$tc]['content'].='</ul>';
				++$tc;
			}
			if($data['additional']!='' || $data['destco']!=''){
				$tabs[$tc]['title']='Hinweise';
				if($data['additional']){
					if(is_array($data['additional'])) $data['additional']=implode('<br>',$data['additional']);
					$tabs[$tc]['content'].=preg_replace('/\|([^\|]{1,})\|/','<h3 style="text-decoration:underline;">$1</h3>',$data['additional']);
				}
				if($data['destco']!=''){
					$tabs[$tc]['content'].='[passolution destco="'.$data['destco'].'"]';
				}
				$tabs[$tc]['content'].='<br>Änderungen vorbehalten';
				++$tc;
			}
			$ins['meta_input']['tour_tabs_meta']=array('tour_badge'=>'1','tabs'=>$tabs);
			if(is_array($data['images']) && sizeOf($data['images'])>0){
				$ic=0;
				$gallery=array();
				foreach($data['images'] as $img){
					$aid=$this->getImageFromUrl($img);
					if($aid){
						if(++$ic==1){
							$ins['meta_input']['_thumbnail_id']=$aid;
						}else{
							$gallery[]=$aid;
						}
					}
				}
				if(sizeOf($gallery)>0) $ins['meta_input']['_product_image_gallery']=implode(',',$gallery);
			}
			if(is_array($data['guides']) && sizeOf($data['guides'])>0){
				$guides=array();
				foreach($data['guides'] as $guide){
					if(isset($guides_cache[$guide])) $guides[]=$guides_cache[$guide];
					else{
						$gq=new WP_Query(array(
							'post_type'=>'guide',
							'posts_per_page'=>1,
							'title'=>$guide,
						));
						if(sizeOf($gq->posts)==1){
							$guides[]=$gq->posts[0]->ID;
							$guides_cache[$guide]=$gq->posts[0]->ID;
						}
					}
				}
				$ins['meta_input']['_tour_guides']=implode(',',$guides);
			}
			if(is_array($data['organizator']) && sizeOf($data['organizator'])>0){
				$orgs=array();
				foreach($data['organizator'] as $org){
					if(isset($orgs_cache[$org])) $orgs[]=$orgs_cache[$org];
					else{
						$oq=new WP_Query(array(
							'post_type'=>'partner',
							'posts_per_page'=>1,
							'title'=>$org,
						));
						if(sizeOf($oq->posts)==1){
							$orgs[]=$oq->posts[0]->ID;
							$orgs_cache[$org]=$oq->posts[0]->ID;
						}
					}
				}
				$ins['meta_input']['_tour_partner']=$orgs;
			}
			if(is_array($data['dates']) && sizeOf($data['dates'])>0){
				$min=$max='';
				$dates=array();
				foreach($data['dates'] as $date){
					if($min=='' || $date<=$min) $min=$date;
					if($max=='' || $date>=$max) $min=$date;					
				}
				$dates[]=array(
					'exact_dates'=>$data['dates'],
					'limit'=>(is_numeric($data['tickets_number']) ? $data['tickets_number'] : 0),
					'spec_price'=>'',
					'mode'=>"exact-dates",
					'from'=>$min,
					'to'=>$max,
				);
				$ins['meta_input']['tour_booking_periods']=$dates;
			}
			if(sizeOf($attributes)>0) $ins['meta_input']['_product_attributes']=$attributes;
			if($data['subtitle']!=''){
				$ins['meta_input']['header_section_meta']=array('section_mode'=>'banner','header_section_id'=>'','slider_alias'=>'','banner_subtitle'=>$data['subtitle'],'banner_image'=>'','is_banner_image_parallax'=>'','banner_image_repeat'=>'no-repeat','banner_mask'=>'');
				$ins['post_content']='[tours_gallery hide_thumbs="yes"]'.$ins['post_content'];
			}
			
			$ins['post_content'].='[tabs style="with-shadow"]';
			if($data['map']!='') $ins['post_content'].='[tab_item title="Karte" is_active="on"]'.$data['map'].'[/tab_item]';
			if($data['highlight']!=''){
				$ins['post_content'].='[tab_item title="Höhepunkte"]<ul class="bullet-check">';
				$hs=explode(";",$data['highlight']);
				foreach($hs as $h) if(trim($h)!='') $ins['post_content'].='<li>'.$h.'</li>';
				$ins['post_content'].='</ul>[/tab_item]';
			}
			if(is_array($data['prices']) && sizeOf($data['prices'])>0){
				$ins['post_content'].='[tab_item title="Termine &amp; Preise"]'.$this->getTermineAndPrice($data['prices']).'[/tab_item]';
			}
			$ins['post_content'].='[tab_item title="Reiseleiter"][guides_list][/tab_item]';
			if($data['requirement']!='') $ins['post_content'].='[tab_item title="Anforderung"]'.$data['requirement'].'[/tab_item]';
			$ins['post_content'].='[/tabs]';	
			$prices=$this->getPriceForProductsList($ins['post_content']);
			if($prices) $ins['meta_input']['_prices_values_by_dates']=$prices;
		//	var_dump($ins);
		//	die();
			$id=wp_insert_post($ins);
			$this->createTourVariation($id,array(
				'attributes'=>array('age'  => 'Erwachsener'),
				'sku'           => '',
				'regular_price' => $data['price'],
				'sale_price'    => '',
				'stock_qty'     => 10,
			));
			$this->ids[]=$id;
		}
		return true;
	}
	function getTermineAndPrice($prices=array()){
		if(!is_array($prices) || sizeOf($prices)==0) return '';
		$output='<table class="prices_table"><tbody>';
		foreach($prices as $p){
			if(!is_array($p)) $parts=explode('::',$p);
			else $parts=$p;
			$output.='<tr>';
			if(sizeOf($parts)!=1){
				$output.='<td width="30%">'.$parts[0].'</td>';
				$output.='<td width="15%">'.$parts[1].'</td>';
				$output.='<td width="20%">'.$parts[2].'</td>';
				$output.='<td width="35%">'.$parts[3].'</td>';
			}else{
				$output.='<td width="100%" colspan="4" class="large black">'.$parts[0].'</td>';				
			}
			$output.='</tr>';
		}
		$output.='</tbody></table>';
		return $output;
	}
	private function createTourVariation($pid,$variation_data=array()){
		$product=get_post($pid);
		$variation_post=array(
			'post_title'  => $product->post_title,
			'post_name'   => 'product-'.$pid.'-variation',
			'post_status' => 'publish',
			'post_parent' => $pid,
			'post_type'   => 'product_variation',
			'guid'        => get_permalink($pid),
		);
		$variation_id=wp_insert_post($variation_post);
		$variation=new WC_Product_Variation($variation_id);
		foreach ($variation_data['attributes'] as $attribute=>$term_name){
			$taxonomy = 'pa_'.$attribute;
			if(!taxonomy_exists($taxonomy)){
				register_taxonomy(
					$taxonomy,
				   'product_variation',
					array(
						'hierarchical' => false,
						'label' => ucfirst( $attribute ),
						'query_var' => true,
						'rewrite' => array( 'slug' => sanitize_title($attribute) ), // The base slug
					)
				);
			}
			if(!term_exists($term_name, $taxonomy))	wp_insert_term($term_name, $taxonomy);
			$term_slug=get_term_by('name', $term_name, $taxonomy )->slug;
			$post_term_names =  wp_get_post_terms( $pid, $taxonomy, array('fields' => 'names') );
			if(!in_array($term_name, $post_term_names)) wp_set_post_terms($pid, $term_name, $taxonomy, true);
			update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
		}
		if(!empty( $variation_data['sku'])) $variation->set_sku( $variation_data['sku']);
		
		if(empty($variation_data['sale_price'])){
			$variation->set_price( $variation_data['regular_price'] );
		}else{
			$variation->set_price($variation_data['sale_price']);
			$variation->set_sale_price($variation_data['sale_price']);
		}
		$variation->set_regular_price($variation_data['regular_price']);
		
		if(!empty($variation_data['stock_qty'])){
			$variation->set_stock_quantity( $variation_data['stock_qty'] );
			$variation->set_manage_stock(true);
			$variation->set_stock_status('');
		}else{
			$variation->set_manage_stock(false);
		}
		$variation->set_weight('');
		$variation->save();
	}
	private function getImageFromUrl($url,$post_id=0,$timeout_seconds=5){
		if(!is_numeric($post_id)) return false;
		require_once(ABSPATH.'wp-admin/includes/file.php');
		require_once(ABSPATH.'wp-admin/includes/media.php');
		require_once(ABSPATH.'wp-admin/includes/image.php');
		$temp_file=download_url($url,$timeout_seconds);
		$type='';
		if(mb_strpos($url,'.jpg',0,'UTF-8') || mb_strpos($url,'.jpeg',0,'UTF-8')) $type='image/jpeg';
		elseif(mb_strpos($url,'.png',0,'UTF-8')) $type='image/png';
		elseif(mb_strpos($url,'.gif',0,'UTF-8')) $type='image/gif';
		if($type=='') return false;
		if(!is_wp_error($temp_file)){
			$file=array(
				'name'     => basename($url),
				'type'     => $type,
				'tmp_name' => $temp_file,
				'error'    => 0,
				'size'     => filesize($temp_file),
			);
			$overrides=array(
				'test_form' => false,
				'test_size' => true,
			);
			$results=wp_handle_sideload($file,$overrides);
			
			if(!empty($results['error'])) return false;
			else{
				$file=$results['file'];
				$url=$results['url'];
				$type=$results['type']; 
			}
			$title=preg_replace('/\.[^.]+$/', '', basename($file));
			$content='';
			if($image_meta=wp_read_image_metadata($file)){
				if(trim($image_meta['title']) && !is_numeric(sanitize_title($image_meta['title'])))	$title = $image_meta['title'];
				if(trim($image_meta['caption'])) $content = $image_meta['caption'];
			}
			if(isset($desc)) $title = $desc;		
			
			$attachment = array(
				'post_mime_type' => $type,
				'guid' => $url,
				'post_parent' => $post_id,
				'post_title' => $title,
				'post_content' => $content,
			);
			unset($attachment['ID']);

			$id=wp_insert_attachment($attachment,$file,$post_id);
			if(!is_wp_error($id)) wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$file));
			else return false;
			return $id;
		}
	}
	private function getPriceForProductsList($content){
		$tour_info_regexp = '/(\d{2}\.\d{2}\.).+?(\d{2}\.\d{2}\.\d{4}).*?(\d{1,2}).*?(\d+\.\d{3}|\d{3})/s'; /* 1-start date;2-end date;3-days;4-price */
		if(preg_match($tour_info_regexp, $content)){
			preg_match_all($tour_info_regexp,$content,$tour_info, PREG_SET_ORDER);
			$result=array();
			foreach($tour_info as $row){
				preg_match('/\d{2}\.(\d{2})/',$row[1],$month_of_start_date);
				preg_match('/\d{2}\.(\d{2})/',$row[2],$month_of_end_date);
				preg_match('/\d{2}/',$row[2],$day_of_end_date);
				preg_match('/\d{4}/',$row[2],$current_year);
				if($month_of_start_date[1] == 12 && $month_of_end_date[1] == 1 && ($day_of_end_date[0] - $row[3]) < 0){
					$start_date = $row[1] . ($current_year[0] - 1);
				}else{
					$start_date = $row[1] . $current_year[0];
				}
				$result[]=array(
					'date_from'=>$start_date,
					'date_to'=>$row[2],
					'price'=>$row[4],
				);
			}
		}
		return $result;
	}
}

?>