<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;
use UKMNorge\Nettverk\Administratorer;

use Exception;
use SQLdel;
use SQLins;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Twig\Twig;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

class WriteOmrade {

    /**
     * Legg til en administrator i et område
     *
     * @param Omrade $omrade
     * @param Administrator $user
     * @return Bool
     */
    public function leggTilAdmin( Omrade $omrade, Administrator $admin ) {
        $sql = new SQLins('ukm_nettverk_admins');
        $sql->add('wp_user_id', $admin->getId());
        $sql->add('geo_type', $omrade->getType());
        $sql->add('geo_id', $omrade->getForeignId());

        $res = $sql->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å relatere '. $admin->getName() .' til '. $omrade->getNavn(),
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
        foreach( $omrade->getArrangementer($sesong)->getAll() as $arrangement ) {
            try {
                Blog::leggTilBruker(
                    Blog::getIdByPath( $arrangement->getPath() ),
                    $admin->getUser()->getId(),
                    'editor'
                );
            } catch( Exception $e ) {
                $error_names[] = $arrangement->getNavn();
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
        foreach( $omrade->getArrangementer($sesong)->getAll() as $arrangement ) {
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
    public function fjernAdmin( Omrade $omrade, Administrator $admin ) {
        $sql = new SQLdel(
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
                'Klarte ikke å fjerne '. $admin->getName() .' fra '. $omrade->getNavn(),
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