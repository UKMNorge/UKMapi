<?php 
require_once('UKM/_collection.class.php');
require_once('UKM/sql.class.php');
require_once('UKM/kontakt.class.php');
	
class kontaktpersoner extends Collection {
	public $pl_id = null;
	
	public function __construct( $pl_id ) {
		$this->pl_id = $pl_id;
		
		$this->_load();
		
		parent::__construct();
	}
	
	private function _load() {
		$sql = new SQL( kontakt_v2::getLoadQry() 
						. " JOIN `smartukm_rel_pl_ab` AS `rel` ON (`rel`.`ab_id` = `kontakt`.`id`) "
                        . " WHERE `rel`.`pl_id` = '#id'"
                        . " ORDER BY `rel`.`order` ASC, "
                        . " `kontakt`.`firstname` ASC",
					array('id' => $this->pl_id )
					);
		$res = $sql->run();
		while( $rad = SQL::fetch( $res ) ) {
			$this->add( new kontakt_v2( $rad ) );
		}
	}
	
}
?>