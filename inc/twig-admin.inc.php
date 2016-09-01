<?php
function TWIG($template, $dataarray, $templatefolder, $debug=false) {
	require_once('Twig/Autoloader.php');
	Twig_Autoloader::register();
	$loader = new Twig_Loader_Filesystem($templatefolder.'/twig/');
	
	$environment = array('debug' => $debug);
	if( defined('TWIG_CACHE_PATH') ) {
		$environment['cache'] = TWIG_CACHE_PATH;
		$environment['auto_reload'] = true;
	}
	$twig = new Twig_Environment($loader, $environment);
	
	
	// Add dato-filter
	$filter = new Twig_SimpleFilter('dato', 'TWIG_date');
	$twig->addFilter($filter);
	// Add filesize-filter
	$filter = new Twig_SimpleFilter('filesize', 'TWIGfilesize');
	$twig->addFilter($filter);
	// Add kroner-filter
	$filter = new Twig_SimpleFilter('kroner', 'TWIGkroner');
	$twig->addFilter($filter);
	
	// Set language to French
	putenv('LC_ALL=nb_NO');
	setlocale(LC_ALL, 'nb_NO');

	
	if($debug)
		$twig->addExtension(new Twig_Extension_Debug());

	$template = $template . (strpos($template,'.html.twig')===false ? '.twig.html' : '');
	$template = str_replace('.twig.html.twig.html','.twig.html', str_replace(':',DIRECTORY_SEPARATOR,$template));
	
	return $twig->render($template, $dataarray);
}

function TWIGkroner( $number, $decimals = 0, $decPoint = ',', $thousandsSep = ' ' ) {
	$price = number_format($number, $decimals, $decPoint, $thousandsSep);
	$price = ''.$price;
	return $price;
}
function TWIGrender($template, $dataarray, $debug=false) {
	return TWIG($template, $dataarray, str_replace('/twig/','', TWIG_PATH), $debug);
}

function TWIG_date($time, $format) {
	if( is_string( $time ) && !is_numeric( $time ) ) {
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
}
?>
