<?php

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

/**
 * MELDING TIL DELTAKERE SOM HAR SAGT NEI, MEN OGSÅ SIER AT DE HAR OMBESTEMT SEG
 * Meldingen sendes kun ut fra arrangørsystemet (én gang).
 */
 class Ombestemt  {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for samtykkeskjema',
			'navn' 		=> 'Personens navn',
			'fornavn'	=> 'Personens fornavn',
			'mobil' 	=> 'Personens mobilnummer (8 sifre, integer)',
			'alder'	 	=> 'Personens alder',
			'kategori'	=> 'Navn på kategorien personen inngår i (under 15 osv)',
		];
	}
	
	public static function getMelding() {
        return 'Hei %fornavn! '.
            'Hvis du vil endre valget ditt om bilder og film av deg på UKM, kan du gjøre dette her: '.
            SMS::LINK;
    }
    
    public function __toString() {
        return static::getMelding();
    }
}