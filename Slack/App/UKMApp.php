<?php

namespace UKMNorge\Slack\App;

use UKMNorge\Slack\API\App;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Slack\Response;

class UKMApp extends App
{
    const TABLE = 'slack_access_token';

    /**
     * App redirect URL som satt opp i Slack
     * 
     * @return String url
     */
    public static function getOAuthRedirectUrlRaw()
    {
        return 'https://slack.' . UKM_HOSTNAME . '/auth/';
    }

    /**
     * Hent ønskede scopes
     *
     * @return Array scopes
     */
    public static function getScope()
    {
        return [
            'identify',
            'bot',
            'incoming-webhook',
            'users.profile:read',
            'chat:write:bot',
            'commands', # Add shortcuts and/or slash commands that people can use
            'users.profile:read' # View profile details about people in the workspace
        ];
    }

    /**
     * Hent access token ut fra gitt Team ID
     *
     * Team ID kommer alltid med henvendelser fra Slack
     *
     * @return Bool success
     */
    public static function setAPITokenFromTeamId($team_id)
    {
        $sql = new Query(
            "SELECT `access_token`
			FROM `#table`
			WHERE `team_id` = '#team'",
            [
                'table' => self::TABLE,
                'team' => $team_id
            ]
        );
        $token = $sql->run('field', 'access_token');

        if (!$token) {
            $response = new Response(
                'ephemeral',
                ':sob: Beklager, kan ikke se at teamet ditt er godkjent for bruk av denne appen. Kontakt support@ukm.no'
            );
            $response->renderAndDie();
        }
        App::setToken($token);
        return true;
    }
}
