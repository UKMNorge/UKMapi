<?php

namespace UKMNorge\Arrangement\Kontaktperson;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Samling extends Collection {
    public $pl_id = null;
    
    /**
     * Opprett ny samling
     *
     * @param Int $pl_id
     */
	public function __construct( Int $pl_id ) {
		$this->pl_id = $pl_id;
				
		parent::__construct();
	}
	
	public function _load() {
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
    
    /**
     * Sjekk at objekter som legger til fungerer som kontaktpersoner
     *
     * @param KontaktInterface $kontaktperson
     * @return self
     */
    public function add( $kontaktperson ) {
        if( !isset( class_implements($kontaktperson)['UKMNorge\Arrangement\Kontaktperson\KontaktInterface'] )) {
            throw new Exception(
                'Kontaktpersoner må implementere KontaktInterface'
            );
        }
        return parent::add($kontaktperson);
    }
}