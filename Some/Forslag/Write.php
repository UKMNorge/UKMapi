<?php

namespace UKMNorge\Some\Forslag;

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

        if( !$query->hasChanges() ) {
            return true;
        }

        $query->run();
        return true;
    }
}