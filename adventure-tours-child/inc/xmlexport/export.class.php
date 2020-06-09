<?php

class XMLExport{
	protected static $_instance=null;
	private $id=null;
	public static function getInstance(){
		if(!self::$_instance){
			self::$_instance=new self();	
		}
		return self::$_instance;
	}
	public static function inst(){
		return self::getInstance();
	}
	function __construct(){
		$this->addUrlRewrites();
		add_action('wp',array($this,'output'));
	}
	private function addUrlRewrites(){
		flush_rewrite_rules();
		add_filter('query_vars', function($vars){
			$vars[]="xml_tour";
			$vars[]="id_tour";
			return $vars;
		});
		add_rewrite_rule('^(xml-tour)/?$','index.php?xml_tour=1','top');
		add_rewrite_rule('^(xml-tour)/([0-9]{1,})/?$','index.php?xml_tour=1&id_tour=$matches[2]','top');
	}
	public function output(){
		if(is_admin() || headers_sent()) return;
		$is_tour=get_query_var('xml_tour');
		$this->id=get_query_var('id_tour');
		if($is_tour!=1) return;
		$ids=adventure_tours_get_option('tours_in_xml');
		if(!is_numeric($this->id)){
			$args=array(
				'post_type'=>'product',
				'posts_per_page'=>-1,
				'tax_query'=>array(
					array(
						'taxonomy'=>'product_type',
						'field'=>'slug',
						'terms'=>'tour',
						'opeartor'=>'=',
					),
				),
			);
			if(is_array($ids)){
				foreach($ids as $k=>$v) if(!is_numeric($v)) unset($ids[$k]);
				if(sizeOf($ids)>0) $args['post__in']=$ids;
			}
			$tours_query=new WP_Query($args);
			$this->tours=$tours_query->posts;
			if(sizeOf($this->tours)==0) return;
			$this->listOutput();
		}else{
			$this->id=intval($this->id);
			if(is_array($ids) && sizeOf($ids)>0 && !in_array($this->id,$ids)) return;
			$tour=get_post($this->id);
			if(!$tour || is_wp_error($tour)) return;
			$this->tour=$tour;
			$this->singleOutput();
		}
		die();
	}
	private function getTourXMLLink($tid=''){
		if(!is_numeric($tid)) return '';
		return site_url('/xml-tour/'.$tid.'/');
	}
	private function listOutput(){			
		$data['tours']=array();
		foreach($this->tours as $tour){
			$data['tours'][]=array(
				'code'=>get_post_meta($tour->ID,'_sku',true),
				'date'=>$tour->post_date_gmt,
				'link'=>$this->getTourXMLLink($tour->ID),
			);
		}
		header('Content-Type: application/xml');	
		include "xml_list.php";
	}
	private function singleOutput(){
		$data=array();
		$data['name']=$this->tour->post_title;
		$data['code']=get_post_meta($this->tour->ID,'_sku',true);
		$data['excerpt']=get_post_meta($this->tour->ID,'_sku',true);
		$subtitle=get_post_meta($this->tour->ID,'header_section_meta',true);
		$data['excerpt']=$subtitle['banner_subtitle'];
		$data['description']=$this->tour->post_content=preg_replace('/\[tours_gallery([^\]]{1,})\]/','',$this->tour->post_content);
		if(strpos($data['description'],'[tabs')!==false) $data['description']=substr($data['description'],0,strpos($data['description'],'[tabs'));
		$data['preview']=$this->getTourPreview();
		$data['images']=$this->getTourImagesList();
		$terms=$this->getPostTerms(array('pa_duration','pa_hardness','pa_moskitos','pa_price','pa_extension',));
		$data['group']=$terms['pa_hardness']['value'] ? $terms['pa_hardness']['value'] : '';
		$data['rating']=$terms['pa_moskitos']['value'] ? $terms['pa_moskitos']['value'] : '';
		$data['extension']=$terms['pa_extension']['value'] ? $terms['pa_extension']['value'] : '';
		$data['price_text']=$terms['pa_price']['value'] ? $terms['pa_price']['value'] : '';
		$data['duration']=$terms['pa_duration']['value'] ? $terms['pa_duration']['value'] : '';
		$highlights=explode("\r\n",strip_tags(str_replace("</li>","</li>\r\n",$this->getContentTab('Höhepunkte'))));
		$data['highlights']=array();
		foreach($highlights as $hs) if(trim($hs)!='') $data['highlights'][]=trim($hs);
		$data['map']=$this->getContentTab('Karte');
		$data['price']=get_post_meta($this->tour->ID,'regular_price',true);
		$data['requirement']=preg_replace('/\[moskitos([^\]]{1,})\]/','',$this->getContentTab('Anforderung'));
		$data['individual']=$this->getTourIndividual();
		$data['extras']=$this->getExtras();
		$data['additional']=$this->getMetaTab('Hinweise');
		$data['destco']='';
		if(strpos($data['additional'],'[passolution')!==false){
			preg_match('/destco="([^"]{1,})"/',$data['additional'],$dto);
			$data['destco']=$dto[1];
			$data['additional']=preg_replace('/\[passolution([^\]]{1,})\]/','',$data['additional']);
		}
		$data['services']=$this->getServices();
		$data['operator']='MOSKITO Adventures';
		$data['dates']=$this->getTourDates();
		$data['guides']=$this->getTourGuides();
		$data['prices']=$this->getPricesTermine();
		header('Content-Type: application/xml');
		include "xml_template.php";
	}
	private function getTourPreview(){		
		if(!has_post_thumbnail($this->tour->ID)) return '';
		$aid=get_post_thumbnail_id($this->tour->ID);
		$url=wp_get_attachment_url($aid);
		return $url && !is_wp_error($url) ? $url : '';
	}
	private function getTourImagesList(){
		$aids=array();
		$gallery=explode(',',get_post_meta($this->tour->ID,'_product_image_gallery',true));
		foreach($gallery as $g) if(is_numeric($g)) $aids[]=$g;
		if(!is_array($aids) || sizeOf($aids)==0) return array();	
		$top_image=get_post_meta($this->tour->ID,'header_section_meta',true);
		$result=array();
		if($top_image['banner_image']!=''){
			$result[]=$top_image['banner_image'];
		}
		foreach($aids as $aid){
			$img=wp_get_attachment_url($aid);
			if($img && !is_wp_error($img)) $result[]=$img;
		}
		return $result;
	}
	private function getPostTerms($terms=array()){
		if(!is_array($terms) || sizeOf($terms)==0) return array();
		$result=array();
		foreach($terms as $t){
			$tax=get_taxonomy($t);
			if($tax && !is_wp_error($tax)){
				$pt=wp_get_post_terms($this->tour->ID,$t);
				$this->replaceMoskitos($pt[0]->name);
				$result[$t]=array('name'=>wc_attribute_label($t),'value'=>$pt[0]->name);
			}
		}
		return $result;
	}
	private function replaceMoskitos(&$content){
		if(strpos($content,'[moskitos')!==false){
			preg_match('/count\=\"([^\"]{1,})"/',$content,$count);
			if(is_numeric($count[1])){
				$content=$count[1];
			}else $content=0;
		}
	}
	private function getMetaTab($pos=''){
		if($pos=='') return '';
		global $meta_tabs;
		if(!isset($meta_tabs[$this->tour->ID])){
			$meta=get_post_meta($this->tour->ID,'tour_tabs_meta',true);
			foreach($meta['tabs'] as $t){
				$meta_tabs[$this->tour->ID][]=array('title'=>$t['title'],'content'=>$t['content']);
			}
		}
		if(!is_array($meta_tabs[$this->tour->ID])) return '';
		$result='';
		foreach($meta_tabs[$this->tour->ID] as $tab){
			if($tab['title']==$pos) $result=$tab['content'];
		}
		return $result;
	}
	private function getContentTab($pos=''){
		if($pos=='') return '';
		global $content_tabs;
		if(!isset($content_tabs[$this->tour->ID])){
			preg_match('/\[tabs[^\]]{1,}\](.*)\[\/tabs\]/s',$this->tour->post_content,$matches);
			if($matches[1]){
				$tabs=explode('[/tab_item]',$matches[1]);
				foreach($tabs as $t) if(strpos($t,'[tab_item')!==false){
					preg_match('/title="([^"]{1,})"/',$t,$title);
					$t=preg_replace('/\[tab_item([^\]]{1,})\]/','',$t);
					if($title[1]!='' && $t!='') $content_tabs[$this->tour->ID][]=array('title'=>$title[1],'content'=>$t);
				}
			}
		}
		if(!is_array($content_tabs[$this->tour->ID])) return '';
		$result='';
		foreach($content_tabs[$this->tour->ID] as $tab){
			if($tab['title']==$pos || $tab['title']==htmlspecialchars($pos)) $result=$tab['content'];
		}
		return $result;
	}
	private function getTourIndividual(){
		$result=array();
		$days=$this->getMetaTab('Reiseverlauf');
		preg_match_all('/\[timeline_item([^\]]{1,}\])([^\[]{1,})\[\/timeline_item\]/U',$days,$matches);
		foreach($matches[0] as $k=>$m){
			preg_match('/item_number="([^"]{1,})"/',$m,$num);
			preg_match('/[0-9]{1,}/',$num[1],$n);
			preg_match('/<div class="title">([^<]{1,})<\/div>/',$matches[2][$k],$title);
			$text=preg_replace('/<div class="title">([^<]{1,})<\/div>/','',$matches[2][$k]);
			$result[]=array(
				'num'=>intval($n[0]),
				'num_name'=>$num[1],
				'title'=>trim($title[1]),
				'text'=>$text,
			);
		}
		return $result;
	}
	private function getExtras(){
		$extras=$this->getMetaTab('Leistungen');
		if(strpos($extras,'<h3>Extras:</h3>')===false) return array();
		$result=array();
		$extras=substr($extras,strpos($extras,'<h3>Extras:</h3>'));
		$extras=preg_replace('/<h3>([^<]{1,})<\/h3>/','',$extras);
		$extras=explode("\r\n",strip_tags(str_replace("</li>","</li>\r\n",$extras)));
		foreach($extras as $ex) if(trim($ex)!='') $result[]=trim($ex);
		return $result;
	}
	private function getServices(){
		$services=$this->getMetaTab('Leistungen');
		if(strpos($services,'<h3>Extras:</h3>')===false) return array();
		$result=array();
		$services=substr($services,0,strpos($services,'<h3>Extras:</h3>'));
		$services=preg_replace('/<h3>([^<]{1,})<\/h3>/','',$services);
		$services=explode("\r\n",strip_tags(str_replace("</li>","</li>\r\n",$services)));
		foreach($services as $ex) if(trim($ex)!='') $result[]=trim($ex);
		return $result;		
	}
	private function getTourDates(){
		$dates=get_post_meta($this->tour->ID,'tour_booking_periods',true);
		$dates=$dates[0];
		if(!is_array($dates) || !is_array($dates['exact_dates'])) return '';
		$result=array();
		foreach($dates['exact_dates'] as $date) $result[]=$date;
		return $result;
	}
	private function getTourGuides(){
		$guides=get_post_meta($this->tour->ID,'_tour_guides',true);
		if(!$guides) return array();
		$gids=explode(',',$guides);
		foreach($gids as $k=>$v) if(!is_numeric($v)) unset($gids[$k]);
		if(sizeOf($gids)==0) return array();
		$guides_query=new WP_Query(array(	
			'post_type'=>'guide',
			'posts_per_page'=>-1,
			'orderby'=>'title',
			'order'=>'ASC',
			'post__in'=>$gids,
		));
		$result=array();
		foreach($guides_query->posts as $guide){
			$result[]=array(
				'name'=>$guide->post_title,
				'description'=>$guide->post_content,
			);
			$thumb=get_the_post_thumbnail_url($guide->ID,'thumbnail');
			$text=get_post_meta($guide->ID,'guide_guide_by',true);
			$output.='<li '.(!$thumb ? 'class="no_image"' : '').'>';
			if($thumb) $output.='<div class="image"><img src="'.$thumb.'"></div>';
			$output.='<div class="info"><div class="title">';
			$output.='<b>'.$guide->post_title.'</b>';
			if($text) $output.=' <span class="guide_by">'.$text.'</span>';
			$output.='</div>';
			$output.='<div class="text">'.$guide->post_content.'</div></div>';
			$output.='</li>';
		}
		return $result;
	}
	private function getPricesTermine(){
		$prices=$this->getContentTab('Termine & Preise');
		if(!$prices) return array();
		$result=array();
		$available="<br><p><b><i><span><s><u><strong><del>";
		$prices=explode("\r\n",strip_tags(str_replace(array("</tr>","</td>"),array("</tr>\r\n","|||"),$prices),$available));
		foreach($prices as $price){
			if(trim($price)=='') continue;
			list($date,$tage,$price,$text)=explode('|||',$price);
			$result[]=array(
				'date'=>trim($date),
				'tage'=>trim($tage),
				'price'=>trim($price),
				'text'=>trim($text),
			);
		}
		return $result;
	}
}
XMLExport::inst();
?>