<?php
function TWIG($template, $dataarray, $templatefolder) {
	require_once('Twig/Autoloader.php');
	Twig_Autoloader::register();
	$loader = new Twig_Loader_Filesystem($templatefolder);
	$twig = new Twig_Environment($loader);

	return $twig->render($template, $dataarray);
}
?>