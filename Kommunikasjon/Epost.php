<?php

namespace UKMNorge\Kommunikasjon;

require_once("lib/autoload.php");
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Misd\Linkify\Linkify;

require_once('UKM/Autoloader.php');

class Epost
{
    var $svar_til = null; // @var Mottaker
    var $fra = null; // @var Mottaker
    var $emne = null;
    var $melding = null;
    var $blindkopi = []; // @var Array<Mottaker>
    var $mottakere = []; // @var Array<Mottaker>

    public function __construct()
    { }

    public static function fraSupport()
    {
        $epost = new Epost();
        $epost->setFra(Mottaker::fraEpost(UKM_MAIL_FROM, UKM_MAIL_FROMNAME));
        $epost->setSvarTil(Mottaker::fraEpost(UKM_MAIL_REPLY, UKM_MAIL_FROMNAME));
        return $epost;
    }

    /**
     * Hvem er avsender?
     *
     * @param Mottaker $mottaker
     * @throws Exception Avsender uten navn
     * @return self
     */
    public function setFra(Mottaker $mottaker)
    {
        if (!$mottaker->harNavn()) {
            throw new Exception(
                'Vil ikke sende e-post fra avsender uten navn',
                402004
            );
        }
        $this->fra = $mottaker;
        return $this;
    }
    /**
     * Hvor skal svaret gå? (reply-to)
     *
     * @param Mottaker $mottaker
     * @throws Exception Avsender uten navn
     * @return self
     */
    public function setSvarTil(Mottaker $mottaker)
    {
        if (!$mottaker->harNavn()) {
            throw new Exception(
                'Vil ikke sende e-post fra avsender uten navn',
                402004
            );
        }
        $this->svar_til = $mottaker;
        return $this;
    }

    /**
     * Sett mottakere
     *
     * @param Array<Mottakere> $mottakere
     * @return self
     */
    public function setMottakere(array $mottakere)
    {
        $this->mottakere = $mottakere;
        return $this;
    }

    /**
     * Legg til en mottaker
     *
     * @param Mottaker $mottaker
     * @return self
     */
    public function leggTilMottaker(Mottaker $mottaker)
    {
        $this->mottakere[] = $mottaker;
        return $this;
    }

    /**
     * Legg til e-post på blindkopi
     *
     * @param Mottaker $mottaker
     * @return self
     */
    public function leggTilBlindkopi(Mottaker $mottaker)
    {
        $this->blindkopi[] = $mottaker;
        return $this;
    }

    /**
     * Sett melding
     * HTML eller plaintext
     *
     * @param String HTML eller tekst $text
     * @return self
     */
    public function setMelding($html)
    {
        // Forsikre at dette er utf8
        if (!preg_match('!!u', $html)) {
            $html = utf8_encode($html);
        }

        // Hvis det ikke finnes tags, er det plaintext. Fiks lenker.
        if (strlen($html) == strlen(strip_tags($html))) {
            $linkify = new Linkify();
            $html = nl2br($linkify->process($html));
        }

        $this->melding = $html;
        return $this;
    }

    /**
     * Sett emne
     *
     * @param String $subject
     * @return void
     */
    public function setEmne($emne)
    {
        // Forsikre at dette er utf8
        if (!preg_match('!!u', $emne)) {
            $emne = utf8_encode($emne);
        }

        $this->emne = $emne;
        return $this;
    }

    /**
     * Send e-posten
     *
     * @return Bool $success
     * @throws Exception
     */
    public function send()
    {
        if (null == $this->emne) {
            throw new Exception(
                'Kan ikke sende e-post: Mangler emne-felt',
                402001
            );
        }

        if (null == $this->melding) {
            throw new Exception(
                'Kan ikke sende e-post: Mangler innhold i e-posten',
                402002
            );
        }

        if (null == $this->mottakere || sizeof($this->mottakere) == 0) {
            throw new Exception(
                'Kan ikke sende e-post: Mangler mottakere',
                402003
            );
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        try {
            $mail->SMTPAuth   = true;
            //$mail->SMTPDebug  = 2;
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer_name' => false,
                )
            );
            $mail->Host       = UKM_MAIL_HOST;
            $mail->Username   = UKM_MAIL_USER;
            $mail->Password   = UKM_MAIL_PASS;
            $mail->setFrom($this->fra->getEpost(), $this->fra->getNavn());

            $supportSkalOgsaHa = false;
            foreach ($this->mottakere as $mottaker) {
                // Hvis support er mottaker, må svar-til (og avsender?) ikke være
                // support, da freshdesk nekter å motta den da...
                if ($mottaker->getEpost() == UKM_MAIL_REPLY && $this->svar_til->getEpost() == UKM_MAIL_REPLY) {
                    $supportSkalOgsaHa = true;
                }
                if( $mottaker->harNavn() ) {
                    $mail->addAddress($mottaker->getEpost(), $mottaker->getNavn());
                } else {
                    $mail->addAddress($mottaker->getEpost());
                }
            }

            foreach ($this->blindkopi as $mottaker) {
                $mail->addBCC($mottaker->getEpost(), $mottaker->getNavn());
            }

            if ($supportSkalOgsaHa) {
                $mail->addReplyTo($this->fra->getEpost(), $this->fra->getNavn());
            } else {
                $mail->addReplyTo($this->svar_til->getEpost(), $this->svar_til->getNavn());
            }

            $mail->Subject = $this->emne;

            $mail->MsgHTML($this->melding);

            if( UKM_HOSTNAME == 'ukm.dev' ) {
                echo '<code>
                    <h1>E-post kan ikke sendes!</h1>
                    <p>Det er ikke mulig å sende e-post fra dev-miljøet.</p>
                    <p><b>Mottakere:</b><br />';
                foreach( $this->mottakere as $mottaker ) {
                    if( $mottaker->harNavn() ) {
                        echo $mottaker->getNavn(). ' ';
                    }
                    echo '&lt;'. $mottaker->getEpost() .'&gt;<br />';
                }
                echo '</p>
                <p><b>Fra: </b>'. $this->fra->getNavn() .' &lt;'. $this->fra->getEpost() .'&gt;</p>
                <p><b>SvarTil: </b>'. $this->svar_til->getNavn() .' &lt;'. $this->svar_til->getEpost() .'&gt;</p>
                <p><b>Emne: </b> '. $this->emne .'</p>
                <p><b>Melding:</b><hr />'. $this->melding .'</p>';
                echo '</code>';
                return true;
            } else {
                $res = $mail->send();
            }
            return $res;
        } catch (PHPMailerException $e) {
            //Pretty error messages from PHPMailer
            throw new Exception(
                'Mailer: ' . $e->errorMessage(),
                402005
            );
        } catch (Exception $e) {
            //Boring error messages from anything else!
            throw new Exception(
                'Mailer: ' . $e->getMessage(),
                402006
            );  
        }
        throw new Exception(
            'Beklager, klarte ikke å sende e-posten. Server sa: ' . $e->getMessage(),
            402004
        );
    }
}
