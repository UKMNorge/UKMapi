<?php

namespace UKMNorge\Slack\API;

interface AppInterface {
    /**
     * Get app scope
     * 
     * Returns CSV-list of requested scopes according to Slack docs
     * @see https://api.slack.com/scopes
     *
     * @return Array scopes
     */
    public static function getScope();

    /**
     * Get the redirect url for your app
     * 
     * Remember to see Slack requirements for redirect urls
     * @see https://api.slack.com/authentication/oauth-v2#redirect_urls
     *
     * @return String HTML
     */
    public static function getOAuthRedirectUrlRaw();
}