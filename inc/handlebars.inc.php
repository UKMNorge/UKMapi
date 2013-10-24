<?php
function HANDLEBARS($templatefolder, $prefix='') {
	$SCRIPT = '';
	
	$id = 'handlebars-'. ( empty($prefix) ? '' : $prefix .'-' );

	$templates = glob($directory . "*.txt");
	foreach($templates as $template) {
		if($template != '.' && $template != '..') {
			$SCRIPT .= '<script id="'. $id . $template .'" type="text/x-handlebars-template">'
					. file_get_contents( $templatefolder .'/'. $template )
					. '</script>';
		}	
	}
	return $SCRIPT;
}