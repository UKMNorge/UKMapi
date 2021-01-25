<?php

namespace UKMNorge\Kommunikasjon;

/*
    EKSEMPEL:
    $engangskode = 'A8X';
    $mobilnummer = '+4799999999';
    $melding = 'Hei! Din engangskode er '. $engangskode;

    SMS::setSystemId('UKMid', 0);
    $sms = new SMS('UKMNorge');
    $result = $sms->setMelding( $melding )->setMottaker( Mottaker::fraMobil( $mobilnummer ) )->send();
*/


use Exception;
use UKMNorge\Http\Curl;
use UKMNorge\Kommunikasjon\SMS\Logg;

class SMS
{
    static $system_id;
    static $user_id;
    static $arrangement_id;

    private $avsender;
    private $melding;
    private $mottaker;

    const PRICE = 0.4; // Credit pris
    const AVSENDER_MAKSLENGDE = 11; // Begrensning fra leverandør (sveve.no)
    const KAN_SENDE_UTEN_BRUKERID = [
        'UKMid',                // Fra ID-portalen
        'UKMdelta',             // Fra påmeldingssystemet (Delta)
        'UKMsjekk',             // Sjekk om vi har all info (ved videresending)
        'UKM-brukervalidering', // Motsatt sms-validering av bruker (Delta i dag, ID i fremtiden)
        'samtykke',             // Fra samtykke-systemet
        'samtykke-barn',        // Fra samtykke-systemet
        'samtykke-takk',        // Fra samtykke-systemet
        'IllegalPrefix',        // Autosvar fra inngående sms-system
    ];

    /**
     * Angi hvilket system (og tilhørende bruker) som sender sms'en
     *
     * @param String $system_id
     * @param  $user_id
     * @return void
     */
    public static function setSystemId(String $system_id, $user_id)
    {
        static::$system_id = $system_id;
        static::$user_id = $user_id;
    }

    /**
     * Angi hvilket arrangement man sender på vegne av
     *
     * @param Int $arrangement_id
     * @return void
     */
    public static function setArrangementId(Int $arrangement_id)
    {
        static::$arrangement_id = $arrangement_id;
    }

    /**
     * Opprett en ny SMS
     *
     * @param String $sender (mobilnummer eller tekst)
     * @return SMS
     */
    public function __construct(String $sender)
    {
        static::validateSystemId();

        // Tar ut landskode, da vi per i dag ikke støtter dette.
        if (strpos($sender, '+47') === 0 or strpos($sender, '0047') === 0) {
            $sender = str_replace(['+47', '0047'], '', $sender);
        }
        $this->avsender = Mottaker::clean($sender, 'A-Za-z0-9-', static::AVSENDER_MAKSLENGDE);
    }

    /**
     * Angi meldingen som skal sendes
     * 
     * @param String $melding
     * @return self
     */
    public function setMelding(String $melding)
    {
        $this->melding = $melding;
        return $this;
    }

    /**
     * Hent meldingen som skal sendes
     * 
     * @return String
     */
    public function getMelding()
    {
        return $this->melding;
    }

    /**
     * Angi mottaker av meldingen
     *
     * @param Mottaker $mottaker
     * @throws Exception
     * @return self
     */
    public function setMottaker(Mottaker $mottaker)
    {
        if(Reservasjoner::erBlokkertMotSms($mottaker->getMobil())) {
            throw new Exception('Mottakeren er blokkert fra å motta SMS.', 148009);
        }
        $this->mottaker = $mottaker;
        return $this;
    }

    /**
     * Hent mottaker av meldingen
     *
     * @return Mottaker
     */
    public function getMottaker()
    {
        return $this->mottaker;
    }

    /**
     * Hent avsender-tekst (/mobilnummer)
     *
     * @return String
     */
    public function getAvsender()
    {
        return $this->avsender;
    }

    /**
     * Send meldingen
     *
     * @throws Exception
     * @return bool true
     */
    public function send()
    {
        $this->valider();
        try {
            $res = $this->sveveSend();
            Logg::sendt($this);
            return true;
        } catch (Exception $e) {
            Logg::ikkeSendt($this);
            throw $e;
        }
    }

    /**
     * Hent antall SMS som trengs for å sende meldingen
     *
     * @return Int
     */
    public function getAntallSMS()
    {
        if (strlen($this->melding) <= 160) {
            return 1;
        }
        return (int)1 * ceil(strlen($this->melding) / 154);
    }

    /**
     * Hent system ID
     *
     * @return String
     */
    public static function getSystemId()
    {
        return static::$system_id;
    }

    /**
     * Hent bruker ID
     *
     * @return String
     */
    public static function getUserId()
    {
        return static::$user_id;
    }

    /**
     * Hent arrangement ID
     *
     * @return Int
     */
    public static function getArrangementId()
    {
        return static::$arrangement_id;
    }


    /**
     * Er avsender identifisert tilstrekkelig?
     *
     * @return void
     */
    private static function validateSystemId()
    {
        // Mangler system-ID 
        if (is_null(static::$system_id)) {
            throw new Exception(
                'Kan ikke sende SMS før avsender-system er definert (SMS::setSystemId())',
                148001
            );
        }

        // Mangler brukerID, og har ikke lov til å sende uten å identifisere bruker
        if (!in_array(static::$system_id, SMS::KAN_SENDE_UTEN_BRUKERID) && is_null(static::$user_id)) {
            throw new Exception(
                'Systemet har ikke lov til å sende SMS uten å identifisere brukeren.',
                148002
            );
        }

        if (static::$system_id == 'wordpress' && (int) static::$arrangement_id == 0) {
            throw new Exception(
                'Kan ikke sende SMS uten arrangement-ID',
                148003
            );
        }
    }

    /**
     * Faktisk send SMS (via sveve-api)
     *
     * @throws Exception
     * @return bool true
     */
    private function sveveSend()
    {
        $url = 'https://www.sveve.no/SMS/SendSMS'
            .  '?user=' . UKM_SVEVE_ACCOUNT
            .  '&to=' . $this->getMottaker()->getMobil()
            .  '&from=' . $this->getAvsender()
            .  '&msg=' . urlencode($this->getMelding())
            .  '&user=' . UKM_SVEVE_ACCOUNT
            .  '&passwd=' . UKM_SVEVE_PASSWORD;

        // I dev-miljø kaster vi en Exception uten å sende sms, 
        // da prod-miljøet MÅ håndtere alle exceptions
        if (UKM_HOSTNAME == 'ukm.dev') {
            throw new Exception(
                'SMS "SENDT" I DEV-MILJØ TIL (' . $this->getMottaker()->getMobil() . '): ' .
                    $this->getMelding(),
                148005
            );
        }

        $curl = new Curl();
        $curl->request($url);
        return $this->sveveAnalyzeResponse($curl->getResult());
    }

    /**
     * Sjekk at alt gikk bra med sendingen
     *
     * @param String $response
     * @throws Exception
     * @return bool true
     */
    private function sveveAnalyzeResponse(String $response)
    {
        $response = simplexml_load_string($response);
        $response = $response->response;

        if (isset($response->errors)) {
            if (isset($response->errors->fatal)) {
                throw new Exception(
                    'SVEVE ERROR: ' . $response->errors->fatal,
                    148006
                );
            }
            if (isset($response->errors->error)) {
                throw new Exception(
                    'SVEVE ERROR: ' . $response->errors->error->message,
                    148007
                );
            }
            throw new Exception(
                'SVEVE ERROR: Ukjent feil oppsto',
                148008
            );
        }

        return true;
    }

    /**
     * Valider at meldingen har alt som trengs
     *
     * @throws Exception
     * @return void
     */
    private function valider()
    {
        if (empty($this->melding)) {
            throw new Exception(
                'Kan ikke sende tom SMS',
                148004
            );
        }
    }
}
