<?php

/**
 * https://helsedirektoratet.no/folkehelse/kosthold-og-ernering/erneringsarbeid-i-helse-og-omsorgstjenesten/religiose-og-kulturelle-kostholdshensyn#syvendedags-adventister
 * https://www.mattilsynet.no/mat_og_vann/merking_av_mat/generelle_krav_til_merking_av_mat/matmerking_allergener__ikke_ferdigpakket_mat.16637/binary/Matmerking%20Allergener%20-%20ikke%20ferdigpakket%20mat
 */
require_once('UKM/allergen.class.php');

class Allergener {
	private static $allergener = null;
	private static $kulturelle = null;
	private static $all = null;

	public static function getKulturelle() {
		if( static::$kulturelle == null ) {
			static::_loadAll();
		}
		return static::$kulturelle;
	}

	public static function getStandard() {
		if( static::$allergener == null ) {
			static::_loadAll();
		}
		return static::$allergener;
	}

	public static function getAll() {
		if( static::$all == null ) {
			static::_loadAll();
		}
		return static::$all;
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
			
			if( $intoleranse->getKategori() == 'kulturell' ) {
				static::$kulturelle[ $intoleranse->getId() ] = $intoleranse;
			} else {
				static::$allergener[ $intoleranse->getId() ] = $intoleranse;
			}
			static::$all[ $intoleranse->getId() ] = $intoleranse;
		}
	}

	private static function _getDefinitions() {
		return [
			[
				'id' => 'vegan',
				'navn' => 'Vegan',
				'beskrivelse' => '',
				'kategori' => 'kulturell',
			],
			[
				'id' => 'vegetarianer',
				'navn' => 'Vegetar',
				'beskrivelse' => '',
				'kategori' => 'kulturell',
			],
			[
				'id' => 'halal',
				'navn' => 'Halal',
				'beskrivelse' => '',
				'kategori' => 'kulturell',
			],
			[
				'id' => 'kosher',
				'navn' => 'Kosher',
				'beskrivelse' => '',
				'kategori' => 'kulturell',
			],
			[
				'id' => 'storfe',
				'navn' => 'Storfe',
				'beskrivelse' => '',
				'kategori' => 'kulturell',
			],
			[
				'id' => 'gluten',
				'navn' => 'Gluten',
				'beskrivelse' => 'Hvete, rug, bygg, havre, spelt, korasanhvete o.l',
				'kategori' => 'standard',
			],
			[
				'id' => 'melk',
				'navn' => 'Melk',
				'beskrivelse' => 'Melk finner du i smør, ost, fløte, iskrem, desserter, melkepulver, yoghurt, bakverk, supper og sauser o.l.',
				'kategori' => 'standard',
			],
			[
				'id' => 'laktose',
				'navn' => 'Laktose',
				'beskrivelse' => 'Laktose, melkesukker, er et karbohydrat og en sukkerart som finnes i melk.',
				'kategori' => 'standard',
			],
			[
				'id' => 'skalldyr',
				'navn' => 'Skalldyr',
				'beskrivelse' => 'Dette inkluderer krabbe, hummer, reker, krill, kreps og scampi o.l.',
				'kategori' => 'standard',
			],
			[
				'id' => 'egg',
				'navn' => 'Egg',
				'beskrivelse' => 'Egg finner du ofte i kaker, majones, sufflé, pasta, paier, noen kjøttprodukter, sauser, desserter og matvarer som er penslet med egg.',
				'kategori' => 'standard',
			],
			[
				'id' => 'fisk',
				'navn' => 'Fisk',
				'beskrivelse' => 'Fisk finner du ofte i skalldyrog fiskeretter, leverpostei, salatdressinger, tapenade, buljong og i Worcestersaus.',
				'kategori' => 'standard',
			],
			[
				'id' => 'peanotter',
				'navn' => 'Peanøtter',
				'beskrivelse' => 'Peanøtter finner du ofte i kjeks, kaker, desserter, sjokolader, iskrem, peanøttolje, peanøttsmør, asiatiske og orientalske retter.',
				'kategori' => 'standard',
			],
			[
				'id' => 'notter',
				'navn' => 'Nøtter',
				'beskrivelse' => '',
				'kategori' => 'standard',
			],
			[
				'id' => 'soya',
				'navn' => 'Soya',
				'beskrivelse' => 'Soya finner du i tofu, miso, tempeh, soyasaus, soyadrikker og soyamel o.l.',
				'kategori' => 'standard',
			],
			[
				'id' => 'selleri',
				'navn' => 'Selleri',
				'beskrivelse' => 'Dette inkluderer stangselleri (stilkselleri), samt blader, frø og rot (knoll) av selleriplanten.',
				'kategori' => 'standard',
			],
			[
				'id' => 'sennep',
				'navn' => 'Sennep',
				'beskrivelse' => 'Dette inkluderer sennep, sennepspulver og sennepsfrø.',
				'kategori' => 'standard',
			],
			[
				'id' => 'sesamfro',
				'navn' => 'Sesamfrø',
				'beskrivelse' => 'Sesamfrø finner ofte du i brød, vegetarretter, godteri, knekkebrød, kjeks, hummus, sesamolje, sesammel og tahini (sesampasta).',
				'kategori' => 'standard',
			],
			[
				'id' => 'svoveldioksid_og_sulfitter',
				'navn' => 'Svoveldioksid og sulfitter',
				'beskrivelse' => 'Sulfitt brukes ofte til konservering av frukt og grønnsaker (inklusive tomat), og i noen kjøttprodukter, så vel som i brus, juice, vin og øl.',
				'kategori' => 'standard',
			],
			[
				'id' => 'lupin',
				'navn' => 'Lupin',
				'beskrivelse' => 'Dette inkluderer lupinfrø og lupinmel, og kan finnes i noen typer brød, bakervarer, mel, vegetarprodukter og pasta.',
				'kategori' => 'standard',
			],
			[
				'id' => 'blotdyr',
				'navn' => 'Bløtdyr',
				'beskrivelse' => 'Dette inkluderer muslinger, snegler, blekksprut, blåskjell, kamskjell, østers, hjerteskjell, kråkeboller, akkar, kalamari, sjøsnegler o.l.',
				'kategori' => 'standard',
			],
			[
				'id' => 'lok',
				'navn' => 'Løk',
				'beskrivelse' => '',
				'kategori' => 'standard',
			],
		];
	}
}