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

    public static function getButton()
    {
        return '<a href="https://slack.'. UKM_HOSTNAME .'/install">'
            .'<img alt="Add to Slack" height="40" width="139" '
            .'src="https://platform.slack-edge.com/img/add_to_slack.png" '
            .'srcSet="https://platform.slack-edge.com/img/add_to_slack.png 1x, '
            .'https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" />'
            .'</a>';
    }

    /**
     * Hent ønskede scopes
     *
     * @return Array scopes
     */
    public static function getScope()
    {
        return [
            /*
            @see getButton() which indirectly utilizes SLACK_SHAREABLE_URL
            'identify',
            'bot',
            'incoming-webhook',
            'users.profile:read',
            'chat:write:bot',
            'commands', # Add shortcuts and/or slash commands that people can use
            'users.profile:read' # View profile details about people in the workspace
            */
        ];
    }

    /**
     * Hent access token ut fra gitt Team ID
     *
     * Team ID kommer alltid med henvendelser fra Slack
     *
     * @param String $team_id
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

    /**
     * Hent bot access token ut fra gitt Team Id
     *
     * @param String $team_id
     * @return Bool success
     */
    public static function getBotTokenFromTeamId(String $team_id)
    {
        $sql = new Query(
            "SELECT `bot_token`
            FROM `#table`
            WHERE `team_id` = '#team'",
            [
                'table' => self::TABLE,
                'team' => $team_id
            ]
        );
        $token = $sql->run('field', 'bot_access_token');

        if (!$token) {
            $response = new Response(
                'ephemeral',
                ':sob: Beklager, UKM-bot\'en er ikke godkjent for ditt team enda. Kontakt support@ukm.no'
            );
            $response->renderAndDie();
        }
        App::setBotToken($token);
        return true;
    }
}
