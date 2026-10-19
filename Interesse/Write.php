<?php
    
namespace UKMNorge\Interesse;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Log\Logger;
use UKMNorge\Wordpress\User;
use UKMNorge\Database\SQL\Query;

use UKMmail;

require_once('UKMconfig.inc.php');
require_once('UKM/mail.class.php');

class Write {
		
	/**
     * Opprett eller oppdater Interesse
     *
     * Hvis Interesse har id -1, opprettes det, ellers hvis interesse id finnes da oppdateres det
     *
     * @param Interesse $interesse
     * @return int|false interesse_id eller false hvis det ikke gikk bra
     */
	public static function saveOrCreateInteresse(Interesse $interesse ) {
        // Sjekker om det er ny Interesse (-1) eller som finnes fra før (har en id fra før);
        if($interesse->getId() > -1) {
            // oppdater
            $interesse_id = static::updateInteresse($interesse);
        }
        else {
            // opprett
            $sql = new Insert('smartukm_interesse');
            $sql->add('navn', $interesse->getNavn());
            $sql->add('beskrivelse', $interesse->getBeskrivelse());
            $sql->add('epost', $interesse->getEpost());
            $sql->add('mobil', $interesse->getMobil());
            $sql->add('arrangor_interesse', $interesse->isArrangorInteresse() ? 1 : 0);

            
            $interesse_id = $sql->run();
        }

        
        // Lagre kommuner
        static::saveKommunerForInteresse($interesse_id, $interesse->getKommuner());

        // Send epost til admin i kommune 
        foreach($interesse->getKommuner() as $kommune_id) {
            // Støtter ikke fylke enda (kommune har 4 siffer mens fylke har 2)
            if(strlen($kommune_id) != 4) {
                continue;
            }

            try {
                $admins = new Administratorer('kommune', $kommune_id);

		
                $mail = new UKMmail();

                $navn = $interesse->getNavn();
                $epost = $interesse->getEpost();
                $mobil = $interesse->getMobil();
                $beskrivelse = $interesse->getBeskrivelse();
                $arrangorInteresse = $interesse->isArrangorInteresse() ? 'Ja' : 'Nei';

                $cc = [];

                foreach($admins->getAll() as $admin) {
                    $user = static::getAdminInfoFromWP($admin->getId());
                    if($user) {
                        $cc[] = ['email' => $user['user_email'], 'name' => $user['display_name']];
                    }
                }

                $melding = "Det er meldt interesse fra {$navn} for å delta på UKM i din kommune.\n\n"
                    . "Navn: {$navn}\n"
                    . "Epost: {$epost}\n"
                    . "Mobil: {$mobil}\n"
                    . "Beskrivelse: {$beskrivelse}\n"
                    . "Arrangor interesse: {$arrangorInteresse}\n\n"
                    . "Når du planlegger neste UKM-festival, oppfordrer vi deg til å ta kontakt med {$navn} for å høre mer om interessen og mulighetene for å delta.\n\n"
                    . "\n\n"
                    . "Har du spørsmål, kan du kontakte {$navn} på e-post {$epost} eller telefon {$mobil}.\n\n";

                $to = $cc[0];
                // Remove the first email from the cc array because it will be sendt as TO and not CC
                $cc = array_slice($cc, 1);
                foreach($cc as $email) {
                    $mail->addCC($email['email'], $email['name']);
                }

                // Legg til kushtrim@ukm.no as BCC for testing purposes
                $mail->addBlindcopy('kushtrim@ukm.no', 'Kushtrim Aliu');
                    
                $mail->
                    to($to['email'])->
                    setFrom('support@ukm.no', 'UKM Norge')->
                    subject('Det er meldt interesse fra '. $interesse->getNavn() .' på din kommune på UKM')->
                    message(
                        $melding
                );
                
                $mail_result = $mail->ok();	

                $cc[] = $to;

                if($mail_result) {
                    static::saveSendtAdmin(
                        (int) $interesse_id,
                        (int) $kommune_id,
                        array_column($cc, 'email'),
                        $melding
                    );
                }

            } catch(Exception $e) {
                continue;
            }
        }

        return $interesse_id ?? false;
	}

    /**
     * Get admin information from WordPress database
     * 
     * @param int $adminId ID of the admin
     * @return array|null Returns an array with admin info or null if not found
     */
    private static function getAdminInfoFromWP($adminId) {
        $query = new Query(
            "SELECT
                u.display_name,
                u.user_email AS user_email,
                um.meta_value AS user_phone
            FROM wpms2012_users AS u
            LEFT JOIN wpms2012_usermeta AS um
            ON um.user_id = u.ID
            AND um.meta_key = 'user_phone'
            WHERE u.ID = '#user_id'",
            [
                'user_id' => $adminId
            ], 
            'wordpress'
        );
        
        
        $query->setDatabase('wordpress');
        return $query->run('array');
    }

    /**
     * Oppdater interesse
     * 
     * @param Interesse $interesse
     * @return Int|null interesse id
     **/
    private static function updateInteresse(Interesse $interesse) {
    $sql = new Update('smartukm_interesse', [
            'id' => $interesse->getId(), 
            'navn' => $interesse->getNavn(),
            'beskrivelse' => $interesse->getBeskrivelse(),
            'epost' => $interesse->getEpost(),
            'mobil' => $interesse->getMobil(),
            'arrangor_interesse' => $interesse->isArrangorInteresse() ? 1 : 0
        ]);
        $res = $sql->run();

        return $res ? $interesse->getId() : null;
    }

    /**
     * Lagre at eposten er sendt til administratorer for et område
     *
     * @param int $interesse_id
     * @param int $omraade_id
     * @param array $emails
     * @param string $tekst
     * @return void
     */
    private static function saveSendtAdmin(int $interesse_id, int $omraade_id, array $emails, string $tekst) : void {
        if($interesse_id < 1 || $omraade_id < 1) {
            return;
        }

        $emails = array_values(array_unique(array_filter($emails)));

        $sql = new Insert('interesse_sendt_admin');
        $sql->add('interesse_id', $interesse_id);
        $sql->add('omraade_id', $omraade_id);
        $sql->add('emails', implode(', ', $emails));
        $sql->add('tekst', $tekst);
        $sql->run();
    }

    private static function saveKommunerForInteresse(int $interesse_id, array $kommuner) : void {
        if(!$interesse_id || $interesse_id < 0 || empty($kommuner)) {
            return;
        }

        // Slett gamle kommuner
        $sqlDelete = new Delete('smartukm_interesse_kommune', [
            'interesse_id' => $interesse_id
        ]);
        $sqlDelete->run();

        // Legg til nye kommuner
        foreach($kommuner as $kommune_id) {
            // Kommune id må være integer (ikke float eller double)
            if (filter_var($kommune_id, FILTER_VALIDATE_INT) === false) {
                continue;
            }
            $sqlInsert = new Insert('smartukm_interesse_kommune');
            $sqlInsert->add('interesse_id', $interesse_id);
            $sqlInsert->add('kommune_id', $kommune_id);
            try{
                $sqlInsert->run();
            } catch(Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }
   
}