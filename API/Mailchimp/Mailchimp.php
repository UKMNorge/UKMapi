<?php
namespace UKMNorge\API;

require_once("Autoloader.php");
require_once("UKMconfig.inc.php");
require_once("UKM/curl.class.php");

use stdClass;
use Exception;
use UKMCURL;
use UKMNorge\API\Mailchimp\MCList;


/**
 *
 * Inneholder state-informasjon, f.eks "workflow running"?
 */
class Mailchimp {
	
	private $mailchimp_url;
	private $lists = null;
	private $pageSize = 50;
	private $result = null;
		
	public function __construct() {
		if( !defined("MAILCHIMP_API_BASE_URL") || !defined("MAILCHIMP_API_KEY")) {
			throw new Exception("Missing mailchimp defines - see Readme.md");
		}

		$this->mailchimp_url = MAILCHIMP_API_BASE_URL;

		if(substr($this->mailchimp_url, 0, 5) != "https") {
			throw new Exception("Can't use Mailchimp-API without https to ensure API Key secrecy.");
		}
	}

	/**
	 * Henter alle mail-lister fra Mailchimp, og returnerer de som et array.
	 * @return Array [List]
	 */
	public function getLists() {
		$this->lists = $this->sendGetRequest("lists", 0)->lists;
		return($this->lists);
	}

	/**
	 *
	 * @return MCList $list
	 * @throws Exception $list_not_found
	 */
	public function getList($id) {
		if($this->lists == null) {
			$this->getLists();
		}

		foreach($this->lists as $list) {
			if($list->id == $id) {
				return new MCList($list->id, $list->name, $list->permission_reminder, $list->stats);
			}
		}
		// Not found
		throw new Exception("List not found!");
	}

	/**
	 * @param MCList @list - the list object that has been modified.
	 * @return Boolean - true if all changes passed. False if some changes failed. Call getFailedUpdates to retrieve the failed ones to modify or try again.
	 */
	public function saveListChanges(MCList $list) {
		// You can add up to 500 members for each API call
		$limit = 500;
		$data['members'] = $list->getChangedSubscribers();
		if(count($data['members']) > $limit) {
			throw new Exception("Can only add 500 new members per API-call");
		}

		$data['update_existing'] = $list->willUpdateExistingSubscribers();

		$this->result = $this->sendPostRequest("lists/".$list->getId(), $data);

		if($this->result->error_count == 0) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Gets the raw result object
	 * @return stdClass
	 */
	public function getResult() {
		if($this->result == null) {
			throw new Exception("No result found");
		}

		return $this->result;
	}
	
	/**
	 * Returns an array of all failed updates
	 * @return Array
	 */
	public function getFailedUpdates() {
		if($this->result == null) {
			throw new Exception("No result found");
		}

		return $this->result->errors;
	}

	/**
	 * Returns the amount of fields that failed to update
	 * @return int
	 */
	public function getTotalFailed() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}

	/**
	 * Returns the amount of newly created fields
	 * @return int
	 */
	public function getTotalCreated() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}
	
	/**
	 * Returns the amount of updated fields
	 * @return int
	 */
	public function getTotalUpdated() {
		if($this->result == null) {
			throw new Exception("No result found");
		}
	}

	/**
	 * Adds a tag to a subscriber.
	 * @return bool
	 */
	public function addSubscriberToTag(MCList $list, $tag, $email) {
		// Verify that the list has the tag
		$taglist = $this->getAllTags($list);
		$tagId = null;
		foreach($taglist as $segment) {
			if($segment->name == $tag) {
				$tagId = $segment->id;
			}
		}

		if($tagId == null) {
			// Missing tag, creating it... Throws exception on failure.
			$tagId = $this->createTag($list, $tag);
		}

		$data['email_address'] = $email;
		$addResult = $this->sendPostRequest('/lists/'.$list->getId().'/segments/'.$tagId.'/members', $data);

		#var_dump($addResult);
		if($addResult == null) {
			throw new Exception("Curl-error occurred");
		}

		if(isset($addResult->status) && $addResult->status != 0) {
			throw new Exception("Mailchimp-error occurred: ".$addResult->detail);
		}

		return true;
	}

	/**
	 * Adds an array of clear-text tags to a subscriber.
	 * Tags don't need to exist, they are created on-the-fly if required.
	 *
	 * @return bool
	 */
	public function addTagsToSubscriber(MCList $list, $tags, $email) {
		foreach($tags as $tag) {
			$this->addSubscriberToTag($list, $tag, $email);
		}
		return true;
	}

	/** 
	 * Returns all tags that exist.
	 * Note: Tags belong to a list - but we'll only ever be using one list.
	 *
	 * @return Array
	 */
	public function getAllTags(MCList $list) {
		$tags_result = $this->sendGetRequest('/lists/'.$list->getId().'/segments');
		if( $tags_result == null ) {
			throw new Exception("A Curl-error occurred");
		}

		if( isset($tags_result->errors ) ) {
			throw new Exception("An error with Mailchimp occurred!");
		}
		return $tags_result->segments;
	}

	/**
	 * Creates a tag on a list 
	 *
	 * @return id of new list on success, throws Exception if failure
	 */
	public function createTag( MCList $list, $tag ) {
		if($tag == null) {
			throw new Exception("Tag must not be null.");
		}
		if( empty($tag) ) {
			throw new Exception("Tag must not be empty.");
		}

		$data = array();
		$data['name'] = $tag;
		$data['static_segment'] = array();
		
		$newTag = $this->sendPostRequest( '/lists/'.$list->getId().'/segments', $data);

		if($newTag == null | !is_object($newTag)) {
			throw new Exception("Mailchimp-request failed");
		}
		if( isset($newTag->status) ) {
			$un = uniqid();
			error_log($un." Mailchimp-request faild to create tag, error ".$newTag->status.". Error message: ". $newTag->detail);
			throw new Exception("Klarte ikke å opprette tag i Mailchimp - søk i loggen etter ".$un." for detaljer.");
		}
		return $newTag->id;
	}

	/**
	 * Sends a POST request to create new objects on the server
	 */
	private function sendPostRequest($resource, $data) {
		$url = $this->mailchimp_url."/".$resource;

		$curl = new UKMCURL();
		$curl->json($data);
		$curl->requestType("POST");
		$curl->user('userpwd:'.MAILCHIMP_API_KEY);
		$response = $curl->request($url);

		return $response;
	}

	/**
	 * Sends the request to the correct mailchimp server and parses the response, including any errors.
	 * @param String $resource - lists, total_subscribers, ping etc
	 */
	private function sendGetRequest($resource, $page = null) {
		$url = $this->mailchimp_url."/".$resource;
		if($page != null) {
			$url .= "?offset".$page;
			$url .= "&count".$this->pageSize*($page+1);
		}

		$curl = new UKMCURL();
		$curl->requestType("GET");
		$curl->user('userpwd:'.MAILCHIMP_API_KEY);

		$response = $curl->request($url);

		if($response == false) {
			// TODO: Do some error checking etc here.
		}

		return $response;
	}

	public function ping() : bool {
		$response = $this->sendGetRequest("ping", null);

		if(is_object($response)) {
			return true;
		}
		return false;
	}
}