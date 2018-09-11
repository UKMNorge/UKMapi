<?php
require_once('PHPSpreadsheet/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$objPHPExcel = new Spreadsheet();
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setCellValue('A1', 'Hello World !');

if(!function_exists('i2a')) {
	function i2a($a) {
		return ($a-->26?chr(($a/26+25)%26+ord('A')):'').chr($a%26+ord('A'));
	}
}
function excss($case, $adjustments=array()) {
	$set = array();
	$hvit = 'FFFFF7E1';
	$grey = 'FF0A6158';
	$back = 'FF6DC6C1';
	$center = PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
	$right = PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
	$solid = PHPExcel_Style_Fill::FILL_SOLID;
	switch($case) {
		case 'fontcolor_0':
		case 'fontcolor_1':
			$set['font']['color']['argb'] = 'FF1E4E45';
			break;
		case 'fontcolor_2':
			$set['font']['color']['argb'] = 'FF6DC6C1';
			break;
		case 'fontcolor_3':
			$set['font']['color']['argb'] = 'FFf3776F';
			break;
		case 'fontcolor_4':
			$set['font']['color']['argb'] = 'FF000000';
			break;
		case 'fontcolor_5':
			$set['font']['color']['argb'] = 'FFF69A9B';
			break;

		case 'bold':
			$set['font']['bold'] = true;
			break;
		case 'h1':
			$set['font']['bold'] = true;
			$set['font']['size'] = 22;
			$set['alignment']['horizontal'] = $center;
			break;
		case 'h2':
			$set['font']['bold'] = true;
			$set['font']['size'] = 18;
			$set['alignment']['horizontal'] = $center;
			break;
		case 'h3':
			$set['font']['bold'] = true;
			$set['font']['size'] = 14;
			$set['alignment']['horizontal'] = $center;
			break;
		case 'h4':
			$set['font']['bold'] = true;
			$set['font']['size'] = 12;
			$set['alignment']['horizontal'] = $center;
			break;
		case 'grey':
			$set['font']['bold'] = true;
			$set['fill']['type'] = $solid;
			$set['fill']['color']['argb'] = $grey;
			$set['font']['color']['argb'] = $hvit;
			break;
		case 'greyright':
			$set['font']['bold'] = true;
			$set['fill']['type'] = $solid;
			$set['fill']['color']['argb'] = $grey;
			$set['font']['color']['argb'] = $hvit;
			$set['alignment']['horizontal'] = $right;
			break;
		case 'hvit':
			$set['fill']['type'] = $solid;
			$set['fill']['color']['argb'] = $hvit;
			break;
		case 'bakgrunn':
			$set['fill']['type'] = $solid;
			$set['fill']['color']['argb'] = $back;
			break;
		case 'right':
			$set['alignment']['horizontal'] = $right;
			break;
	}	
	// OVERSTYR
	foreach($adjustments as $key => $val)
		$set[$key] = $val;
	// RETURNER INNSTILLINGER
	return $set;	
}
function exsetcss($celle, $stil=false,$stilextra=array()) {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getStyle($celle)->applyFromArray(excss($stil,$stilextra));
	return $celle;
}
// GENERER CELLE
function excell($celle, $verdi, $stil=false,$stilextra=array()) {
	global $objPHPExcel;
	if(strpos($celle,':') !== false) {
		$objPHPExcel->getActiveSheet()->mergeCells($celle);
		$temp = explode(':', $celle);
		$celle = $temp[0];
	}
	$objPHPExcel->getActiveSheet()->getCell($celle)->setValue($verdi);
	if($stil !== false)
		$objPHPExcel->getActiveSheet()->getStyle($celle)->applyFromArray(excss($stil,$stilextra));
	return $celle;
}

function exformat($celle) {
	global $objPHPExcel;
	if(!empty($celle))
		$objPHPExcel->getActiveSheet()->getStyle($celle)->getNumberFormat()->setFormatCode('#,##0.00');
	return $celle;
}
// SETT KOLONNEBREDDER
function excolwidth($col, $width) {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($width);
}

function exwrap($celle,$height=24) {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getStyle($celle)->getAlignment()->setWrapText(true);
	$objPHPExcel->getActiveSheet()->getRowDimension(substr($celle,1))->setRowHeight($height);
	return $celle;
}

function exunlock($celle) {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getStyle($celle)->getProtection()->setLocked( PHPExcel_Style_Protection::PROTECTION_UNPROTECTED 
);
	return $celle;
}

function exlocksheet() {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getProtection()->setPassword('Fjord17010Trheim');
	$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
}

function exprint($area) {
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(true);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setPrintArea($area);
}

function exorientation($dir='portrait') {
	global $objPHPExcel;
	if($dir=='portrait')
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	else
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
}

function exSheetName($name, $color=false){
	global $objPHPExcel;
	$objPHPExcel->getActiveSheet()->setTitle($name);
	if($color)
		$objPHPExcel->getActiveSheet()->getTabColor()->setRGB($color);
}

function excond($celle) {
	global $objPHPExcel;

	$objConditional1 = new PHPExcel_Style_Conditional();
	$objConditional1->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
	$objConditional1->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHAN);
	$objConditional1->addCondition('0');
	$objConditional1->getStyle()->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
#	$objConditional1->getStyle()->getFont()->getColor()->setARGB('FFF3776F');
	$objConditional1->getStyle()->getFont()->setBold(true);
	$objConditional1->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');
	
	$objConditional2 = new PHPExcel_Style_Conditional();
	$objConditional2->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
	$objConditional2->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHANOREQUAL);
	$objConditional2->addCondition('0');
	$objConditional2->getStyle()->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_BLACK);
	$objConditional2->getStyle()->getFont()->setBold(true);
	$objConditional2->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');

	$conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($celle)->getConditionalStyles();
	array_push($conditionalStyles, $objConditional1);
	array_push($conditionalStyles, $objConditional2);
	$objPHPExcel->getActiveSheet()->getStyle($celle)->setConditionalStyles($conditionalStyles);
}

function exWrite($objPHPExcel,$filename) {
	$filename = $filename .'.xlsx';
	if( defined('EXCEL_WRITE_PATH') ) {
		$internal = EXCEL_WRITE_PATH;
	} else {
		$internal = '/home/ukmno/public_html/temp/phpexcel/';
	}
	if( strpos( $_SERVER['HTTP_HOST'], 'ukm.dev' ) !== false ) {
		$internal = '/phpexcel/';
		if( !file_exists( $internal ) ) {
			mkdir( $internal, 0777, true );
		}
	}
	$external = 'http://ukm.no/UKM/subdomains/download/?folder=phpexcel&filename='.urlencode($filename);
	$external = 'http://download.ukm.no/?folder=phpexcel&filename='.urlencode($filename);

	$objPHPExcel->setActiveSheetIndex(0);

	ini_set('display_errors', true);
	error_log(E_ALL);
	if( !is_writable( $internal ) ) {
		echo 'AU, KAN IKKE SKRIVE TIL '. $internal;
	} else {
		echo 'kan skrive til '. $internal;
	}

	$handle = fopen( $internal.$filename, 'w+' );

	if( !is_writable( $internal.$filename ) ) {
		echo 'AU, KAN IKKE SKRIVE TIL '. $internal.$filename;
	} else {
		echo 'kan skrive til '. $internal.$filename;
	}

	
	fwrite( $handle, 'heihei' );
	fclose( $handle );

	$username = posix_getpwuid(posix_geteuid())['name'];
	var_dump( $username );

	echo 'Write to: '. $internal.$filename;
	$objWriter = new Xlsx( $objPHPExcel );
	$res = $objWriter->save($internal.$filename);
	return $external;
}

function exInit() {
	global $objPHPExcel;
	$objPHPExcel = new PHPExcel($docTitle='Dokument uten navn', $orientation='portrait');
	exorientation($orientation);

	$objPHPExcel->getProperties()->setCreator('UKM Norges arrangørsystem');
	$objPHPExcel->getProperties()->setLastModifiedBy('UKM Norges arrangørsystem');
	$objPHPExcel->getProperties()->setTitle($docTitle);
	$objPHPExcel->getProperties()->setKeywords($docTitle);

	## Sett standard-stil
	$objPHPExcel->getDefaultStyle()->getFont()->setName('Calibri');
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(12);

	####################################################################################
	## OPPRETTER ARK
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->setActiveSheetIndex(0)->getTabColor()->setRGB('A0CF67');
}
?>
