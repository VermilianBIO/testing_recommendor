<?php

//******** FINISHED


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");

include_once '../config/db.php';

//==================================================================
//==================================================================
//==================================================================

$database = new Database();
$db = $database->getConnection();

//==================================================================
//==================================================================
//==================================================================

$scope_input = isset($_GET['scope']) ? $_GET['scope'] : '';
$scope = '';
switch ($scope_input){
	case 'alltime':
		break;
	case '3months':
		$scope = 'AND OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 3 month)';
		break;
	case '6months':
		$scope = 'AND OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 6 month)';
		break;
	case '9months':
		$scope = 'AND OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 9 month)';
		break;
	case '12months':
		$scope = 'AND OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 12 month)';
		break;
	default:
		$scope = 'AND OrderDate > DATE_SUB((SELECT max(OrderDate) FROM `SL_purch_hist_2017_2019_June_compact`),INTERVAL 12 month)';
}

echo json_encode($scope_input);


$query = "SELECT B.ProdGroup, GROUP_CONCAT(DISTINCT C.GroupName) as 'Name',count(C.GroupName) as 'Count', GROUP_CONCAT(DISTINCT C.GroupImage) as 'Image'
		FROM (
			SELECT DISTINCT Vipcode,substring_index(GROUP_CONCAT(ItemDesc),',',1) as 'FirstProduct',RegionGroup
			FROM `SL_purch_hist_2017_2019_June_compact`
			WHERE Vipcode <> ''
			{$scope}
			GROUP BY Vipcode,RegionGroup ORDER BY Vipcode ASC) A
			JOIN `Prod_Desc_Mapper_xcel` B on A.FirstProduct = B.ProdDesc JOIN `product_score` C on B.ProdGroup = C.ProdGroup
		GROUP BY C.ProdGroup ORDER BY count(C.ProdGroup) DESC";

$stmt = $db->prepare($query);

$stmt->execute();

$popular_list_array = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	array_push($popular_list_array, array(
										'Name' => $Name,
										'Group' => $ProdGroup,
										'Image' => $Image,
										'Count' => $Count
									));
}

if(!empty($popular_list_array)){
	http_response_code(202);
	echo json_encode($popular_list_array,JSON_PRETTY_PRINT);
	/*echo '<table>';
	foreach($popular_list_array as $k){
		echo sprintf("<tr>
		<td><img src='%s' width='30%%' height='auto'></img></td>
		<td>%s</td>
		<td>%s</td>
		</tr>",
		$k['Image'],
		$k['Name'],
		$k['Count']);
	}
	echo '</table>';*/
}else{
	http_response_code(400);
	echo json_encode(array("message"=>"Error retreiving Popular Product List"));
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

//==================================================================
//==================================================================
//==================================================================

?>