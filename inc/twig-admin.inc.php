<?php

use UKMNorge\Twig\Twig as TwigAdmin;
require_once('UKM/Twig/Twig.php');
require_once('UKMconfig.inc.php');

function TWIG($template, $dataarray, $templatefolder, $debug=false) {
    // Add template and default paths
    TwigAdmin::addPath($templatefolder.'/twig/');
	if( class_exists('UKMwp_innhold') ) {
		TwigAdmin::addPath( UKMwp_innhold::getPath().'twig/');
    }
    TwigAdmin::addPath(dirname( __DIR__ ). '/Twig/templates/');
    TwigAdmin::addData( $dataarray );
    TwigAdmin::setData('UKM_HOSTNAME', UKM_HOSTNAME);
        
    TwigAdmin::enableDebugMode( $debug );

	if( defined('TWIG_CACHE_PATH') ) {
        TwigAdmin::setEnvironment('cache', TWIG_CACHE_PATH);
        TwigAdmin::setEnvironment('auto_reload', true);
    }
    
    TwigAdmin::addFilter('dato', 'TWIG_date');
    TwigAdmin::addFilter('filesize', 'TWIGfilesize');
    TwigAdmin::addFilter('kroner', 'TWIGkroner');
    TwigAdmin::addFilter('varighet', 'TWIGtid');
    TwigAdmin::addFilter('strips', 'TWIGstrips');
    TwigAdmin::addFunction('GET', 'TWIG_GET');

	putenv('LC_ALL=nb_NO');
	setlocale(LC_ALL, 'nb_NO');

	$template = $template . (strpos($template,'.html.twig')===false ? '.twig.html' : '');
	$template = str_replace('.twig.html.twig.html','.twig.html', str_replace(':',DIRECTORY_SEPARATOR,$template));
    
	return TwigAdmin::render( $template, $dataarray);
}
function TWIG_GET( $var ) {
	if( isset( $_GET[ $var ] ) ) {
		return $_GET[ $var ];
	}
	return false;
}

function TWIGkroner( $number, $decimals = 0, $decPoint = ',', $thousandsSep = ' ' ) {
	$price = number_format($number, $decimals, $decPoint, $thousandsSep);
	$price = ''.$price;
	return $price;
}
function TWIGrender($template, $dataarray, $debug=false) {
	return TWIG($template, $dataarray, str_replace('/twig/','', TWIG_PATH), $debug);
}

function TWIGtid( $seconds ) {
	$q = floor($seconds / 60);
	$r = $seconds % 60;
	
	if ($q == 0)
		return $r.' sek';
	
	if ($r == 0)
		return $q.' min';
	
	return $q.'m '.$r.'s';
}

function TWIG_date($time, $format) {
	if( is_object( $time ) && get_class( $time ) == 'DateTime' ) {
		$time = $time->getTimestamp();
	} elseif( is_string( $time ) && !is_numeric( $time ) ) {
		$time = strtotime($time);
	}
	$date = date($format, $time);

	return str_replace(array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday',
							 'Mon','Tue','Wed','Thu','Fri','Sat','Sun',
							 'January','February','March','April','May','June',
							 'July','August','September','October','November','December',
							 'Jan','Feb','Mar','Apr','May','Jun',
							 'Jul','Aug','Sep','Oct','Nov','Dec'),
					  array('mandag','tirsdag','onsdag','torsdag','fredag','lørdag','søndag',
					  		'man','tir','ons','tor','fre','lør','søn',
					  		'januar','februar','mars','april','mai','juni',
					  		'juli','august','september','oktober','november','desember',
					  		'jan','feb','mar','apr','mai','jun',
					  		'jul','aug','sep','okt','nov','des'), 
					  $date);
}

function TWIGfilesize( $size, $precision = 2 ) {
    for($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {}
    return round($size, $precision).['B','kB','MB','GB','TB','PB','EB','ZB','YB'][$i];
function TWIGstrips($data)
{
    if (is_string($data)) {
        return stripslashes($data);
    }
    return $data;
}
