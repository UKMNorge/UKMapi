<?php

namespace UKMNorge\Slack\API;

use UKMNorge\Http\Curl;
use UKMNorge\Slack\API\Exceptions\InitException;
use UKMNorge\Slack\Exceptions\CommunicationException;

abstract class App implements AppInterface
{
    private static $id;
    private static $secret;
    private static $token;

    /**
     * Initiate and identify app
     *
     * @param String $id
     * @param String $secret
     * @return void
     */
    public static function initFromAppDetails(String $id, String $secret)
    {
        static::setId($id);
        static::setSecret($secret);
        static::$oauth = null;
    }

    /**
     * Initiate app api access from token
     *
     * @param String $token
     * @return void
     */
    public static function initFromToken(String $token)
    {
        static::setToken($token);
    }

    /**
     * Set app Id
     * 
     * See Slack App Credentials for your app id
     *
     * @param String $id
     * @return void
     */
    private static function setId(String $id)
    {
        static::$id = $id;
    }

    /**
     * Get app ID
     *
     * @return String
     */
    public static function getId()
    {
        if (is_null(static::$id)) {
            throw new InitException('idsecret');
        }
        return static::$id;
    }

    /**
     * Set app secret
     * 
     * See Slack App Credentials for your app secret
     *
     * @param String $secret
     * @return void
     */
    private static function setSecret(String $secret)
    {
        static::$secret = $secret;
    }

    /**
     * Get app secret
     *
     * @return String
     */
    public static function getSecret() {
        if(is_null(static::$secret)) {
            throw new InitException('idsecret');
        }
        return static::$secret;
    }

    /**
     * Set Slack Access Token
     *
     * @param String $token
     * @return void
     */
    private static function setToken($token)
    {
        static::$token = $token;
    }

    /**
     * Get Slack Access Token
     *
     * @return String
     */
    public static function getToken()
    {
        if (is_null(static::$token)) {
            throw new InitException('token');
        }

        return static::$token;
    }

    /**
     * Get encoded oAuth redirect url
     *
     * @return String urlencoded redirect url
     */
	public static function getOAuthRedirectUrl() {
        return urlencode( static::getOAuthRedirectUrlRaw() );
	}

    /**
     * Get oAuth access token
     *
     * @param String $code
     * @return mixed curl request result
     */
	public static function getOAuthAccessToken( $code ) {
		$curl = new Curl();
		$curl->post([
			'client_id' => static::getId(),
			'client_secret' => static::getSecret(),
			'code' => $code,
			'redirect_uri' => static::getRedirectUrlRaw(false)
		]);
		$result = $curl->request('https://slack.com/api/oauth.access');

		if( is_object( $result ) && $result->ok ) {
			return $result;
		}

		throw new CommunicationException(
			'Could not get access token. Slack said: '.
			$result->error,
			181003
		);
	}

    /**
     * Get "Add to Slack"-button
     *
     * @return String html button
     */
	public static function getButton() {
		return '<a href="'. static::getAuthUrl() .'">'.
			'<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" '.
				' srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" />'.
			'</a>';
    }
    
    /**
     * Get Auth URL for add to slack
     *
     * @return String url
     */
    public static function getAuthUrl() {
        return 'https://slack.com/oauth/authorize'.
            '?scope='. static::getScope() .
			'&client_id='. static::getId() .
			'&redirect_uri='. static::getRedirectUrl();
    }
}
