<?php
/* 
Part of: UKM Norge core
Description: For bruk til testing av SQL-sp¿rringer mot UKM-databasen
Author: UKM Norge / M Mandal 
Version: 2.0 
*/

##################################################################################################################
##################################################################################################################
##												SQL CLASS TO RUN QUERIES										##
##################################################################################################################
##################################################################################################################
class SQL {
	var $sql;
	var $db;
	
	function SQL($sql, $keyval=array()) {
		global $db;
		foreach($keyval as $key => $val) {
			if (get_magic_quotes_gpc())
				$val = stripslashes($val);
			$sql = str_replace('#'.$key, mysql_real_escape_string(trim(strip_tags($val))), $sql);
		}
		$this->sql = $sql;
	}
	function connect() {
		$this->db = @mysql_connect("localhost","betaukm_betaukm","LGTwzUWmoG6d") or die($ERR);
		if (!$this->db) die($ERR);
		mysql_select_db('betaukm_ss3',$this->db);	
	}
	
	function run($what='resource', $name='') {
		$this->connect();
		$temp = mysql_query($this->sql, $this->db);
		if(!$temp) return false;
		switch($what) {
			case 'field':
				$temp = mysql_fetch_array($temp);
				return $temp[$name];
			case 'array':
				return mysql_fetch_assoc($temp);
			default:
				return $temp;
		}
	}
	function debug() {
		return $this->sql;
	}
}

##################################################################################################################
##################################################################################################################
##												SQL CLASS TO DELETE STUFF										##
##################################################################################################################
##################################################################################################################
class SQLdel {
	var $db;
	var $sql;
	
	function SQLdel($table, $where=array()) {
		$wheres = '';
		$max = sizeof($where);
		$i = 0;
		foreach($where as $field => $val) {
			$i++;
			$wheres .= "`".$field."` = '".$val."'";
			if($i<$max)
				$wheres .= ' AND ';
		}
		
		$this->sql = 'DELETE FROM `'.$table.'` WHERE '.$wheres.';';
		$this->connect();
	}
	
	function connect() {
		$this->db = @mysql_connect("localhost","betaukm_betaukm","LGTwzUWmoG6d") or die($ERR);
		if (!$this->db) die($ERR);
		mysql_select_db('betaukm_ss3',$this->db);	
	}
	
	function run() {
		var_dump($qry);
		#$qry = mysql_query($this->sql, $this->db);
		return mysql_affected_rows();
	}
}
##################################################################################################################
##################################################################################################################
##												SQL CLASS TO INSERT STUFF										##
##################################################################################################################
##################################################################################################################
class SQLins {
	var $wheres = ' WHERE ';
	var $db;
	
	function SQLins($table, $where=array()) {
		$this->table = $table;
		## IF THIS IS A UPDATE-QUERY
		if(sizeof($where) > 0) {
			$this->update = true;
			foreach($where as $key => $val) {
				$this->wheres .= "`".$key."`='".$val."' AND ";
			}
			$this->wheres = substr($this->wheres, 0, (strlen($this->wheres)-5));
		## IF THIS IS A INSERT-ARRAY
		} else {
			$this->update = false;	
		}
	}
	
	function add($key, $val) {
		$this->keys[] = $key;
		$this->vals[] = $val;
	}
	
	function debug() {
		$this->run(false);	
	}
	
	function run($run=true) {
		if($this->update) {
			## init query
			$sql = 'UPDATE `'.$this->table.'` SET ';
			## set new values
			for($i=0; $i<sizeof($this->keys); $i++) {
				$sql .= "`".$this->keys[$i]."` = '".$this->vals[$i]."', ";
			}
			$sql = substr($sql, 0, (strlen($sql)-2));

			## add where
			$sql .= $this->wheres;	
		} else {
			## set the new values
			for($i=0; $i<sizeof($this->keys); $i++) {
				$keylist .= '`'.$this->keys[$i].'`, ';
				$vallist .= "'".$this->vals[$i]."', ";
			}
			$keylist = substr($keylist, 0, (strlen($keylist)-2));
			$vallist = substr($vallist, 0, (strlen($vallist)-2));
			## complete query
			$sql = 'INSERT INTO `'.$this->table.'` ('.$keylist.') VALUES ('.$vallist.');';
		}
			
		$this->connect();
		if(!$run) utf8_decode(var_dump($sql));
		else $qry = mysql_query(utf8_decode($sql), $this->db);
		return mysql_affected_rows();
	}
	
	function connect() {
		$this->db = @mysql_connect("localhost","betaukm_betaukm","LGTwzUWmoG6d") or die($ERR);
		if (!$this->db) die($ERR);
		mysql_select_db('betaukm_ss3',$this->db);	
	}
}
?>