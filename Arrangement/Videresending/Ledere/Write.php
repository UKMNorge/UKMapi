<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use Exception;
use UKMNorge\Database\SQL\Common;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

class Write
{

    /**
     * Opprett en leder
     *
     * @param Leder $leder
     * @return Leder $leder
     */
    public static function create(Leder $leder)
    {
        if ($leder->eksisterer()) {
            throw new Exception(
                'Kan ikke opprette leder som allerede finnes. Bruk save()',
                5601001
            );
        }

        $query = static::_transferDataToQuery(
            new Insert(Leder::TABLE),
            $leder
        );
        $query->add('arrangement_fra', $leder->getArrangementFraId());
        $query->add('arrangement_til', $leder->getArrangementTilId());

        $id = $query->run();

        $leder->setId($id);
        
        return $leder;
    }

    /**
     * Sett opp databasespørring med oppdatert data
     *
     * @param Common $query
     * @param Leder $leder
     * @return Common $query
     */
    private static function _transferDataToQuery(Common $query, Leder $leder)
    {
        $query->add('l_navn', $leder->getNavn());
        $query->add('l_epost', $leder->getEpost());
        $query->add('l_mobilnummer', $leder->getMobil());
        $query->add('l_type', $leder->getType());
        return $query;
    }

    /**
     * Lagre endringer i gitt leder-objekt
     *
     * @param Leder $leder
     * @return Bool
     */
    public static function save(Leder $leder)
    {
        $query = static::_transferDataToQuery(
            new Update(
                Leder::TABLE,
                [
                    'l_id' => $leder->getId()
                ]
            ),
            $leder
        );

        $res = $query->run();

        return true;
    }

    /**
     * Slett en leder fra databasen
     *
     * @param Leder $leder
     * @return Bool
     */
    public static function delete( Leder $leder ) {
        $query = new Delete(
            Leder::TABLE,
            ['l_id' => $leder->getId()]
        );
        $res = $query->run();

        // Delete hovedleder
        try{
            Write::deleteHovedLeder($leder->getId());
        } catch(Exception $e) {
            // Gjør ingengting, ikke nødvendigvis feil hvis hovedleder finnes ikke eller det skjedde noe.
        }

        return !!$res;
    }

    /**
     * Lagre en natt
     *
     * @param Natt $natt
     * @return Natt $natt
     */
    public static function saveNatt( Natt $natt ) {
        if( $natt->eksisterer() ) {
            $query = new Update(
                Natt::TABLE,
                [
                    'dato' => $natt->getDato(),
                    'l_id' => $natt->getLederId()
                ]
            );
        } else {
            $query = new Insert(
                Natt::TABLE
            );
        }

        $query->add('l_id', $natt->getLederId());
        $query->add('dato', $natt->getDato());
        $query->add('sted', $natt->getSted());

        $res = $query->run();
                
        if( !$natt->eksisterer() ) {
            $natt->setNattId( intval($res));
        }

        return true;
    }

    /**
     * Slett overnattingsvalget for en natt (for en gitt leder)
     *
     * @param Natt $natt
     * @return Bool
     */
    public static function deleteNatt( Natt $natt ) {
        if( !$natt->eksisterer()) {
            return true;
        }

        $deleteNatt = new Query(
            "DELETE
                FROM `ukm_videresending_leder_natt`
                WHERE `dato` = '#dato'
                AND `l_id` = '#l_id'",
            array(
                'dato' => $natt->getDato(),
                'l_id' => $natt->getLederId(),
            )
        );

        $res = $deleteNatt->run();
        return !!$res;
    }

    /**
     * Lagre hvem som er hovedleder en gitt natt
     *
     * @param Hovedleder $hovedleder
     * @return Bool
     */
    public static function saveHovedLeder( Hovedleder $hovedleder ) {
        $unique_database_id = [
            'dato' => $hovedleder->getDato(),
            'arrangement_fra' => $hovedleder->getArrangementFraId(),
            'arrangement_til' => $hovedleder->getArrangementTilId()
        ];

        $test_finnes = new Query(
            "SELECT `id`
            FROM `". Hovedleder::TABLE ."`
            WHERE `dato` = '#dato'
            AND `arrangement_fra` = '#arrangement_fra'
            AND `arrangement_til` = '#arrangement_til'",
            $unique_database_id
        );
        $test = $test_finnes->run();

        if( Query::numRows( $test ) == 0 ) {
            $save = new Insert( Hovedleder::TABLE );
            $save->add('dato', $hovedleder->getDato());
            $save->add('arrangement_fra', $hovedleder->getArrangementFraId());
            $save->add('arrangement_til', $hovedleder->getArrangementTilId());
        } else {
            $save = new Update(
                Hovedleder::TABLE,
                $unique_database_id
            );
        }
        $save->add('l_id', $hovedleder->getLederId());

        $res = $save->run();

        return !!$res;
    }

    /**
     * Slett hovedleder
     *
     * @param int $hovedleder_l_id
     * @return Bool
     */
    public static function deleteHovedLeder($hovedleder_l_id) {
        $query = new Delete(
            Hovedleder::TABLE,
            ['l_id' => $hovedleder_l_id]
        );
        $res = $query->run();

        return !!$res;
    }
}
