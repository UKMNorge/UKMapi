<?php
    
namespace UKMNorge\Interesse;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Delete;

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
        // Lagre kommuner
        static::saveKommunerForInteresse($interesse_id, $interesse->getKommuner());
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

    private static function saveKommunerForInteresse(int $interesse_id, array $kommuner) : void {
        if(!$interesse_id || $interesse_id < 0 || empty($kommuner)) {
            return;
        }

        // Slett gamle kommuner
        $sqlDelete = new Delete('smartukm_interesse_kommune', [
            'interesse_id' => $interesse_id
        ]);
        $sqlDelete->run();

        // Legg til nye kommuner
        foreach($kommuner as $kommune_id) {
            // Kommune id må være integer (ikke float eller double)
            if (filter_var($kommune_id, FILTER_VALIDATE_INT) === false) {
                continue;
            }
            $sqlInsert = new Insert('smartukm_interesse_kommune');
            $sqlInsert->add('interesse_id', $interesse_id);
            $sqlInsert->add('kommune_id', $kommune_id);
            try{
                $sqlInsert->run();
            } catch(Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }
   
}