<?php

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

/**
 * MELDING TIL DELTAKERE
 */
 // DELTAKERE (FRA 15 OG OPP )
 class Deltaker  {
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
        return 'Hei %fornavn! Som du sikkert vet, har du blitt påmeldt UKM. '.
            'Fordi UKM er et offentlig arrangement kan det bli tatt bilder og film av deg. '.
            'Du kan reservere deg og lese om personvern og datalagring her: '.
            SMS::LINK;
    }
    
    public function __toString() {
        return static::getMelding();
    }
}