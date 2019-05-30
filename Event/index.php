<?php

use MariusMandal\APIEvent;

Dispatcher::setHttpClient($httpClient);
Dispatcher::setStorage($storage);
Dispatcher::run();


interface HttpInterface
{
	public function post($url, $data);
	public function get($url, $parameters);
}

interface StorageInterface
{
	public function insert($model, $key_value_pairs);
	public function update($model, $id, $key_value_pairs);
	public function get($model, $where_key_value_pairs, $return = null);
	public function delete($model, $id);
}

interface StorageResponseInterface
{
	public function isSuccess();
	public function setId($id);
	public function getId();
	public function getErrorCode();
	public function getErrorMessage();
}

class Storage implements StorageInterface
{

	public function insert($model, $key_value_pairs)
	{
		$sql = new SQL($model);
		foreach ($key_value_pairs as $key => $value) {
			$sql->add($key, $value);
		}
		$res = $sql->run();
		if (!$res) {
			$response = new StorageResponse(false);
			$response->setError(0, $sql->getError());
		} else {
			$response = new StorageResponse(true);
			$response->setId($res);
		}

		return new StorageResponse(true);
	}
	public function get($model, $where_key_value_pairs, $return = null)
	{
		$query = "SELECT * FROM `". $model ."` WHERE";
		$sql = new SQL(
			"SELECT * FROM `#model`" $model,
			$key_value_pairs
		);
	}
}

class StorageResponse implements StorageResponseInterface
{
	private $isSuccess = null;
	private $errorCode = null;
	private $errorMessage = null;
	private $id = null;

	public function __construct(bool $success)
	{
		$this->isSuccess = $success;
	}

	public function setError(int $errorCode, String $errorMessage)
	{
		$this->errorCode = $errorCode;
		$this->errorMessage = $errorMessage;
	}

	public function isSuccess()
	{
		return $this->isSuccess();
	}
	public function getErrorCode()
	{
		return $this->errorCode;
	}
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}
	public function setId(int $id)
	{
		$this->id = $id;
		return $this;
	}
	public function getId()
	{
		return $this->id;
	}
}

class Dispatcher
{
	private $http = null;
	private $storage = null;

	public static function setHttpClient(HttpInterface $httpClient)
	{
		self::$http = $httpClient;
	}
	public static function setStorage(StorageInterface $storage)
	{
		self::$storage = $storage;
	}
}
