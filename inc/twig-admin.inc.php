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
	
	
	// or a simple PHP function
	$filter = new Twig_SimpleFilter('dato', 'TWIG_date');
	$twig->addFilter($filter);

	// Set language to French
	putenv('LC_ALL=nb_NO');
	setlocale(LC_ALL, 'nb_NO');

	
	if($debug)
		$twig->addExtension(new Twig_Extension_Debug());

	return $twig->render($template, $dataarray);
}

function TWIGrender($template, $dataarray, $debug=false) {
	return TWIG($template.'.twig.html', $dataarray, str_replace('/twig/','', TWIG_PATH), $debug);
}

function TWIG_date($time, $format) {
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
?>
