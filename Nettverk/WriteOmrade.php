<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;

use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Arrangement\Load;



use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Twig\Twig;
use UKMNorge\Wordpress\Blog;

class WriteOmrade {

    public static function leggTilOmradeKontaktperson( Omrade $omrade, OmradeKontaktperson $omradeKontaktperson ) {
        if( $omradeKontaktperson->getMobil() == null && $omradeKontaktperson->getEpost() == null ) {
            throw new Exception(
                'Kontaktpersonen må ha mobilnummer eller epostadresse',
                562004
            );
        }

        // OmradeKontaktpersoner::OMRADE_RELATION_TABLE

        // Sjekk om kontaktpersonen allerede er opprettet fra tidligere
        $query = new Query(
            "SELECT id 
            FROM `". OmradeKontaktpersoner::TABLE ."`
            WHERE 
            `mobil` = '#mobil' or `epost` = '#epost'",
            [
                'mobil' => $omradeKontaktperson->getMobil(),
                'epost' => $omradeKontaktperson->getEpost()
            ]
        );
        
        $kontaktperson_id = null;
        $res = $query->run();
        if( Query::numRows( $res ) > 0 ) {
            // Kontaktpersonen finnes fra før
            $kontaktperson_id = Query::fetch($res)['id'];
        } else {
            // Kontaktpersonen finnes ikke fra før, må opprettes
            $sql = new Insert(OmradeKontaktpersoner::TABLE);
            $sql->add('fornavn', $omradeKontaktperson->getFornavn());
            $sql->add('etternavn', $omradeKontaktperson->getEtternavn());
            $sql->add('mobil', $omradeKontaktperson->getMobil());
            $sql->add('beskrivelse', $omradeKontaktperson->getBeskrivelse());
            $sql->add('epost', $omradeKontaktperson->getEpost());
            $kontaktperson_id = $sql->run();
        }

        $sqlRel = new Insert(OmradeKontaktpersoner::OMRADE_RELATION_TABLE);
        $sqlRel->add('kontaktperson_id', $kontaktperson_id);
        $sqlRel->add('omrade_id', $omrade->getForeignId());
        $sqlRel->add('omrade_type', $omrade->getType());

        try {
            $resRel = $sqlRel->run();
        } catch( Exception $e ) {
            if($e->getCode() != 901001) {
                throw 'Klarte ikke å lagre relasjonen. Feilmelding: ' . $e;
            }
        }
    }

    /**
     * Legg til en administrator i et område
     *
     * @param Omrade $omrade
     * @param Administrator $user
     * @return Bool
     */
    public static function leggTilAdmin( Omrade $omrade, Administrator $admin ) {
        $sql = new Insert('ukm_nettverk_admins');
        $sql->add('wp_user_id', $admin->getId());
        $sql->add('geo_type', $omrade->getType());
        $sql->add('geo_id', $omrade->getForeignId());

        $res = $sql->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å relatere '. $admin->getNavn() .' til '. $omrade->getNavn(),
                562001
            );
        }
        return true;
    }

    /**
     * Legg til en administrator i alle områdets arrangementer (blogger)
     *
     * @param Omrade $omrade
     * @param Administrator $admin
     * @param Int $sesong
     * @return Bool
     * @throws Exception inkludert liste med hvilke arrangementer som feilet
     */
    public static function leggTilAdminIAlleArrangementer( Omrade $omrade, Administrator $admin, Int $sesong ) {
        $error_names = [];
        foreach( $omrade->getArrangementer()->getAll() as $arrangement ) {

            // Kommune
            try {
                $kommune = $omrade->getKommune();
                static::leggTilAdminKommune($kommune, $admin);
            } catch(Exception $e) {
                // Område har ikke kommune, prøv å hente fylke
                if($e->getCode() == 162002) {
                    $fylke = $omrade->getFylke();
                    static::leggTilAdminFylke($omrade->getFylke(), $admin);
                } else {
                    throw $e;
                }
            }
            try {
                Blog::leggTilBruker(
                    Blog::getIdByPath( $arrangement->getPath() ),
                    $admin->getUser()->getId(),
                    'editor'
                );
            } catch( Exception $e ) {
                if($e->getCode() != 172007) {
                    $error_names[] = $arrangement->getNavn();
                }
            }
        }

        if( sizeof($error_names) > 0 ) {
            throw new Exception(
                'Kunne ikke legge til '. $admin->getNavn() .' som administrator for '. join(', ', $error_names),
                562003
            );
        }
        return true;
    }

    /**
     * Legg til admin i alle arrangmeneter i en kommune
     *
     * @param Kommune $kommune
     * @param Administrator $admin
     * @return Bool
     * @throws Exception inkludert liste med hvilke arrangementer som feilet
     */
    private static function leggTilAdminKommune(Kommune $kommune, Administrator $admin) {
        $error_names = [];
        // For alle arrangementer i kommune
        foreach(Load::forKommune($kommune)->getAll() as $arrangement) {
            // Legg til bruker i blog
            try {
                Blog::leggTilBruker(
                    Blog::getIdByPath( $arrangement->getPath() ),
                    $admin->getUser()->getId(),
                    'editor'
                );
            } catch( Exception $e ) {
                if($e->getCode() != 172007) {
                    $error_names[] = $arrangement->getNavn();
                }
            }
        }

        // Det var noe som gikk galt
        if( sizeof($error_names) > 0 ) {
            throw new Exception(
                'Kunne ikke legge til '. $admin->getNavn() .' som administrator for '. join(', ', $error_names),
                562003
            );
        }
        return true;
    }

    /**
     * Legg til admin i alle arrangmeneter for alle kommuner i et fylke
     *
     * @param Fylke $fylke
     * @param Administrator $admin
     * @return Bool
     * @throws Exception inkludert liste med hvilke arrangementer som feilet
     */
    private static function leggTilAdminFylke(Fylke $fylke, Administrator $admin) {
        $error_names = [];

        // For alle kommuner i fylke
        foreach ($fylke->getKommuner()->getAll() as $kommune) {
            $alle_arrangementer = Load::forKommune($kommune);
            // For alle arrangementer i kommune
            foreach($alle_arrangementer->getAll() as $arrangement) {
                try {
                    Blog::leggTilBruker(
                        Blog::getIdByPath( $arrangement->getPath() ),
                        $admin->getUser()->getId(),
                        'editor'
                    );
                } catch( Exception $e ) {
                    if($e->getCode() != 172007) {
                        $error_names[] = $arrangement->getNavn();
                    }
                }
            }
        }

        // Det var noe som gikk galt
        if( sizeof($error_names) > 0 ) {
            throw new Exception(
                'Kunne ikke legge til '. $admin->getNavn() .' som administrator for '. join(', ', $error_names),
                562003
            );
        }
        return true;
    }

    /**
     * Fjern administrator fra alle områdets arrangementer (blogger)
     *
     * @param Omrade $omrade
     * @param Administrator $admin
     * @param Int $sesong
     * @return Bool
     * @throws Exception inkludert liste med hvilke arrangementer som feilet
     */
    public static function fjernAdminFraAlleArrangementer( Omrade $omrade, Administrator $admin, Int $sesong ) {
        $error_names = [];
        foreach( $omrade->getArrangementer()->getAll() as $arrangement ) {
            try {
                Blog::fjernBruker(
                    Blog::getIdByPath( $arrangement->getPath() ),
                    $admin->getUser()->getId()
                );
            } catch( Exception $e ) {
                $error_names[] = $arrangement->getNavn();
            }
        }

        if( sizeof($error_names) > 0 ) {
            throw new Exception(
                'Kunne ikke fjerne '. $admin->getNavn() .' som administrator for '. join(', ', $error_names),
                562003
            );
        }
        return true;
    }

    /**
     * Fjern en administrator fra et område
     *
     * @param User $user
     * @param Administratorer $administratorer
     * @return Bool
     * 
     */
    public static function fjernAdmin( Omrade $omrade, Administrator $admin ) {
        $sql = new Delete(
            'ukm_nettverk_admins',
            [
                'wp_user_id' => $admin->getId(),
                'geo_type' => $omrade->getType(),
                'geo_id' => $omrade->getForeignId()
            ]
        );
        $res = $sql->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å fjerne '. $admin->getNavn() .' fra '. $omrade->getNavn(),
                562002
            );
        }

        $omrade->getAdministratorer()->fjern( $admin->getId() );

        return true;
    }

    public static function sendVelkommenTilNyttOmrade( String $navn, String $epostadresse, Omrade $omrade ) {
        Twig::standardInit();
        Twig::addPath( __DIR__ . '/twig/' );
        Twig::addPath( dirname(__DIR__) . '/Wordpress/twig/' );

        $epost = Epost::fraSupport();
        $epost->setEmne('Velkommen til '. $omrade->getNavn());
        $epost->setMelding(
            Twig::render(
                'epost_velkommen_til_omrade.html.twig',
                [
                    'navn' => $navn,
                    'brukernavn' => $epostadresse,
                    'omrade' => $omrade->getNavn()
                ]
            )
        );
        $epost->leggTilMottaker(
            Mottaker::fraEpost(
                $epostadresse,
                $navn
            )
        );

        return $epost->send();
    }
}