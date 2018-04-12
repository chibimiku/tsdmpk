<?php 

//a simple character page create.
//chibimiku@tsdm.net

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('PURECSS', true);

if($_G['gp_new_char']){
	
	//some check...
	$my_chars = DB::result_array_parm('plugin_tsdmpk_char', '*', 'uid=?', array($_G['uid']));
	if(count($my_chars) >= 2){
		showmessage('err_tsdmpk_cannot_create_more_than_2_chars.', dreferer());
	}
	
	//check same name...
	$rs = DB::result_first_parm('plugin_tsdmpk_char', '*', 'name=?', array($_G['gp_char_name']));
	if($rs){
		showmessage('err_tsdmpk_same_char_name', dreferer());
	}
	
	//check if pool / edge , illegal.
	$data_index = array('might', 'speed', 'intellect'); //属性index，省去每次都打这三个名字
	$pool_data = array();
	$edge_data = array();
	foreach($data_index as $row){
		$pool_data[$row] = intval($_G['gp_char_pool_'.$row]);
		$edge_data[$row] = intval($_G['gp_char_edge_'.$row]);
	}
	
	//一开始有5x3 可分配15 累积30
	//优势edge有+2
	if(array_sum($pool_data) > 30 || array_sum($edge_data) > 2){
		showmessage('err_tsdmpk_illigal_input');
	}
	
	DB::insert('plugin_tsdmpk_char', array(
		'name' => $_G['gp_char_name'],
		'gender' => intval($_G['gp_char_gender']),
		'class' => $_G['gp_class'],
		'uid' => $_G['uid'],
		'might_pool' => $pool_data['might'],
		'speed_pool' => $pool_data['speed'],
		'intellect_pool' => $pool_data['intellect'],
		'might_edge' => $edge_data['might'],
		'speed_edge' => $edge_data['speed'],
		'intellect_edge' => $edge_data['intellect'],
		'exp' => 0,
	));
	
	
	
}else{
	include template('tsdmpk:new_char');
}

?>