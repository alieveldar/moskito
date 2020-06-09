<?php

require get_stylesheet_directory().'/inc/dompdf/lib/html5lib/Parser.php';
require get_stylesheet_directory().'/inc/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
require get_stylesheet_directory().'/inc/dompdf/lib/php-svg-lib/src/autoload.php';
require get_stylesheet_directory().'/inc/dompdf/src/Autoloader.php';
Dompdf\Autoloader::register();
use Dompdf\Dompdf;
use Dompdf\Options;
function createPDFProductFile($print_id,$params=array()){
	if(!is_numeric($print_id)) return false;
	$exists=new WP_Query(array(
		'post_type'=>'product',
		'page_id'=>$print_id,
		'posts_per_page'=>1,
	));
	if(sizeOf($exists->posts)!=1) return false;
	$pdf_content=getPDFHTML($exists->posts[0]);
	$map=$params['map_img']=='' ? '' : '<img src="'.$params['map_img'].'">';
	$pdf_content=str_replace('{map_img}',$map,$pdf_content);
	$uploads=wp_get_upload_dir();
	$path=$uploads['path'];
	$url=$uploads['url'];
	$options = new Options();
	$options->set('isRemoteEnabled', true);
	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($pdf_content);
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render();
	$canvas=$dompdf->getCanvas();
	$pdf=$canvas->get_cpdf();
	foreach($pdf->objects as &$o){
		if($o['t']==='contents'){
			$o['c']=str_replace('{t}', $canvas->get_page_count(), $o['c']);
		}
	}
	$output=$dompdf->output();
	$name=preg_replace('/[^0-9a-zA-Z\-_ ]/','-',$exists->posts[0]->post_title);
	$put=file_put_contents($path.'/'.$name.'.pdf', $output);
	if(!$put) return false;
	return $url.'/'.$name.'.pdf';
}
function getTourImagesList($post){
	$aids=array();
	if(has_post_thumbnail($post->ID)){
		$aids[]=get_post_thumbnail_id($post->ID);
	}
	$gallery=explode(',',get_post_meta($post->ID,'_product_image_gallery',true));
	foreach($gallery as $g) if(is_numeric($g)) $aids[]=$g;
	if(!is_array($aids) || sizeOf($aids)==0) return array();	
	$top_image=get_post_meta($post->ID,'header_section_meta',true);
	$result=array();
	if($top_image['banner_image']!=''){
		$result[]=$top_image['banner_image'];
	}
	foreach($aids as $aid){
		$img=get_attached_file($aid);
		if($img && !is_wp_error($img)) $result[]=$img;
	}
	return $result;
}
function getContentTab($post,$pos=''){
	if($pos=='') return '';
	global $content_tabs;
	if(!isset($content_tabs[$post->ID])){
		preg_match('/\[tabs[^\]]{1,}\](.*)\[\/tabs\]/s',$post->post_content,$matches);
		if($matches[1]){
			$tabs=explode('[/tab_item]',$matches[1]);
			foreach($tabs as $t) if(strpos($t,'[tab_item')!==false){
				preg_match('/title="([^"]{1,})"/',$t,$title);
				$t=preg_replace('/\[tab_item([^\]]{1,})\]/','',$t);
				if($title[1]!='' && $t!='') $content_tabs[$post->ID][]=array('title'=>$title[1],'content'=>$t);
			}
		}
	}
	if(!is_array($content_tabs[$post->ID])) return '';
	$result='';
	foreach($content_tabs[$post->ID] as $tab){
		if($tab['title']==$pos || $tab['title']==htmlspecialchars($pos)) $result=$tab['content'];
	}
	return $result;
}
function getMetaTab($post,$pos=''){
	if($pos=='') return '';
	global $meta_tabs;
	if(!isset($meta_tabs[$post->ID])){
		//$metas=get_post_meta($post->ID,'');
		$meta=get_post_meta($post->ID,'tour_tabs_meta',true);
		foreach($meta['tabs'] as $t){
			$meta_tabs[$post->ID][]=array('title'=>$t['title'],'content'=>$t['content']);
		}
	}
	if(!is_array($meta_tabs[$post->ID])) return '';
	$result='';
	foreach($meta_tabs[$post->ID] as $tab){
		if($tab['title']==$pos) $result=$tab['content'];
	}
	return $result;
}
function getPostTerms($post,$terms=array()){
	if(!$post || !$terms || sizeOf($terms)==0) return array();
	$result=array();
	foreach($terms as $t){
		$tax=get_taxonomy($t);
		if($tax && !is_wp_error($tax)){
			$pt=wp_get_post_terms($post->ID,$t);
			replaceMoskitos($pt[0]->name);
			$result[]=array('name'=>wc_attribute_label($t),'value'=>$pt[0]->name);
		}
	}
	return $result;
}
function replaceMoskitos(&$content){
	if(strpos($content,'[moskitos')!==false){
		preg_match('/count\=\"([^\"]{1,})"/',$content,$count);
		if(is_numeric($count[1])){
			$moskitos='<div class="moskitos_list">';    
			for($i=0;$i<5;++$i){
				if($i>=$count[1]) $img=get_stylesheet_directory()."/images/moskitos-empty.png";
				elseif($count[1]!=floor($$count[1]) && $i==floor($count[1])) $img=get_stylesheet_directory()."/images/moskitos-half.png";
				else $img=get_stylesheet_directory()."/images/moskitos.png";
				$moskitos.='<img src="'.$img.'">';
			}
			$moskitos.='</div>';
		}
		$content=preg_replace('/\[moskitos([^\]]{1,})\]/',$moskitos,$content);
	}
}
function getTourOrganizers($post){
	$christian=get_post_meta($post->ID,'tour_christian',true);
	$christof=get_post_meta($post->ID,'tour_christof',true);
	if(trim($christof)=='' && trim($christian)=='') return '';
	$output='<div class="organizers"><div class="line"><img src="'.get_stylesheet_directory().'/images/pdf_line.jpg"></div>';
	if(trim($christian)!=''){
		$output.='<div class="item row">
            <div class="image"><img src="'.get_stylesheet_directory().'/images/chris4.jpg" alt="avatar" /></div>
            <div class="info">
                <div class="title">'.__('Ihr Ansprechpartner für diese Reise:','adventure-tours-child').'</div>
                <div class="name">Christian Hertel</div>
                <div class="contacts">
                    <div class="phone"><img src="'.get_stylesheet_directory().'/images/mobile-phone-icon.png" width="8" height="8"/> 034292 44 93 39</div>
                    <div class="email"><img src="'.get_stylesheet_directory().'/images/envelope-icon.png" width="10" height="10"/> c.hertel@moskito-adventures.de</div>
                </div>
            </div>
		</div>';
	}
	if(trim($christof)!=''){
		$output.='<div class="item row">
            <div class="image"><img src="'.get_stylesheet_directory().'/images/christof4.jpg" /></div>
            <div class="info">			
                <div class="title">'.__('Ihr Ansprechpartner für diese Reise:','adventure-tours-child').'</div>
                <div class="name">Christof Schor</div>
                <div class="contacts">
                    <div class="phone"><img src="'.get_stylesheet_directory().'/images/mobile-phone-icon.png" width="8" height="8"/> 0178 47 28 570</div>
                    <div class="email"><img src="'.get_stylesheet_directory().'/images/envelope-icon.png" width="10" height="10"/> c.schor@moskito-adventures.de</div>
                </div>
            </div>
		</div>';
	}
	$output.='</div>';
	return $output;
}
function getTourGuides($post){
	if(!$post) return '';
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
		$output.='<b>'.$guide->post_title.'</b>';
		if($text) $output.=' <span class="guide_by">'.$text.'</span>';
		$output.='</div>';
		$output.='<div class="text">'.$guide->post_content.'</div></div>';
		$output.='</li>';
	}
	$output.='</ul></div>';
	return $output;
}
function getPDFHTML($post){
	$images=getTourImagesList($post);
	$img_c=0;
	ob_start(); ?>
	<html>
		<head>
			<style>
				@page{ margin-top: 110px; margin-left: 30px; margin-right: 30px; margin-bottom: 40px; }
				body { margin: 0px; font-family: 'Calibri', sans-serif; }
				h1{ color: #600; margin-bottom: 15px; font-size: 22px; text-align: center; font-weight: 600; }
				h2.sub{ color: #000; margin-bottom: 10px; font-size: 13.5px; text-align: center; font-weight: 600; }
				h2{ color: #600; margin-bottom: 10px; font-size: 13.5px; text-align: left; font-weight: 600; }
				h3{ color: #600; margin-bottom: 5px; font-size: 12px; text-align: left; font-weight: 600; }
				img{ max-width: 100%; height: auto; }
				.top_info{ position: fixed; left: 0; top: -100px; width: 100%; height: 80px; border-bottom: 2px solid #000; opacity: 0.7; }
				.top_info .logo{ position: absolute; left: 0; top: 0; height: 80px; width: auto; }
				.top_info .tour_code{ position: absolute; right: 0; top: 0; }
				.bottom_info{ position: fixed; left: 0; bottom: -40px; width: 100%; height: 30px; opacity: 0.7; font-size: 12px; }
				.bottom_info .date{ position: absolute; left: 0; top: 0; width: 33%; font-style: italic; }
				.bottom_info .site{ position: absolute; left: 33%; top: 0; width: 33%; text-align: center; color: #600; font-weight: 600; }
				.bottom_info .page{ position: absolute; left: 66%; top: 0; width: 33%; text-align: right; font-style: italic; }
				#pagenum:after { content: counter(page); }
				.main_image_container{ width: 100%; height: auto; display: block; position: relative; }
				.main_image{  }
				.main_info{ text-align: left; }
				.col_5{ position: relative; width: 100%; }
				.col_5:after{ content: " "; display: table; clear: both; }
				.col_5 > div{ float: left; width: 18%; margin-right: 2.5%; }
				.col_5 > div:nth-child(5n){ margin-right: 0; }
				.attributes{ font-size: 12px; color: #000; text-align: center; margin-top: 10px; }
				.attributes .name{ color: #600; font-size: 13.5px; font-weight: 600; margin-bottom: 5px; display: block; }
				.attributes.col_5 > div{ width:16%; }
				.attributes.col_5 > div:last-child{ width:26%; }
				.moskitos_list img{ display: inline-block; width: 20px; height: auto; margin-right: 3px; }
				.moskitos_list img:last-child{ margin-right: 0; }
				.organizers{ font-size: 12px; margin: 15px 150px 20px 150px; }
				.organizers:after{ content: " "; display: table; clear: both; }
				.organizers .line{ width: 100%; margin-bottom: 20px; }
				.organizers .item .image{ float: left; width: 100px; }
				.organizers .item .info{ float: left; margin-left: 15px; }
				.organizers .item .info .title{ color: #600; font-weight: 600; font-size: 13.5px; margin-bottom: 10px; }
				.organizers .item .info .name{ font-weight: 600; }
				.images{ position: relative; margin-bottom: 15px; width: 100%; }
				.images:after{ content: " "; display: table; clear: both; }
				.row:after{ content: " "; display: table; clear: both; }
				.images .row:after{ content: " "; display: table; clear: both; }
				.images img{ float: left; width: 48.5%; margin-right: 2%; margin-bottom: 10px; }
				.images img:nth-of-type(2n){ margin-right: 0; }
				.description,.guides,.days,.additional_info{ margin-bottom: 15px; }
				.tour_guides_list ul{ list-style: none; padding: 0; margin: 0; }
				.tour_guides_list ul li{ margin-bottom: 20px; position: relative; }
				.tour_guides_list ul li:after{ content: " "; display: table; clear: both; }
				.tour_guides_list ul li:last-child{ margin-bottom: 0; }
				.tour_guides_list ul li .image{ float: left; width: 100px; margin-right: 10px; margin-bottom: 10px; }
				.tour_guides_list .guide_by:before { content: '| '; }
				.timeline__item{ margin-bottom: 15px; text-align: justify; font-size: 12px; }
				.timeline__item .timeline__item__icon__text{ font-weight: 600; }
				.timeline__item .title{ font-weight: 600; margin-bottom: 8px; font-size: 12px; font-style: italic; }
				.justify{ text-align: justify; }
				.fs12{ font-size: 12px; }
				.page_break{ page-break-before: always; }
				.right_text{ font-size: 12px; text-align: right; margin-top: 3px; font-weight: 600; }
                .moskito-block { margin-top: 30px; }
                .moskito-block .moskitos_list { display: inline; }
                .moskito-block .moskitos_list:after { content: '\0a'; white-space: pre; }
                .timeline__item__icon-wrap { float: left; padding-right: 10px;}
                .timeline__item { padding-top: 10px; }
                .timeline__item__description { text-align: justify; }
                .timeline__item__description .title { white-space: pre; text-align: left; }
                .timeline__item__description .title:after { content: '\a0.\A\a0'; color: #fff;}
                .additional_info h3 { padding: 20px 0 10px; margin: 0; }
                .extras h3 { font-size: 13px; }
			</style>
		</head>
		<body style="margin: auto">
			<div class="top_info">
				<img src="<?=get_stylesheet_directory().'/images/logo.jpg'?>" class="logo">
				<div class="tour_code">
					<?=__('Tourcode')?><br>
					<?=get_post_meta($post->ID,'_sku',true)?>
				</div>
			</div>
			<div class="bottom_info">
				<div class="date"><?=__('Stand')?> <?=date('d.m.Y')?></div>
				<div class="site"><?=site_url()?></div>
				<div class="page"><span id="pagenum"><?=__('Seite')?> </span> <span><?=__('von')?> {t}</span></div>
			</div>
			<div class="main_info">
				<h1><?=$post->post_title?></h1>
				<?php
					$subtitle=get_post_meta($post->ID,'header_section_meta',true);
					if($subtitle['banner_subtitle']) :
				?>
					<h2 class="sub"><?=$subtitle['banner_subtitle']?></h2>
				<?php endif; ?>
				<?php if(sizeOf($images)>0 && isset($images[$img_c])) : ?>
				<div class="main_image_container"><img src="<?=$images[$img_c]?>" class="main_image"></div>
				<?php ++$img_c; ?>
				<?php endif; ?>
				<?php $terms=getPostTerms($post,array('pa_duration','pa_hardness','pa_moskitos','pa_price','pa_extension',));
				if(sizeOf($terms)>0) : ?>
				<div class="attributes col_5">
					<?php foreach($terms as $t) : ?>
					<div>
						<span class="name"><?=$t['name']?></span>
						<span><?=$t['value']?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
				<?php
					$honepunkte=getContentTab($post,'Höhepunkte');
					if($honepunkte!='') :
				?>
				<div class="row" style="width:100%;">
					<h2 style="float:left;width:10%;padding-top:19px;font-size:12px;font-family:'Calibri',sans-serif;"><?=__('Höhepunkte')?></h2>
					<div style="float:left;padding: 0 15px;width:90%;" class="justify fs12"><?=$honepunkte?></div>
				</div>
				<?php endif; ?>
				<?php
				$organizers=getTourOrganizers($post);
                if($organizers!='') :
                ?>
				<?=$organizers?>
				<?php endif; ?>
			</div>
			<div class="description">
				<div class="images">
					{map_img}
					<?php if(isset($images[$img_c])) : ?>
					<img src="<?=$images[$img_c]?>">
					<?php ++$img_c; endif; ?>
				</div>
				<?php $post->post_content=preg_replace('/\[tours_gallery([^\]]{1,})\]/','',$post->post_content)?>
				<div class="fs12 justify"><?=substr($post->post_content,0,strpos($post->post_content,'[tabs'))?></div>
				<?php $moskitos=getContentTab($post,'Anforderung');
				replaceMoskitos($moskitos);
				if($moskitos) : ?>
				<div class="fs12 justify moskito-block">
					<h2 style="display: inline-block; padding-right: 10px;">Anforderung</h2>
                    <?=$moskitos?>
				</div><br>
				<?php endif; ?>
			</div>
			<div class="guides">
				<?php $guides=getTourGuides($post);
					if($guides) :
				?>
					<h2>Ihre Reiseleiter für diese Tour (Auswahl):</h2>
					<div class="justify fs12"><?=$guides?></div>
				<?php endif; ?>
			</div>
            <div class="page_break"></div>
			<div class="days">
                <div class="images" style="margin-top:15px;">
                    <?php if(isset($images[$img_c])) : ?>
                        <img src="<?=$images[$img_c]?>">
                        <?php ++$img_c; endif; ?>
                    <?php if(isset($images[$img_c])) : ?>
                        <img src="<?=$images[$img_c]?>">
                        <?php ++$img_c; endif; ?>
                </div>
				<?php
					$days=getMetaTab($post,'Reiseverlauf');
					if($days) :
				?>
					<h2>Reiseverlauf:</h2>
					<?=do_shortcode($days)?>
					<div class="right_text">Änderungen vorbehalten!</div>
				<?php endif; ?>
			</div>
			<div class="page_break"></div>
			<div class="additional_info justify fs12">
				<div class="images">
					<?php if(isset($images[$img_c])) : ?>
					<img src="<?=$images[$img_c]?>">
					<?php ++$img_c; endif; ?>
					<?php if(isset($images[$img_c])) : ?>
					<img src="<?=$images[$img_c]?>">
					<?php ++$img_c; endif; ?>
				</div>
				<?php $prices=getContentTab($post,'Termine & Preise');
					if($prices) :
				?>
					<h2>Termine & Preise:</h2>
					<div class="fs12"><?=$prices?></div>
				<?php endif; ?>
				<?php
					$pt=wp_get_post_terms($post->ID,'pa_hardness');
                    if($pt && sizeOf($pt)>0) :
                ?>
				<div class="fs12 extras">
				<h2><?=__('Teilnehmer:')?> <span><?=$pt[0]->name?></span></h2>
				<?php endif; ?>
				<?php
					$extras=getMetaTab($post,'Leistungen');
					if($extras) :
				?>
				<?=$extras?>
				<div class="right_text">Änderungen vorbehalten!</div>
				<?php endif; ?>
				</div>
				<div class="page_break"></div>
				<?php
					$pass=getMetaTab($post,'Hinweise');
					if($pass) :
				?>
				<h2><?=__('Hinweise:')?></h2>
				<?=do_shortcode($pass)?>
				<?php endif; ?>
                <br><br>
				<div class="right_text">Änderungen vorbehalten!</div>
			</div>
			<div class="page_break"></div>
            <?php if(sizeOf($images)>=$img_c+1) :
				$k=0;
			?>
			<div class="images">
				<?php
				$i=0;
				for($images[$img_c];$img_c<sizeOf($images);++$img_c) : ?>
				<?php if($i%2==0) : ?><div class="row"><?php endif; ?>
					<img src="<?=$images[$img_c]?>" style="max-height:220px;">
				<?php if(++$i%2==0) : ?></div><?php endif; ?>
				<?php if(++$k==8) break; ?>
				<?php endfor; ?>
			</div>
			<?php endif; ?>
		</body>
	</html>
	<?php
	return ob_get_clean();
}
?>