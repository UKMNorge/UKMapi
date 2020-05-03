<?php

namespace UKMNorge\Slack\API;

use UKMNorge\Http\Curl;
use UKMNorge\Slack\Exceptions\CommunicationException;
use UKMNorge\Slack\Exceptions\InitException;
use UKMNorge\Slack\Exceptions\ResponseException;
use UKMNorge\Slack\Exceptions\TokenException;
use UKMNorge\Slack\Exceptions\VerificationException;

abstract class App implements AppInterface
{
    const SLACK_API_URL = 'https://slack.com/api/';

    protected static $id;
    protected static $secret;
    protected static $signing_secret;
    protected static $token;
    protected static $curl;
    protected static $bot_token;


    /**
     * Verify request origin
     *
     * @param String body of POST request
     * @return Bool
     * @throws VerificationException
     */
    public static function verifyRequestOrigin(String $request_body) {
        $version = explode('=',$_SERVER['HTTP_X_SLACK_SIGNATURE'])[0];
        
        $sign_data = 
            'v0:'. 
            $_SERVER['HTTP_X_SLACK_REQUEST_TIMESTAMP'] .
            ':'. $request_body;

        $signature = $version.'='. hash_hmac(
            'sha256',
            $sign_data,
            static::getSigningSecret()
        );

        if( !hash_equals($_SERVER['HTTP_X_SLACK_SIGNATURE'], $signature )) {
            throw new VerificationException('Could not verify that request originated from Slack');
        }
        return true;
    }

    /**
     * Initiate and identify app
     *
     * @param String $id
     * @param String $secret
     * @return void
     */
    public static function initFromAppDetails(String $id, String $secret, String $signing_secret)
    {
        static::setId($id);
        static::setSecret($secret);
        static::setSigningSecret($signing_secret);
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
     * Initiate app api access from bot token
     *
     * @param String $bot_token
     * @return void
     */
    public static function initFromBotToken(String $bot_token)
    {
        static::setBotToken($bot_token);
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
     * Set app signing secret
     * 
     * See Slack App Credentials for your app signing secret
     *
     * @param String signing secret
     * @return void
     */
    private static function setSigningSecret(String $secret)
    {
        static::$signing_secret = $secret;
    }

    /**
     * Get app signing secret
     *
     * @return String
     */
    public static function getSigningSecret() {
        if(is_null(static::$signing_secret)) {
            throw new InitException('signing');
        }
        return static::$signing_secret;
    }

    /**
     * Set Slack Access Token
     *
     * @param String $token
     * @return void
     */
    public static function setToken($token)
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
     * Set Slack Bot Access Token
     *
     * @param String $bot_token
     * @return void
     */
    public static function setBotToken(String $bot_token)
    {
        static::$bot_token = $bot_token;
    }

    /**
     * Get Slack Bot Access Token
     *
     * @return String
     */
    public static function getBotToken()
    {
        if (is_null(static::$bot_token)) {
            throw new InitException('bottoken');
        }

        return static::$bot_token;
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
			'redirect_uri' => static::getOAuthRedirectUrlRaw(false)
		]);
		$result = $curl->request( static::SLACK_API_URL . 'oauth.access');

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
            '?scope='. join(',',static::getScope()) .
			'&client_id='. static::getId() .
			'&redirect_uri='. static::getOAuthRedirectUrl();
    }

    /**
	 * Send user authenticated request to Slack API
     * 
     * @param String api endpoint id
     * @param Array data for query string
     * @return stdClass Slack api response
	 */
    public static function get( String $endpoint, Array $query_parameters = null ) {
        return static::_get(static::getToken(), $endpoint, $query_parameters);
    }

	/**
	 * Send POST request to Slack API
	 *
     * Uses static::$curl for potential debug purposes 
     * currently not implemented (auch)
     * 
	 * @param String api endpoint id
	 * @param Array json-data to post
     * @return stdClass Slack api response
	 */
	public static function post( String $endpoint, Array $json_data ) {
        return static::_post(static::getToken(), $endpoint, $json_data);
    }
    
    /**
     * Send bot authenticated request to Slack API
     *
     * @param String $endpoint
     * @param Array $query_parameters
     * @return stdClass Slack api response
     */
    public static function botGet( String $endpoint, Array $query_parameters = null ) {
        return static::_get(static::getBotToken(), $endpoint, $query_parameters);
    }
    
    /**
     * Send bot authenticated request to Slack API
     *
     * @param String $endpoint
     * @param Array $query_parameters
     * @return stdClass Slack api response
     */
    public static function botPost( String $endpoint, Array $json_data ) {
        return static::_post(static::getBotToken(), $endpoint, $json_data);
    }

    /**
     * Prepare get query
     *
     * @param String $token
     * @param String $endpoint
     * @param Array $data
     * @return stdClass Slack api response
     */
    private static function _get(String $token, String $endpoint, Array $data = null ) {
        $endpoint .= (!is_null($data) && sizeof($data) > 0) ?
        '?'. http_build_query( $data ) :
        '';
        return static::query('GET', $token, $endpoint);
    }

    /**
	 * Prepare post query
     *
     * @param String access token
     * @param String api endpoint id
	 * @param Array json-data to post
     * @return stdClass Slack api response
     */
    public static function _post(String $token, String $endpoint, Array $data ) {
        return static::query('POST', $token, $endpoint, $data);
    }

    /**
     * Send the authenticated request to slack
     * 
     * Uses static::$curl for potential debug purposes 
     * currently not implemented (auch)
     *
     * NOTE FOR GET: since http_build_query omits all key-value pairs with
     * null value, you should make sure every key has a not-null value. 
     * 
     * @param String GET|POST method
     * @param String $accessToken
     * @param String slack api endpoint url (without https://slack.com/api/)
     * @param Array additional data
     * @return stdClass Slack api response
     */
    private static function query( String $method, String $accessToken, String $endpoint, Array $data=null ) {
        static::$curl = new Curl();
        switch( $method ) {
            case 'POST':
            case 'post':
                static::$curl->json( $data );
            break;
        }
        static::$curl->addHeader('Authorization: Bearer '. $accessToken );
        $result = static::$curl->request( static::SLACK_API_URL . $endpoint);

        if( !$result->ok ) {
            switch( $result->error ) {
                case 'invalid_auth':
                    throw new TokenException('Invalid authentication given for request for endpoint '. $endpoint);
                case 'missing_scope':
                    throw new TokenException('Given token does not have the correct scope ('.$result->needed.') for endpoint '. $endpoint .'. Token has '. $result->provided);
            }
            throw new ResponseException('Unknown exception. Slack said '. $result->error .' ('. $endpoint .')');
        }

        return $result;
    }
}
