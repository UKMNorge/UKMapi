<?php
define('DROPBOX_APP_NAME', 'UKMdigark/1.0');
define('DROPBOX_REDIR_URL', 'https://'. UKM_HOSTNAME .'/wp-content/plugins/UKMsystem_tools/dropbox.php');

require_once('UKMconfig.inc.php');
require_once('Dropbox/vendor/autoload.php');

$appInfo = new Dropbox\AppInfo( DROPBOX_APP_ID, DROPBOX_APP_SECRET );
// Temp-store CSRF Token
$csrfTokenStore = new Dropbox\ArrayEntryStore( $_SESSION, 'dropbox-auth-csrf-token' );
// Define auth-details
$webAuth = new Dropbox\WebAuth( $appInfo, DROPBOX_APP_NAME, DROPBOX_REDIR_URL, $csrfTokenStore );
