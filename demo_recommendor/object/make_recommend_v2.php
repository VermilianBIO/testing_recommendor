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
	$score_splitter = explode(",",$Score);
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
	array_push($score_array[$ID], $fetch_item);
}

//var_dump($score_array);

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

$mapped_input_array = explode(",",$prod);


foreach($mapped_input_array as $k=>$v){
	
	$groupID = '-1';
	
	foreach($mapper_array as $m=>$n){
		
		if($v == $n["ProdDesc"]){ $groupID = $n["ProdGroup"]; }
	}
	
	$mapped_input_array[$k] = array("Name"=>$mapped_input_array[$k],"Group"=>$groupID);
}

//var_dump($mapped_input_array);

//==================================================================
//==================================================================
//==================================================================

$tmp = [];
foreach($mapped_input_array as $k=>$v){
	//echo $v["Group"];
	
	if(array_key_exists($v["Group"],$score_array)){
		//echo $v["Group"];
		//echo json_encode($score_array[$v["Group"]])."\n\n";
		//$tmp[$v] = $score_array[$v["Group"]][0]["Score"];
		if($score_array[$v["Group"]][0]["Score"] != [""]){
			array_push($tmp,$score_array[$v["Group"]][0]["Score"]);
		}
	}
	
}
//echo json_encode($tmp)."\n\n";


$summed_score = [];
array_walk_recursive($tmp, function($item,$key) use (&$summed_score){
	$summed_score[$key] = isset($summed_score[$key]) ? (string)($item + $summed_score[$key]) : $item;
});

//echo json_encode($summed_score)."\n\n";


//==================================================================
//==================================================================
//==================================================================

foreach(array_unique($summed_score) as $unique){
	//echo($unique.":\n");
	$key_of_unique = array_keys($summed_score,$unique);
	
	foreach($key_of_unique as $key){
		//echo($key."\n");
		if(array_key_exists($key,$sold_count_array)){
			//echo "yes \n";
			
			$summed_score[$key] = array("score"=>$summed_score[$key],"sold"=>$sold_count_array[$key],"name"=>$score_array[$key][0]["GroupName"],"image"=>$score_array[$key][0]["GroupImage"]);
		}
	}
	
	//$summed_score[$array_keys($unique)]
}
//echo json_encode($summed_score)."\n\n";

arsort($summed_score);
//echo json_encode($summed_score)."\n\n";

//==================================================================
//==================================================================
//==================================================================

foreach($mapped_input_array as $index=>$value){
	if(array_key_exists($value["Group"],$summed_score)){
		//echo $value["Group"]. "\n";
		unset($summed_score[$value["Group"]]);
	}
}
//echo json_encode($summed_score)."\n\n";

$summed_score = array_slice($summed_score,0,3,true);
//echo json_encode($summed_score)."\n\n";
//echo json_encode(array_keys($summed_score))."\n\n";

if(empty($summed_score)){
	echo 'No recommendation for this product';
}else{
	foreach($summed_score as $row=>$value){
		echo sprintf("<tr>
	<td><img src='%s' width='30%%' height='auto'></img></td>
	<td>%s</td>
	<td>%s</td>
	</tr>",
	$value["image"],
	$value["name"],
	$value["score"]);
	}
}
//==================================================================
//==================================================================
//==================================================================

?>
