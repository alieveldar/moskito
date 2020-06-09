<?php
return array(
	array(
		'name' => '_page_continent',
		'label' => esc_html__( 'Kontinent','adventure-tours' ),
		'type' => 'select',
		'items'=>getContinentsList(),
		'default' => '',
	),
	array(
		'name' => '_page_country',
		'label' => esc_html__( 'Land','adventure-tours' ),
		'type' => 'select',
		'items'=>getCountriesList(),
		'default' => '',
	),
);
function getContinentsList(){
	$args=array(
		'taxonomy'=>'pa_kontinent',
		'hide_empty'=>false,
	);
	$terms=get_terms($args);
	$result=array();
	foreach($terms as $t){
		$result[]=array('value'=>$t->slug,'label'=>$t->name);
	}
	return $result;
}
function getCountriesList(){
	$args=array(
		'taxonomy'=>'pa_land',
		'hide_empty'=>false,
	);
	$terms=get_terms($args);
	$result=array();
	foreach($terms as $t){
		$result[]=array('value'=>$t->slug,'label'=>$t->name);
	}
	return $result;
}
?>