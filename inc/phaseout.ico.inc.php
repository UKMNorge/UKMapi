<?php
/* 
Part of: UKM Norge core
Description: Inneholder alle funksjoner som omhandler ikoner og behandling av disse..
Author: UKM Norge / M Mandal 
Version: 2.0 
*/

## FIND A ICO IN SUITABLE SIZE
function UKMN_icoAlt($ico, $alt, $size=16) {
	return UKMN_ico($ico, $size, true, $alt);
}

function UKMN_iphone($ico, $size=32) {
	return 'http://ico.ukm.no/iphone/iphone-'.$ico.'-'.$size.'.png';
}

function UKMN_ico($ico, $size=16, $wrapper=true, $alttext='') {

    if(!$wrapper)
		return 'http://ico.ukm.no/'.$ico.'-'.$size.'.png';
		
	return '<img src="http://ico.ukm.no/'.$ico.'-'.$size.'.png" width="'.$size.'" style="border: 0px none; margin: 2px; margin-bottom: -2px; padding: 0px;" border="0" />';
}

function UKMN_icoButton($ico, $size, $text, $fontsize=9) {
	return '<div align="center" style="font-size: '.$fontsize.'px;">'
		.  UKMN_icoAlt($ico, $text, $size)
		.  '<br clear="all" />'
		.  strtolower($text)
		.  '</div>';
}
function UKMN_icoButtonLine($ico, $size, $text, $fontsize=9) {
	return '<div align="center" style="font-size: '.$fontsize.'px; height: '.($size+2).'px;">'
		.  UKMN_icoAlt($ico, $text, $size)
		.  ' '
		.  strtolower($text)
		.  '</div>';
}

function UKMN_icoImageSubmit($ico, $size, $text, $form, $fieldname,$value = '', $fontsize=9) {
		return '<div
				align="center"
				style="font-size: '.$fontsize.'px;"
				fieldname="'.$fieldname.'"
				value="'.$value.'"
				form="'.$form.'">'
			.  UKMN_icoAlt($ico, $text, $size)
			.  '<br clear="all" />'
			.  strtolower($text)
			.  '</div>';
}
?>