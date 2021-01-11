<?php

require_once("UKM/Autoloader.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\Kommunikasjon\SMS;
use UKMNorge\Kommunikasjon\Mottaker;

class SMSTest extends TestCase {

    public function testSendToSingleNumber() {
        SMS::setSystemId('UKMid', 0);
        $sms = new SMS('UKMNorge');
        $sms->setMelding( 'Test' )->setMottaker( Mottaker::fraMobil(98004248));
        $this->assertInstanceOf(SMS::class, $sms);
        // Trying to send in dev generates exception, so no need to assert anything on this line.
        try {
            $sms->send();
        } catch(Exception $e) {
            $this->assertEquals(148005, $e->getCode());
            $this->assertEquals('SMS "SENDT" I DEV-MILJØ TIL (98004248): Test', $e->getMessage());
        }
    }

    public function testGetAntallSMS() {
        $melding = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.";
        $dobbelMelding = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tristique, sapien sit amet vulputate ultricies, neque eros mattis leo, non varius magna lectus et arcu. Nunc rhoncus libero dictum donec.";
        SMS::setSystemId('UKMid', 0);
        $sms = new SMS('UKMNorge');

        $sms->setMelding($melding);
        $this->assertEquals(1, $sms->getAntallSMS());

        $sms->setMelding($dobbelMelding);
        $this->assertEquals(2, $sms->getAntallSMS());        
    }

    /**
     * Requires a row in the `sms_block`-table for 98004248. Could add this to the test, but can't be bothered yet. Will add automated test setup / teardown when we need it.
     */
    public function testBlokkertMobil() {
        SMS::setSystemId('UKMid', 0);
        $sms = new SMS('UKMNorge');
        try {
            $sms->setMelding( 'Test' )->setMottaker( Mottaker::fraMobil(98004240));
            throw new Exception("Blokkert mobilnummer laget ikke Exception");
        } catch (Exception $e) {
            $this->assertEquals("Mottakeren er blokkert fra å motta SMS.", $e->getMessage());
            $this->assertEquals(148009, $e->getCode());
        }
    }
}