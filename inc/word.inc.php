<?php
require_once('lib/autoload.php');
use PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\IOFactory;
global $PHPWord;

$PHPWord = new PHPWord();

function woText(&$section, $text, $style=false) {
	$text = htmlspecialchars( $text );
	if(!$style)
		$section->addText($text, 'f_p','p_p');
	else
		$section->addText($text, 'f_'.$style, 'p_'.$style);
}

function woCell(&$table, $width, $text, $style=false){
	$c = $table->addCell($width);
	woText($c, $text, $style);
}

function woListItem(&$section, $text, $depth=0, $fontStyle='f_p', $listStyle=PHPWord_Style_ListItem::TYPE_BULLET_FILLED, $pStyle='p_p') {
	$text = utf8_decode($text);
	// ListStyle - Constants from PHPWord_Style_ListItem
	/*
	TYPE_NUMBER	7	
	TYPE_NUMBER_NESTED	8	
	TYPE_ALPHANUM	9	
	TYPE_BULLET_FILLED	3	
	TYPE_BULLET_EMPTY	5	
	TYPE_SQUARE_FILLED	1
	*/
	
	#$list = new PHPWord_Section_ListItem($text, $depth, $fontStyle, $listStyle, $pStyle);
	#$section->addListItem($list);
	$section->addListItem($text, $depth, $fontStyle, $listStyle, $pStyle);
	var_dump($section);
}

function woWrite($filename){
	$filename = $filename .'.docx';	

	global $PHPWord;
	$objWriter = IOFactory::createWriter($PHPWord, 'Word2007');
	$objWriter->save( DOWNLOAD_PATH_WORD . $filename );
	return DOWNLOAD_URL_WORD . $filename;
}


class wordSettings {
	private $orientation;
	private $name;
	private $title;
	private $description;
	private $info;
	private $headers = true;

	public function __construct( $orientation = 'portrait' ) {
		$this->info = [];
		$this->setOrientation( $orientation );
	}

	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}

	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}
	public function getTitle() {
		return $this->title;
	}

	public function setDescription( $description ) {
		$this->description = $description;
		return $this;
	}
	public function getDescription() {
		return $this->description;
	}

	public function setOrientation( $orientation ) {
		if( !in_array( $orientation, ['portrait','landscape']) ) {
			throw new Exception('Ukjent orientering');
		}
		$this->orientation = $orientation;
		return $this;
	}
	public function getOrientation() {
		return $this->orientation;
	}

	public function addInfo( $info ) {
		$this->info[] = $info;
		return $this;
	}

	public function getInfo() {
		return $this->info;
	}

	public function addHeaders() {
		$this->headers = true;
	}
	public function removeHeaders() {
		$this->headers = false;
	}
	public function showHeaders() {
		return $this->headers;
	}

}
/**
 * TODO: class.rapport.php burde bruke denne som utgangspunkt.
 * Akkurat nå er denne en klone med motsatt parameter-rekkefølge.
 * 
 * 
 */
function word_init($wordConfig) {
	global $PHPWord;
	$section = $PHPWord->createSection(array('orientation' => $wordConfig->getOrientation(),
			'marginLeft' => 1100,
			'marginRight' => 1100,
			'marginTop' => 1100,
			'marginBottom' => 1100));
	$properties = $PHPWord->getDocInfo(); 
	$properties->setCreator('UKM Norge'); 
	$properties->setCompany('UKM Norges arrangørsystem');
	$properties->setTitle('UKM '. $wordConfig->getTitle() );
	$properties->setDescription('Generert fra UKM Norges arrangørsystem'); 
	$properties->setCategory('UKM');
	$properties->setLastModifiedBy('UKM Norges arrangørsystem');
	$properties->setCreated( time() );
	$properties->setModified( time() );

	// Definer noen styles
	$PHPWord->addFontStyle('f_p', array('size'=>10));
	$PHPWord->addParagraphStyle('p_p', array('spaceAfter'=>0, 'spaceBefore'=>0));
	$PHPWord->addParagraphStyle('p_bold', array('spaceAfter'=>0, 'spaceBefore'=>0));
	
	$PHPWord->addParagraphStyle('p_center', array('spaceAfter'=>0, 'spaceBefore'=>0, 'align'=>'center'));
			
	$PHPWord->addParagraphStyle('p_grp', array('align'=>'left', 'spaceAfter'=>300));
	$PHPWord->addParagraphStyle('p_h1', array('align'=>'left', 'spaceAfter'=>100));
	$PHPWord->addParagraphStyle('p_h2', array('align'=>'left', 'spaceAfter'=>100));
	$PHPWord->addParagraphStyle('p_h3', array('align'=>'left', 'spaceAfter'=>0, 'spaceBefore'=>100));
	$PHPWord->addParagraphStyle('p_h4', array('align'=>'left', 'spaceAfter'=>0, 'spaceBefore'=>100));
	$PHPWord->addFontStyle('f_grp', array('size'=>20, 'align'=>'left', 'bold'=>true));
	$PHPWord->addFontStyle('f_h1', array('size'=>16, 'align'=>'left', 'bold'=>true));
	$PHPWord->addFontStyle('f_h2', array('size'=>14, 'align'=>'left', 'bold'=>true));
	$PHPWord->addFontStyle('f_h3', array('size'=>12, 'align'=>'left', 'bold'=>true));
	$PHPWord->addFontStyle('f_h4', array('size'=>10, 'align'=>'left', 'bold'=>true));
	$PHPWord->addFontStyle('f_bold', array('bold'=>true, 'spaceAfter'=>0, 'spaceBefore'=>0));
	
	$PHPWord->addParagraphStyle('p_rapportIkonSpacer', array('spaceBefore'=>3000));
	$PHPWord->addParagraphStyle('p_rapportIkonSpacerLandscape', array('spaceBefore'=>1500));
	$PHPWord->addParagraphStyle('p_rapport', array('align'=>'center', 'spaceBefore'=>400));
	$PHPWord->addParagraphStyle('p_place', array('align'=>'center'));
	$PHPWord->addFontStyle('f_rapport', array('size'=>35, 'bold'=>true, 'color'=>'1e4a45'));
	$PHPWord->addFontStyle('f_place', array('size'=>25, 'bold'=>true, 'color'=>'1e4a45'));
	
	# Offset ganges med 0.05, 360 = 18 i Word-avsnitt-stil.
	$PHPWord->addParagraphStyle('p_diplom_navn', array('spaceAfter'=>0, 'spaceBefore'=>360, 'align'=>'center'));
	$PHPWord->addParagraphStyle('p_diplom_mellom', array('spaceAfter'=>0, 'spaceBefore'=>0, 'align'=>'center'));
	$PHPWord->addParagraphStyle('p_diplom_monstring', array('spaceAfter'=>0, 'spaceBefore'=>0, 'align'=>'center'));
	$PHPWord->addFontStyle('f_diplom_navn', array('size'=>25, 'bold'=>true, 'color'=>'1e4a45'));
	$PHPWord->addFontStyle('f_diplom_mellom', array('size'=>18, 'bold'=>false, 'color'=>'1e4a45'));
	$PHPWord->addFontStyle('f_diplom_monstring', array('size'=>18, 'bold'=>true, 'color'=>'1e4a45'));

	$PHPWord->addParagraphStyle('p_page_divider', array('spaceAfter'=>0, 'spaceBefore'=>0, 'align'=>'center'));
	$PHPWord->addFontStyle('f_page_divider', array('size'=>30, 'bold'=>true, 'color'=>'f3776f'));


	$orientation = $section->getSettings()->getOrientation();
	if($orientation == 'landscape')
		woText($section, '','rapportIkonSpacerLandscape');
	else
		woText($section, '','rapportIkonSpacer');
	
	$section->addImage('https://grafikk.ukm.no/profil/logoer/UKM_logo_sort_0300.png', array('width'=>300, 'height'=>164, 'align'=>'center'));

	woText($section, $wordConfig->getTitle(), 'rapport');
	woText($section, $wordConfig->getDescription(), 'place');
	
	if( sizeof( $wordConfig->getInfo() ) > 0 ) {
		woText($section, ''); // Adds an empty line before contacts/add. info
	}
	foreach( $wordConfig->getInfo() as $line ) {
		woText($section, $line, 'diplom_monstring');
	}
	$section->addPageBreak();
	
	if( $wordConfig->showHeaders() ) {
		// Add header
		$header = $section->createHeader();
		$HT = $header->addTable();
		$HT->addRow(720);
		if($orientation == 'landscape')
			$HT->addCell(10000)->addText('UKM-rapporter : '. $wordConfig->getName(), array('align'=>'left'));
		else
			$HT->addCell(5000)->addText('UKM-rapporter : '. $wordConfig->getName(), array('align'=>'left'));
		$HT->addCell(5000, array('align'=>'right'))->addText(date('d.m.Y H:i:s'), array('align'=>'right'), array('align'=>'right'));
		// Add footer
		$footer = $section->createFooter();
		$footer->addPreserveText('Side {PAGE} av {NUMPAGES}.', array('align'=>'right'));
	}
	return $section;
}
