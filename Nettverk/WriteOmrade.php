<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;

use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Arrangement\Load;

use UKMNorge\Nettverk\WriteOmradeKontaktperson;


use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Twig\Twig;
use UKMNorge\Wordpress\Blog;


class WriteOmrade {

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