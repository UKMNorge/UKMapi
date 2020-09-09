<?php

namespace UKMNorge\Slack\API;

class Conversations
{
    /**
     * Start (or resume) a conversation with given user
     * 
     * @param String slack id
     * @return String slack response
     */
    public static function startWithUser(String $user_id)
    {
        return static::start(['users' => $user_id]);
    }

    /**
     * Start (or resume) a conversation in a channel
     *
     * @param String $channel_id
     * @return String slack response
     */
    public static function startWithChannel(String $channel_id)
    {
        return static::start(['channel' => $channel_id]);
    }

    /**
     * Start (or resume) a dm with multiple users
     *
     * @param Array slack ids
     * @return String slack response
     */
    public static function startWithUsers(array $user_ids)
    {
        return static::start(['users' => implode(',', $user_ids)]);
    }

    /**
     * Actually start a conversation
     *
     * @param Array $data
     * @return String slack response
     */
    private static function start(array $data)
    {
        $request_data = array_merge(
            [
                'types' => 'public_channel,private_channel,im',
            ],
            $data
        );
        return App::botPost('conversations.open', $request_data);
    }
}
