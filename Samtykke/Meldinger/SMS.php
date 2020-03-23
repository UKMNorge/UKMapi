<?php

namespace UKMNorge\Samtykke\Meldinger;

use Exception;
use \SMS as UKMlibSMS;
use UKMNorge\Database\SQL\Insert;

/**
 * Utsending av SMS ifbm samtykke
 */
class SMS {
	const LINK = 'https://personvern.ukm.no/pamelding/?id=%link_id';
	const LINK_FORESATT = 'https://personvern.ukm.no/pamelding/?id=%link_id&foresatt=true';
    
    /**
     * Send en melding til en person
     * 
     * @param $melding_id ref Samtykke\Melding\
     * @param Samtykke\Person $samtykke
     * 
     * @return Samtykke\Melding Meldingen som ble sendt
     */
	public static function send( $melding_id, $samtykke ) {
		$melding = self::getMelding( $melding_id, $samtykke );
		
		self::insertMelding( $samtykke, $melding, $melding_id );
		
		if( strpos($melding_id, 'foresatt') !== false ) {
			$mottaker = $samtykke->getForesatt()->getMobil();
			self::updateForesattSamtykke( $samtykke, $melding_id );
		} else {
			$mottaker = $samtykke->getMobil();
			self::updateSamtykke( $samtykke, $melding_id );
		}
		self::_doSend( $mottaker, $melding );
		return $melding;
	}
	
	private static function _doSend( $mottaker, $melding ) {
        require_once('UKM/sms.class.php');
        if( UKM_HOSTNAME == 'ukm.dev' ) {
            echo '<h3>SMS-debug</h3>'.
                '<b>TEXT: </b>'. $melding .' <br />'.
                '<b>TO: </b>'. $mottaker;
        } else {
            $sms = new UKMlibSMS('samtykke', 0);
            $sms->text($melding)->to($mottaker)->from('UKMNorge')->ok();
        }
	}
	
	public static function getMelding( $id, $samtykke ) {
		$data = self::getBasicData( $samtykke );
        $data['fornavn'] = $samtykke->getPerson()->getFornavn();
    
		switch( $id ) {
            case 'samtykke':
				$melding_id = $samtykke->getKategori()->getId() == '15o' ? 'deltaker' : 'deltaker_u15';
			    break;
			case 'samtykke_foresatt':
				$melding_id = $samtykke->getStatus()->getId() == 'godkjent' ? 'foresatt_deltakergodkjent' : 'foresatt';
                break;
            case 'purring_deltaker':
                $melding_id = 'purring_deltaker';
                break;
            case 'purring_foresatt':
                $melding_id = 'purring_foresatt';
                break;
            case 'ombestemt':
                $melding_id = 'ombestemt';
                break;
            default:
                throw new Exception('Systemet støtter ikke meldingen `'. $id .'`');
        }

        $melding = Meldinger::getById( $melding_id );
        
        return self::prepare( $data, $melding::getMelding(), $melding::getTemplateDefinition() );
	}
	
	public static function getBasicData( $samtykke ) {
		return [
			'link_id' => $samtykke->getMobil().'-'.$samtykke->getId(),
			'navn' => $samtykke->getNavn(),
			'mobil' => $samtykke->getMobil(),
			'alder' => $samtykke->getAlder(),
			'kategori' => $samtykke->getKategori()->getNavn(),
			'kategori_id' => $samtykke->getKategori()->getId(),
		];
	}
	
	public static function prepare( $values, $melding, $template_definition ) {
		$replace = [];
		foreach( array_keys( $template_definition ) as $replace_key ) {
			if( isset( $values[ $replace_key ] ) ) {
				$replace[ '%'.$replace_key ] = $values[ $replace_key ];
			}
		}
		return str_replace( array_keys($replace), $replace, $melding );
	}
	
	public static function insertMelding( $samtykke, $melding, $type ) {
		$sql = new Insert('samtykke_deltaker_kommunikasjon');
		$sql->add('samtykke_id', $samtykke->getId());
		$sql->add('mobil', $samtykke->getMobil());
		$sql->add('type', $type);
		$sql->add('melding', $melding);
		$res = $sql->run();
    }
    
    private static function getIp() {
        if( isset( $_SERVER['HTTP_CF_CONENCTING_IP'] ) ) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
	
	public static function updateSamtykke( $samtykke, $melding_id ) {
		$samtykke->setStatus( 'ikke_sett', self::getIp() );
		$samtykke->persist();
	}
	
	public static function updateForesattSamtykke( $samtykke, $melding_id ) {
		$samtykke->setForesattStatus( 'ikke_sett', self::getIp() );
		$samtykke->persist();
	}

}