<?php 

//tsdm doing battle arena.
//chibimiku@tsdm.net

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/tsdmutil'); //读取通用lib

/*
 * 流程：init_battle -> init_user_group -> spawn_monsters -> [input -> generate_action_list -> do_actions -> result] (loop) -> summary
 * 
 */

if(isset($_G['gp_battle_input'])){
	//load battle info.
	$battle_id = intval($_G['gp_battle_id']);
	$battle_info = DB::fetch_first_parm('plugin_tsdmpk_battle', '*', 'battle_id=?', array($battle_id));
	//load user's chars.
	$user_chars_ids = DB::result_array_parm('plugin_tsdmpk_battle_chars', '*', 'battle_id=?', array($battle_id));
	$user_chars = array();
	foreach($user_chars_ids as $row){
		$user_chars[] = DB::fetch_first_parm('plugin_tsdmpk_char', '*', 'char_id=?' , array($row['char_id']));
	}
	//load monsters.
	$monsters_chars = DB::result_array_parm('plugin_tsdmpk_monster', '*', 'battle_id=?', array(battle_id));
	
	//load inputs.
	$inputs = array();
	foreach($_POST as $key => $row){
		if(strpos($key, 'do_action_') === 0){
			$inputs[$key] = $row;
		}
	}
	
	//action的原型：
	//$do_action = array('from_id' => 0, 'to_id' => 0, 'action_id' => 0);
	
	
	switch ($_G['gp_battle_action']){ //TODO 执行实际的动作
		case 'attack':
			break;
		case 'cast':
			break;
		default:
			//do nothing.
		
		//into summary for this turn.
		
		//check if battle ends.
		DB::update_value_parm('plugin_tsdmpk_battle', 'turns', '+', 1, 'battle_id=?', array($battle_id));
	}
}

//初始化一个bat
function init_battle($map_id){
	global $_G;
	//check if in battle.
	$my_ar = DB::fetch_first_parm('plugin_tsdmpk_battle', '*', 'sponsor_uid=? AND status=?', array($_G['uid'],0));
	if($my_ar){
		showmessage('err_tsdmpk_cannot_establish_new_battle_in_already', dreferer());
	}
	
	//load map data.
	$map_data = DB::fetch_first_parm('plugin_tsdmpk_map', '*', 'map_id=?', array($map_id));
	
	DB::insert('plugin_tsdmpk_battle', array(
		'sponsor_uid' => $_G['uid'],
		'status' => 0, //status. 0:started, 1:end.
		'start_time' => TIMESTAMP,
		'end_time' => 0,
		'turns' => 0,
		'map_id' => 0,
	));
	$battle_id = DB::insert_id();
	//init unit group.
	init_user_group($battle_id, $_G['uid'], false);
	spawn_monster($battle_id, $map_id); //for PvE only
}

function init_user_group($battle_id, $is_enemy = false){
	global $_G;
	//load current data.
	//$char_info = DB::fetch_first_parm('plugin_tsdmpk_char', '*', 'char_id=?', array($char_id));
	//$get_index = array('name', 'gender', 'class', 'head_image', 'might_current', 'speed_current', 'intellect_current', 'might_pool', 'speed_pool', 'intellect_pool', 'might_edge', 'speed_edge', 'intellect_edge', 'exp', 'hp', 'max_hp', 'mp', 'max_mp'); //有哪些需要从上一个表里直接取得的数据
	
	//load my chars.
	$my_chars = DB::result_array_parm('plugin_tsdmpk_char', '*', 'uid=?', array($_G['uid']));
	foreach($my_chars as $key => $row){
		DB::insert('plugin_tsdmpk_battle_chars', array('char_id' => $row['char_id'], 'battle_id' => $battle_id, 'type' => $is_enemy ? 1 : 0));  //对于这里登记只记录其ID在这个集合里面。考虑了下直接对user表进行操作更简洁一些。
	}
}

function spawn_monsters($battle_id, $map_id){
	//按设定是先 load 一下原型列表后再生成，这里作为测试用先从简，直接用copy的生成。
	DB::insert('plugin_tsdmpk_monster', array('monster_id' => 1));
}

function generate_action_list($user_chars, $monsters_chars, $user_actions){
	//TODO: 按规则书设计生成action list.
	$action_list = array();
	//TODO: 合并后按speed决定值排序，生成action_list.
	//现在是先user行动，然后monster行动的.这比较弱智…
	foreach($user_actions as $row){
		$action_list[] = $row;
	}
	
	//生成user的chars id供mon选取id用
	$user_ids = array();
	foreach($user_chars as $row){
		$user_ids[] = $row['char_id'];
	}
	foreach($monster_chars as $mon){
		$action_list[] = monster_do_simple_attack_action($mon['monster_id'], $user_ids);
	}
	
	return $action_list;
}

function do_actions($action_list){
	foreach($action_list as $row){
		if($row['is_mon']){
			//DB::update_value_parm('plugin_tsdmpk_plugin_tsdmpk_char', '');
		}else{
			//DB::update_value_parm('plugin_tsdmpk_monter', );
		}
	}
}

//monster 随机选择目标反应
function monster_do_simple_attack_action($monster_imp_id, $targets){
	$final_target_id = $targets[array_rand($targets,1)];
	return array('to_id' => array($final_target_id), 'from_id' => $monster_imp_id, 'skill_id' => 1, 'is_mon' => 1); //1是norm attack ，注意to_id为array，支持多个目标。
}

?>