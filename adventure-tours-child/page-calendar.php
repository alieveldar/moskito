<?php
/**
 * Template Name: Calendar
 *
 * @package ivatotheme
 */

get_header();
global $tours_list,$tours_query;
$months=array(1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember');
$year=isset($_GET['y']) ? $_GET['y'] : date('Y');
if(isset($_GET['month'])){
	$prev_year=isset($_GET['y']) ? $_GET['y'] : date('Y');
	$next_year=isset($_GET['y']) ? $_GET['y'] : date('Y');
	$prev_month=intval($_GET['month'])-1;
	if($prev_month==0){
		$prev_month=12;
		$prev_year-=1;
	}
	$next_month=intval($_GET['month'])+1;
	if($next_month>12){
		$next_month=1;
		$next_year+=1;
	}
}
?>
<div class="main">
    <div class="row">
        <div class="col-xs-12">
		<?php 
		if(!isset($_GET['month']) || !isset($tours_list[$year.'_'.$_GET['month']])) : ?>		
		<div class="year_tabs">
			<ul>
				<?php for($i=0;$i<4;++$i) : ?>
					<li class="year <?php echo ($year==date('Y')+$i ? 'active' : '');?>"><a href="<?=get_permalink()?>?y=<?=date('Y')+$i?>"><?=date('Y')+$i?></a></li>
				<?php endfor; ?>
			</ul>
		</div>
		<div class="clndr__row">
			<?php for($i=1;$i<=12;++$i) :
				$count=is_array($tours_list[$year.'_'.$i]) ? sizeOf($tours_list[$year.'_'.$i]) : 0;
                $img=get_stylesheet_directory_uri().'/images/months/' . $i . '.jpg';
			?>		
				<div class="clndr__month <?php echo (date('n')>$i && $year==date('Y') || $year<date('Y') ? 'clndr__month_disabled' : '');?>">
					<div class="clndr__month-name"><i class="td-calendar-2"></i><?=$months[$i]?></div>
					<div class="clndr__month-pic" style="background-image: url('<?=htmlspecialchars($img)?>')"><a href="<?=add_query_arg('month',$i)?>" class="clndr__month-link"></a>
						<p class="clndr__month-value"><?=$count.' '._n('Reise','Reisen',$count,'ivatotheme')?></p>
					</div>
				</div>
			<?php endfor; ?>
		</div>
		<?php else : ?>
            <div class="calendar_navigation">
                <div class="calendar_navigation-months">
                    <a href="<?=get_permalink()?>?month=<?=$prev_month?>&y=<?=$prev_year?>"><?=$months[$prev_month]?> <?=$prev_year?></a> <b>|</b>
                    <span><?=__('Termine für')?> <?=$months[$_GET['month']]?> <?=$year?></span> <b>|</b>
                    <a href="<?=get_permalink()?>?month=<?=$next_month?>&y=<?=$next_year?>"><?=$months[$next_month]?> <?=$next_year?></a>
                </div>
                <div>
                    <a href="<?php $uri = explode('?', $_SERVER['REQUEST_URI'], 2); echo home_url().$uri[0].'?y='.$year;?>">zurück zum Reisejahr <?=$year?></a>
                </div>
            </div>
			<div class="list-tours">	
				<div class="lstour lstour_header">
					<div class="lstour__image"></div>
					<div class="lstour__desc"><?=__('Reise')?></div>
					<div class="lstour__details">
                        <p class="lstour__duration" id="sort_duration"><?=__('Dauer')?><span></span></p>
                        <p class="lstour__from" id="sort_date_from"><?=__('Date')?><span></span></p>
                        <p class="lstour__price" id="sort_price"><?=__('Preis')?><span></span></p>
					</div>
				</div>
				<?php 
				$current_month=intval($_GET['month']);
				while($tours_query->have_posts()) :
					$tours_query->the_post();		
					$_product=wc_get_product(get_the_ID());
					$img=get_the_post_thumbnail_url(get_the_ID());
					if(!$img || is_wp_error($img)) $img=get_stylesheet_directory_uri().'/images/moskitos.png';
					$continent=wp_get_post_terms(get_the_ID(),'pa_kontinent');
					$rating=wp_get_post_terms(get_the_ID(),'pa_moskitos');
					$duration=wp_get_post_terms(get_the_ID(),'pa_duration');
					$header_meta=get_post_meta(get_the_ID(),'header_section_meta',true);
					$header_meta['banner_subtitle']=mb_strlen($header_meta['banner_subtitle'],'UTF-8')>100 ? mb_substr($header_meta['banner_subtitle'],0,100,'UTF-8').'...' : $header_meta['banner_subtitle'];
					$date='';
                    $prices_and_dates=get_post_meta(get_the_ID(),'_prices_values_by_dates');
					$days_list=array();
                    if(is_array($prices_and_dates)){
                        $prices = array();
                        foreach($prices_and_dates as $row) {
                            foreach($row as $val){
								$parts=explode('.',$val['date_from']);
                                $month_of_start_date=intval($parts[1]);
                                $year_of_start_date=$parts[2];
                                if($month_of_start_date==$current_month && $year_of_start_date==$year && !isset($days_list[$val['date_from']])){
                                    $price=str_replace('.','',($val['price']));
									$days_list[$val['date_from']]=array('date'=>$val['date_from'],'price'=>$price);
                                }
                            }
                        }
                        $tour_price = $prices[0];
                    }else{						
						$dates=get_post_meta(get_the_ID(),'tour_booking_periods',true);
						if(is_array($dates)){
							foreach($dates as $d) if($d['exact_dates']){		
								foreach($d['exact_dates'] as $val){
									$val=explode('-',$val);
									if(isset($_GET['month']) && intval($val[1])==$_GET['month'] && intval($val[0])==$year)
										$days_list[]=array('date'=>$val[2].'.'.$val[1].'.'.$val[0],'price'=>$_product->get_price());
								}
							}
						}
					}
					foreach($days_list as $dl) :
					?>					
						<a class="lstour <?php echo ($continent && $continent[0] ? 'lstour_'.$continent[0]->slug : '');?>" href="<?=get_permalink()?>">
							<div class="lstour__image" style="background-image: url('<?=htmlspecialchars($img)?>')"></div>
							<div class="lstour__desc">
								<h2 class="lstour__title"><?=get_the_title()?></h2>
								<h3 class="lstour__subtitle"><?=$header_meta['banner_subtitle']?></h3>
								<?=do_shortcode($rating[0]->name)?>
							</div>
							<div class="lstour__details">
								<p class="lstour__duration"><?php echo $duration[0]->name . (strpos(strtolower($duration[0]->name),'tag')===false ? __(' tage') : '');?></p>
								<p class="lstour__from"><?=$dl['date']?></p>
								<p class="lstour__price"><?=wc_price($dl['price'])?></p>
							</div>
						</a>
				<?php endforeach; endwhile;
				wp_reset_postdata();
				?>
			</div>
			<script type="text/javascript">window.addEventListener('load',function(){
				if(document.querySelector('.lstour__from')) document.querySelector('.lstour__from').click();
			})</script>
		<?php endif; ?>
	    </div>
	</div>
</div><!-- #main -->

<?php
get_footer();
