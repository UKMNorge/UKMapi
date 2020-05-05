<?php
	
namespace UKMNorge\Slack\API;

class Channels {    
    public static function getAll() {
        $request_data = [
            'types' => 'public_channel,private_channel,im',
        ];
        return App::botPost('conversations.list', $request_data);
    }
}