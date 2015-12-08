<?php
/* 
Part of: UKM Norge core
Description: SQL-klasse for bruk av SQL-sp¿rringer opp mot UKM-databasen.
Author: UKM Norge / M Mandal 
Version: 2.0 
*/

##################################################################################################################
##################################################################################################################
##												SQL CLASS TO RUN QUERIES										##
##################################################################################################################
##################################################################################################################
if(!class_exists('SQL')) {
	require_once('UKMconfig.inc.php');
	class SQL {
		var $sql;
		var $db;
		
		function SQL($sql, $keyval=array()) {
			$this->error = false;
			global $db;
			foreach($keyval as $key => $val) {
				if (get_magic_quotes_gpc())
					$val = stripslashes($val);
				$this->connect();
				$sql = str_replace('#'.$key, mysql_real_escape_string(trim(strip_tags($val))), $sql);
			}
			$this->sql = $sql;
		}
		
		function charset($set='UTF-8') {
			$this->charset = $set;
		}
		function connect() {
			$this->db = mysql_connect(UKM_DB_HOST, UKM_DB_USER, UKM_DB_PASSWORD) or die(mysql_error());
			if (!$this->db) die($ERR);
			mysql_select_db(UKM_DB_NAME,$this->db);	
		}
		
		function error() {
			$this->error = true;
		}
		
		function run($what='resource', $name='') {
			$this->connect();
			if(isset($this->charset)) {
				mysql_set_charset( $this->charset, $this->db );
			}
			if($this->error)
				$temp = mysql_query($this->sql, $this->db) or die(mysql_error());
			else
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
			return $this->sql.'<br />';
		}
		
		function insid() {
			return mysql_insert_id( $this->db );
		}
	}
}

##################################################################################################################
##################################################################################################################
##												SQL CLASS TO DELETE STUFF										##
##################################################################################################################
##################################################################################################################
if(!class_exists('SQLdel')) {
	class SQLdel {
		var $db;
		var $sql;
		
		function SQLdel($table, $where=array()) {
			$wheres = '';
			$max = sizeof($where);
			$i = 0;
			foreach($where as $field => $val) {
				$i++;
				if( intval( $val ) > 0 )
	                $wheres .= "`".$field."` = ".$val;
	            else
	                $wheres .= "`".$field."` = '".$val."'";
	                
				if($i<$max)
					$wheres .= ' AND ';
			}
			
			$this->sql = 'DELETE FROM `'.$table.'` WHERE '.$wheres.';';
			$this->connect();
		}

		function charset($set='UTF-8') {
			$this->charset = $set;
		}

		function connect() {
			$this->db = @mysql_connect(UKM_DB_HOST, UKM_DB_WRITE_USER, UKM_DB_PASSWORD) or die(mysql_error());
			if (!$this->db) die($ERR);
			mysql_select_db(UKM_DB_NAME,$this->db);
		}
		
		function debug() {
			return $this->run(false);
		}	
		
		function run($run=true) {
			if(!$run)
				return $this->sql.'<br />';
			if(isset($this->charset)) {
				mysql_set_charset( $this->charset, $this->db );
			}
			$qry = mysql_query($this->sql, $this->db);
			//echo mysql_error();
			return mysql_affected_rows();
		}
	}
}
##################################################################################################################
##################################################################################################################
##												SQL CLASS TO INSERT STUFF										##
##################################################################################################################
##################################################################################################################
if(!class_exists('SQLins')) {
	class SQLins {
		var $wheres = ' WHERE ';
		var $db;
		var $keys = array();
		var $vals = array();

		function SQLins($table, $where=array()) {
			$this->table = $table;
			## IF THIS IS A UPDATE-QUERY
			if(sizeof($where) > 0) {
				$this->update = true;
				foreach($where as $key => $val) {
					$this->wheres .= "`".$key."`='".$val."' AND ";
				}
				// Remove last 5 chars (' AND ')
				$this->wheres = substr($this->wheres, 0, (strlen($this->wheres)-5));
			## IF THIS IS A INSERT-ARRAY
			} else {
				$this->update = false;	
			}
		}

		function charset($set='UTF-8') {
			$this->charset = $set;
		}

		function add($key, $val) {
			$this->keys[] = $key;
			$this->vals[] = $val;
		}
		
		function debug() {
			return $this->run(false);
		}
		
		function run($run=true) {
			$this->connect();
			if(isset($this->charset)) {
				mysql_set_charset( $this->charset, $this->db );
			}

			$keylist = $vallist = '';
			if($this->update) {
				## init query
				$sql = 'UPDATE `'.$this->table.'` SET ';
				## set new values
				for($i=0; $i<sizeof($this->keys); $i++) {
					$val = mysql_real_escape_string(trim(strip_tags($this->vals[$i])));
					$sql .= "`".$this->keys[$i]."` = '".$val."', ";
				}
			
				$sql = substr($sql, 0, (strlen($sql)-2));
	
				## add where
				$sql .= $this->wheres;	
			} else {
				## set the new values
				if (isset($this->keys) && is_array($this->keys)) {
					for($i=0; $i<sizeof($this->keys); $i++) {
						$keylist .= '`'.$this->keys[$i].'`, ';
						$vallist .= "'".$this->vals[$i]."', ";
					}
				}
				$keylist = substr($keylist, 0, (strlen($keylist)-2));
				$vallist = substr($vallist, 0, (strlen($vallist)-2));
				## complete query
				$sql = 'INSERT INTO `'.$this->table.'` ('.$keylist.') VALUES ('.$vallist.');';
			}
				

			if(!$run) return $sql.'<br />';
					#'<div class="widefat" style="margin: 12px; margin-top: 18px; width: 730px;padding:10px; background: #f1f1f1;">'.$sql.'</div>';
			else{
				$qry = mysql_query(utf8_decode($sql), $this->db);
				return mysql_affected_rows();
			}
		}
		
		function connect() {
			$this->db = @mysql_connect(UKM_DB_HOST, UKM_DB_WRITE_USER, UKM_DB_PASSWORD) or die('MySQL connect error');
			if (!$this->db) die($ERR);
			mysql_select_db(UKM_DB_NAME,$this->db);
		}
		function insid() {
			return mysql_insert_id( $this->db );
		}
	}
}
?>
