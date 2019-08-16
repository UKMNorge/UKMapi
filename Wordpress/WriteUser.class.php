<?php

namespace UKMNorge\Wordpress;
use Exception;
use WP_Error;

class WriteUser
{

    /**
     * Lagre / opprett et brukerobjekt
     *
     * @param User $user
     * @return User $user
     */
    public static function save(User $user)
    {
        // Opprett bruker hvis det var en placeholder
        if( !$user->isReal() ) {
            // Sjekk at brukernavn ikke er tatt
            if( User::isAvailableUsername( $user->getUsername() ) ) {
                throw new Exception(
                    'Kan ikke opprette wordpress-bruker. Brukernavnet er allerede tatt.',
                    571002
                );
            }

            // Sjekk at e-post ikke er tatt
            if( User::isAvailableEmail( $user->getEmail() ) ) {
                throw new Exception(
                    'Kan ikke opprette wordpress-bruker. E-postadressen er allerede tatt.',
                    571003
                );
            }

            $password = wp_generate_password( 20, true );

            $wp_user = wp_create_user(
                $user->getUsername(),
                $password,
                $user->getEmail()
            );

            // Bruker ble ikke opprettet - dø.
            if( get_class( $wp_user ) == 'WP_Error' ) {
                throw new Exception(
                    'Wordpress feilet i å opprette bruker. '.
                    'Wordpress sa: '. $wp_user->get_error_message(),
                    571001
                );
            }
        }
        // Herfra er user for real (🎉)

        // Oppdater basisinfo
        wp_update_user(
            [
                'ID' => $user->getId(),
                'user_nicename' => $user->getName(),
                'display_name' => $user->getName(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName()
            ]
        );

        // Oppdater meta-info
        update_user_meta(
            $user->getId(),
            'user_phone',
            $user->getPhone()
        );

        return $user;
    }
}
