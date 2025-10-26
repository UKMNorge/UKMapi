<?php
    
namespace UKMNorge\Interesse;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Write {
		
	/**
     * Opprett eller oppdater Interesse
     *
     * Hvis Interesse har id -1, opprettes det, ellers hvis interesse id finnes da oppdateres det
     *
     * @param Interesse $interesse
     * @return int|false interesse_id eller false hvis det ikke gikk bra
     */
	public static function saveOrCreateInteresse(Interesse $interesse ) {
        // Sjekker om det er ny Interesse (-1) eller som finnes fra før (har en id fra før);
        if($interesse->getId() > -1) {
            // oppdater
            $interesse_id = static::updateInteresse($interesse);
        }
        else {
            // opprett
            $sql = new Insert('smartukm_interesse');
            $sql->add('navn', $interesse->getNavn());
            $sql->add('beskrivelse', $interesse->getBeskrivelse());
            $sql->add('epost', $interesse->getEpost());
            $sql->add('mobil', $interesse->getMobil());
            $sql->add('arrangor_interesse', $interesse->isArrangorInteresse() ? 1 : 0);

            
            $interesse_id = $sql->run();
        }
        return $interesse_id ?? false;
	}

    /**
     * Oppdater interesse
     * 
     * @param Interesse $interesse
     * @return Int|null interesse id
     **/
    private static function updateInteresse(Interesse $interesse) {
    $sql = new Update('smartukm_interesse', [
            'id' => $interesse->getId(), 
            'navn' => $interesse->getNavn(),
            'beskrivelse' => $interesse->getBeskrivelse(),
            'epost' => $interesse->getEpost(),
            'mobil' => $interesse->getMobil(),
            'arrangor_interesse' => $interesse->isArrangorInteresse() ? 1 : 0
        ]);
        $res = $sql->run();

        return $res ? $interesse->getId() : null;
    }

   
}