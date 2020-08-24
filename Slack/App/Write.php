<?php

namespace UKMNorge\Slack\App;

use Exception;
use UKMNorge\Database\SQL\Insert;

class Write {
    /**
	 * Lagre nylig mottatt access token
	 *
	 * @param stdClass data
	 * @return Bool true
	 * @throws Exception
	 */
	public static function storeAPIAccessToken($data)
	{
		$sql = new Insert(UKMApp::TABLE);
		$sql->add('team_id', $data->team->id);
		$sql->add('team_name', $data->team->name);
        $sql->add('access_token', $data->authed_user->access_token);
        $sql->add('bot_id', $data->bot_user_id);
        $sql->add('bot_access_token', $data->access_token);

		$sql->add('data', json_encode($data));

		$res = $sql->run();
		 if(!$res) {
			throw new Exception('Kunne ikke lagre data.');
		}
		return true;
    }
}