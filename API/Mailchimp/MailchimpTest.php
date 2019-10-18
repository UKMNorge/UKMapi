<?php

// No auto-loading.
require_once("Mailchimp.php");
require_once("MCList.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\API\Mailchimp;
use UKMNorge\API\Mailchimp\MCList;

class MailchimpTest extends TestCase {

	public function testInit() {
		$mailchimp = new Mailchimp();
		$this->assertInstanceOf(Mailchimp::class, $mailchimp);
		return $mailchimp;
	}

	/**
	 * @depends testInit
	 */
	public function testPing( $mailchimp ) {
		$this->assertTrue($mailchimp->ping());
	}

	/**
	 * @depends testInit
	 */
	public function testGetLists( $mailchimp ) {
		$lists = $mailchimp->getLists();
		$this->assertIsArray($lists);
		$this->assertInstanceOf(stdClass::class, $lists[0]);
	}

	/**
	 * @depends testInit
	 */
	public function testKnownGoodList( $mailchimp ) {
		$list = $mailchimp->getList("ca20f97cda");
		$this->assertInstanceOf(MCList::class, $list);
		$this->assertIsString($list->getName());
	}

	/**
	 * @depends testInit
	 */
	public function testNonExistentList( $mailchimp ) {
		$this->expectException(Exception::class);
		try {
			$mailchimp->getList("ikke-eksisterende-id");	
		} finally {
			$this->assertTrue(true);
		}
	}

	/**
	 * @depends testInit
	 */
	public function testGetAllTags( $mailchimp ) {
		$list = $mailchimp->getList('ca20f97cda');
		$tags = $mailchimp->getAllTags($list);
		$this->assertIsArray($tags);
	}

	/**
	 * @depends testInit
	 */
	public function testAddSubscriberToTag( $mailchimp ) {
		$list = $mailchimp->getList('ca20f97cda');
		$tag = 'test';
		$addResult = $mailchimp->addSubscriberToTag($list, $tag, 'asgeirsh@ukmmedia.no');

		$this->assertTrue($addResult);
	}

	/**
	 * @depends testInit
	 */
	public function testCreateTag( $mailchimp ) {
		$this->expectException(Exception::class);
		try {
			$list = $mailchimp->getList('ca20f97cda');
			$newTagId = $mailchimp->createTag($list, "test-2");
			$this->assertNotEqual(0, $newTagId);

			return $newTagId;
		} finally {
			$this->assertTrue(true);
		}
	}

	/**
	 * @depends testInit
	 */
	public function testAddTagsToSubscriber( $mailchimp ) {
		$list = $mailchimp->getList('ca20f97cda');
		$tags = array('test', 'test-2');

		$addResult = $mailchimp->addTagsToSubscriber($list, $tags, "asgeirsh@ukmmedia.no");
		$this->assertTrue($addResult);
	}

	/**
	 * @depends testInit
	 * @depends testCreateTag
	 */
	public function testDeleteTag( $mailchimp, $newTagId ) {
        $this->markTestIncomplete('The Delete Tag-test has not been implemented yet, as the functionality is not required for this version' );
		#$mailchimp->deleteTag(  )
	}
}