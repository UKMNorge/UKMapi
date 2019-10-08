<?php

namespace UKMNorge\Arrangement\Kontaktperson;

use UKMNorge\Database\SQL\Query;

class Samling {
    public $pl_id = null;
	
	public function __construct( $pl_id ) {
		$this->pl_id = $pl_id;
		
		$this->_load();
		
		parent::__construct();
	}
	
	private function _load() {
		$sql = new Query( Kontaktperson::getLoadQry() 
						. " JOIN `smartukm_rel_pl_ab` AS `rel` ON (`rel`.`ab_id` = `kontakt`.`id`) "
                        . " WHERE `rel`.`pl_id` = '#id'"
                        . " ORDER BY `rel`.`order` ASC, "
                        . " `kontakt`.`firstname` ASC",
					array('id' => $this->pl_id )
					);
		$res = $sql->run();
		while( $rad = Query::fetch( $res ) ) {
			$this->add( new Kontaktperson( $rad ) );
		}
	}
}