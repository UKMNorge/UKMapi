<?php
/* 
Part of: UKM Norge core
Description: Inneholder forskjellige småfunksjoner benyttet i eksterne moduler. Inneholder også API'et.
Author: UKM Norge / M Mandal 
Version: 3.0
*/

function UKMN_tid($sec, $long=false) {
	$hours = floor($sec / 3600);
	$minutes = floor(($sec / 60) % 60);
	$seconds = $sec % 60;

	$h = $long ? ' time'.($h==1?'':'r') : 't';
	$m = $long || ($hours==0 && $seconds==0) ? ' min' : 'm';
	$s = $long || ($hours==0 && $minutes==0) ? ' sek' : 's';	
	
	$str = '';

	if($hours > 0)
		$str .= $hours.$h.' ';

	if( !empty($str) || $minutes > 0)
		$str .= $minutes.$m.' ';
	
	if( empty($str) || $seconds > 0)
		$str .= $seconds.$s;	

	return $str;
}

## KUTT NED EN STRING MIDT I (SHORTEN)
function shortString( $str, $length = 14 ) {
	if( strlen( $str ) > $length ) {
		$separator = '...';
		$separatorlength = strlen($separator) ;
		$maxlength = $length-3;
		$start = $maxlength / 2 ;
		$trunc =  strlen($str) - $maxlength;
		return substr_replace($str, $separator, $start, $trunc);
	}
	return $str;
}

function UKMN_poststed($id) {
	$query = new SQL('SELECT `postalplace` FROM `smartukm_postalplace` WHERE `postalcode` = \'#code\';', array('code'=>$id));
	$result = $query->run('field','postalplace');
	return $result;
}