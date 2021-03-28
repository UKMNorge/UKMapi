<?php

namespace UKMNorge\Innslag\Playback;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write {

    /**
     * Opprett en ny playbackfil
     *
     * @param Arrangement $arrangement
     * @param Int $innslagId
     * @param String $filnavn
     * @param String $nicename
     * @param String $beskrivelse
     * @throws Exception
     * @return Playback
     */
    public static function opprett( Arrangement $arrangement, Int $innslagId, String $filnavn, String $nicename, String $beskrivelse ) {        
        $sql = new Insert(Playback::TABLE);
		$sql->add('pl_id', $arrangement->getId());
		$sql->add('b_id', $innslagId);
		$sql->add('pb_file', $filnavn);
		$sql->add('pb_season', $arrangement->getSesong());
        $sql->add('pb_name', $nicename);
        $sql->add('pb_description', $beskrivelse);

        try {
            $res = $sql->run();
        } catch( Exception $e ) {
            if( $e->getCode() == 901001 ) {
                throw new Exception(
                    'Kunne ikke opprette mediefil. '
                );
            } else {
                throw $e;
            }
        }

        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette mediefil',
                533002
            );
        }
        return Samling::getById( $res );
    }

    /**
     * Lagre endringer i playbackfil
     * 
     * OBS: støtter kun noen få felt (atm i alle fall), 
     * da vi ikke har noen use-case for å endre innslag, fil, sesong osv
     *
     * @param Playback $playback
     * @throws Exception
     * @return Bool
     */
    public static function lagre( Playback $playback ) {
        $database_playback = Samling::getById($playback->getId());
        
        $sql = new Update(
            Playback::TABLE,
            [
                'pb_id' => $playback->getId()
            ]
        );
        
        $database = [
            'pb_name' => 'getNavn',
            'pb_description' => 'getBeskrivelse'
        ];

        foreach( $database as $felt => $funksjon ) {
            if( $playback->$funksjon() != $database_playback->$funksjon() ) {
                $sql->add( $felt, $playback->$funksjon());
            }
        }
        
        if( !$sql->hasChanges() ) {
            return true;
        }

        $res = $sql->run();
    
        if( !$res || $res == -1 ) {
            throw new Exception(
                'Kunne ikke lagre mediefil',
                533001
            );
        }
    }

    public static function slett( Arrangement $arrangement, Playback $playback ) {
        $sql = new Delete(
            Playback::TABLE,
            [
                'pb_id' => $playback->getId(),
                'pl_id' => $arrangement->getId(),
                'b_id' => $playback->getInnslagId()
            ]
        );
        
        if( $sql->run() ) {
            return true;
        }

        throw new Exception(
            'Kunne ikke slette mediefil',
            533003
        );
    }
}