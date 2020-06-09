<?php

class ToursImporterAdmin{ 
    function __construct(){
		add_action('admin_menu', array($this, 'adminMenu'));
		if(isset($_POST['load_tours'])) $this->loadTours();
		if(isset($_POST['remove_products'])){
			$ids=explode(',',$_POST['remove_products']);
			$rids=array();
			foreach($ids as $id) if(is_numeric($id)){
				$rm=wp_delete_post($id,true);
				if($rm) $rids[]=$id;
			}
			echo json_encode($rids);
			die();
		}
    }
 
    function adminMenu(){
		$hook=add_management_page('Tours Importer Page', 'Tours Importer Page', 'install_plugins', 'tours_importer', array($this, 'adminPage'), '');
		add_action("load-$hook", array( $this,'adminPageLoad'));
    }
	function loadTours(){
		if(is_uploaded_file($_FILES['tour_import_file']['tmp_name'])){
			require_once get_stylesheet_directory()."/inc/importer/importer.class.php";
			$ti=new ToursImporter($_FILES['tour_import_file']['tmp_name'],$_FILES['tour_import_file']['name'],array('skip'=>$_POST['tour_skip_lines']));
			if($ti->error==''){
				set_query_var('inserted_ids',$ti->ids);
				set_query_var('exists_tours',$ti->exists);
				add_action('admin_notices', function(){
					$ids=get_query_var('inserted_ids');
					ob_start(); ?>
					<div class="updated notice">
						<p><?php _e( 'Data was succesfully loaded'); ?></p>
					</div>
					<?php echo ob_get_clean();
				});
			}else{
				set_query_var('ti_error_message',$ti->error);
				add_action('admin_notices', function(){
					ob_start(); ?>
					<div class="error notice">
						<p><?=__( 'While loading error occured:').' '.get_query_var('ti_error_message')?></p>
					</div>
					<?php echo ob_get_clean();
				});			
			}
		}
	}
 
    function adminPageLoad(){}
	
    function adminPage(){
		$ids=get_query_var('inserted_ids');		
		ob_start(); 
		$this->includeScripts();
		?>
		<style>
			.tours_load_form{ margin: 30px 0; }
			.tours_load_form button{ display: inline-block; line-height: 3.000em; padding: 0 2em; border: none; border-radius: 3px; white-space: nowrap; vertical-align: top; text-transform: uppercase;  color: #fff; font-size: 15px; font-family: Oxygen; font-weight: 700; font-style: normal; -webkit-transition: all 0.2s ease-in-out; -o-transition: all 0.2s ease-in-out; transition: all 0.2s ease-in-out; background: #f63; cursor: pointer; margin: 15px 0; }
			a{ cursor: pointer; }
			table{ margin-bottom: 15px; }
			table tr td,table tr th{ padding: 7px 10px; }
			.actions a,.actions span{ display: inline-block; vertical-align: middle; font-size: 16px; margin-right: 10px; }
			.actions span{ font-size: 12px; }
			.actions a:last-child{ margin-right: 0; }
			.ajax_loader{ display: none; position: relative; width: 64px; height: 64px; margin: 20px 0; }
			.ajax_loader.active{ display: block; }
			.ajax_loader div{ display: inline-block; position: absolute; left: 6px; width: 13px; background: #f63; animation: ajax_loader 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite; }
			.ajax_loader div:nth-child(1) { left: 6px; animation-delay: -0.24s; }
			.ajax_loader div:nth-child(2) { left: 26px; animation-delay: -0.12s; }
			.ajax_loader div:nth-child(3) { left: 45px; animation-delay: 0; }
			@keyframes ajax_loader {
			  0% { top: 6px; height: 51px; }
			  50%, 100% { top: 19px; height: 26px; }
			}
		</style>
		<form class="tours_load_form" method="POST" name="tours_load_form" enctype="multipart/form-data">
			<h1>Tours data import</h1>
			<h3>You can load tours data via specific XLS(X) or XML file.</h3>
			<div>Examples:</div>
			<ul>
				<li><a href="<?=get_stylesheet_directory_uri().'/inc/importer/sample/sample.xml'?>">XML</a></li>
				<li><a href="<?=get_stylesheet_directory_uri().'/inc/importer/sample/sample.xlsx'?>">XLSX</a></li>
			</ul>
			<div class="label">
				<input type="hidden" name="load_tours" value="1">
				<div>
					<div style="margin-bottom: 5px; font-weight: 600;">Skip first lines (0 or empty to disable skipping)</div>
					<input type="text" name="tour_skip_lines" value="1">
				</div>
				<div>
					<div style="margin: 5px 0; font-weight: 600;">Import file</div>
					<input type="file" name="tour_import_file">
				</div>
				<div>
					<button type="submit">Load</button>
				</div>
			</div>
		</form>
		<?php 
		if(!is_array($ids)) $ids=array();
		if(sizeOf($ids)>0) :
		?>
			<div id="import_result">
				<h3><?=sizeOf($ids)?> tours has been imported: </h3>
				<table id="imported_tours">
					<thead>
						<th></th>
						<th><?=__('Tour name')?></th>
						<th><?=__('Admin page')?></th>
						<th><?=__('Site page')?></th>
					</thead>
					<tbody>
						<?php foreach($ids as $id) : 
							$post=get_post($id);
						?>
						<tr id="row_for_<?=$id?>">
							<td><input type="checkbox" id="post_<?=$id?>" value="<?=$id?>"></td>
							<td><label for="post_<?=$id?>"><?=$post->post_title?></td>
							<td><a href="<?=get_edit_post_link($post->ID)?>" target="_blank">edit</a></td>
							<td><a href="<?=get_permalink($post->ID)?>" target="_blank">view</a></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="actions">
					<a onclick="removeSelectedImportedTours();"><?=__('Cancel selected tours')?></a> 
					<span>or</span> 
					<a onclick="removeAllImportedTours();"><?=__('Cancel all imported tours')?></a> 
				</div>
				<div class="ajax_loader" id="ajax_loader"><div></div><div></div><div></div></div>
			</div>
		<?php endif;
		$exists_tours=get_query_var('exists_tours');		
		if(is_array($exists_tours)) : ?>
			<h3>Some tours wasn't added because there are already exists tours with such tour code:</h3>
			<table>
				<thead>
					<th><?=__('Tour name')?></th>
					<th><?=__('Admin page')?></th>
					<th><?=__('Site page')?></th>
				</thead>
				<tbody>
			<?php foreach($exists_tours as $tour) : ?>				
				<tr id="row_for_<?=$tour->ID?>">
					<td><label for="post_<?=$id?>"><?=$tour->post_title?></td>
					<td><a href="<?=get_edit_post_link($tour->ID)?>" target="_blank">edit</a></td>
					<td><a href="<?=get_permalink($tour->ID)?>" target="_blank">view</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php
		endif;
		echo ob_get_clean();
    }
	
	function includeScripts(){
		?>
		<script type="text/javascript">
			function removeAllImportedTours(){
				if(confirm('Are you sure?')){
					var cbs=document.querySelectorAll('#imported_tours input[type="checkbox"]');
					var ids=[];
					for(var i=0;i<cbs.length;++i){
						ids.push(cbs[i].id.replace('post_',''));
					}
					if(ids.length>0) removePosts(ids);
				}
			}
			function removeSelectedImportedTours(){
				if(confirm('Are you sure?')){
					var cbs=document.querySelectorAll('#imported_tours input[type="checkbox"]');
					var ids=[];
					for(var i=0;i<cbs.length;++i){
						if(cbs[i].checked) ids.push(cbs[i].id.replace('post_',''));
					}
					if(ids.length>0) removePosts(ids);	
				}				
			}
			function removePosts(ids){
				window.product_delete_loading=true;
				$('#ajax_loader').addClass('active');
				$('.actions').hide();
				$.ajax({
					url: window.location,
					type: 'post',
					data: 'remove_products='+ids.join(','),
					success:function(data){
						data=$.parseJSON(data);
						for(var i=0;i<data.length;++i){ $('#row_for_'+data[i].toString()).hide(); }
						$('#ajax_loader').removeClass('active');
						$('.actions').show();
						window.product_delete_loading=false;
						var trs=document.querySelectorAll('#imported_tours tbody tr');
						var all=true;
						for(var i=0;i<trs.length;++i) if(trs[i].style.display!='none'){ all=false; break; }
						if(all) $('#import_result').hide();
					},
					error:function(){
						$('#ajax_loader').removeClass('active');
						$('.actions').show();
						window.product_delete_loading=false;
					}
				});
			}
		</script>
		<?php
	}
 
}
$tia=new ToursImporterAdmin();
?>