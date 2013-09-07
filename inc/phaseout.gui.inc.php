<?php
/* 
Part of: UKM Norge core
Description: Mulig utgŒtt UKM-funksjonalitet for bruk til splashscreens
Author: UKM Norge / M Mandal 
Version: 2.0 
*/

################################
## GUI FUNCTIONS
## FUNCTION TO GENERATE THE CONTENT OF A MENUCELL
function UKMN_menuCellContent($i, $size, $header='h3') {
	global $UKMN;
	# IF ONLY ONE LINK
	if(sizeof($i['links']) == 1) {
		$link = array_keys($i['links']);
		$ico = '<a href="'.$link[0].'">'.UKMN_ico($i['icon'], $size).'</a>';
	} elseif(isset($i['splashscreen']) && !empty($i['splashscreen']))
		$ico = '<a href="'.$i['splashscreen'].'">'.UKMN_ico($i['icon'], $size).'</a>';
	else
		$ico = UKMN_ico($i['icon'], $size);

	return '<table cellpadding="2" cellspacing="2" width="100%">'
		 .  '<tr>'
		 .   '<td width="120" height="120" valign="bottom" align="center">'. $ico .'</td>'
		 .  '</tr>'
		 .  '<tr>'
		 .   '<td>'
		 .    '<'.$header.'>'.$i['name'].'</'.$header.'>'
		 .   '</td>'
		 .  '</tr>'
		 .  '<tr>'
		 .   '<td>'. UKMN_menuCellContentLinks($i['links']) . '</td>'
		 .  '</tr>'
		 . '</table>';
}

## FUNCTION TO GENERATE A LIST OF LINKS IN A MENU CELL
function UKMN_menuCellContentLinks($i) {
	$return = '';
	foreach($i as $link => $text)
		$return .= '<a href="'.$link.'">'.$text.'</a><br />';
	
	return $return;
}

## FUNCTION TO CREATE A UKM-STYLE FIELDSET
function UKMN_fieldset($legend, $content, $width="auto") {
	return '
		<fieldset class="widefat" style="margin: 10px; margin-top: 18px; width: '.$width.'; background: #f1f1f1; padding: 8px;">
			<legend style="border: none; margin-left: 10px; font-weight: bold; font-size: 16px;">
				'.$legend.'
			</legend>
			'.$content.'
		</fieldset>';	
}

if(!function_exists('tab')) {
	function tab(){
		return ' &nbsp; &nbsp; &nbsp; ';
	}
}
?>