<?php

namespace UKMNorge\Samtykke;
use Exception;

require_once('kategori.class.php');
require_once('Melding/meldinger.collection.php');

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
                self::$kategorier[ $data['id'] ] = new Kategori( $data['id'], $data['navn'], $data['krav'], $data['sms'] );
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
                'navn' => 'Under 13 år',
                'krav' => 'Bes om å oppgi forelder/foresatt, og det er ønskelig at foresatt har sett informasjonen.',
                'sms' => Meldinger\Meldinger::getById('deltaker_u15')
            ],
            [
                'id' => 'u15', // fom 13 tom 14
                'navn' => 'Under 15 år',
                'krav' => 'Bes om å oppgi forelder/foresatt, men bør kunne ta valget selv.',
                'sms' => Meldinger\Meldinger::getById('deltaker_u15')
            ],
            [
                'id' => '15o', // fom15
                'navn' => '15 år eller eldre',
                'krav' => 'Deltakeren kan selv forholde seg til personvern og datalagring og har fått informasjon om hvor dette er.',
                'sms' => Meldinger\Meldinger::getById('deltaker')
            ]
        ];
    }
}