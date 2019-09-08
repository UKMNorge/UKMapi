<?php

namespace UKMNorge\Arrangement\Videresending;

use UKMNorge\Database\SQL\Query;
use Exception, DateTime;
use fylker, kommune;

class Videresending
{

    private $id;
    private $mottakere;
    private $avsendere;

    public function __construct(Int $pl_id)
    {
        $this->id = $pl_id;
    }


    /**
     * Hvem kan denne mønstringen sende innslag til?
     *
     * @return Array[Arrangement]
     */
    public function getMottakere()
    {
        if (null == $this->mottakere) {
            require_once('UKM/Arrangement/Videresending/Mottaker.php');
            $this->_loadMottakere();
        }
        return $this->mottakere;
    }

    /**
     * Hvem kan sende innslag til denne mønstringen?
     *
     * @return Array[Arrangement]
     */
    public function getAvsendere()
    {
        if (null == $this->avsendere) {
            require_once('UKM/Arrangement/Videresending/Avsender.php');
            $this->_loadAvsendere();
        }
        return $this->avsendere;
    }

    /**
     * Load avsendere
     *
     * @return void
     */
    private function _loadAvsendere()
    {
        return $this->_load('avsendere');
    }

    /**
     * Load mottakere
     *
     * @return void
     */
    private function _loadMottakere()
    {
        return $this->_load('mottakere');
    }

    /**
     * Faktisk load
     *
     * @param String $type (avsendere|mottakere)
     * @return void
     */
    private function _load( $type ) {
        require_once('UKM/Database/SQL/select.class.php');
        require_once('UKM/fylker.class.php');
        require_once('UKM/kommune.class.php');

        $sql = new Query(
            "SELECT `rel`.*,
            `place`.`pl_name`, 
            `place`.`pl_start`,
            `place`.`pl_owner_fylke`,
            `place`.`pl_owner_kommune`,
            `place`.`pl_registered`
            FROM `ukm_rel_pl_videresending` AS `rel`
            JOIN `smartukm_place` AS `place`
                ON( `place`.`pl_id` = `rel`.`#motsatt_retning`)
            WHERE `rel`.`#retning` =  '#pl_id'",
            [
                'motsatt_retning' => $type != 'mottakere' ? 'pl_id_sender' : 'pl_id_receiver',
                'retning' => $type == 'mottakere' ? 'pl_id_sender' : 'pl_id_receiver',
                'pl_id' => $this->getId()
            ]
        );

        $res = $sql->run();
        while( $row = Query::fetch( $res ) ) {

            if( $row['pl_owner_fylke'] > 0 ) {
                $eier = fylker::getById( $row['pl_owner_fylke'] );
            }
            elseif( $row['pl_owner_kommune'] > 0 ) {
                $eier = new kommune( $row['pl_owner_kommune'] );
            }

            $class = $type == 'avsendere' ? 
                new Avsender( $row['pl_id_receiver'], $row['pl_id_sender'] ) :
                new Mottaker( $row['pl_id_receiver'], $row['pl_id_sender'] );
            ;

            // "Proxy" navn for hurtig-load
            $class->setNavn( $row['pl_name'] );
            $class->setRegistrert( $row['pl_registered'] == 'true');
            $class->setStart( new DateTime($row['pl_start']) );
            $class->setEier( $eier );
            
            $this->$type[ $class->getPlId() ] = $class;
        }
    }

    /**
     * Hent Arrangement-ID
     */ 
    public function getId()
    {
        return $this->id;
    }
}
