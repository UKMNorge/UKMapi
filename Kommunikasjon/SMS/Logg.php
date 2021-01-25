<?php

namespace UKMNorge\Kommunikasjon\SMS;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Kommunikasjon\SMS;

class Logg
{

    private $credits;
    private $sms;


    /**
     * Logg en transaksjon som sendt
     *
     * @param SMS $sms
     * @return void
     */
    public static function sendt(SMS $sms)
    {
        $sql = static::createTransaction($sms);
        $sql->add('tr_status', 'sent');
        $sql->run();
    }

    /**
     * Logg en transaksjon som ikke sendt
     *
     * @param SMS $sms
     * @return void
     */
    public static function ikkeSendt(SMS $sms)
    {
        $sql = static::createTransaction($sms);
        $sql->add('tr_status', 'error');
        $sql->run();
    }

    /**
     * Opprett en transaksjon
     *
     * @param SMS $sms
     * @return Insert
     */
    private static function createTransaction(SMS $sms)
    {
        $transaction = new Insert('log_sms_transactions');
        $transaction->add('pl_id',         SMS::getArrangementId());
        $transaction->add('t_system',     SMS::getSystemId());
        $transaction->add('wp_username', SMS::getUserId());
        $transaction->add('t_credits',  static::getCredits($sms));
        $transaction->add('t_comment',    $sms->getMelding());
        $transaction->add('t_action',    'sendte_sms_for');

        $transaction_id = $transaction->run();

        $recipient_add = new Insert('log_sms_transaction_recipients');
        $recipient_add->add('t_id',         $transaction_id);
        $recipient_add->add('tr_recipient', $sms->getMottaker()->getMobil());

        return $recipient_add;
    }

    /**
     * Beregn hvor mange credits meldingen krever
     *
     * @param SMS $sms
     * @return Int
     */
    private static function getCredits(SMS $sms)
    {
        return -1 * $sms->getAntallSMS();
    }
}
