<?php

namespace UKMNorge\Some\Tekst;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Some\Forslag\Ide;
use UKMNorge\Some\Kanaler\Kanal;

class Write
{
    const MAP = [
        'objekt_type' => 'getObjektType',
        'objekt_id' => 'getObjektId',
        'kanal_id' => 'getKanalId',
        'team_id' => 'getTeamId',
        'user_id' => 'getUserId',
        'tekst' => 'getTekst'
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
    public static function opprettForIde(Ide $ide, Kanal $kanal, String $team_id, String $user_id, String $tekst = null)
    {
        return static::opprett(
            'ide',
            $ide->getId(),
            $kanal->getId(),
            $team_id,
            $user_id,
            $tekst
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
    private static function opprett(String $objekt_type, Int $objekt_id, String $kanal_id, String $team_id, String $user_id, String $tekst = null)
    {
        $query = new Insert(Tekst::TABLE);
        $query->add('objekt_type', $objekt_type);
        $query->add('objekt_id', $objekt_id);
        $query->add('kanal_id', $kanal_id);
        $query->add('team_id', $team_id);
        $query->add('user_id', $user_id);
        $query->add('tekst', $tekst);

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
            if ($db_tekst->$function() != $tekst->$function()) {
                $value = $tekst->$function();
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
