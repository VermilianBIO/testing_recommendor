<?php

//******** FINISHED

include_once '../config/db.php';
include_once '../object/product_score.php';

$PROD_GROUP_RANGE = 1000;

//==================================================================
//==================================================================
//==================================================================

$database = new Database();
$db = $database->getConnection();

$prod_score = new ProdScore($db);

//==================================================================
//==================================================================
//==================================================================

$scope_input = isset($_POST['scope']) ? $_POST['scope'] : die(json_encode(array('message' => 'No data scope specified','troubleshoot'=>$_POST['scope'])));
$scope = '';
switch ($scope_input){
	case 'alltime':
		break;
	case '3months':
		$scope = 'AND A.OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 3 month)';
		break;
	case '6months':
		$scope = 'AND A.OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 6 month)';
		break;
	case '9months':
		$scope = 'AND A.OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 9 month)';
		break;
	case '12months':
		$scope = 'AND A.OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 12 month)';
		break;
	default:
		$scope = '';
}


//all time purchases_list
$query = "SELECT DISTINCT A.StoreID,A.OrderNo,GROUP_CONCAT(DISTINCT A.Vipcode)as VipCode,GROUP_CONCAT(DISTINCT B.ProdID) as ProdID,GROUP_CONCAT(DISTINCT B.ProdGroup ORDER BY B.ProdGroup ASC) as ProdGroup
	FROM `SL_purch_hist_2017_2019_June_compact` A JOIN `Prod_Desc_Mapper_xcel` B on A.ItemDesc = B.ProdDesc
	WHERE B.ProdGroup != -1
		AND A.Vipcode != ''
		AND A.OrderDate > '2018-12-30'
		{$scope}
    GROUP BY A.OrderNo,A.StoreID
	HAVING count(DISTINCT ProdGroup) > 1
	ORDER BY A.StoreID ASC, A.OrderNo ASC";
$stmt = $db->prepare($query);

$stmt->execute();

$purchases_list_array = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	$fetch_item = array(
			"StoreID" => $StoreID,
			"OrderNo" => $VipCode,
			"ProdGroup" => explode(",",$ProdGroup),
			//"ProdGroup" => implode(",",array_unique(explode(",",$ProdGroup))),
			);
	array_push($purchases_list_array, $fetch_item);
}
//print_r($purchases_list_array);
//echo max($purchases_list_array['ProdGroup']);

//==================================================================
//==================================================================
//==================================================================


$total_combi_array = [];
foreach($purchases_list_array as $k=>$v){
	
	//$current_set = $purchases_list_array[0]["ProdGroup"];
	$current_set = $purchases_list_array[$k]["ProdGroup"];
	$power_set = array_all_combi($current_set);
	//unset($power_set[0]);
	asort($power_set);
	$combi_array =[];
	$i = 0;
	foreach($power_set as $element){
		sort($element);
		foreach($element as $kk){
			if(isset($combi_array[$i])){
				$combi_array[$i] = $combi_array[$i].",".$kk;
			}else{
				$combi_array[$i] = $kk;
			}
		}
		$i+=1;
	}
	
	$scoresheet = [];
	foreach($combi_array as $k){
		
		//echo json_encode(($k));
		$pusher = count_exclude_needle($k,implode(",",$current_set));
		array_push($scoresheet,$pusher);
	}
	$scoresheet = array_filter($scoresheet);
	//echo json_encode($scoresheet);
	
	
	//array_push($total_combi_array,($combi_array));
	array_push($total_combi_array,$scoresheet);
	
	
	
	
}
$total_combi_array = call_user_func_array('array_merge',$total_combi_array);
$merged_total_combi_array = [];
foreach ($total_combi_array as $kmain){
	
	foreach($kmain as $ksub=>$kvalue){
		if(!isset($merged_total_combi_array[$ksub])){
			$merged_total_combi_array[$ksub] = [];
		}
		array_push($merged_total_combi_array[$ksub],$kvalue);
	}
}
//echo "\n".json_encode(($total_combi_array));


$final_scoresheet = [];
foreach($merged_total_combi_array as $k=>$v){
	$summed_score = [];
	array_walk_recursive($v, function($item,$key) use (&$summed_score){
	$summed_score[$key] = isset($summed_score[$key]) ? (string)($item + $summed_score[$key]) : $item;
	});
	arsort($summed_score);
	$final_scoresheet[$k]=$summed_score;
}
ksort($final_scoresheet);
//echo "\n".json_encode(($final_scoresheet));

//==================================================================
//==================================================================
//==================================================================

$query = "SELECT * FROM  product_score_type3";

$stmt = $db->prepare($query);

$stmt->execute();

$old_scoresheet = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	$old_scoresheet[$ProdGroup] = [];
	
	$fetch_item = array(
			"ProdGroup" => ($ProdGroup),
			"Score" => $Score,
			"Status" => $Status,
			"LastUpdate" => $LastUpdate
			);
	array_push($old_scoresheet[$ProdGroup], $fetch_item);
}

//var_dump($old_scoresheet);

//==================================================================
//==================================================================
//==================================================================

$curr_time = date('Y-m-d H:i:s');

$reset_query = "UPDATE
				`product_score_type3`
			SET
				Score = '',
				RollbackScope = ?,
				LastUpdate = ?
				";
$reset_stmt = $db->prepare($reset_query);
$reset_stmt->bindParam(1,$scope_input);
$reset_stmt->bindParam(2,$curr_time);

$reset_stmt->execute();

foreach($final_scoresheet as $group=>$score){
	
	//print_r($score);
	
	
	//echo $group . "\n";
	//echo json_encode($score) . "\n";
	$imploded_score = "";
	
	foreach($score as $k=>$v){
		if(empty($imploded_score)){
			$imploded_score = $imploded_score . $k.":".$v;
		}else{
			$imploded_score = $imploded_score. "," . $k.":".$v;
		}
	}
	//echo $group."{".$imploded_score."}\n\n";
	
	if(!array_key_exists($group,$old_scoresheet)){
		$query = "INSERT INTO
					`product_score_type3` (ProdGroup,Score,Status,LastUpdate)
				VALUES
					(
					?,
					?,
					1,
					?)
				";
		$stmt3 = $db->prepare($query);
		
		$stmt3->bindParam(1,$group);
		$stmt3->bindParam(2,$imploded_score);
		$stmt3->bindParam(3,$curr_time);

		if($stmt3->execute()){
			echo json_encode(array("message"=>"ProdGroup $group : insert success"));
		}else{
			echo json_encode(array("message"=>"ProdGroup $group : insert error"));
		}
	}else{
		//echo $old_scoresheet[$group]["Score"]."\n";
		//echo $score."\n";
		if($old_scoresheet[$group][0]["Score"] != $imploded_score){
			$query = "UPDATE
						`product_score_type3`
					SET
						Score = ?,
						LastUpdate = ?
					WHERE
						ProdGroup = ?
					";
			$stmt4 = $db->prepare($query);
			
			$stmt4->bindParam(1,$imploded_score);
			$stmt4->bindParam(2,$curr_time);
			$stmt4->bindParam(3,$group);
			
			if($stmt4->execute()){
				echo json_encode(array("message"=>"ProdGroup $group : update success"));
			}else{
				echo json_encode(array("message"=>"ProdGroup $group : update error"));
			}
		}else{
			echo json_encode(array("message"=>"ProdGroup $group : no need to update"));
		}
	}
}

//==================================================================
//==================================================================
//==================================================================

function array_flatten($array, $prefix = ''){
	$result = [];
	foreach($array as $k=>$v){
		if(is_array($v)){
			$result = $result + array_flatten($v, $prefix . $k . '.');
		}else {
			$result[$prefix.$k]=$v;
		}
	}
	return $result;
}

function array_all_combi($array) {
	
    $results = [[]];

	foreach ($array as $element){
		foreach ($results as $combination=>$value){
			array_push($results, array_merge([$element], $value));
		}
	}
    return $results;
}

function count_exclude_needle($needle,$haystack){
	$needle_arr = explode(",",$needle);
	$haystack_arr = explode(",",$haystack);
	
	foreach($needle_arr as $k){
		if(isset($haystack_arr[array_keys($haystack_arr,$k)[0]])){
			unset($haystack_arr[array_keys($haystack_arr,$k)[0]]);
		}
	}
	asort($haystack_arr);
	$haystack_arr = array_values($haystack_arr);
	if(!empty($haystack_arr)){
		return array($needle=>array_count_values($haystack_arr));
	}
}

?>