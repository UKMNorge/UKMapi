<?php

namespace UKMNorge\Slack;

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
		$sql = new Insert(self::TABLE);
		$sql->add('team_id', $data->team_id);
		$sql->add('team_name', $data->team_name);
		$sql->add('access_token', $data->access_token);
		$sql->add('data', json_encode($data));

		$res = $sql->run();
		 if(!$res) {
			throw new Exception('Kunne ikke lagre data.');
		}
		return true;
    }
}