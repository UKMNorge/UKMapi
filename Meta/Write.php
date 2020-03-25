<?php

namespace UKMNorge\Meta;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Write {

    /**
     * Sett eller oppdater verdi
     *
     * @param Value $value
     * @return bool true
     * @throws Exception $persist_error
     */
    public static function set( Value $value ) {
        
        // false er standard-svar hvis vi ikke finner verdien, 
        // derfor er det greit å rydde opp litt når verdien == false
        if( $value->getValue() == false && $value->eksisterer() ) {
            static::delete($value);
        }
        // og hvis vi ikke har lagret false, ikke gjør det heller
        elseif( $value->getValue() == false) {
            return true;
        }

        if( $value->eksisterer() ) {
            $persist = new Update(
                'ukm_meta',
                [
                    'id' => $value->getId()
                ]
            );
        } else {
            $persist = new Insert('ukm_meta');
        }
        $persist->add('parent_type', $value->getParent()->getType());
        $persist->add('parent_id', $value->getParent()->getId());
        $persist->add('name', $value->getKey());
        $persist->add('value', $value->getAsJson());

        $persist->allowHtmlFor('value');

        $result = $persist->run();
        #echo $persist->debug();

        // Lagring ok
        if( $result ) {
            if( !$value->eksisterer() ) {
                $value->setId( $result );
            }
            return true;
        }
        // Oppdatering av verdi uten endringer er fortsatt suksess
        if( $value->eksisterer() && $result == 0 ) {
            return true;
        }
        throw new Exception(
            'Klarte ikke å lagre MetaVerdi '. $value->getKey(),
            523001
        );
    }

    /**
     * Slett gitt meta-value
     *
     * @param Value $value
     * @return Bool true
     * @throws Exception $delete_error
     */
    public static function delete( Value $value ) {
        $value->set(false);
        $where = 
            [
                'parent_type' => $value->getParent()->getType(),
                'parent_id' => $value->getParent()->getId(),
                'name' => $value->getKey()
            ];
        
        $delete = new Delete(
            'ukm_meta',
            $where
        );
        $result = $delete->run();

        // Hvis slettet, all ok
        if( $result ) {
            return true;
        }
        
        // Hvis ikke slettet, og ikke i databasen er det en slags suksess
        $test = new Query(
            "SELECT `value` 
            FROM `ukm_meta`
            WHERE `parent_type` = '#parent_type'
            AND `parent_id` = '#parent_id'
            AND `name` = '#name'
            LIMIT 1",
            $where
        );
        $res = $test->run();
        if( Query::numRows($res) == 0 ) {
            return true;
        }
        
        throw new Exception(
            'Klarte ikke å slette MetaVerdi '. $value->getKey(),
            523002
        );
    }

    /**
     * @alias of set
     */
    public static function update( Value $value ) {
        static::set($value);
    }
}