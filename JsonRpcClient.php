<?php

namespace JsonRpc;

/**
 * Description of JsonRpcClient
 *
 * @author frosty
 */
class JsonRpcClient extends JsonRpc {
	
	/**
	 * Default exception of response 
	 * if not return exists exception
	 */
	const DEFAULT_EXCEPTION_RESPONSE = '\Exception';
	
	/**
	 * Url of server
	 * @var string
	 */
	private $url;
	
	/**
	 * Array of call method
	 * @var array
	 */
	private $call = array();
	
	/**
	 * Array of results
	 * @var array
	 */
	private $results = array();
	
	/**
	 * Inicialize JsonRpcClient
	 * @param string $url URL of server
	 */
	public function __construct($url) 
	{
		if (empty($url))
			throw new JsonRpcException("Url of server must be set");
		$this->url = $url;
	}

	/**
	 * Magic method
	 * @param string $method name of method
	 * @param array $args array of arguments 
	 */
	public function __call($method, $args = array())
	{  
		return $this->callRemoteMethod($method, $args);
	}

	/**
	 * Call remote method
	 * @param string $method name of method
	 * @param array $args array of arguments 
	 */	
	public function callRemoteMethod($method, $args = array())
	{
		$index = $this->addToCall($method, $args);
		$response = $this->execute();
		$return = $this->decodeJson($response);
		$this->results = $return[self::POST_JSON_DATA];
		
		if (isset($this->results[$index]["exception"])) {
			$class = self::DEFAULT_EXCEPTION_RESPONSE; 
			if (class_exists($this->results[$index]["exception"]["class"])) 
				$class = $this->results[$index]["exception"]["class"]; 
			throw new $class($this->results[$index]["exception"]["message"], $this->results[$index]["exception"]["code"]);
		}
		
		return $this->results[$index]["return"];
	}

	/**
	 * Add method to list of calls
	 * @param string $method name of method
	 * @param array $args array of arguments
	 */
	private function addToCall($method, $args)
	{
		$index = count($this->call);
		$this->call[$index] = array("name" => $method, "args" => $args);
		return $index;
	}
	
	/**
	 * Inicialize request
	 * @return string json
	 */
	private function init()
	{	
		if (!count($this->call))
			throw new JsonRpcException("Queue of methods to call doesnt exists");
		
		return array(self::POST_JSON_DATA => $this->encodeJson(array("call" => $this->call)));
	}
	
	/**
	 * Execute request
	 * @return string
	 */
	private function execute()
	{ 
		$fields = http_build_query($this->init());
		$ch = \curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($fields))); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		if (isset($info["http_code"]) && ($info["http_code"] == 500))
			throw new JsonRpcException("RPC server error");
	
		return $output;
	}
	
	/**
	 * Get list of methods of server
	 * @param $debugbar add methods to nette debugbar
	 * @return array
	 */
	public function getListOfMethod($debugbar = true) 
	{
		$r = $this->callRemoteMethod("getListOfMethod");
		if ($debugbar) \Nette\Diagnostics\Debugger::addPanel(new \JsonRpc\JsonRpcPanel($r));
		return $r;
	}
	
	
	
	
}


