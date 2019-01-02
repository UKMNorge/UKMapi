<?php

namespace UKMNorge\Samtykke\Meldinger;

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
        return 'Velkommen til UKM, %fornavn! '.
            'UKM er et offentlig arrangement, og det kan bli tatt bilder og film av deg. '.
            'Les mer om dette, hvordan vi lagrer påmeldingen din, '.
            'og gi oss beskjed hvis du ikke vil at UKM skal ta bilder av deg her: '. 
            SMS::LINK;
        /*
            return 'Hei %fornavn! Om det ikke er ønskelig at vi tar bilder og/eller film av deg på UKM, må vi ha beskjed. ' ."\r\n". 
                'Svar oss på lenken nedenfor. '.
                SMS::LINK;
        */
	}
}

// DELTAKERE UNDER 15
class DeltakerU15 extends Deltaker {
	public static function getMelding() {
        return 'Velkommen til UKM, %fornavn! '.
            'Vi trenger mobilnummeret til en av dine foreldre/foresatte fordi UKM er et offentlig arrangement, '.
            'og det kan bli tatt bilder og film av deg. '.
            'Gi oss dette, les om hvordan vi lagrer påmeldingen din, '.
            'og si fra hvis du ikke vil at UKM skal ta bilder av deg her: ' .
            SMS::LINK;
        /*
            return 'Hei %fornavn! Om det ikke er ønskelig at vi tar bilder og/eller film av deg på UKM, må vi ha beskjed. ' ."\r\n". 
                'Svar oss på lenken nedenfor. '.
                SMS::LINK;
        */
	}
}