<?php

//******** FINISHED


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");

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

echo json_encode($scope_input);

//all time purchases_list
$query = "SELECT VipCode,GROUP_CONCAT(DISTINCT ProdGroup) as ProdGroup
		FROM (SELECT DISTINCT A.Vipcode,B.ProdDesc,B.ProdID,B.ProdGroup, A.OrderDate
			FROM `SL_purch_hist_2017_2019_June_compact` A JOIN `Prod_Desc_Mapper_xcel` B on A.ItemDesc = B.ProdDesc
			WHERE B.ProdGroup != -1
				AND A.Vipcode != ''
				{$scope}
			ORDER BY A.Vipcode ASC, B.ProdGroup ASC) PList
		GROUP BY VipCode
		ORDER BY VIPCode ASC";
$stmt = $db->prepare($query);

$stmt->execute();

$purchases_list_array = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	$fetch_item = array(
			"VipCode" => $VipCode,
			"ProdGroup" => array_unique(explode(",",$ProdGroup)),
			//"ProdGroup" => implode(",",array_unique(explode(",",$ProdGroup))),
			);
	array_push($purchases_list_array, $fetch_item);
}
//print_r($purchases_list_array);
//echo max($purchases_list_array['ProdGroup']);

$processsheet = [];
$new_scoresheet = [];
for($i = 1; $i < $PROD_GROUP_RANGE; $i++){
	
	$processsheet["$i"]=[];
	foreach($purchases_list_array as $row){
	
		//print_r($row);
	
		if(in_array($i,$row["ProdGroup"]) && count($row["ProdGroup"]) > 1){
			//echo $i . " : " . $row["VipCode"] . " ==\n";
			//print_r($row["ProdGroup"]);
			
			$del_key = array_search($i,$row["ProdGroup"]);
			unset($row["ProdGroup"][$del_key]);
			//print_r($row["ProdGroup"]);
			
			//array_push($new_scoresheet["Group $i"],array((($row["ProdGroup"]))));
			array_push($processsheet["$i"],(($row["ProdGroup"])));
			
		}
		
	}
	$processsheet["$i"] = (array_count_values(array_flatten($processsheet["$i"])));
	arsort($processsheet["$i"]);
	
	foreach($processsheet["$i"] as $k=>$v){
		//echo $k.":".$v."\n";
		if(empty($new_scoresheet["$i"])){
			$new_scoresheet["$i"] = $k.":".$v;
		}else{
			$new_scoresheet["$i"] = $new_scoresheet["$i"]. "," .$k.":".$v;
		}
	}
}

//print_r(implode(":",$new_scoresheet));
//var_dump(json_encode($processsheet));
//var_dump(json_encode(($new_scoresheet)));
//var_dump(json_encode(explode(",",$new_scoresheet["Group 1"])));

//==================================================================
//==================================================================
//==================================================================

$stmt2 = $prod_score->getAll();

$old_scoresheet = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	$old_scoresheet[$ID] = [];
	
	$fetch_item = array(
			"ProdGroup" => ($ProdGroup),
			"Score" => $Score,
			"Status" => $Status,
			"LastUpdate" => $LastUpdate
			);
	array_push($old_scoresheet[$ID], $fetch_item);
}

//echo "\n test\n";
//var_dump($old_scoresheet);

//==================================================================
//==================================================================
//==================================================================


$curr_time = date('Y-m-d H:i:s');

$reset_query = "UPDATE
				`product_score`
			SET
				Score = '',
				RollbackScope = ?,
				LastUpdate = ?
				";
$reset_stmt = $db->prepare($reset_query);
$reset_stmt->bindParam(1,$scope_input);
$reset_stmt->bindParam(2,$curr_time);

$reset_stmt->execute();

foreach($new_scoresheet as $group=>$score){
	
	//echo $group . "\n";
	//echo $score . "\n";
	
	if(!array_key_exists($group,$old_scoresheet)){
		$query = "INSERT INTO
					`product_score` (ID,ProdGroup,Score,Status,LastUpdate)
				VALUES
					(?,
					?,
					?,
					1,
					?)
				";
		$stmt3 = $db->prepare($query);
		
		$stmt3->bindParam(1,$group);
		$stmt3->bindParam(2,$group);
		$stmt3->bindParam(3,$score);
		$stmt3->bindParam(4,$curr_time);

		if($stmt3->execute()){
			echo json_encode(array("message"=>"ProdGroup $group : insert success"));
		}else{
			echo json_encode(array("message"=>"ProdGroup $group : insert error"));
		}
	}else{
		//echo $old_scoresheet[$group]["Score"]."\n";
		//echo $score."\n";	
		
		if($old_scoresheet[$group][0]["Score"] == $score){
			echo json_encode(array("message"=>"ProdGroup $group : no need to update"));
		}else{
			$query = "UPDATE
						`product_score`
					SET
						Score = ?,
						LastUpdate = ?
					WHERE
						ID = ?
					";
			$stmt4 = $db->prepare($query);
			
			$stmt4->bindParam(1,$score);
			$stmt4->bindParam(2,$curr_time);
			$stmt4->bindParam(3,$group);
			
			if($stmt4->execute()){
				echo json_encode(array("message"=>"ProdGroup $group : update success"));
			}else{
				echo json_encode(array("message"=>"ProdGroup $group : update error"));
			}
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


?>