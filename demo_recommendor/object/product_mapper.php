<?php
class ProdMapper{

	private $conn;
	private $tb_name = 'Prod_Desc_Mapper_xcel';
	
	public $ID;
	public $ProdDesc;
	public $ProdID;
	public $ProdGroup;
	public $status;
	
	public function __construct($db){
        $this->conn = $db;
    }
	
	public function getConn(){
		return $this->conn;
	}
	
	public function getAll(){
		
		// select all query
		$query = "SELECT * FROM  {$this->tb_name} WHERE ProdGroup!=-1";

		// prepare query statement
		$stmt = $this->conn->prepare($query);
	 
		// execute query
		$stmt->execute();
	 
		return $stmt;
	}

}
?>