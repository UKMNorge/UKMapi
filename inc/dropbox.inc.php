<?php
define('DROPBOX_APP_NAME', 'UKMdigark/1.0');
define('DROPBOX_REDIR_URL', 'https://'. UKM_HOSTNAME .'/wp-content/plugins/UKMsystem_tools/dropbox.php');

require_once('UKMconfig.inc.php');
require_once('Dropbox/vendor/autoload.php');

$dropboxKey = 'fwnfgdlcqnq77rv';
$dropboxSecret = 'lcilu4dtwcl3b2l';
$appName = 'UKMdigarkBilder/1.0';

$appInfo = new Dropbox\AppInfo( $dropboxKey, $dropboxSecret );
// Temp-store CSRF Token
$csrfTokenStore = new Dropbox\ArrayEntryStore( $_SESSION, 'dropbox-auth-csrf-token' );
// Define auth-details
$webAuth = new Dropbox\WebAuth( $appInfo, $appName, DROPBOX_REDIR_URL, $csrfTokenStore );