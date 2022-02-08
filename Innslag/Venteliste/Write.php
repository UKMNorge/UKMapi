<?php

namespace UKMNorge\Innslag\Venteliste;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;


class Write {

    /**
     * Opprett en ny VentelistePerson
     *
     * @param Ventelisteperson $vePerson
     * @throws Exception
     * @return bool
     */
    public static function opprett( Ventelisteperson $vePerson, $kommune = null ) {       
        if(Venteliste::staarIVenteliste($vePerson->getPerson()->getId(), $vePerson->getArrangement()->getId()) != null) {
            return true;
        }

        $sql = new Insert(Venteliste::TABLE);

		$sql->add('pl_id', $vePerson->getArrangement()->getId());
		$sql->add('p_id', $vePerson->getPerson()->getId());
        $sql->add('k_id', $kommune == null ? null : $vePerson->getKommune()->getId());

        try {
            $res = $sql->run();
        } catch( Exception $e ) {
            if( $e->getCode() == 901001 ) {
                throw new Exception(
                    'Kunne ikke opprette Venteliste. '
                );
            } else {
                throw $e;
            }
        }

        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette Venteliste',
                533002
            );
        }
        return true;
    }

    public static function fjern(VentelistePerson $vePerson) {
        if(Venteliste::staarIVenteliste($vePerson->getPerson()->getId(), $vePerson->getArrangement()->getId()) == null) {
            return true;
        }

        $sql = new Delete(
            Venteliste::TABLE,
            [
                'pl_id' => $vePerson->getArrangement()->getId(),
                'p_id' => $vePerson->getPerson()->getId(),
            ]
        );
        
        if( $sql->run() ) {
            return true;
        }

        throw new Exception(
            'Kunne ikke slette VentelistePerson',
            533003
        );
    }
}