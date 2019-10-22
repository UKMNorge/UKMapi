<?php
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Dropbox;

require_once('UKMconfig.inc.php');
require_once('lib/autoload.php');

if( defined( 'DROPBOX_AUTH_ACCESS_TOKEN' ) ) {
	$DROPBOX_APP = new DropboxApp(DROPBOX_APP_ID, DROPBOX_APP_SECRET, DROPBOX_AUTH_ACCESS_TOKEN);
} else {
	$DROPBOX_APP = new DropboxApp(DROPBOX_APP_ID, DROPBOX_APP_SECRET);
}
$DROPBOX = new Dropbox( $DROPBOX_APP );
