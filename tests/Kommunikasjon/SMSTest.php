<?php

require_once("UKM/Autoloader.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\Kommunikasjon\SMS;

class SMSTest extends TestCase {

    public function testSendToSingleNumber() {
        $this->expectException(Exception::class);
        SMS::setSystemId('UKMid', 0);
        $sms = new SMS('UKMNorge');
        $sms->setMelding( 'Test' )->setMottaker( Mottaker::fraMobil(98004248));
        $this->assertInstanceOf(SMS::class, $sms);
        // Trying to send in dev generates exception, so no need to assert anything on this line.
        $sms->send();
    }

/*     public function testSendToMultipleNumbers() {
        
    }

    public function testSendToSwedishNumber() {

    }

    public function testGetCredits() {

    } */
}