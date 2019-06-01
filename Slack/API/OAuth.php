<?php
	
namespace UKMNorge\Slack\API;
use UKMCURL;
use Exception;

class OAuth extends API {

	public static function getRedirectUrl($encode=true) {
		$url = 'https://slack.'. UKM_HOSTNAME .'/auth/';
		if( $encode ) {
			return urlencode( $url );
		}
		return $url;
	}

	public static function access( $code ) {
		$curl = new UKMCURL();
		$curl->post([
			'client_id' => SLACK_CLIENT_ID,
			'client_secret' => SLACK_CLIENT_SECRET,
			'code' => $code,
			'redirect_uri' => self::getRedirectUrl(false)
		]);
		$result = $curl->request('https://slack.com/api/oauth.access');

		if( is_object( $result ) && $result->ok ) {
			return $result;
		}

		throw new Exception(
			'Could not get access token. Slack said: '.
			$result->error,
			181003
		);
	}

	public static function getButton() {
		return '<a href="'.
			'https://slack.com/oauth/authorize?scope=incoming-webhook,commands,users.profile:read'.
			'&client_id='. SLACK_CLIENT_ID .
			'&redirect_uri='. self::getRedirectUrl() .
			'">'.
			'<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" '.
				' srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" />'.
			'</a>';
	}
}

