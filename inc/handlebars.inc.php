<?php
function HANDLEBARS($templatefolder, $prefix='') {
	$SCRIPT = '';
	
	$templatefolder .'/handlebars/';
	
	$id = 'handlebars-'. ( empty($prefix) ? '' : $prefix .'-' );

	$templates = glob($directory . "*.handlebars.html");
	
	var_dump($templatefolder);
	var_dump($templates);
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$SCRIPT .= 'script id="'. $id . $template .'" type="text/x-handlebars-template">'
					. file_get_contents( $templatefolder . $template )
					. '/script>';
		}	
	}
	return $SCRIPT;
}