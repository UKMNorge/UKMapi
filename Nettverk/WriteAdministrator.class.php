<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;
use UKMNorge\Nettverk\Administratorer;

use Exception;
use SQLdel;
use SQLins;

require_once('UKM/Nettverk/Administrator.collection.php');

class WriteAdministrator {

    /**
     * Legg til en administrator i et område
     *
     * @param User $user
     * @param Administratorer $administratorer
     * @return Bool
     */
    public function leggTilIOmrade( Administrator $admin, Administratorer $administratorer ) {
        $sql = new SQLins('ukm_nettverk_admins');
        $sql->add('wp_user_id', $admin->getId());
        $sql->add('geo_type', $administratorer->getGeoType());
        $sql->add('geo_id', $administratorer->getGeoId());

        $res = $sql->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å relatere '. $admin->getName() .' til '. $administratorer->getNavn(),
                561001
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
     */
    public function fjernFraOmrade( Administrator $admin, Administratorer $administratorer ) {
        $sql = new SQLdel(
            'ukm_nettverk_admins',
            [
                'wp_user_id' => $admin->getId(),
                'geo_type' => $administratorer->getGeoType(),
                'geo_id' => $administratorer->getGeoId()
            ]
        );
        $res = $sql->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å fjerne '. $admin->getName() .' fra '. $administratorer->getNavn(),
                561002
            );
        }

        $administratorer->fjern( $admin->getId() );

        return true;
    }
}