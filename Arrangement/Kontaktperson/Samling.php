<?php

namespace UKMNorge\Arrangement\Kontaktperson;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Nettverk\OmradeKontaktpersoner;

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
		// Activate this on MIGRATIONDES24
        // $sql = new Query( Kontaktperson::getLoadQry() 
		// 				. " JOIN `smartukm_rel_pl_ab` AS `rel` ON (`rel`.`ab_id` = `kontakt`.`id`) "
        //                 . " WHERE `rel`.`pl_id` = '#id'"
        //                 . " ORDER BY `rel`.`order` ASC, "
        //                 . " `kontakt`.`firstname` ASC",
		// 			array('id' => $this->pl_id )
		// 			);
		// $res = $sql->run();
		// while( $rad = Query::fetch( $res ) ) {
		// 	$this->add( new Kontaktperson( $rad ) );
		// }
        
        // Deactivate this on MIGRATIONDES24
        // Get område kontaktpersoner (Se klassen UKMNorge\Nettverk\OmradeKontaktperson)
        // OmradeKontaktperson implementerer interface KontaktInterface
        $omrade_kontaktpersoner = new OmradeKontaktpersoner($this->pl_id, 'monstring');
        foreach( $omrade_kontaktpersoner->getAll() as $okp ) {
            $this->add( $okp );
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