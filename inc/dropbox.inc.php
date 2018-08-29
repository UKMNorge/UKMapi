<?php
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Dropbox;

define('DROPBOX_APP_NAME', 'UKMdigark/1.0');
define('DROPBOX_REDIR_URL', 'https://'. UKM_HOSTNAME .'/wp-content/plugins/UKMsystem_tools/dropbox.php');

require_once('UKMconfig.inc.php');
require_once('Dropbox/vendor/autoload.php');

if( defined( 'DROPBOX_AUTH_ACCESS_TOKEN' ) ) {
	$DROPBOX_APP = new DropboxApp(DROPBOX_APP_ID, DROPBOX_APP_SECRET, DROPBOX_AUTH_ACCESS_TOKEN);
} else {
	$DROPBOX_APP = new DropboxApp(DROPBOX_APP_ID, DROPBOX_APP_SECRET);
}
$DROPBOX = new Dropbox( $DROPBOX_APP );