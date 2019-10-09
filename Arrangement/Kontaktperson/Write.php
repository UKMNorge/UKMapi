<?php

namespace UKMNorge\Arrangement\Kontaktperson;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Log\Logger;

class Write {
    
    /**
	 * create()
	 *
	 * Oppretter et nytt kontakt og lagrer i databasen.
	 *
	 * @param string $fornavn
	 * @param string $etternavn
	 * @param string $mobil
	 * @return Kontaktperson
	 */
	public static function create( $fornavn, $etternavn, $telefon ) {
		// Valider logger
		if( !Logger::ready() ) {
			throw new Exception(
				'Logger is missing or incorrect set up.',
				50701
			);
		}
        
        $sql = new Insert('smartukm_contacts');
        $sql->add('firstname', $fornavn);
        $sql->add('lastname', $etternavn);
        $sql->add('tlf', $telefon);
        $kontakt_id = $sql->run(); 
        
        // Database-oppdatering feilet
        if( !$kontakt_id ) {
            throw new Exception(
                "Klarte ikke å opprette kontaktperson ".$fornavn." ". $etternavn.".",
                511001
            );
        }

        return new Kontaktperson( $kontakt_id );
    }
 
    public static function save( $kontakt_save ) {
		// DB-OBJEKT
		$kontakt_db = new Kontaktperson( $kontakt_save->getId() );
		
		// TABELLER SOM KAN OPPDATERES
		$smartukm_contacts = new Insert(
			'smartukm_contacts', 
			[
				'id' => $kontakt_save->getId()
			]
		);
		
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Fornavn' 		=> ['firstname', 1103],
			'Etternavn' 	=> ['lastname', 1104],
			'Telefon'	    => ['tlf', 1105],
			'Tittel'	    => ['title', 1108],
			'Epost'		    => ['email', 1106],
            'Facebook'		=> ['facebook', 1107],
            'Bilde'         => ['picture', 1109]
		];
		
		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $logValues ) {
			$function = 'get'.$functionName;
			$field = $logValues[0];
			$action = $logValues[1];
			
			if( $kontakt_db->$function() != $kontakt_save->$function() ) {
				# Mellomlagre verdi som skal settes
                $value = $kontakt_save->$function();
				# Legg til i SQL
				$smartukm_contacts->add( $field, $value );
				# Logg (eller dø) før vi kjører run
				Logger::log( $action, $kontakt_save->getId(), $value );
			}
        }
		
		$res = true; // Fordi smartukm_place->run() vil overskrive hvis det oppstår feil
		if( $smartukm_contacts->hasChanges() ) {
            $smartukm_contacts->add( 'name', $kontakt_save->getNavn() ); // Concat navn for lagring (standard for denne..)
			$res = $smartukm_contacts->run();
		}
		if( !$res ) {
			throw new Exception('Kunne ikke lagre kontakt', 511002);
        }
    
        return $res;
    }

    public static function delete( $kontakt ) {
        if( !Kontaktperson::validateClass($kontakt) ) {
            throw new Exception('Kunne ikke slette kontakt, da gitt objekt ikke er kontaktperson-objekt', 511003);
        }
        if( !is_numeric( $kontakt->getId() ) ) {
            throw new Exception('Kunne ikke slette kontakt da gitt kontakt mangler numerisk ID', 511004);
        }

        $delete = new Delete(
            'smartukm_contacts',
            [
                'id' => $kontakt->getId()
            ]
        );
        $res = $delete->run();
		if( !$res ) {
			throw new Exception('Kunne ikke slette kontakt fra databasen', 511005);
        }
    }
}