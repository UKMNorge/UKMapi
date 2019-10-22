<?php

namespace UKMNorge\Wordpress;
use Exception;
use WP_Error;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Twig\Twig;

class WriteUser
{

    /**
     * Aktiver en bruker
     *
     * @param User $user
     * @return void
     */
    public static function aktiver( User $user ) {
        update_user_meta( $user->getId(), 'disabled', false);
        delete_user_meta( $user->getId(), 'disabled');
    }
    /**
     * Deaktiver en bruker
     *
     * @param User $user
     * @return void
     */
    public static function deaktiver( User $user ) {
        update_user_meta( $user->getId(), 'disabled', true);
    }

    public static function setPassord( User $user, $passord ) {
        wp_set_password( $passord, $user->getId() );
    }

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
            if( !User::isAvailableUsername( $user->getUsername() ) ) {
                throw new Exception(
                    'Kan ikke opprette wordpress-bruker. Brukernavnet er allerede tatt.',
                    571002
                );
            }

            // Sjekk at e-post ikke er tatt
            if( !User::isAvailableEmail( $user->getEmail() ) ) {
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
            if( !is_numeric( $wp_user ) ) {
                throw new Exception(
                    'Wordpress feilet i å opprette bruker. '.
                    'Wordpress sa: '. $wp_user->get_error_message(),
                    571001
                );
            }

            $user->setId( $wp_user );
            static::sendVelkommen( $user->getName(), $user->getEmail(), $password );
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

    /**
     * Send velkommen-epost til brukeren
     *
     * @param String $navn
     * @param String $epost
     * @param String $passord
     */
    public static function sendVelkommen( String $navn, String $epostadresse, String $passord ) {
        Twig::standardInit();
        Twig::addPath( __DIR__ . '/twig/' );

        $epost = Epost::fraSupport();
        $epost->setEmne('Velkommen til UKMs arrangørsystem!');
        $epost->setMelding(
            Twig::render(
                'epost_ny_bruker.html.twig',
                [
                    'brukernavn' => $epostadresse,
                    'passord' => $passord
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
