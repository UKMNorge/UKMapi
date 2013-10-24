<?php
function HANDLEBARS($templatefolder, $prefix='') {
	$SCRIPT = '';
	
	$templatefolder .= '/handlebars/';
	
	$id = 'handlebars-'. ( empty($prefix) ? '' : $prefix .'-' );

	$templates = glob($templatefolder . "*.handlebars.html");
	
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$templateID = str_replace(array('.handlebars.html','_'),
									  array('', '-'),
									  basename($template)
									 );
			$SCRIPT .= '<script id="'. $id . $templateID .'" type="text/x-handlebars-template">'
					. file_get_contents( $template )
					. '</script>';
		}	
	}
	return $SCRIPT;
}