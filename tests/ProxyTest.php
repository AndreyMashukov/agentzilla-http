<?php

/**
 * PHP version 7.1
 *
 * @package Agentzilla\http
 */

namespace Tests;

use \Agentzilla\HTTP\Proxy;
use \PHPUnit\Framework\TestCase;

/**
 * Proxy test
 *
 * @author  Andrey Mashukov <a.mashukoff@gmail.com>
 * @version SVN: $Date: 2018-02-10 18:37:32 +0000 (Sat, 10 Feb 2018) $ $Revision: 2 $
 * @link    $HeadURL: https://svn.agentzilla.ru/http/trunk/tests/ProxyTest.php $
 *
 * @runTestsInSeparateProcesses
 */

class ProxyTest extends TestCase
    {

	/**
	 * Prepare data for testing
	 *
	 * @return void
	 */

	public function setUp()
	    {
		define("AGENTZILLA", "https://agentzilla.ru");
	    } //end setUp()


	/**
	 * Tear down test data
	 *
	 * @return void
	 */

	public function tearDown()
	    {
		parent::tearDown();
	    } //end tearDown()


	/**
	 * Should return proxy data
	 *
	 * @return void
	 */

	public function testShouldReturnProxyData()
	    {
		$proxy    = new Proxy();
		$newproxy = $proxy->get("servicename");
		$this->assertRegExp("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]+/", $newproxy["proxy"]);
		$this->assertRegExp("/[0-9]{1}/", (string) $newproxy["proxytype"]);
		$this->assertRegExp("/.+/", $newproxy["uagent"]);
	    } //end testShouldReturnProxyData()


	/**
	 * Should lock proxy by service
	 *
	 * @return void
	 */

	public function testShouldLockProxyByService()
	    {
		$proxy    = new Proxy();
		$newproxy = $proxy->get("servicename");
		$this->assertRegExp("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]+/", $newproxy["proxy"]);
		$this->assertRegExp("/[0-9]{1}/", (string) $newproxy["proxytype"]);
		$this->assertRegExp("/.+/", $newproxy["uagent"]);

		$proxy->lock($newproxy, "servicename");
		$secondproxy = $proxy->get("servicename");
		$this->assertEquals($newproxy["proxy"], $secondproxy["proxy"]);
		$this->assertEquals($newproxy["proxytype"], (string) $secondproxy["proxytype"]);
		$this->assertEquals($newproxy["uagent"], $secondproxy["uagent"]);
		$proxy->bad($newproxy);

		$secondproxy = $proxy->get("servicename");
		$this->assertNotEquals($newproxy["proxy"], $secondproxy["proxy"]);
		$this->assertNotEquals($newproxy["uagent"], $secondproxy["uagent"]);
		$this->assertRegExp("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]+/", $secondproxy["proxy"]);
		$this->assertRegExp("/[0-9]{1}/", (string) $secondproxy["proxytype"]);
		$this->assertRegExp("/.+/", $secondproxy["uagent"]);

	    } //end testShouldLockProxyByService()


	/**
	 * Should not allow lock empty proxy by service
	 *
	 * @return void
	 */

	public function testShouldNotAllowLockEmptyProxyByService()
	    {
		$proxy    = new Proxy();
		$newproxy = $proxy->get("servicename");
		$this->assertRegExp("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]+/", $newproxy["proxy"]);
		$this->assertRegExp("/[0-9]{1}/", (string) $newproxy["proxytype"]);
		$this->assertRegExp("/.+/", $newproxy["uagent"]);

		$newproxy["proxy"] = "";

		$proxy->lock($newproxy, "servicename");
		$secondproxy = $proxy->get("servicename");
		$this->assertNotEquals($newproxy["proxy"], $secondproxy["proxy"]);
	    } //end testShouldNotAllowLockEmptyProxyByService()


    } //end class

?>
