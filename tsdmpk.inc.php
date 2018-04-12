<?php
/*
初音家二小姐@天使动漫
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, array(), array('login' => 1));
}

$phpself ='plugin.php?id=' . $identifier . ':' . $module;
$plug = $_G['setting']['plugins'];
$pre=$_G['config'][db][1][tablepre];

$truefight = 0;

if(submitcheck('submitoppuid',1)){
	$truefight = 1;
}

if(!isset($_G['gp_oppuid'])){
	$opp_id = 94;
}else{
	$opp_id = intval($_G['gp_oppuid']);
}

//set player array
$player1 = array('id','hp','atk','def');
$player2 = array('id','hp','atk','def');

//set player data
$player1['id'] = $_G['username'];
$player1_calc = calcStatBase($player1['id']);
$player1 = instAttr($player1,$player1_calc);

//load enemy data
$player2_data = DB::fetch_first("SELECT username FROM ".DB::table('common_member')." WHERE uid=$opp_id");
if($player2_data['username']==''){
	$report = '不存在这个uid：'.$opp_id;
	showmessage($report);
}

$player2['id']= $player2_data['username'];
$player2_calc = calcStatBase($player2['id']);
$player2 = instAttr($player2,$player2_calc);

//debug
//print_r($newarr);

//cheatsetting
if($player2['id'] == 94){
	$player2['hp'] = $player2['hp'] +400;
}

$message = '';

if($truefight == 1){
	$fightmessage = '<div id="war1"></div><div>'.fightloop($player1,$player2).'</div>';
	$fightmessage = str_replace('<br />','</div><div class="fi">',$fightmessage);
}

function fightloop($p1,$p2){
	$message = '';
	while($p1['hp']>0 && $p2['hp']>0){
			srand();
			$order = rand(0,1);
		if($order == 0){
			$result = meele($p2,$p1);
			$p1['hp'] = $result[0];
			$message = $message.$result[1];
			unset($result);
			if($p1['hp']>0){
				$result = meele($p1,$p2);
				$p2['hp'] = $result[0];
				$message = $message.$result[1];
				unset($result);
			}else{
				break;
			}
		}else{
			$result = meele($p1,$p2);
			$p2['hp'] = $result[0];
			$message = $message.$result[1];
			unset($result);
			if($p2['hp']>0){
				$result = meele($p2,$p1);
				$p1['hp'] = $result[0];
				$message = $message.$result[1];
				unset($result);
			}else{
				break;
			}
		}
	}
	if($p1['hp']<=0){
		$message = $message.$p2['id'].'胜利~';
	}else{
		$message = $message.$p1['id'].'胜利~';
	}
	return $message;
}

function meele($atker,$defer){
	$rtb = array();
	$dmg = 0;
	$dmg = $atker['atk'] - $defer['def'];
	
	if($dmg < 1){
		$dmg = 1;
	}
	//dmg_change
	$dmg = floor($dmg * (rand(80,120)/100));
	
	if($dmg < 1){
		$dmg = 1;
	}
	$defer['hp'] = $defer['hp'] - $dmg;
	$message = $message.$atker['id'].'攻击了'.$defer['id'].'，造成了'.$dmg.'点伤害。<br />';
	$rtb[] = $defer['hp'];
	$rtb[] = $message;
	return $rtb;
}

function calcStatBase($username){
	$addstat = array('base','hp','atk','def');
	$statbase = md5($username.'_tsdm');
	//$addstat['base'] = $statbase;
	$addstat['hp'] = hexdec(substr($statbase,0,2))*7+50;
	$addstat['atk'] = hexdec(substr($statbase,2,2)) + 1;
	$addstat['def'] = hexdec(substr($statbase,4,2));
	return $addstat;
}

function instAttr($inputarray,$addarray){
	$inputarray['hp'] = $inputarray['hp'] + $addarray['hp'];
	$inputarray['atk'] = $inputarray['atk'] + $addarray['atk'];
	$inputarray['def'] = $inputarray['def'] + $addarray['def'];
	return $inputarray;
}

function makeSkill($username){
	
}

function makeFeature($username){

}

include template("tsdmpk:tsdmpk");
?>
