<?php
require_once('UKM/sql.class.php');
require_once('UKM/fylker.class.php');
	
class kommune {
	public function __construct( $id ) {
		$sql = new SQL("SELECT *
						FROM `smartukm_kommune`
						WHERE `id` = '#id'",
					    array('id' => $id ) );
		$res = $sql->run('array');
		$this->id = $res['id'];
		$this->name = utf8_encode($res['name']);
		$this->fylke = fylker::getById( $res['idfylke'] );
		$this->name_nonutf8 = $res['name'];
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getNameNonUTF8() {
		return $this->name_nonutf8;
	}
	
	public function getFylke() {
		return $this->fylke;
	}
}