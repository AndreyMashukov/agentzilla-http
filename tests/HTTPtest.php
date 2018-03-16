<?php

/**
 * PHP version 7.1
 *
 * @package Agentzilla\http
 */

namespace Tests;

use \Agentzilla\HTTP\HTTPclient;
use \PHPUnit\Framework\TestCase;
use \SimpleXMLElement;

/**
 * HTTP client test
 *
 * @author  Andrey Mashukov <a.mashukoff@gmail.com>
 * @version SVN: $Date: 2018-02-10 18:37:32 +0000 (Sat, 10 Feb 2018) $ $Revision: 2 $
 * @link    $HeadURL: https://svn.agentzilla.ru/http/trunk/tests/HTTPtest.php $
 *
 * @runTestsInSeparateProcesses
 */

class HTTPTest extends TestCase
    {

	/**
	 * Prepare data for testing
	 *
	 * @return void
	 */

	public function setUp()
	    {
		parent::setUp();
	    } //end setUp()


	/**
	 * Destroy testing data
	 *
	 * @return void
	 */

	public function tearDown()
	    {
		parent::tearDown();
	    } //end setUp()


	/**
	 * Should get page with proxy
	 *
	 * @return void
	 */

	public function testShouldGetPageWithProxy()
	    {
		define("SERVICE_NAME", "servicename");
		$xmlconfig = new SimpleXMLElement(file_get_contents(__DIR__ . "/datasets/configs/avito_irkutsk.xml"));
		$headers   = array();
		if (empty($xmlconfig->{"headers"}) === false)
		    {
			foreach ($xmlconfig->{"headers"}->{"header"} as $header)
			    {
				$headers[(string) $header["name"]] = (string) $header;
			    }
		    }

		$url  = "https://m.avito.ru/irkutsk/kvartiry/sdam/?s=0";
		$http = new HTTPclient($url, array(), $headers);
		$html = $http->getWithProxy();
		$this->assertContains("Аренда квартир - снять квартиру без посредников в Иркутске на Avito", gzdecode($html));
	    } //end testShouldGetPageWithProxy()


    } //end class

?>
