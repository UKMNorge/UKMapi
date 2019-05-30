<?php

namespace UKMNorge\Trello;

use \Exception;
use \UKMCURL;

require_once('UKM/curl.class.php');

class Trello
{
	static $key = null;
	static $token = null;
	static $board = null;
	static $lists = null;

	public static function PUT($url, $data = null)
	{
		$curl = new UKMCURL();
		$curl->requestType('PUT');
		if ($data !== null) {
			$curl->json($data);
		}
		return $curl->request('https://api.trello.com/1/' . $url);
	}

	public static function POST($url, $data)
	{
		$curl = new UKMCURL();
		$curl->requestType('POST');
		$curl->post($data);
		return $curl->request('https://api.trello.com/1/' . $url);
	}

	public static function GET($url)
	{
		$curl = new UKMCURL();
		return $curl->request('https://api.trello.com/1/' . $url);
	}

	public static function setId($key, $token)
	{
		self::$key = $key;
		self::$token = $token;
	}

	public static function setBoardId($board_id)
	{
		self::$board = $board_id;
	}

	public static function getBoardId()
	{
		return self::$board;
	}

	public static function getBoard($board_id)
	{
		$result = self::GET('boards/' . $board_id . self::getId('?'));

		if (!is_object($result)) {
			var_dump($result);
			throw new Exception('Fant ikke board ' . $board_id);
		}

		return $result;
	}

	public static function getBoardLongId($board_id)
	{
		$board = self::getBoard($board_id);
		return $board->id;
	}

	public static function getId($prefix = '')
	{
		return $prefix . 'key=' . self::$key . '&token=' . self::$token;
	}

	public static function getBoardCustomFields($board_id)
	{
		$result = self::GET('boards/' . $board_id . '/customFields' . self::getId('?'));

		if (empty($result)) {
			var_dump($result);
			throw new Exception('Fant ikke boardets custom fields ' . $board_id);
		}

		return $result;
	}

	public static function createCard($list_id, $name, $description)
	{
		$result = self::POST(
			'cards?idList=' . $list_id . self::getId('&'),
			[
				'name' => $name,
				'desc' => $description,
			]
		);

		if (!is_object($result)) {
			throw new Exception('Kunne ikke opprette kort!', 201);
		}
		return $result->id;
	}

	public static function attachUrl($card_id, $url, $name=null)
	{
		$data = [
			'url' => $url
		];
		if( $name !== null ) {
			$data['name'] = $name;
		}
		$result = self::POST(
			'cards/'. $card_id .'/attachments'. self::getId('?'),
			$data
		);

		if (!is_object($result)) {
			throw new Exception('Kunne ikke legge til URL', 201);
		}
		return $result->id;
	}

	public static function setCustomField($card_id, $custom_field_id, $field_type, $value)
	{
		$value = [
			'value' =>
			[
				$field_type => $value
			]
		];

		$result = self::PUT(
			'card/' . $card_id . '/customField/' . $custom_field_id . '/item' . self::getId('?'),
			$value
		);

		if (!is_object($result)) {
			var_dump($result);
			throw new Exception('Kunne ikke legge til custom field til kort');
		}

		return $result;
	}

	public static function moveCard($card_id, $list_id)
	{
		$result = self::PUT('cards/' . $card_id . '?idList=' . $list_id . self::getId('&'));

		if (!is_object($result)) {
			var_dump($result);
			throw new Exception('Kunne ikke flytte kort');
		}

		return $result;
	}


	public static function createList($name)
	{
		try {
			$liste = self::getListByName($name);
		} catch (Exception $e) {
			$liste = self::POST(
				'lists?idBoard=' . self::getBoardLongId(self::getBoardId()) . '&name=' . urlencode($name) . self::getId('&'),
				[]
			);
		}
		return $liste;
	}

	public static function getLists()
	{
		if (null == self::$lists) {
			$response = self::GET('boards/' . self::getBoardId() . '/lists' . self::getId('?'));
			self::$lists = json_decode($response);
		}
		return self::$lists;
	}

	public static function getListByName($list_name)
	{
		foreach (self::getLists() as $list) {
			if (strtolower($list_name) == strtolower($list->name)) {
				return $list;
			}
		}
		throw new Exception('Fant ikke liste med navn ' . $list_name, 102);
	}

	public static function getListById($list_id)
	{
		foreach (self::getLists() as $list) {
			if ($list_id == $list->id) {
				return $list;
			}
		}
		throw new Exception('Fant ikke liste med id ' . $list_id, 101);
	}
}
