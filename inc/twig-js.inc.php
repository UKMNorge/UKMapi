<?php
function TWIGjs($templatefolder, $prefix='') {
	$SCRIPT = '';
	
	$templatefolder .= '/twig/js/';
	
	$id = 'twigJS'. ( empty($prefix) ? '' : $prefix .'' );

	$templates = glob($templatefolder . "*.twig*");
	
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$templateID = str_replace(array('.twig.html','.html.twig','_','.'),
									  array('','','','_'),
									  basename($template)
									 );
			$SCRIPT .= '<script type="text/javascript">'
					. 'var '. $id.$templateID.' = twig({data: \''.str_replace("'","\'",preg_replace( "/\r|\n/", "",file_get_contents( $template ))).'\'});'
					. '</script>'
					. "\r\n";
		}
	}
	return $SCRIPT;
}
function TWIGjs_simple($templatefolder) {
	$SCRIPT = '<script type="text/javascript">';
	
	$templates = glob($templatefolder . "/twig/js/*.html.twig");
	
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$templateID = str_replace(array('.html.twig','_','.'),
									  array('', '','_'),
									  basename($template)
									 );
			$SCRIPT .= 'var twigJS_'. $templateID.' = twig({data: \''.str_replace("'","\'",preg_replace( "/\r|\n/", "",file_get_contents( $template ))).'\'});';
		}
	}
	$SCRIPT .= '</script>'
		. "\r\n";
;
	return $SCRIPT;
}