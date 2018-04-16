<?php 

//tsdm doing battle arena.
//chibimiku@tsdm.net

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//action类
class battle_action{
	var $owner_id;
	var $target_id;
	
	function __construct($owner_id, $target_id){
		$this->owner_id = $owner_id;
		$this->target_id = $target_id;
	}
}

require_once libfile('function/tsdmutil'); //读取通用lib

/*
 * 流程：init_battle -> init_user_group -> spawn_monsters -> [input -> generate_action_list -> do_actions -> result] (loop) -> summary
 * 
 */

 //执行操作，并输出 console log
if(isset($_G['gp_battle_input'])){
	
	$message = '';
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
	output_info($message);
	
	switch ($_G['gp_battle_action']){ //TODO 执行实际的动作
		case 'attack':
			break;
		case 'cast':
			break;
		default:
			//do nothing.
		
		//into summary for this turn.
		
		//check if battle ends.
		DB::update_value_parm('plugin_tsdmpk_battle', 'turns', '+', 1, 'battle_id=?', array($battle_id)); //更新turn数
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
function monster_do_simple_attack_action($monster_imp_id, $targets, $skill_id = 1){
	$final_target_id = $targets[array_rand($targets,1)];
	return array('to_id' => array($final_target_id), 'from_id' => $monster_imp_id, 'skill_id' => $skill_id, 'is_mon' => 1); //1是norm attack ，注意to_id为array，支持多个目标。
}

//根据 rules 进行计算，返回dmg数量
//注意
function rules_calc_dmg($from_char, $to_char, $skill_id, $pool_cost){
	$skill_info = DB::fetch_first_parm('plugin_tsdmpk_skill', '*', 'skill_id=?', array($skill_id));
	if(!$skill_info){
		output_info('err_tsdmpk_no_skill');
		return false;
	}
	$real_enegry = $from_char[rules_calc_dmg($skill_info['type'])] + $pool_cost;
	
	//check for hits. 
	$speed_difference = $from_char['speed_current'] + $from_char['speed_edge'] - $to_char['speed_current'] - $to_char['speed_edge'];
	//根据diff做个差异修正，这里临时先用这个计算方法。Armor class被我吃了…
	$is_hit = false;
	$hit_rate = min(1, 0.75 + $speed_difference * 0.2) * 100;
	$roll = rand(1,100);
	if($roll <= $hit_rate){
		$is_hit = true;
	}
	if($is_hit){
		//计算实际的dmg值，这个减免也被我吃了。
		//TODO 根据$to_char的armor减免.
		$real_dmg = $skill_info['base_dmg'] + ($pool_cost * $skill_info['addtional_dmg']);
		//进行$real_dmg的计算
		return $real_dmg;
	}else{
		return 0;
	}
}

//实际进行action并进行结算.
function do_dmg($from_image_id, $to_image_id, $skill_id, $pool_cost){
	//TODO：进一步检查char的所有权
	$from_char = DB::fetch_first_parm('');
}

//---------------一些数据转化工具------------
//从 plugin_tsdmpk_battle_chars 里获取 in battle 的镜像
function image_id_to_real_id($image_id){
	$rs = DB::fetch_first_parm('plugin_tsdmpk_battle_chars', 'char_id', 'image_id=?', $image_id);
	if(!$rs){
		output_info('err_cannot_find_real_id:'.$image_id);
		return 0;
	}
	$ret_id = 0;
	return array('target_id' => $rs['char_id'], 'target_type' => $rs['type']);
}

//从 plugin_tsdmpk_battle_chars 里的ID获取实际char/monster的信息。
function image_id_to_real_info($image_id){
	$my_data = image_id_to_real_id($image_id);
	if($my_data['type'] == 0){ //type == 0: pc, type == 1: monster
		return DB::fetch_first_parm('plugin_tsdmpk_char', '*', 'char_id=?', $my_data['char_id']);
	}elseif($my_data['type'] == 1){
		return DB::fetch_first_parm('plugin_tsdmpk_monster', '*', 'monster_imp_id=?', $my_data['char_id']);
	}
}

//把skill下面的type转化为对应的pool key名称
function get_pool_key($skill_type){
	$key_array = array(0 => 'might_current', 1 => 'speed_current', 2 => 'intellect_current');
	return $key_array[$skill_type];
}

//----------------------一些系统方法的封装--------------
//

//以json格式返回
function output_info($message, $is_ok = true){
	$info_array = array('message' => $message, 'status' => $is_ok ? 1 : 0);
	echo json_encode($info_array);
}

?>