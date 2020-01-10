<?php

namespace UKMNorge\Wordpress;
use Exception;
use WP_Error;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Twig\Twig;
use UKMNorge\Database\SQL\Insert;

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

    /**
     * Oppdater en brukers passord
     *
     * @param User $user
     * @param String $passord
     * @return void
     */
    public static function setPassord( User $user, String $passord ) {
        wp_set_password( $passord, $user->getId() );
    }
    
    /**
     * Generer et nytt passord
     *
     * @return String $passord
     */
    public static function genererPassord() {
        return wp_generate_password( 18, true );
    }

    /**
     * Oppgraderer en bruker fra en vanlig deltakerbruker.
     * Finner roller selv. 
     * 
     * @param User
     * @param Int $blog_id
     * @return bool true hvis brukeren er oppgradert.
     * @throws Exception dersom brukeren ikke finnes eller ikke har en rolle som kan oppgraderes.
     */
    public static function oppgraderBruker( User $user, Int $blog_id ) {

    }

    /**
     * Nedgrader en bruker til vanlig deltakerbruker.
     * 
     * @param User
     * @param Int $blog_id
     * @return bool true hvis brukeren er nedgradert.
     * @throws Exception dersom brukeren ikke finnes eller ikke har en rolle på bloggen.
     */
    public static function nedgraderBruker( User $user, Int $blog_id ) {

    }

    /**
     * Lagre / opprett et brukerobjekt
     *
     * @param User $user
     * @param bool $sendVelkommen (optional) - Om vi skal sende velkommen-hilsen eller ikke. Defaulter til true, men settes false blant annet for deltakerbrukere som logger inn via UKMdelta.
     * @return User $user
     */
    public static function save(User $user, bool $sendVelkommen = true)
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
            if( $sendVelkommen ) {
                static::sendVelkommen( $user->getName(), $user->getEmail(), $password );
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
    /**
     * Opprett en moderne deltaker-bruker med innlogging fra Delta.
     * Oppretter et brukerobjekt, lagrer det til wordpress-databasen og legger til en rad for brukeren i ukm_delta_wp_user.
     *
     * @param String $username
     * @param String $email
     * @param String $first_name
     * @param String $last_name
     * @param Int $phone
     * @return User $user
     */
    public static function createParticipantUser(String $username, String $email, String $first_name, String $last_name, Int $phone, Int $participant_id) {
        $user = User::createEmpty();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $user->setPhone($phone);

        static::save($user, false);

        # TODO: Fjern WP-brukeren hardt og brutalt om Insert under feiler.
        $sql = new Insert('ukm_delta_wp_user');
        $sql->add('wp_id', $user->getId());
        $sql->add('participant_id', $participant_id);
        $sql->run();

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

        return $epost->send();
    }

    /**
     * Send velkommen-epost til brukeren
     *
     * @param String $navn
     * @param String $epost
     * @param String $passord
     */
    public static function sendNyttPassord( String $navn, String $epostadresse, String $passord ) {
        Twig::standardInit();
        Twig::addPath( __DIR__ . '/twig/' );

        $epost = Epost::fraSupport();
        $epost->setEmne('Nytt UKM-passord');
        $epost->setMelding(
            Twig::render(
                'epost_nytt_passord.html.twig',
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
        
        return $epost->send();
    }
}
