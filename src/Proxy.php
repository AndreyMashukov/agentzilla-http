<?php

/**
 * PHP version 7.1
 *
 * @package Agentzilla\http
 */

namespace Agentzilla\HTTP;

/**
 * Proxy
 *
 * @author  Andrey Mashukov <a.mashukoff@gmail.com>
 * @version SVN: $Date: 2018-02-28 18:55:33 +0000 (Wed, 28 Feb 2018) $ $Revision: 3 $
 * @link    $HeadURL: https://svn.agentzilla.ru/http/trunk/src/Proxy.php $
 */

class Proxy
    {

	/**
	 * Prepare class to work
	 *
	 * @return void
	 */

	public function __construct()
	    {
	    } //end __construct()


	/**
	 * Get proxy type
	 *
	 * @param string $value Name of proxy type
	 *
	 * @return int Proxy type code
	 */

	private function _getProxyType(string $value)
	    {
		$types = array(
			  "SOCKS4" => CURLPROXY_SOCKS4,
			  "SOCKS5" => CURLPROXY_SOCKS5,
			  "HTTP"   => CURLPROXY_HTTP,
			 );

		return $types[$value];
	    } //end _getProxyType()


	/**
	 * Get new proxy
	 *
	 * @param string $servicename Name of service
	 *
	 * @return array New proxy
	 */

	public function get($servicename = false)
	    {
		if (file_exists(__DIR__ . "/data/lock/" . $servicename) === true && $servicename !== false)
		    {
			$result = json_decode(file_get_contents(__DIR__ . "/data/lock/" . $servicename), true);

			if (preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{1,6}/ui", $result["proxy"]) > 0)
			    {
				return $result;
			    } //end if

		    } //end if

		$goodlist = array();

		$http = new HTTPclient(AGENTZILLA . "/proxy/get.json", ["data" => sha1("maj736023")]);
		$res  = json_decode($http->post());

		if (isset($res->proxy) === true)
		    {
			if (preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{1,6}/ui", $res->proxy) > 0)
			    {
				$userslist = $this->_getUserAgents();
				$randagent = $userslist[rand(0, (count($userslist) - 1))];

				return array(
				        "proxy"     => trim($res->proxy),
				        "proxytype" => $this->_getProxyType($res->type),
				        "uagent"    => $randagent,
				       );
			    } //end if

		    } //end if

		exit();
	    } //end get()


	/**
	 * Lock current proxy by service
	 *
	 * @param string $proxy       Proxy
	 * @param string $servicename Service name
	 *
	 * @return void
	 */

	public function lock($proxy, $servicename)
	    {
		if (preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{1,6}/ui", $proxy["proxy"]) > 0)
		    {
			file_put_contents(__DIR__ . "/data/lock/" . $proxy["proxy"], $servicename . "\n" . (time() + 30));
			file_put_contents(__DIR__ . "/data/lock/" . $servicename, json_encode($proxy));
		    }
	    } //end lock()


	/**
	 * Fail (bad proxy)
	 *
	 * @param string $proxy       Proxy
	 *
	 * @return void
	 */

	public function fail($proxy)
	    {
		$http = new HTTPclient(AGENTZILLA . "/proxy/del.json", ["data" => sha1("maj736023"), "proxydata" => json_encode([
			"ip" => $proxy["proxy"],
		    ])
		]);
		$res  = json_decode($http->post());

		$data = explode("\n", file_get_contents(__DIR__ . "/data/lock/" . $proxy["proxy"]));

		unlink(__DIR__ . "/data/lock/" . $data[0]);
	    } //end fail()


	/**
	 * Bad proxy
	 *
	 * @param string $proxy Proxy
	 *
	 * @return void
	 */

	public function bad($proxy)
	    {
		$this->fail($proxy);
		file_put_contents(__DIR__ . "/data/blacklist/" . $proxy["proxy"], time());
	    } //end bad()


	/**
	 * Get users agents
	 *
	 * @return array Agents
	 */

	private function _getUserAgents():array
	    {
		$uagents = file_get_contents(__DIR__ . "/data/UserAgents.txt");
		return explode("\n", $uagents);
	    } //end _getUserAgents()


    } //end class

?>