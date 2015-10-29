<?php
	class Sql{
		private $hostname = "localhost";
		private $username = "root";
		private $password = "root";
		private $databaseName = "crawler";
		public  $link = null;
		public function __construct(){
			$this->link = mysqli_connect($this->hostname,$this->username,$this->password) or die("connet database failed !");
			mysqli_select_db($this->link, $this->databaseName) or die("database select failed !");
			mysqli_query($this->link, "set names GBK");
		}
		//返回一个二维关联数组
		public function dql($sql){
			$result = mysqli_query($this->link, $sql);
			$r = array();
			while($row = mysqli_fetch_row($result))$r[]=$row;
			mysqli_free_result($result);
			return $r;
			
		}
		public function dml($sql){
			$result = mysqli_query($this->link, $sql);
			return $result;
		}
		public function __destruct(){
			mysqli_close($this->link);
		}
	}
