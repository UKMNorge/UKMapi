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
    
	public static function createAktivitet(string $navn, string $sted, string $beskrivelse, string $beskrivelseLeder, int $plId ) : Aktivitet {
        $sql = new Insert(Aktivitet::TABLE);
        $sql->add('navn', Sanitizer::sanitizeNavn($navn));
        $sql->add('sted', $sted);
        $sql->add('beskrivelse', $beskrivelse);
        $sql->add('beskrivelseLeder', $beskrivelseLeder);
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
        string $beskrivelseLeder 
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
        $sql->add('beskrivelseLeder', $beskrivelseLeder);

        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return new Aktivitet($aktivitetId);
    }

    public static function deleteAktivitet(Aktivitet $aktivitet) : bool {
        // fjern tagger før sletting
        static::removeAllTagsFromAktivitet($aktivitet);

        $delete = new Delete(
            Aktivitet::TABLE,
            [
                'aktivitet_id' => $aktivitet->getId()
            ]
        );

        $res = $delete->run();

        if( !$res || $res < 1 ) {
            throw new Exception('Kunne ikke slette aktiviteten fra databasen', 511005);
        }

        return true;
    }

    public static function createAktivitetTidspunkt(
        string $sted, 
        DateTime $start, 
        DateTime $slutt, 
        int|null $varighetMinutter, 
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
        $sql->add('varighet_min', ($varighetMinutter ? $varighetMinutter : 0));
        $sql->add('maksAntall', ($maksAntall > 0 ? $maksAntall : 9999));
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
        int|null $varighetMinutter, 
        int $maksAntall, 
        int|null $hendelseId, 
        bool $harPaamelding,
        bool $erSammeStedSomAktivitet,
        bool $kunInterne
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
        $sql->add('varighet_min', ($varighetMinutter ? $varighetMinutter : 0));
        $sql->add('maksAntall', ($maksAntall > 0 ? $maksAntall : 9999));
        $sql->add('c_id', $hendelseId);
        $sql->add('harPaamelding', $harPaamelding ? 1 : 0);
        $sql->add('erSammeStedSomAktivitet', $erSammeStedSomAktivitet ? 1 : 0);
        $sql->add('kunInterne', $kunInterne ? 1 : 0);

        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return new AktivitetTidspunkt($tidspunktId);
    }

    public static function deleteAktivitetTidspunkt(AktivitetTidspunkt $tidspunkt) : bool {
        if(count($tidspunkt->getDeltakere()->getAll()) > 1) {
            throw new Exception("Tidspunktet ". $tidspunkt  ." har deltakere og kan derfor ikke slettes!");
        }

        static::removeAllKlokkeslettFromTidspunkt($tidspunkt);

        
        $delete = new Delete(
            AktivitetTidspunkt::TABLE,
            [
                'tidspunkt_id' => $tidspunkt->getId()
            ]
        );
        
        $res = $delete->run();

        if( !$res || $res < 1 ) {
            throw new Exception('Kunne ikke slette tidspunkt fra databasen', 511005);
        }

        return true;
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

    // TAGS
    public static function createTag(
            string $navn, 
            string $beskrivelse, 
            int $plId 
        ) : AktivitetTag {
        $sql = new Insert(AktivitetTag::TABLE);
        $sql->add('navn', Sanitizer::sanitizeNavn($navn));
        $sql->add('beskrivelse', $beskrivelse);
        $sql->add('pl_id', $plId);
        
        try {
            $tagId = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$tagId ) {
            throw new Exception(
                "Klarte ikke å opprette taggen",
                511001
            );
        }
        
        $tag = null;
        try {
            $tag = AktivitetTag::getById($tagId);
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return $tag;
    }

    public static function updateAktivitetTag(
        int $tagId,
        string $navn, 
        string $beskrivelse, 
        int $plId
    ) {

        $sql = new Update(
            AktivitetTag::TABLE, 
            [
                'tag_id' => $tagId
            ]
        );
        $sql->add('navn', $navn);
        $sql->add('beskrivelse', $beskrivelse);
        $sql->add('pl_id', $plId);
        
        
        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return AktivitetTag::getById($tagId);
    }

    public static function deleteTag(AktivitetTag $tag) : bool {
        $delete = new Delete(
            AktivitetTag::TABLE,
            [
                'tag_id' => $tag->getId()
            ]
        );

        $res = $delete->run();

        if( !$res || $res < 1 ) {
            throw new Exception('Kunne ikke slette taggen fra databasen', 511005);
        }

        return true;
    }

    public static function addTagsToAktivitet(Aktivitet $aktivitet, array $tags) : bool {
        // Fjern alle tags først
        static::removeAllTagsFromAktivitet($aktivitet);

        // Add all tags
        foreach($tags as $tag) {
            $sql = new Insert('aktivitet_tag_relation');
            $sql->add('aktivitet_id', $aktivitet->getId());
            $sql->add('tag_id', $tag->getId());

            try {
                $res = $sql->run(); 
            } catch( Exception $e ) {
                if($e->getCode() != 901001) {
                    throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
                }
            }
        }


        return true;
    }

    private static function removeAllTagsFromAktivitet(Aktivitet $aktivitet) : bool {
        $delete = new Delete(
            'aktivitet_tag_relation',
            [
                'aktivitet_id' => $aktivitet->getId(),
            ]
        );

        $res = $delete->run();

        return true;
    }



    // KLOKKESLETT ---------------------------------------------------------------------------------------------------
    public static function createAktivitetKlokkeslett(
        string $navn,
        DateTime $start, 
        DateTime $stop, 
        int $plId 
    ) : AktivitetKlokkeslett {

        $sql = new Insert(AktivitetKlokkeslett::TABLE);
        $sql->add('navn', Sanitizer::sanitizeNavn($navn));
        $sql->add('start', $start->format('Y-m-d H:i:s'));
        $sql->add('stop', $stop->format('Y-m-d H:i:s'));
        $sql->add('pl_id', $plId);
        
        try {
            $kSlettId = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        // Database-oppdatering feilet
        if( !$kSlettId ) {
            throw new Exception(
                "Klarte ikke å opprette klokkeslett",
                511001
            );
        }
        
        $kSlett = null;
        try {
            $kSlett = AktivitetKlokkeslett::getById($kSlettId);
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return $kSlett;
    }

    public static function updateAktivitetKlokkeslett(
        int $id,
        string $navn,
        DateTime $start, 
        DateTime $stop, 
        int $plId 
    ) : AktivitetKlokkeslett{

        $sql = new Update(
            AktivitetKlokkeslett::TABLE, 
            [
                'id' => $id
            ]
        );
        $sql->add('navn', $navn);
        $sql->add('start', $start->format('Y-m-d H:i:s'));
        $sql->add('stop', $stop->format('Y-m-d H:i:s'));
        $sql->add('pl_id', $plId);
        
        
        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
        }

        return AktivitetKlokkeslett::getById($id);
    }

    public static function deleteAktivitetKlokkeslett(AktivitetKlokkeslett $kSlett) : bool {
        $delete = new Delete(
            AktivitetKlokkeslett::TABLE,
            [
                'id' => $kSlett->getId()
            ]
        );

        $res = $delete->run();

        if( !$res || $res < 1 ) {
            throw new Exception('Kunne ikke slette klokkeslett fra databasen', 511005);
        }

        return true;
    }

    private static function removeAllKlokkeslettFromTidspunkt(AktivitetTidspunkt $tidspunkt) : bool {
        $delete = new Delete(
            'aktivitet_tidspunkt_klokkeslett_relation',
            [
                'tidspunkt_id' => $tidspunkt->getId(),
            ]
        );

        $res = $delete->run();

        return true;
    }

    public static function addKlokkeslettToTidspunkt(AktivitetTidspunkt $tidspunkt, AktivitetKlokkeslett|null $kSlett) : bool {
        static::removeAllKlokkeslettFromTidspunkt($tidspunkt);

        // Ingen klokkeslett er valgt, derfor er det ikke noe å gjøre siden gammelt klokkeslett er fjernet
        if($kSlett == null) {
            return true;
        }

        $sql = new Insert('aktivitet_tidspunkt_klokkeslett_relation');
        $sql->add('tidspunkt_id', $tidspunkt->getId());
        $sql->add('klokkeslett_id', $kSlett->getId());

        try {
            $res = $sql->run(); 
        } catch( Exception $e ) {
            if($e->getCode() != 901001) {
                throw new Exception($e->getMessage() .' ('. $e->getCode() .')');
            }
        }

        return true;
    }







    /**
     *
     * @param Aktivitet $aktivitet
     * @param bool $deletedImage is true when the image is deleted (no aktivitet image)
     * @throws Exception
     * @return void
     */
    public static function uploadAktivitetImage($file, Aktivitet $aktivitet, bool $deletedImage) : string|null {    
        // Profilbildet er fjernet (ingen profilbilde)
        if($deletedImage && $file['size'] == 0) {
            $aktivitet->setImage(null);
            static::updateAktivitetImage($aktivitet);
            return null;
        }

        // Bilder er ikke lastet opp (ikke endringer)
        if($file['size'] == 0) {
            throw new Exception('Ingen bilder ble lastet opp. Sjekk filstørrelse', 400);
            return null;
        }

        $file_name = $file['name'];
        $file_temp = $file['tmp_name'];
        // Check if the file is an image
        $check = getimagesize($file_temp);
        if($check === false) {
            throw new Exception('Filen er ikke et bilde', 400);
        }

        $image_data = file_get_contents( $file_temp );
        $filename = basename( $file_name );
        $filetype = wp_check_filetype($file_name);
        $randomFilename = bin2hex(random_bytes(8)); // 16 characters of randomness
        $filename =  $randomFilename.time().'.'.$filetype['ext'];
        
        if(UKM_HOSTNAME == 'ukm.dev') {
            $upload_dir = [
                "path" => "/var/www/wordpress/wp-content/uploads/aktiviteter_bilder",
                "url" => "http://". UKM_HOSTNAME ."/wp-content/uploads/aktiviteter_bilder",
                "subdir" => "/aktiviteter_bilder",
                "basedir" => "/var/www/wordpress/wp-content/uploads",
                "baseurl" => "http://". UKM_HOSTNAME ."/wp-content/uploads"
            ];
        }
        else {
            $upload_dir = [
                "path" => "/home/ukmno/public_html/wp-content/uploads/aktiviteter_bilder",
                "url" => "http://". UKM_HOSTNAME ."/wp-content/uploads/aktiviteter_bilder",
                "subdir" => "/aktiviteter_bilder",
                "basedir" => "/home/ukmno/public_html/wp-content/uploads",
                "baseurl" => "http://". UKM_HOSTNAME ."/wp-content/uploads"
            ];
        }


        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        }
        else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents( $file, $image_data );
        $wp_filetype = wp_check_filetype( $filename, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $file );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        
        $url = $upload_dir['url'] . '/' . $filename;

        $aktivitet->setImage($url);
        $res = static::updateAktivitetImage($aktivitet);

        return $url;
    }

    private static function updateAktivitetImage(Aktivitet $aktivitet) : bool {
        // Update DB
        $updateImageSql = new Update(
            Aktivitet::TABLE, 
            [
                'aktivitet_id' => $aktivitet->getId()
            ]
        );
        $updateImageSql->add('image', $aktivitet->getImage());
        $res = $updateImageSql->run();

        return $res;        
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