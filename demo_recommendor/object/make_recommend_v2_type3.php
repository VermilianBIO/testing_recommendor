<?php

//Product-base filter
//** FINISHED


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");

include_once '../config/db.php';
include_once '../object/product_score.php';
include_once '../object/product_mapper.php';

$database = new Database();
$db = $database->getConnection();

$prod_score = new ProdScore($db);
$prod_score_list = $prod_score->getAllAvailable();
$prod_score_list_type3 = $prod_score->getAllAvailableType3();

$prod_mapper = new ProdMapper($db);
$mapper = $prod_mapper->getAll();

$prod = isset($_GET['prod']) ? $_GET['prod'] : die(json_encode(array('message' => 'No product specified','troubleshoot'=>$_GET['prod'])));
//$prodID = isset($_GET['prodID']) ? $_GET['prodID'] : die(json_encode(array('message' => 'No prodID specified','troubleshoot'=>$_GET['prodID'])));


//==================================================================
//======================= Fetching List ============================
//==================================================================

$score_array = [];
while ($row = $prod_score_list->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	$score_array[$ID] = [];
	$score_splitter = !empty($Score)? explode(",",$Score):[];
	if(!empty($score_splitter)){
		foreach($score_splitter as $k=>$v){
			$group_score = explode(":",$v);
			$score_splitter[$k] = array($group_score[0]=>$group_score[1]);
		}
	}
	
	$fetch_item = array(
			"ProdGroup" => ($ProdGroup),
			"GroupName" => $GroupName,
			"Score" => $score_splitter,
			"Status" => $Status,
			"LastUpdate" => $LastUpdate,
			"GroupImage" => $GroupImage
			);
	array_push($score_array[$ID], $fetch_item);
}

//var_dump($score_array);

$score_array_type3 = [];
while ($row = $prod_score_list_type3->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	$score_array_type3[$ProdGroup] = [];
	$score_splitter = !empty($Score)? explode(",",$Score):[];
	foreach($score_splitter as $k=>$v){
		if(!empty($score_splitter[$k])){
			$group_score = explode(":",$v);
			$score_splitter[$k] = array($group_score[0]=>$group_score[1]);
		}
	}
	
	$fetch_item = array(
			"ProdGroup" => ($ProdGroup),
			"GroupName" => $GroupName,
			"Score" => $score_splitter,
			"Status" => $Status,
			"LastUpdate" => $LastUpdate,
			"GroupImage" => $GroupImage
			);
	$score_array_type3[$ProdGroup] = $fetch_item;
	//array_push($score_array_type3[$ProdGroup], $fetch_item);
}

//var_dump($score_array_type3);

$mapper_array = [];
while ($row = $mapper->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	$fetch_item = array(
			"ProdDesc" => ($ProdDesc),
			"ProdID" => $ProdID,
			"ProdGroup" => $ProdGroup,
			);
	array_push($mapper_array, $fetch_item);
}

//var_dump($mapper_array);

//==================================================================
//==================================================================
//==================================================================

$mapped_input_array = explode(",",$prod);

$mapped_input_array = array_filter($mapped_input_array);
foreach($mapped_input_array as $k=>$v){
	
	$groupID = '-1';
	
	foreach($mapper_array as $m=>$n){
		
		if($v == $n["ProdDesc"]){ $groupID = $n["ProdGroup"]; }
	}
	if($groupID>0){

		$mapped_input_array[$k] = array("Product Name"=>$mapped_input_array[$k],"Group"=>$groupID);
	}	
}

usort($mapped_input_array, function($a,$b) {
	return $a["Group"] > $b["Group"];
});

//var_dump($mapped_input_array);

$input_groupname = '';
foreach($mapped_input_array as $k=>$v){
	if(empty($input_groupname)){
		$input_groupname = $v["Group"];
	}else{
		$input_groupname = $input_groupname.",".$v["Group"];
	}
}
//echo $input_groupname."\n\n";

//==================================================================
//==================================================================
//==================================================================

$query = "SELECT ProdGroup,count(ProdGroup) as Count
		FROM `vip_purchases_list`
		GROUP BY ProdGroup
		ORDER BY `Count` DESC";
$stmt = $db->prepare($query);

$stmt->execute();

$sold_count_array = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	
	/*$fetch_item = array(
			($ProdGroup) => $Count,
			);
	array_push($sold_count_array, $fetch_item);
	*/
	$sold_count_array[$ProdGroup] = $Count;
}

//echo json_encode($sold_count_array)."\n";

//==================================================================
//==================================================================
//==================================================================

if(array_key_exists($input_groupname,$score_array_type3)){
	$pre_result = $score_array_type3[$input_groupname]["Score"];
	$result=[];
	foreach($pre_result as $key=>$value){
		foreach($value as $k=>$v){
			$result[$k] = [];
		$result[$k] = ["score"=>$v,"sold"=>$sold_count_array[$k],"name"=>$score_array[$k][0]["GroupName"],"image"=>$score_array[$k][0]["GroupImage"]];
		}
	}
	arsort($result);
	//echo json_encode($result)."\n\n";
	
	$result = array_slice($result,0,3,true);
	if(!empty($result)){
		foreach($result as $row=>$value){
			echo sprintf("<tr>
			<td><img src='%s' width='30%%' height='auto'></img></td>
			<td>%s</td>
			<td>%s</td>
			</tr>",
			$value["image"],
			$value["name"],
			$value["score"]);
		}
	}else{
		echo "The result of this combination doesn't exist\n\n";
	}
}else{
	echo "The result of this combination doesn't exist\n\n";
}

//==================================================================
//==================================================================
//==================================================================


?>
