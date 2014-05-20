<?php
define('PHPWORD_BASE_PATH','/usr/local/lib/php/');
require_once('PHPWord/PHPWord.php');
global $PHPWord;

$PHPWord = new PHPWord();

function woText(&$section, $text, $style=false) {
	$text = utf8_decode($text);
	if(!$style)
		$section->addText($text, 'f_p','p_p');
	else
		$section->addText($text, 'f_'.$style, 'p_'.$style);
}

function woCell(&$table, $width, $text, $style=false){
	$c = $table->addCell($width);
	woText($c, $text, $style);
}

function woWrite($filename){
	$filename = $filename .'.docx';
	$internal = '/home/ukmno/public_html/temp/phpword/';
	$external = 'http://ukm.no/UKM/subdomains/download/?folder=phpword&filename='.urlencode($filename);
	$external = 'http://download.ukm.no/?folder=phpword&filename='.urlencode($filename);
	global $PHPWord;
	$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
	$objWriter->save($internal.$filename);
	return $external;
}


function word_init($name,$orientation='portrait') {
	global $PHPWord;
	$section = $PHPWord->createSection(array('orientation' => $orientation,
		    'marginLeft' => 1100,
		    'marginRight' => 1100,
		    'marginTop' => 1100,
		    'marginBottom' => 1100));
	$properties = $PHPWord->getProperties();
	$properties->setCreator('UKM Norge'); 
	$properties->setCompany('UKM Norges arrangørsystem');
	$properties->setTitle('UKM '. ucfirst(str_replace('_',' ',$name)));
	$properties->setDescription('Rapport generert fra UKM Norges arrangørsystem'); 
	$properties->setCategory('UKM-rapporter');
	$properties->setLastModifiedBy('UKM Norge arrangørsystem');
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

	$PHPWord->addParagraphStyle('p_page_divider', array('spaceAfter'=>0, 'spaceBefore'=>0, 'align'=>'center'));
	$PHPWord->addFontStyle('f_page_divider', array('size'=>30, 'bold'=>true, 'color'=>'f3776f'));


	$orientation = $section->getSettings()->getOrientation();
	if($orientation == 'landscape')
		woText($section, '','rapportIkonSpacerLandscape');
	else
		woText($section, '','rapportIkonSpacer');
	$section->addImage('/home/ukmno/public_html/wp-content/plugins/UKMrapporter/UKM_logo.png', array('width'=>300, 'height'=>164, 'align'=>'center'));

	woText($section, ucfirst(str_replace('_',' ',$name)), 'rapport');
	woText($section, get_option('season'),'place');
	$section->addPageBreak();
	
	// Add header
	$header = $section->createHeader();
	$HT = $header->addTable();
	$HT->addRow(720);
	if($orientation == 'landscape')
		$HT->addCell(10000)->addText('UKM-rapporter : '.utf8_decode($name), array('align'=>'left'));
	else
		$HT->addCell(5000)->addText('UKM-rapporter : '.utf8_decode($name), array('align'=>'left'));
	$HT->addCell(5000, array('align'=>'right'))->addText(date('d.m.Y H:i:s'), array('align'=>'right'), array('align'=>'right'));
	// Add footer
	$footer = $section->createFooter();
	$footer->addPreserveText('Side {PAGE} av {NUMPAGES}.', array('align'=>'right'));

	return $section;
}

?>
