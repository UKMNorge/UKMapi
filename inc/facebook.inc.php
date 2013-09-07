<?php
/* 
Plugin Name: UKM Facebook
Plugin URI: http://www.ukm-norge.no
Description: Facebook-integrering for å håndtere inn- og utlogging av facebook. Vil sette en global variabel som kan benyttes
Author: UKM Norge / M Mandal
Version: 0.1
Author URI: http://www.ukm-norge.no
*/

## Load facebook api / class / thingie from FB
require_once('PHPFacebook/facebook.php');
require_once('UKMconfig.inc.php');

## Actions
if(function_exists('add_action'))
	add_action('plugins_loaded', 'UKMface_cff');

## Check for facebook
function UKMface_cff() {
	## Initiate facebook object
	global $facebook, $FACE;
	$facebook = new Facebook(array(
				  'appId'  => UKMface_APP_ID(),
				  'secret' => UKMface_APP_SECRET(),
					)
				);

	## If we're in admin, do nothing
	if(function_exists('is_admin')&&is_admin()) return;
	
	## Log out from facebook
	if(isset($_GET['face'])&&$_GET['face']=='logout')
		UKMface_logout();
	
	## Log in to facebook
	UKMface_login();
}

function UKMface_APP_ID() {
	return UKM_FACE_APP_ID;
}
function UKMface_APP_SECRET() {
	return UKM_FACE_APP_SECRET;
}

## LOGG INN TIL FACEBOOK
function UKMface_login() {
	global $FACE, $facebook;
	## Finn brukerID til facebook-brukeren
	$FACE = $facebook->getUser();
}

## SLETT ALLE COOKIES OG NULLSTILL OBJEKTET
function UKMface_logout() {
	global $FACE, $facebook;
	$FACE = 'logout';
	$facebook->destroySession();
	
	## Loop all cookies
	foreach($_COOKIE as $key => $val) {
		## Unset facebook cookies
		if(strpos($key, 'fb')===0) {
			setcookie($key, $val,1);
			unset($_COOKIE[$key]);
		}
	}
}

##
function UKMface_userdata() {
	global $facebook, $FACE;

	$user = $facebook->getUser();	
	if ($user) {
		try {
	    	// Proceed knowing you have a logged in user who's authenticated.
    		return $facebook->api('/me');
		} catch (FacebookApiException $e) {
			return false;
		}
	}
}
?>