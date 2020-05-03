<?php

namespace UKMNorge\Slack;

use Exception;

class Response
{
	var $response_type = null;
	var $attachments = null;
	var $text = null;
	var $actions = null;

	public function __construct($response_type, $text = null)
	{
		$this->setResponseType($response_type);
		if ($text !== null) {
			$this->setText($text);
		}

		$this->attachments = [];
		$this->actions = [];
	}

	public function setText($text)
	{
		$this->text = $text;
		return $this;
	}
	public function getText()
	{
		return $this->text;
	}


	public function addAttachment($attachment)
	{
		$this->attachments[$attachment->getId()] = $attachment;
	}

	public function getAttachments()
	{
		return $this->attachments;
	}
	public function hasAttachments()
	{
		return sizeof($this->getAttachments()) > 0;
	}
	public function getAttachment($id)
	{
		if (!isset($this->attachments[$id])) {
			throw new Exception('Kunne ikke finne vedlegget du søker etter');
		}
		return $this->attachments[$id];
	}

	public function renderToJSON()
	{
		return BuildJSON::response($this);
	}

	public function addAction($action)
	{
		$this->actions[$action->getId()] = $action;
		return $this;
	}
	public function getActions()
	{
		return $this->actions;
	}
	public function hasActions()
	{
		return sizeof($this->getActions()) > 0;
	}

	public function setResponseType($response_type)
	{
		if (!in_array($response_type, ['in_channel', 'ephemeral'])) {
			throw new Exception('Ukjent respons-type ' . $response_type);
		}
		$this->response_type = $response_type;
		return $this;
	}
	public function getResponseType()
	{
		return $this->response_type;
	}

	public function renderAndDie()
	{
		header('Content-Type: application/json');
		echo $this->renderToJSON();
		die();
	}
}
