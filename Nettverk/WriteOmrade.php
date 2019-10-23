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
        $epost->leggTilMottaker(
            Mottaker::fraEpost(
                'marius@ukm.no',
                'Marius Mandal'
            )
        );

        return $epost->send();
    }
}