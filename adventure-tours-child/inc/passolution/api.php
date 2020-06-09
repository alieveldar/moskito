<?php
//https://api01.passolution.de/condition/search.php?aid=moskito-adventures@passolution.de&sid=moskito-adventures@passolution.de&apw=JnTNTl8sNn&sidpw=JnTNTl8sNn&descd=0&nat=de&destco=af

class Passolution{
	var $aid='moskito-adventures@passolution.de';
	var $sid='moskito-adventures@passolution.de';
	var $apw='JnTNTl8sNn';
	var $sidpw='JnTNTl8sNn';
	var $descd=0;
	var $nat='de';
	function __construct(){
		add_shortcode('passolution',array($this,'output'));
	}
	function getData($args=array()){
		$res=file_get_contents('https://api01.passolution.de/condition/search.php?aid='.$this->aid.'&sid='.$this->sid.'&apw='.$this->apw.'&sidpw='.$this->sidpw.'&descd='.$this->descd.'&nat='.$this->nat.'&destco='.$args['destco']);
		return $res;
	}
	function output($args,$content){
		$params=array();
		$params['destco']=mb_strtolower($args['destco'],'UTF-8');
		$data=$this->getData($params);
		if(!$data) return '';
		$data=json_decode($data,true);
		$output='<div class="passolution">';
		$output.='<h3><span style="text-decoration: underline;">Einreise</span></h3>';
		$output.='<div>'.$data['response']['entry']['content'].'</div>';
		$output.='<h3><span style="text-decoration: underline;">Visa</span></h3>';
		$output.='<div>'.$data['response']['visa']['content'].'</div>';
		$output.='<h3><span style="text-decoration: underline;">Impfungen</span></h3>';
		$output.='<div>'.$data['response']['inoculation']['content'].'</div>';
		$output.='</div>';
		return $output;
	}
}