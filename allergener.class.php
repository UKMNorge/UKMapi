<?php

require_once('UKM/allergen.class.php');

class Allergener {
	private static $intoleranser = null;

	public static function getAll() {
		if( static::$intoleranser == null ) {
			static::_loadAll();
		}
		return static::$intoleranser;
	}

	public static function getById( $id ) {
		if( is_array( static::getAll() ) ) {
			return static::getAll()[ $id ];
		}
		throw new Exception(
			'Fant ikke intoleranse '. $id,
			119001
		);
	}

	private static function _loadAll() {
		foreach( static::_getDefinitions() as $intoleranse_data ) {
			$intoleranse = new Allergen( $intoleranse_data );
			static::$intoleranser[ $intoleranse->getId() ] = $intoleranse;
		}
	}

	private static function _getDefinitions() {
		return [
			[
				'id' => 'vegan',
				'navn' => 'Veganer',
				'beskrivelse' => ''
			],
			[
				'id' => 'vegetarianer',
				'navn' => 'Vegetarianer',
				'beskrivelse' => ''
			],
			[
				'id' => 'gluten',
				'navn' => 'Gluten',
				'beskrivelse' => 'Hvete, rug, bygg, havre, spelt, korasanhvete o.l'
			],
			[
				'id' => 'skalldyr',
				'navn' => 'Skalldyr',
				'beskrivelse' => 'Dette inkluderer krabbe, hummer, reker, krill, kreps og scampi o.l.'
			],
			[
				'id' => 'egg',
				'navn' => 'Egg',
				'beskrivelse' => 'Egg finner du ofte i kaker, majones, sufflé, pasta, paier, noen kjøttprodukter, sauser, desserter og matvarer som er penslet med egg.'
			],
			[
				'id' => 'fisk',
				'navn' => 'Fisk',
				'beskrivelse' => 'Fisk finner du ofte i skalldyrog fiskeretter, leverpostei, salatdressinger, tapenade, buljong og i Worcestersaus.'
			],
			[
				'id' => 'peanotter',
				'navn' => 'Peanøtter',
				'beskrivelse' => 'Peanøtter finner du ofte i kjeks, kaker, desserter, sjokolader, iskrem, peanøttolje, peanøttsmør, asiatiske og orientalske retter.'
			],
			[
				'id' => 'notter',
				'navn' => 'Nøtter',
				'beskrivelse' => ''
			],
			[
				'id' => 'soya',
				'navn' => 'Soya',
				'beskrivelse' => 'Soya finner du i tofu, miso, tempeh, soyasaus, soyadrikker og soyamel o.l.'
			],
			[
				'id' => 'melk',
				'navn' => 'Melk',
				'beskrivelse' => 'Melk finner du i smør, ost, fløte, iskrem, desserter, melkepulver, yoghurt, bakverk, supper og sauser o.l.'
			],
			[
				'id' => 'laktose',
				'navn' => 'Laktose',
				'beskrivelse' => 'Laktose, melkesukker, er et karbohydrat og en sukkerart som finnes i melk.'
			],
			[
				'id' => 'selleri',
				'navn' => 'Selleri',
				'beskrivelse' => 'Dette inkluderer stangselleri (stilkselleri), samt blader, frø og rot (knoll) av selleriplanten.'
			],
			[
				'id' => 'sennep',
				'navn' => 'Sennep',
				'beskrivelse' => 'Dette inkluderer sennep, sennepspulver og sennepsfrø.'
			],
			[
				'id' => 'sesamfro',
				'navn' => 'Sesamfrø',
				'beskrivelse' => 'Sesamfrø finner ofte du i brød, vegetarretter, godteri, knekkebrød, kjeks, hummus, sesamolje, sesammel og tahini (sesampasta).'
			],
			[
				'id' => 'svoveldioksid_og_sulfitter',
				'navn' => 'Svoveldioksid og sulfitter',
				'beskrivelse' => 'Sulfitt brukes ofte til konservering av frukt og grønnsaker (inklusive tomat), og i noen kjøttprodukter, så vel som i brus, juice, vin og øl.'
			],
			[
				'id' => 'lupin',
				'navn' => 'Lupin',
				'beskrivelse' => 'Dette inkluderer lupinfrø og lupinmel, og kan finnes i noen typer brød, bakervarer, mel, vegetarprodukter og pasta.'
			],
			[
				'id' => 'blotdyr',
				'navn' => 'Bløtdyr',
				'beskrivelse' => 'Dette inkluderer muslinger, snegler, blekksprut, blåskjell, kamskjell, østers, hjerteskjell, kråkeboller, akkar, kalamari, sjøsnegler o.l.'
			],
			[
				'id' => 'lok',
				'navn' => 'Løk',
				'beskrivelse' => ''
			],
		];
	}
}