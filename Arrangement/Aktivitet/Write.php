<?php

namespace UKMNorge\Arrangement\Aktivitet;

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Log\Logger;
use UKMNorge\Tools\Sanitizer;
use UKMNorge\Arrangement\Aktivitet\Aktivitet;

use DateTime;
use Exception;

class Write {
    
	public static function createAktivitet(string $navn, string $sted, string $beskrivelse, int $plId ) {
        $sql = new Insert(Aktivitet::TABLE);
        $sql->add('navn', Sanitizer::sanitizeNavn($navn));
        $sql->add('sted', $sted);
        $sql->add('beskrivelse', $beskrivelse);
        $sql->add('pl_id', $plId);
        
        try {
            $aktivitedId = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$aktivitedId ) {
            throw new Exception(
                "Klarte ikke å opprette aktiviteten",
                511001
            );
        }
        
        $aktivitet = null;
        try {
            $aktivitet = new Aktivitet($aktivitedId);
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return $aktivitet;
    }

    public static function updateAktivitet(
        int $aktivitetId,
        string $navn, 
        string $sted, 
        string $beskrivelse, 
    ) {

        $sql = new Update(
            Aktivitet::TABLE, 
            [
                'aktivitet_id' => $aktivitetId
            ]
        );
        $sql->add('navn', $navn);
        $sql->add('sted', $sted);
        $sql->add('beskrivelse', $beskrivelse);

        var_dump($sql->debug());

        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$res ) {
            throw new Exception(
                "Klarte ikke å oppdatere aktivitet",
                511001
            );
        }

        return new Aktivitet($aktivitetId);
    }

    public static function createAktivitetTidspunkt(
        string $sted, 
        DateTime $start, 
        DateTime $slutt, 
        int $varighetMinutter, 
        int $maksAntall, 
        int $aktivitetId, 
        int|null $hendelseId, 
        bool $harPaamelding,
        bool $erSammeStedSomAktivitet
    ) {

        $sql = new Insert(AktivitetTidspunkt::TABLE);
        $sql->add('sted', $sted);
        $sql->add('start', $start->format('Y-m-d H:i:s'));
        $sql->add('slutt', $slutt->format('Y-m-d H:i:s'));
        $sql->add('varighet_min', $varighetMinutter);
        $sql->add('maksAntall', $maksAntall);
        $sql->add('aktivitet_id', $aktivitetId);
        $sql->add('c_id', $hendelseId);
        $sql->add('harPaamelding', $harPaamelding ? 1 : 0);
        $sql->add('erSammeStedSomAktivitet', $erSammeStedSomAktivitet ? 1 : 0);

        try {
            $aktivitetTidspunktId = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$aktivitetTidspunktId ) {
            throw new Exception(
                "Klarte ikke å opprette aktivitetstidspunkt",
                511001
            );
        }

        return new AktivitetTidspunkt($aktivitetTidspunktId);
    }

    public static function updateAktivitetTidspunkt(
        int $tidspunktId,
        string $sted, 
        DateTime $start, 
        DateTime $slutt, 
        int $varighetMinutter, 
        int $maksAntall, 
        int|null $hendelseId, 
        bool $harPaamelding,
        bool $erSammeStedSomAktivitet
    ) {

        $sql = new Update(
            AktivitetTidspunkt::TABLE, 
            [
                'tidspunkt_id' => $tidspunktId
            ]
        );
        $sql->add('sted', $sted);
        $sql->add('start', $start->format('Y-m-d H:i:s'));
        $sql->add('slutt', $slutt->format('Y-m-d H:i:s'));
        $sql->add('varighet_min', $varighetMinutter);
        $sql->add('maksAntall', $maksAntall);
        $sql->add('c_id', $hendelseId);
        $sql->add('harPaamelding', $harPaamelding ? 1 : 0);
        $sql->add('erSammeStedSomAktivitet', $erSammeStedSomAktivitet ? 1 : 0);
        $sql->add('tidspunkt_id', $tidspunktId);

        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$res ) {
            throw new Exception(
                "Klarte ikke å oppdatere aktivitetstidspunkt",
                511001
            );
        }

        return new AktivitetTidspunkt($tidspunktId);
    }

    public static function createAktivitetDeltaker(int $mobil) {
        $sql = new Insert(AktivitetDeltaker::TABLE);
        $sql->add('mobil', $mobil);

        try {
            $aktivitetDeltakerMobil = $sql->run(); 
        } catch( Exception $e ) {
            if($e->getCode() != 901001) {
                throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
            }
        }

        $aktivitetDeltaker = null;
        try {
            $aktivitetDeltaker = AktivitetDeltaker::getByPhone($mobil);
        } catch( Exception $e ) {
            throw new Exception('Klarte ikke å opprette deltaker');
        }

        return $aktivitetDeltaker;
    }

    public static function addDeltakerToTidspunkt(int $mobil, int $tidspunktId, string|null $sms_code) {

        $sql = new Insert('aktivitet_deltakelse');
        $sql->add('mobil', $mobil);
        $sql->add('tidspunkt_id', $tidspunktId);
        $sql->add('sms_code', $sms_code);

        if( $sms_code != null ) {
            $sql->add('sms_code_created', date('Y-m-d H:i:s'));
        } else {
            $sql->add('sms_code_created', null);
            // Brukeren blir aktiv hvis det ikke er verifisering gjennom sms kode
            $sql->add('aktiv', true);
        }

        try {
            $res = $sql->run();
        } catch( Exception $e ) {
            if($e->getCode() != 901001) {
                throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
            }
        }

        // check if the query was successful
        $selectSql = new Query(
            "SELECT * 
            FROM `aktivitet_deltakelse` 
            WHERE `mobil` = '#mobil' 
            AND `tidspunkt_id` = '#tidspunkt_id'",
            [
                'mobil' => $mobil,
                'tidspunkt_id' => $tidspunktId
            ]
        );

        $res = $selectSql->run();
        if( Query::numRows($res) == 0 ) {
            throw new Exception('Klarte ikke å legge til deltaker til tidspunkt');
        }


        return AktivitetDeltaker::getByPhone($mobil);
    }

 
//     public static function save( $kontakt_save ) {
// 		// DB-OBJEKT
// 		$kontakt_db = new Kontaktperson( $kontakt_save->getId() );
		
// 		// TABELLER SOM KAN OPPDATERES
// 		$smartukm_contacts = new Update(
// 			'smartukm_contacts', 
// 			[
// 				'id' => $kontakt_save->getId()
// 			]
// 		);
		
// 		// VERDIER SOM KAN OPPDATERES
// 		$properties = [
// 			'Fornavn' 		=> ['firstname', 1103],
// 			'Etternavn' 	=> ['lastname', 1104],
// 			'Telefon'	    => ['tlf', 1105],
// 			'Tittel'	    => ['title', 1108],
// 			'Epost'		    => ['email', 1106],
//             'Facebook'		=> ['facebook', 1107],
//             'Bilde'         => ['picture', 1109],
//             'AdminId'       => ['admin_id', 1110]
// 		];
		
// 		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
// 		foreach( $properties as $functionName => $logValues ) {
// 			$function = 'get'.$functionName;
// 			$field = $logValues[0];
// 			$action = $logValues[1];
			
// 			if( $kontakt_db->$function() != $kontakt_save->$function() ) {
// 				# Mellomlagre verdi som skal settes
//                 $value = $kontakt_save->$function();
// 				# Legg til i SQL
// 				$smartukm_contacts->add( $field, $value );
// 				# Logg (eller dø) før vi kjører run
// 				Logger::log( $action, $kontakt_save->getId(), $value );
// 			}
//         }
		
// 		$res = true; // Fordi smartukm_place->run() vil overskrive hvis det oppstår feil
// 		if( $smartukm_contacts->hasChanges() ) {
//             $smartukm_contacts->add( 'name', $kontakt_save->getNavn() ); // Concat navn for lagring (standard for denne..)
// 			$res = $smartukm_contacts->run();
// 		}
// 		if( !$res ) {
// 			throw new Exception('Kunne ikke lagre kontakt', 511002);
//         }
    
//         return $res;
//     }

//     public static function delete( $kontakt ) {
//         if( !Kontaktperson::validateClass($kontakt) ) {
//             throw new Exception('Kunne ikke slette kontakt, da gitt objekt ikke er kontaktperson-objekt', 511003);
//         }
//         if( !is_numeric( $kontakt->getId() ) ) {
//             throw new Exception('Kunne ikke slette kontakt da gitt kontakt mangler numerisk ID', 511004);
//         }

//         $delete = new Delete(
//             'smartukm_contacts',
//             [
//                 'id' => $kontakt->getId()
//             ]
//         );
//         $res = $delete->run();
// 		if( !$res ) {
// 			throw new Exception('Kunne ikke slette kontakt fra databasen', 511005);
//         }
//     }


}