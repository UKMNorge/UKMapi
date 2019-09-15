<?php

// No autoloading here, unfortunately
require_once("Mailchimp.php");
require_once("MCList.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\API\Mailchimp;
use UKMNorge\API\Mailchimp\MCList;


class MCListTest extends TestCase {
	public function testInit () {
		$mailchimp = new Mailchimp();
		$list = $mailchimp->getList('2e6ef5e9fc');
		$this->assertInstanceOf(MCList::class, $list);
		return $list;
	}


	/**
	 * @depends testInit
	 */
	public function testGetName($list) {
		$this->assertIsString($list->getName());
	}

}