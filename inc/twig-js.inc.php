<?php
function TWIGjs($templatefolder, $prefix='') {
	$SCRIPT = '';
	
	$templatefolder .= '/twig/js/';
	
	$id = 'twigJS'. ( empty($prefix) ? '' : $prefix .'' );

	$templates = glob($templatefolder . "*.twig.html");
	
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$templateID = str_replace(array('.twig.html','_'),
									  array('', ''),
									  basename($template)
									 );
			$SCRIPT .= '<script type="text/javascript">'
					. 'var '. $id.$templateID.' = twig({data: \''.str_replace("'","\'",preg_replace( "/\r|\n/", "",file_get_contents( $template ))).'\'});'
					. '</script>';
		}
	}
	return $SCRIPT;
}