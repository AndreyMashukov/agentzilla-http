<?php

/**
 * PHP version 7.1
 *
 * @package Agentzilla\http
 */

namespace Agentzilla\HTTP;

use \CURLFile;

/**
 * HTTP client
 *
 * @author  Andrey Mashukov <a.mashukoff@gmail.com>
 * @version SVN: $Date: 2018-02-10 18:37:32 +0000 (Sat, 10 Feb 2018) $ $Revision: 2 $
 * @link    $HeadURL: https://svn.agentzilla.ru/http/trunk/src/HTTPclient.php $
 */

class HTTPclient
    {

	const USERAGENT = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12 GTB7.1 ( .NET CLR 3.5.30729; .NET4.0E)";

	/**
	 * A cURL session
	 *
	 * @var resourse
	 */
	protected $ch;

	/**
	 * Proxy
	 *
	 * @var Proxy
	 */
	private $_proxy;

	/**
	 * A COOKIE jar
	 *
	 * @var string
	 */
	protected $cookiejar;

	/**
	 * URL for HTTP request
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Associative array containing NVPs to be passed as HTTP headers
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * Associative array containing NVPs to be passed with HTTP request
	 *
	 * @var array
	 */
	protected $request;

	/**
	 * Associative array containing cURL options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Result cURL session
	 *
	 * @var bool
	 */
	protected $lastresult;

	/**
	 * Last received HTTP code or false
	 *
	 * @var mixed
	 */
	protected $lastcode;

	/**
	 * Proxylist
	 *
	 * @var mixed
	 */
	protected $proxylist;

	/**
	 * Last headers
	 *
	 * @var array
	 */
	protected $lastheaders;

	/**
	 * Construct HTTP client and set HTTP request parameters
	 *
	 * @param string $url     URL for HTTP request
	 * @param array  $request Associative array containing NVPs to be passed with HTTP request
	 * @param array  $headers Associative array containing NVPs to be passed as HTTP headers
	 * @param array  $config  Optional cURL configuration
	 *
	 * @return void
	 */

	public function __construct($url, array $request = array(), array $headers = array(), array $config = array())
	    {
		$this->config = $config;

		if (isset($this->config["useragent"]) === false)
		    {
			$this->config["useragent"] = self::USERAGENT;
		    }

		if (isset($this->config["verbose"]) === false)
		    {
			$this->config["verbose"] = false;
		    }

		if (isset($this->config["followlocation"]) === false)
		    {
			$this->config["followlocation"] = true;
		    }

		$this->__wakeup();
		$this->cookiejar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid(mt_rand(), true));

		$this->setRequest($url, $request, $headers);
	    } //end __construct()


	/**
	 * Wakeup magic method: recreates cURL handle on object unserialization
	 *
	 * @return void
	 */

	public function __wakeup()
	    {
		$this->ch = curl_init();

		foreach ($this->config as $option => $value)
		    {
			switch ($option)
			    {
				case "verbose":
					curl_setopt($this->ch, CURLOPT_VERBOSE, $value);
				    break;
				case "followlocation":
					curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $value);
				    break;
				case "proxy":
					curl_setopt($this->ch, CURLOPT_PROXY, $value);
				    break;
				case "proxytype":
					curl_setopt($this->ch, CURLOPT_PROXYTYPE, $value);
				    break;
				case "useragent":
					curl_setopt($this->ch, CURLOPT_USERAGENT, $value);
				    break;
				case "maxredirects":
					curl_setopt($this->ch, CURLOPT_MAXREDIRS, $value);
				    break;
				case "timeout":
					curl_setopt($this->ch, CURLOPT_TIMEOUT, $value);
				    break;
				case "keepalive":
					curl_setopt($this->ch, CURLOPT_FORBID_REUSE, $value === false);
				    break;
				default:
				    break;
			    } //end switch
		    } //end foreach
	    } //end __wakeup()


	/**
	 * Set HTTP request parameters
	 *
	 * @param string $url     URL for HTTP request
	 * @param array  $request Associative array containing NVPs to be passed with HTTP request
	 * @param array  $headers Associative array containing NVPs to be passed as HTTP headers
	 * @param array  $files   Files to upload
	 *
	 * @return void
	 *
	 * @untranslatable scheme
	 * @untranslatable user
	 * @untranslatable pass
	 * @untranslatable host
	 * @untranslatable port
	 * @untranslatable path
	 */

	public function setRequest($url, array $request = array(), array $headers = array(), array $files = array())
	    {
		$parts    = parse_url($this->url);
		$newparts = parse_url($url);

		if (isset($parts["path"]) === true && isset($newparts["path"]) === true)
		    {
			if (dirname($newparts["path"]) === ".")
			    {
				$dirname          = dirname($parts["path"]);
				$newparts["path"] = (($dirname === "/") ? "" : $dirname) . "/" . $newparts["path"];
			    }
		    }

		foreach ($newparts as $idx => $value)
		    {
			$parts[$idx] = $value;
		    }

		if (isset($newparts["host"]) === true && isset($newparts["port"]) === false)
		    {
			unset($parts["port"]);
		    }

		$this->url = $this->_part($parts, "", "scheme", "://") .
			     $this->_part($parts, "", "user", $this->_part($parts, ":", "pass", "") . "@") .
			     $this->_part($parts, "", "host", "") .
			     $this->_part($parts, ":", "port", "") .
			     $this->_part($parts, "", "path", "");

		if (isset($parts["query"]) === true)
		    {
			parse_str($parts["query"], $query);
			$this->request = array_merge($request, $query);
		    }
		else
		    {
			$this->request = $request;
		    }

		$this->_addFiles($files);
		$this->headers     = $headers;
		$this->lastresult  = false;
		$this->lastcode    = false;
		$this->lastheaders = array();
	    } //end setRequest()


	/**
	 * Add files to request
	 *
	 * @param array $files Files to upload
	 *
	 * @return void
	 */

	private function _addFiles(array $files)
	    {
		foreach ($files as $idx => $file)
		    {
			if (isset($file["name"]) === true && isset($file["mime"]) === true && isset($file["postname"]) === true)
			    {
				$this->request[$idx] = new CURLFile($file["name"], $file["mime"], $file["postname"]);
			    }
			else if (isset($file["name"]) === true && isset($file["mime"]) === true)
			    {
				$this->request[$idx] = new CURLFile($file["name"], $file["mime"]);
			    }
			else if (isset($file["name"]) === true)
			    {
				$this->request[$idx] = new CURLFile($file["name"]);
			    }
		    }
	    } //end _addFiles()


	/**
	 * Build URL part out of array containing parts and relevant prefix/suffix
	 *
	 * @param array  $parts  Array containing URL parts
	 * @param string $prefix Prefix to be added
	 * @param string $name   Part name to be used
	 * @param string $suffix Suffix to be added
	 *
	 * @return string conaining part of URL
	 */

	private function _part(array $parts, $prefix, $name, $suffix)
	    {
		return ((isset($parts[$name]) === true) ? $prefix . $parts[$name] . $suffix : "");
	    } //end _part()


	/**
	 * Execute HTTP GET request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return mixed false on failure or HTTP page content
	 *
	 * @untranslatable GET
	 */

	public function get($retries = 1)
	    {
		return $this->_execute("GET", $retries);
	    } //end get()


	/**
	 * Get http request wuth proxy
	 *
	 * @return string Result
	 */

	public function getWithProxy()
	    {
		$good = false;
		$n    = 0;
		while (true)
		    {
			if ($good === false)
			    {
				$this->_proxy    = new Proxy();
				$this->proxylist = $this->_proxy->get(SERVICE_NAME);
				$this->_proxy->lock($this->proxylist, SERVICE_NAME);
				$this->config = array(
						 "proxy"     => $this->proxylist["proxy"],
						 "proxytype" => $this->proxylist["proxytype"],
						 "timeout"   => 30,
						 "useragent" => $this->proxylist["uagent"],
						);
			    }

			if (isset($this->config["useragent"]) === false)
			    {
				$this->config["useragent"] = self::USERAGENT;
			    }

			if (isset($this->config["verbose"]) === false)
			    {
				$this->config["verbose"] = false;
			    }

			if (isset($this->config["followlocation"]) === false)
			    {
				$this->config["followlocation"] = true;
			    }

			$this->__wakeup();
			$this->cookiejar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid(mt_rand(), true));

			$this->setRequest($this->url, $this->request, $this->headers);

			$html = $this->get();

			if ($this->lastcode() !== 200 && $this->lastcode() !== 0 && $this->lastcode() !== 404)
			    {
				$good = false;
				$this->_proxy->bad($this->proxylist);
				continue;
			    }
			else if ($this->lastcode() === 200 && preg_match("/>Доступ с Вашего IP временно ограничен/ui", gzdecode($html)) > 0)
			    {
				$good = false;
				$this->_proxy->bad($this->proxylist);
				continue;
			    }
			else if ($this->lastcode() === 0)
			    {
				if ($n > 1)
				    {
					$n = 0;
					$good = false;
					$this->_proxy->fail($this->proxylist);
					continue;
				    }

				$n++;
				continue;
			    }
			else if ($this->lastcode() === 404)
			    {
				$html = null;
				break;
			    }

			$n = 0;

			$this->_proxy->lock($this->proxylist, SERVICE_NAME);

			$good = true;
			break;
		    }

		return $html;
	    } //end getWithProxy()


	/**
	 * Execute HTTP POST request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return mixed false on failure or HTTP page content
	 *
	 * @untranslatable POST
	 */

	public function post($retries = 1)
	    {
		return $this->_execute("POST", $retries);
	    } //end post()


	/**
	 * Execute HTTP PUT request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return mixed false on failure or HTTP page content
	 *
	 * @untranslatable PUT
	 */

	public function put($retries = 1)
	    {
		return $this->_execute("PUT", $retries);
	    } //end put()


	/**
	 * Execute HTTP DELETE request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return mixed false on failure or HTTP page content
	 *
	 * @untranslatable DELETE
	 */

	public function delete($retries = 1)
	    {
		return $this->_execute("DELETE", $retries);
	    } //end delete()


	/**
	 * Returns last HTTP code
	 *
	 * @return int
	 */

	public function lastcode()
	    {
		return $this->lastcode;
	    } //end lastcode()


	/**
	 * Returns last HTTP headers
	 *
	 * @return array
	 */

	public function lastheaders()
	    {
		return $this->lastheaders;
	    } //end lastheaders()


	/**
	 * Returns HTTP header from last call
	 *
	 * @param string $header Header name
	 *
	 * @return string Header value
	 */

	public function httpheader($header)
	    {
		return ((isset($this->lastheaders[$header]) === true) ? trim($this->lastheaders[$header]) : false);
	    } //end httpheader()


	/**
	 * Execute HTTP request
	 *
	 * @param bool $method  HTTP method name
	 * @param int  $retries Number of attempts to be made to fetch the page before giving up
	 *
	 * @return mixed false on failure or HTTP page content
	 *
	 * @untranslatable headerCallback
	 */

	private function _execute($method, $retries)
	    {
		if ($this->lastresult === false)
		    {
			$try = 0;
			do
			    {
				$try++;

				$headers = $this->_prepareHeaders();
				$params  = $this->prepareParameters();

				switch ($method)
				    {
					case "GET":
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
						curl_setopt($this->ch, CURLOPT_POST, false);
						curl_setopt($this->ch, CURLOPT_URL, $this->url . ((is_array($params) === false && $params !== "") ? "?" . $params : ""));
					    break;
					case "POST":
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
						curl_setopt($this->ch, CURLOPT_POST, true);
						curl_setopt($this->ch, CURLOPT_URL, $this->url);
						curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
					    break;
					case "DELETE":
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
						curl_setopt($this->ch, CURLOPT_POST, false);
						curl_setopt($this->ch, CURLOPT_URL, $this->url . ((is_array($params) === false && $params !== "") ? "?" . $params : ""));
					    break;
					case "PUT":
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
						curl_setopt($this->ch, CURLOPT_POST, true);
						curl_setopt($this->ch, CURLOPT_URL, $this->url);
						curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
					    break;
				    } //end switch

				curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($this->ch, CURLOPT_HEADER, false);
				curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this, "headerCallback"));
				if (count($headers) > 0)
				    {
					curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
				    }

				curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookiejar);
				curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookiejar);
				$this->lastresult = curl_exec($this->ch);
				$this->lastcode   = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
			    } while ($this->lastresult === false && $try < $retries);
		    } //end if

		return $this->lastresult;
	    } //end _execute()


	/**
	 * Prepare HTTP headers
	 *
	 * @return array containing HTTP headers
	 */

	private function _prepareHeaders()
	    {
		$headers = array();
		foreach ($this->headers as $index => $value)
		    {
			if (is_array($value) === true)
			    {
				foreach ($value as $item)
				    {
					$headers[] = $index . ": " . $item;
				    }
			    }
			else
			    {
				$headers[] = $index . ": " . $value;
			    }
		    }

		return $headers;
	    } //end _prepareHeaders()


	/**
	 * Prepare HTTP parameters
	 *
	 * @return mixed String containing URL encoded list of parameters or array of post parameters
	 */

	protected function prepareParameters()
	    {
		if (isset($this->request[""]) === true)
		    {
			return $this->request[""];
		    }
		else
		    {
			$files  = array();
			$params = array();
			foreach ($this->request as $idx => $param)
			    {
				if ($param instanceof CURLFile)
				    {
					$files[$idx] = $param;
				    }
				else
				    {
					$params[$idx] = $param;
				    }
			    }

			$reconstructed = array();
			if (empty($params) === false)
			    {
				$query = http_build_query($params);
				$list  = explode("&", $query);
				foreach ($list as $element)
				    {
					list($idx, $param)              = explode("=", $element, 2);
					$reconstructed[urldecode($idx)] = urldecode($param);
				    }
			    }

			if (empty($files) === false)
			    {
				foreach ($files as $idx => $file)
				    {
					$reconstructed[$idx] = $file;
				    }

				return $reconstructed;
			    }
			else
			    {
				return http_build_query($reconstructed);
			    }
		    } //end if
	    } //end prepareParameters()


	/**
	 * Header callback function for cURL
	 *
	 * @param resource $ch         The cURL handle
	 * @param string   $headerline One line from returned headers
	 *
	 * @return int Header length
	 */

	protected function headerCallback($ch, $headerline)
	    {
		unset($ch);
		if (strpos($headerline, ":") !== false)
		    {
			list($header, $value)       = explode(":", $headerline, 2);
			$this->lastheaders[$header] = trim($value);
		    }

		return strlen($headerline);
	    } //end headerCallback()


    } //end class

?>
