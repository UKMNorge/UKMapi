<?php
	
	
/**
 * SAMTYKKE FOR DE UNDER 15
**/	
class samtykke_sms_samtykke_u15 extends samtykke_sms_samtykke {
	public static function getMelding() {
		return 'Hei %fornavn! Om det ikke er ønskelig at vi tar bilder og/eller film av deg på UKM-festivalen, må vi ha beskjed. ' ."\r\n". 
			'Svar oss på lenken nedenfor. '.
			samtykke_sms::LINK;
	}
}

/**
 * SAMTYKKE FOR DE FRA 15 OG OPP
**/
class samtykke_sms_samtykke  {
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
		return 'Hei %fornavn! Om det ikke er ønskelig at vi tar bilder og/eller film av deg på UKM-festivalen, må vi ha beskjed. ' ."\r\n". 
			'Svar oss på lenken nedenfor. '.
			samtykke_sms::LINK;
	}
}

class samtykke_sms_foresatt_godkjent extends samtykke_sms_foresatt {
	public static function getMelding() {
		return 'Hei! Om det ikke er ønskelig at vi tar bilder og/eller film av %navn på UKM-festivalen, '.
			' må vi ha beskjed. %fornavn har selv sagt at det er greit. '.
			'Les mer og svar på lenken nedenfor. '.
			samtykke_sms::LINK_FORESATT;
	}

}

class samtykke_sms_foresatt {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for voksnes samtykkeskjema',
			'navn'		=> 'Deltakerens navn',
			'alder'		=> 'Deltakerens alder',
			'kategori'	=> 'Navn på kategorien personen inngår i (under 15 osv)',
			'fornavn'	=> 'Deltakerens fornavn',
		];
	}
	
	public static function getMelding() {
		return 'Hei! %fornavn ønsker ikke å bli avbildet eller filmet på UKM-festivalen. Vi kan ikke garantere dette. '.
			'Les mer og gi din respons på lenken nedenfor. '.
			samtykke_sms::LINK_FORESATT;
	}
}

class samtykke_sms_purring {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for samtykkeskjema',
			'navn'		=> 'Deltakerens navn',
			'fornavn'	=> 'Deltakerens fornavn',
		];
	}
	
	public static function getMelding() {
		return 'Hei %fornavn! Vi trenger et svar fra deg om bilder og film på UKM-festivalen. '. "\r\n ".
			'Gi oss beskjed på lenken nedenfor. '.
			samtykke_sms::LINK;
	}
}

class samtykke_sms_purring_foresatt {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for samtykkeskjema',
			'navn'		=> 'Deltakerens navn',
			'fornavn'	=> 'Deltakerens fornavn',
		];
	}
	
	public static function getMelding() {
		return 'Hei! Vi savner et svar fra deg om bilder og film '
			.' i forbindelse med %fornavn sin deltakelse på UKM-festivalen. '. "\r\n ".
			'Gi oss beskjed på lenken nedenfor. '.
			samtykke_sms::LINK_FORESATT;
	}
}

