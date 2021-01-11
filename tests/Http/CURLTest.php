<?php

require_once("UKM/Autoloader.php");

use PHPUnit\Framework\TestCase;
use stdClass;
use UKMNorge\Http\Curl;

class CURLTest extends TestCase {

    public function testBasicGoogle() {
        $curl = new CURL();
        $curl->request('https://www.google.com');

        $this->assertNull($curl->error());
    }

    public function testJson() {
        $curl = new CURL();
        
        $result = $curl->request('https://jsonplaceholder.typicode.com/todos/1');

        $this->assertInstanceOf(StdClass::class, $result);
    }

    public function testGetData() {
        $curl = new CURL();
        
        $curl->request('https://jsonplaceholder.typicode.com/todos/1');
        $result = $curl->getData();

        $this->assertInstanceOf(StdClass::class, $result);
    }

    public function testGetRawResult() {
        $curl = new CURL();
        
        $curl->request('https://www.google.com');
        $result = $curl->getResult();
        $this->assertIsString($result);
    }

}
