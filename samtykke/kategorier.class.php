<?php

namespace UKMNorge\Samtykke;
use Exception;

require_once('kategori.class.php');

/**
 * Alle samtykke-kategorier som finnes
 */
class Kategorier {
    private static $initiated = false;
    private static $kategorier;

    /**
     * Hent alle kategorier som finnes
     */
    public static function getAll() {
        self::_init();
        return self::$kategorier;
    }

    /**
     * Hent kategori for en gitt person
     * 
     * @param Samtykke\Person $person
     * @return Samtykke\Kategori
     */
    public static function getFromPerson( $person ) {
        self::_init();
        

        // Under 13 år (tom 12)
		if( $person->getAlderTall() < 13 ) {
            $id = 'u13';
        }
		// Under 15 år (fom 13 tom 14)
		elseif( $person->getAlderTall() < 15 ) {
            $id = 'u15';
		}
		// 15 og opp (fom 15)
		else {
            $id = '15o';
        }
        return self::getById( $id );
    }

    /**
     * Hent kategori fra ID
     * 
     * @param string $id
     * @return Samtykke\Kategori for gitt person
     */
    public static function getById( $id ) {
        self::_init();
        if( !isset( self::$kategorier[ $id ] ) ) {
            throw new Exception('Støtter ikke kategori med ID '. $id );
        }
        return self::$kategorier[ $id ];
    }

    /**
     * Opprett alle kategorier, og lagre de på objektet
     * 
     * @return void 
     */
    private static function _init() {
        if( !self::$initiated ) {
            foreach( self::_getKategoriDefinisjoner() as $data ) {
                self::$kategorier[ $data['id'] ] = new Kategori( $data['id'], $data['navn'], $data['krav'] );
            }
            self::$initiated = true;
        }
    }

    /**
     * Hent definisjoner for alle kategorier som finnes
     * 
     * @return array definisjoner
     */
    private static function _getKategoriDefinisjoner() {
        return [
            [
                'id' => 'u13', // tom 12
                'navn' => 'Under 13',
                'krav' => 'Foresatte må ha bekreftet informasjonen'
            ],
            [
                'id' => 'u15', // fom 13 tom 14
                'navn' => 'Under 15',
                'krav' => 'Foresatte må ha sett informasjonen'
            ],
            [
                'id' => '15o', // fom15
                'navn' => '15år eller eldre',
                'krav' => 'Deltakeren bør ha sett informasjonen'
            ]
        ];
    }
}