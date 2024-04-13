<?php

namespace UKMNorge\Filmer\UKMTV\Direkte;

use Exception;
use UKMNorge\Arrangement\Program\Hendelse;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write
{
    /**
     * Opprett en ny sending
     *
     * @param Int $hendelse_id
     * @param Int $start_offset
     * @param Int $varighet
     * @return Sending
     */
    public static function opprett(Int $hendelse_id, Int $start_offset, Int $varighet)
    {
        $insert = new Insert('ukm_direkte');
        $insert->add('hendelse_id', $hendelse_id);
        $insert->add('start_offset', $start_offset);
        $insert->add('varighet', $varighet);

        $res = $insert->run();

        if (!$res) {
            throw new Exception(
                'Kunne ikke opprette direktesending',
                544001
            );
        }

        return Sendinger::getById($res);
    }

    /**
     * Lagre (eventuelle) endringer i sending
     *
     * @param Sending $sending
     * @return Bool
     * @throws Exception
     */
    public static function lagre(Sending $sending)
    {
        var_dump('lagre()');
        $db_sending = Sendinger::getById($sending->getId());

        $update = new Update(
            'ukm_direkte',
            ['id' => $sending->getId()]
        );

        $map = [
            'start_offset' => 'getStartOffset',
            'varighet' => 'getVarighet'
        ];

        foreach ($map as $field => $function) {
            if ($db_sending->$function() != $sending->$function()) {
                $update->add($field, $sending->$function());
            }
        }

        if (!$update->hasChanges()) {
            return true;
        }

        $res = $update->run();

        if (!$res) {
            throw new Exception(
                'Kunne ikke lagre endringer i sending ' . $sending->getId(),
                544002
            );
        }

        return true;
    }

    /**
     * Slett gitt sending
     *
     * @param Sending $sending
     * @return Bool
     * @throws Exception
     */
    public static function slett(Hendelse $hendelse, Sending $sending)
    {
        $delete = new Delete('ukm_direkte', ['id' => $sending->getId()]);

        $res = $delete->run();

        if (!$res) {
            throw Exception(
                'Kunne ikke slette sending ' . $sending->getId(),
                544003
            );
        }

        $hendelse->fjernSending();

        return true;
    }
}
