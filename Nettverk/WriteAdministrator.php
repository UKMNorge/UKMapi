<?php

namespace UKMNorge\Nettverk;
use Exception;
use UKMNorge\Database\SQL\Update;

class WriteAdministrator {

    /**
     * Lagre hvorvidt administratoren er synlig som kontaktperson
     *
     * @param Administrator $admin
     * @param Omrade $omrade
     * @throws Exception
     * @return Bool
     */
    public static function saveKontaktpersonSynlighet( Administrator $admin, Omrade $omrade ) {
        $query = new Update(
            'ukm_nettverk_admins',
            [
                'wp_user_id' => $admin->getId(),
                'geo_type' => $omrade->getType(),
                'geo_id' => $omrade->getForeignId()
            ]
        );
        $query->add('is_contact', $admin->erKontaktperson($omrade));
        $res = $query->run();
        return true;
    }
}