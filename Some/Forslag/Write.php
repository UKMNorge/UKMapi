<?php

namespace UKMNorge\Some\Forslag;

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;

class Write {

    const MAP = [
        'publisering' => 'getPubliseringsdato',
        'beskrivelse' => 'getBeskrivelse',
        'eier_id' => 'getEier',
        'team_id' => 'getTeam'
    ];

    /**
     * Opprett en ny idé
     *
     * @param String $team_id
     * @param String $eier_id
     * @param String $tekst
     * @return Ide
     */
    public static function create( String $team_id, String $eier_id, String $tekst ) {
        $insert = new Insert(Ide::TABLE);
        $insert->add('team_id', $team_id);
        $insert->add('eier_id', $eier_id);
        $insert->add('beskrivelse', $tekst);

        $insert_id = $insert->run();

        return Ideer::getById($insert_id);
    }

    /**
     * Lagre endringer i gitt idé
     *
     * @param Ide $ide
     * @return Bool true
     */
    public static function save( Ide $ide ) {
        $db_ide = Ide::getById($ide->getId());

        $query = new Update(
            Ide::TABLE,
            ['id' => $ide->getId()]
        );

        foreach( static::MAP as $db_field => $function ) {
            if( $db_ide->$function() != $ide->$function() ) {
                $value = $ide->$function();
                if( is_object( $value )) {
                    $value = json_encode($value);
                }
                $query->add( $db_field, $value );
            }
        }


        foreach( $ide->getKanaler()->getAll() as $kanal ) {
            if( !$db_ide->getKanaler()->har( $kanal->getId() ) ) {
                static::leggtilKanal( $kanal, $this->getId() );
            }
        }

        foreach( $db_ide->getKanaler()->getAll() as $db_kanal ) {
            if( !$ide->getKanaler()->har( $db_kanal->getId() ) ) {
                static::fjernKanal($db_kanal, $this->getId());
            }
        }

        if( !$query->hasChanges() ) {
            return true;
        }

        $query->run();
        return true;
    }

        
    /**
     * Legg til relasjon mellom idé og kanal
     *
     * @param Kanal $kanal
     * @param Int $ide_id
     * @return Bool true
     * @throws Exception
     */
    public static function leggtilKanal( Kanal $kanal, Int $ide_id ) {
        $query = new Insert( Ide::TABLE_REL_KANAL );
        $query->add('kanal_id', $kanal->getId());
        $query->add('ide_id', $ide_id);
        
        try {
            $res = $query->run();
        } catch( Exception $e ) {
            throw $e; // handle e->getCode() == null_affected_rows_error
        }

        return true;
    }
    
    /**
     * Slett relasjon mellom idé og kanal
     *
     * @param Kanal $kanal
     * @param Int $ide_id
     * @return Bool true
     * @throws Exception
     */
    public static function fjernKanal( Kanal $kanal, Int $ide_id ) {
        $query = new Delete(
            Ide::TABLE_REL_KANAL,
            [
                'kanal_id' => $kanal->getId(),
                'ide_id' => $ide_id
            ]
        );

        try {
            $res = $query->run();
        } catch( Exception $e ) {
            throw $e; // handle e->getCode() == null_affected_rows_error
        }

        return true;
    }
}