<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use Exception;
use UKMNorge\Database\SQL\Common;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
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

        $query = new Delete(
            Natt::TABLE,
            [
                'dato' => $natt->getDato(),
                'l_id' => $natt->getLederId()
            ]
        );
        $res = $query->run();

        return !!$res;
    }
}
