<?php

namespace UKMNorge\Some\Tekst;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Slack\Cache\User\User;
use UKMNorge\Some\Forslag\Ide;
use UKMNorge\Some\Kanaler\Kanal;

class Write
{
    const MAP = [
        'objekt_type' => 'getObjektType',
        'objekt_id' => 'getObjektId',
        'kanal_id' => 'getKanalId',
        'team_id' => 'getEier()->getTeamId',
        'user_id' => 'getEier()->getSlackId',
        'tekst' => 'getTekst',
        'notater' => 'getNotater',
        'status' => 'getStatus'
    ];

    /**
     * Opprett en tekst for en idé
     *
     * @param Ide $ide
     * @param Kanal $kanal
     * @param String $team_id
     * @param String $user_id
     * @param String $tekst
     * @return Tekst
     */
    public static function opprettForIde(Ide $ide, Kanal $kanal, User $eier, String $tekst = null, String $notater = null)
    {
        return static::opprett(
            'ide',
            $ide->getId(),
            $kanal->getId(),
            $eier,
            $tekst,
            $notater
        );
    }

    /**
     * Faktisk opprett teksten
     *
     * @param String $objekt_type
     * @param Int $objekt_id
     * @param String $kanal_id
     * @param String $team_id
     * @param String $user_id
     * @param String $tekst
     * @return Tekst
     * @throws Exception
     */
    private static function opprett(String $objekt_type, Int $objekt_id, String $kanal_id, User $eier, String $tekst = null, $notater = null)
    {
        $query = new Insert(Tekst::TABLE);
        $query->add('objekt_type', $objekt_type);
        $query->add('objekt_id', $objekt_id);
        $query->add('kanal_id', $kanal_id);
        $query->add('team_id', $eier->getTeamId());
        $query->add('user_id', $eier->getSlackId());
        $query->add('tekst', $tekst);
        $query->add('notater', $notater);

        $res = $query->run();
        if ($res) {
            return Tekster::getById($res);
        }
        throw new Exception(
            'Kunne ikke opprette tekst'
        );
    }

    /**
     * Lagre endringer i et tekst-objekt
     *
     * @param Tekst $tekst
     * @return Bool
     */
    public static function save(Tekst $tekst)
    {
        $db_tekst = Tekster::getById($tekst->getId());

        $query = new Update(
            Tekst::TABLE,
            [
                'id' => $tekst->getId()
            ]
        );

        foreach (static::MAP as $db_field => $function) {
            if( strpos($function, '()->') !== true ) {
                $functions = explode('()->', $function);
                $value = $tekst;
                $db_value = $db_tekst;
                foreach( $functions as $next_function ) {
                    if( is_null($value)) {
                        continue 2;
                    }
                    $value = $value->$next_function();
                    if( !is_null($db_value)) {
                        $db_value = $db_value->$next_function();
                    }
                }
            } else {
                $value = $tekst->$function();
                $db_value = $db_tekst->$function();
            }
            if ($db_value != $value) {
                if (is_array($value) || is_a($value, '\stdClass')) {
                    $value = json_encode($value);
                }
                $query->add($db_field, $value);
            }
        }


        if (!$query->hasChanges()) {
            return true;
        }

        $query->run();
        return true;
    }
}
