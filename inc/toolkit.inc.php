<?php
/* 
Part of: UKM Norge core
Description: Inneholder forskjellige småfunksjoner benyttet i eksterne moduler. Inneholder også API'et.
Author: UKM Norge / M Mandal 
Version: 3.0
*/

function contact_sms($phone,$returnto='') {
	$returnName = urlencode($returnto);
	$returnUrl = urlencode($_SERVER['QUERY_STRING']);
	
	return '<input type="hidden" class="ukm_contact_sms" name="ukm_contact_sms[]" value="'.$phone.'" />'
	
		.'<a href="?page=UKMSMS_gui'
				   .'&UKMSMS_returnname='.$returnName
				   .'&UKMSMS_returnto='.$returnUrl
				   .'&UKMSMS_recipients='.$phone
				  .'">'.$phone.'</a>';
}
function contact_mail($mail,$nicename=false) {	
	if(!$nicename)
		$nicename = $mail;
		
	return '<input type="hidden" class="ukm_contact_mail" name="ukm_contact_mail[]" value="'.$mail.'" />'
		. '<a href="mailto:'.$mail.'">'.$nicename.'</a>';
}






/////////////////////
// GAMMEL KODE (v 2.1)



/*
if(function_exists('is_admin') && is_admin()) {
	## GUI FOR Å HÅNDTERE NAVIGASJON I ADMIN-SIDER
	require_once('navigation.inc.php');
	require_once('ico.inc.php');
	require_once('gui.inc.php');
}
*/

################################################################
#              FUNCTION TO INCLUDE ALL API'S                   #
################################################################
/*
$dir = ABSPATH.'wp-content/plugins/UKMNorge/api/';
$filliste = array();
if (is_dir($dir)) {
	if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if(!is_dir($dir . $file)){
				$explodeFile = explode('.',$file);
				$filliste[] = array('file'=>$file,'navn'=>$explodeFile[0]);
				
			}
        }
        closedir($dh);
    }
}
foreach($filliste as $file){
#	require_once($dir.$file['file']);
}
*/
###############################################################


function UKMN_tid($sec, $long=false) {
	$hours = floor($sec / 3600);
	$minutes = floor(($sec / 60) % 60);
	$seconds = $sec % 60;

	$h = $long ? ' time'.($h==1?'':'r') : 't';
	$m = $long || ($hours==0 && $seconds==0) ? ' min' : 'm';
	$s = $long || ($hours==0 && $minutes==0) ? ' sek' : 's';	
	
	$str = '';

	if($hours > 0)
		$str .= $hours.$h.' ';

	if( !empty($str) || $minutes > 0)
		$str .= $minutes.$m.' ';
	
	if( empty($str) || $seconds > 0)
		$str .= $seconds.$s;	

	return $str;
}


#####################################################
## FUNCTIONS
#####################################################

function UKMN_btico($bt_id,$kat=false) {
	switch($bt_id) {
		case 1: 
			switch($kat) {
				case 'dans': return 'dans';
				case 'litteratur': return 'litteratur';
				case 'annet': return 'scene';
				case 'teater': return 'teater';
				default: return 'scene';
			}
		case 2: return 'video';
		case 3: return 'utstilling';
		case 4: return 'konferansier';
		case 5: return 'nettredaksjon';
		case 6: return 'matkultur';
		case 7: return 'annet';
		case 8: return 'arrangor';
		case 9: return 'sceneteknikk';
		case 10:return 'nettredaksjon'; 
	}
}

## KUTT NED EN STRING MIDT I (SHORTEN)
function shortString( $str, $length = 14 ) {
	if( strlen( $str ) > $length ) {
		$separator = '...';
		$separatorlength = strlen($separator) ;
		$maxlength = $length-3;
		$start = $maxlength / 2 ;
		$trunc =  strlen($str) - $maxlength;
		return substr_replace($str, $separator, $start, $trunc);
	}
	return $str;
}


## FINN PL-ID FOR AKTIV ADMIN SITE
function UKMN_activePL() {
	global $blog_id;
	$plid = get_blog_option($blog_id,'pl_id');
	$plid = $plid*1;
	if($plid) return $plid;
	$plid = substr($_SERVER['REQUEST_URI'],1, strpos($_SERVER['REQUEST_URI'],'/',2)-1);
	return str_replace('pl','', $plid);
}

function UKMN_config($name) {
	$sql = new SQL("SELECT `conf_value` FROM `smartcore_config` WHERE `conf_name` = '#name'",
				array('name'=>$name));
	$sql = $sql->run();
	
	if(mysql_num_rows($sql) == 0)
		return false;
	
	$sql = mysql_fetch_assoc($sql);
	return $sql['conf_value'];
}

function UKMN_fylker() {
	$query = new SQL('SELECT `id`,`name` FROM smartukm_fylke ORDER BY `name` ASC');
	$result = $query->run();
	while($r=mysql_fetch_assoc($result)) 
		$fylker[(int)$r['id']] = utf8_encode($r['name']);
	return $fylker;

}

$UKMN_fylke_storage = array();
function UKMN_fylke($id) {
	global $UKMN_fylke_storage;
	if(isset($UKMN_fylke_storage[$id]))
		return $UKMN_fylke_storage[$id];
	$query = new SQL('SELECT name FROM smartukm_fylke WHERE id='.$id);
	$result = $query->run('field','name');
	$UKMN_fylke_storage[$id] = utf8_encode($result);
	return utf8_encode($result);
}

function UKMN_kommune($id) {
	$query = new SQL('SELECT name FROM smartukm_kommune WHERE id='.$id);
	$result = $query->run('field','name');
	return $result;
}

function UKMN_kommuner() {
	$query = new SQL('SELECT `name`,`id` FROM `smartukm_kommune` ORDER BY `name` ASC');
	$result = $query->run();
		while($r = mysql_fetch_assoc($result))
			$kommuner[] = array('id'=>$r['id'], 'name'=>utf8_encode($r['name']));
	return $kommuner;
}

function UKMN_poststed($id) {
	$query = new SQL('SELECT `postalplace` FROM `smartukm_postalplace` WHERE `postalcode` = \'#code\';', array('code'=>$id));
	$result = $query->run('field','postalplace');
	return utf8_encode($result);
}

function UKMN_navn($navn){
	# FUNCTION TO MAKE A NAME URL FRENDLY
	$navn = strtolower($navn);
	$navn = trim($navn);
	$arrFrom = array("æ","ø","å","Æ","Ø","Å","("," ",")"); # tar bort særnorske tegn
	$arrTo = array("ae","o","a","ae","o","a","-","-","");
	$navn = str_replace($arrFrom, $arrTo, $navn);
	$navn = str_replace("--","-", $navn);
	$navn = preg_replace("/[^A-Za-z0-9-]/","",$navn);
	return $navn;		
}
function UKMN_mktime($dag,$time,$min){
	$day = explode('-',$dag);
	return mktime($time, $min, 0, $day[1], $day[2], $day[0]);
}

/*
add_filter('rewrite_rules_array','wp_insertMyRewriteRules');
add_filter('query_vars','wp_insertMyRewriteQueryVars');
add_filter('wp_loaded','flushRules');

// Remember to flush_rules() when adding rules
function flushRules(){
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
}

// Adding a new rule
function wp_insertMyRewriteRules($rules)
{
	$newrules = array();
	$newrules['st'] = 'index.php?pagename=about';
	return $newrules + $rules;
}

// Adding the id var so that WP recognizes it
function wp_insertMyRewriteQueryVars($vars)
{
    array_push($vars, 'id');
    return $vars;
}
*/

################################################
## FUNKSJON KOPIERT FRA SMARTE SIDER
## ABSOLUTT NØDVENDIG FOR Å KUNNE LESE DATABASEN
################################################
function SMAS_encoding($content) {
	$characterEncoding = mb_detect_encoding($content."a", 'UTF-8, UTF-16, ISO-8859-1, ISO-8859-15, Windows-1252, ASCII');
	switch ($characterEncoding) {
	 case "UTF-8":
	   $content = utf8_decode($content);
	   break;
	 case "ISO-8859-1":
	   break;
	 default:
	   $content = mb_convert_encoding($content,$characterEncoding);
	   break;
	   
	}
	return $content;	
}
function UKMN_SEASON_urlsafe($text) {
	
	$text = SMAS_encoding($text);

	$text = htmlentities($text);
	
	$ut = array('&Aring;','&aring;','&Aelig;','&aelig;','&Oslash;','&oslash;','&Atilde;','&atilde','Ocedil','ocedil');
	$inn= array('A','a','A','a','O','o','O','o','O','o');
	$text = str_replace($ut, $inn, $text);
	
	$text = preg_replace("/[^A-Za-z0-9-]/","",$text);

	return strtolower($text);
}

function UKM_san($name) {
	return preg_replace('/[^a-z]+/', '', strtolower($name));
}
?>