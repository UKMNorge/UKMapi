<?php
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
?>