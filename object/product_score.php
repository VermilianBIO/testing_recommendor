<?php
class ProdScore {

	private $conn;
	private $tb_name = 'product_score';
	
	public $ID;
	public $ProdID;
	public $Score;
	
	public function __construct($db){
        $this->conn = $db;
    }
	
	public function getConn(){
		return $this->conn;
	}
	
	public function getAll(){
		
		// select all query
		$query = "SELECT * FROM  {$this->tb_name}";

		// prepare query statement
		$stmt = $this->conn->prepare($query);
	 
		// execute query
		$stmt->execute();
	 
		return $stmt;
	}
	
	public function getAllAvailable(){
		
		// select all query
		$query = "SELECT * FROM  {$this->tb_name} WHERE Status = 1";

		// prepare query statement
		$stmt = $this->conn->prepare($query);
	 
		// execute query
		$stmt->execute();
	 
		return $stmt;
	}
	
	public function getAllType3(){
		
		// select all query
		$query = "SELECT * FROM  {$this->tb_name}_type3";

		// prepare query statement
		$stmt = $this->conn->prepare($query);
	 
		// execute query
		$stmt->execute();
	 
		return $stmt;
	}
	
	public function getAllAvailableType3(){
		
		// select all query
		$query = "SELECT * FROM  {$this->tb_name}_type3 WHERE Status = 1";

		// prepare query statement
		$stmt = $this->conn->prepare($query);
	 
		// execute query
		$stmt->execute();
	 
		return $stmt;
	}
	
	public function insertScore(){
		
		$query = "INSERT INTO
					{$this->table_name}
				SET
					ProdID = ?,
					Score = ?";

		$stmt = $this->conn->prepare($query);
		
		$this->ProdID=htmlspecialchars(strip_tags($this->ProdID));
		$this->Score=htmlspecialchars(strip_tags($this->Score));

		//echo('try this' .$this->VipCode);

		$stmt->bindParam(1, $this->ProdID);
		$stmt->bindParam(2, $this->Score);
		
		if($stmt->execute()){
			return true;
		}
	 	file_put_contents('./logs/InsertScore_log.txt',$this->getConn()->error,FILE_APPEND | LOCK_EX);
		
		return false;
		
	}
	
	public function updateScore(){
		
		
	}

}
?>