<?php
function TWIG($template, $dataarray, $templatefolder, $debug=false) {
	require_once('Twig/Autoloader.php');
	Twig_Autoloader::register();
	$loader = new Twig_Loader_Filesystem($templatefolder.'/twig/');
	$twig = new Twig_Environment($loader, array('debug' => $debug));
	
	if($debug)
		$twig->addExtension(new Twig_Extension_Debug());

	return $twig->render($template, $dataarray);
}

function TWIGrender($template, $dataarray, $debug=false) {
	return TWIG($template.'.twig.html', $dataarray, str_replace('/twig/','', TWIG_PATH), $debug);
}
?>
