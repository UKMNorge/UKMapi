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
	global $UKMN;
	
	if(sizeof($UKMN['icosize']) == 0)
		$UKMN['icosize'] = array(16,32,64,128,256);

	## IF IT IS SMALLER OR EQUAL TO THE SMALLEST ONE - GIVE THE SMALLEST ONE!
	if($size <= $UKMN['icosize'][0] && file_exists(UKM_HOME.'/UKM/ico/'.$ico.'-'.$UKMN['icosize'][0].'.png')) {
		$icoSize = 0;
	## IF IT IS BIGGER OR EQUAL TO THE LARGEST ONE - GIVE THE LARGEST ONE!
	} elseif($size >= $UKMN['icosize'][sizeof($UKMN['icosize'])-1] && file_exists(UKM_HOME.'/UKM/ico/'.$ico.'-'.$UKMN['icosize'][sizeof($UKMN['icosize'])-1].'.png')) {
		$icoSize = sizeof($UKMN['icosize'])-1;
	## CALCULATE THE MOST APPROPRIATE ICON
	} else {	
		for($i=1; $i<sizeof($UKMN['icosize']); $i++) {	
			## IF COUNTED ALL TO THE AND (POSSIBLY NOT NEEDED)
			if($i == sizeof($UKMN['icosize'])-1) {
				$icoSize = $i;
				break;
			## IF IT IS NOT BETWEEN THE TWO, BUT EXACTLY THE BOTTOM ONE, GIVE THE BOTTOM ONE - DOES NOT WORK?
			} elseif(($size == $UKMN['icosize'][$i]) && file_exists('../'.$UKMN['ico'].$ico.'-'.$UKMN['icosize'][$i].'.png')) {
				$icoSize = $i;
				break;
			## GIVE THE MIDDLE ONE
			} elseif(($UKMN['icosize'][$i-1] < $size && $UKMN['icosize'][$i] > $size) && file_exists(UKM_HOME.'/UKM/ico/'.$ico.'-'.$UKMN['icosize'][$i].'.png')) {
				$icoSize = $i;	
				break;
			}
		}
	}
	
	if(!$wrapper)
		return 'http://ico.ukm.no/'.$ico.'-'.$UKMN['icosize'][$icoSize].'.png';
		
	return '<img src="http://ico.ukm.no/'.$ico.'-'.$UKMN['icosize'][$icoSize].'.png" width="'.$size.'" alt="'.$alttext.'" style="border: 0px none; margin: 2px; margin-bottom: -2px; padding: 0px;" border="0" />';

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