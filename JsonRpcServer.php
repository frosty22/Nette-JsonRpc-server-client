<?php

namespace JsonRpc;

/**
 * Description of JsonRpcServer
 * @author Ledvinka Vít
 * 
 * @property-read $onSuccessCall Event call on success
 * @property-read $onBeforeCall Event before call
 * @property-read $onExceptionCall Event call on exception
 * 
 */
class JsonRpcServer extends JsonRpc {
	
	/**
	 * Allow annotations
	 * @var bool
	 */
	public $allowAnnotation = true;
	
	/**
	 * Array of decodes request
	 * @var array
	 */
	private $json = array();
	
	/**
	 * Callbacks
	 * @var array
	 */
	private $callbacks = array();
	
	/**
	 * Instance of authorization
	 * @var IJsonRpcAuthorize 
	 */
	private $authorize;
	
	/**
	 * Executed
	 * @var bool
	 */
	private $executed = false;
	
	/**
	 * Can be execute
	 * @var bool
	 */
	private $can_executed = true;
	
	/**
	 * Event call on success call
	 * @var callback
	 * @param $method string name of called method
	 * @param $args array of parameters
	 * @param mixed return of called
	 * @return void
	 */
	private $onSuccessCall;
	
	/**
	 * Event before call
	 * @var callback
	 * @param $method string name of called method
	 * @param $args array of parameters
	 * @return void
	 */
	private $onBeforeCall;
	
	/**
	 * Event call on exception
	 * @var callback
	 * @param $method string name of called method
	 * @param $args array of parameters
	 * @param $exception \Exception get exception
	 * @return void
	 */
	private $onExceptionCall;
	
	/**
	 * Inicialize JsonRpcServer
	 * @param $post array of $POST data
	 */
	public function __construct(array $post = null) 
	{
		// if is not set data, get data from POST
		if (is_null($post)) $post = $_POST;
	 
		// if data for request JSON exists call it
		if (isset($post[self::POST_JSON_DATA])) 
			$this->json = $this->decodeJson($post[self::POST_JSON_DATA]);
		else
			$this->can_executed = false; 

		// Set default methods
		$this->add("getListOfMethod", array($this, "getListOfMethod"));
	}
	
	/**
	 * Set authorization of JsonRpcServer
	 * @param IJsonRpcAuthorize
	 */
	public function setAuthorize(IJsonRpcAuthorize $authorize)
	{
		$this->authorize = $authorize;
	}
	
	/**
	 * Set method to call
	 * @param $method string Name of method
	 * @param $callback callable
	 */
	public function add($method, $callback)
	{
		$this->checkCallback($method, $callback);
		$this->callbacks[$method] = $callback;
	}
	
	/**
	 * Execute of request
	 * @return void
	 */
	public function execute()
	{  
		if (!$this->can_executed) return;
		
		$return = array();
		$this->executed = true;
		if (isset($this->json["call"]))
			foreach ($this->json["call"] as $id => $method) {
				try {
					$return[$id]["return"] = $this->methodCall($method["name"], $method["args"]);
				} catch (\Exception $e) {
					if (isset($this->onExceptionCall))
						call_user_func_array($this->onExceptionCall, array($method["name"], $method["args"], $e));
					$return[$id]["exception"] = array();
					$return[$id]["exception"]["class"] = get_class($e);
					$return[$id]["exception"]["message"] = $e->getMessage();
					$return[$id]["exception"]["code"] = $e->getCode();
				}
			} 
		
		return $this->encodeJson(array(self::POST_JSON_DATA => $return));
	}
	
	/**
	 * Call method (check access if is set)
	 * @param $method string name of method
	 * @param $args array array of arguments
	 * @return mixed
	 */
	private function methodCall($method, $args = null)
	{
		if (isset($this->onBeforeCall)) 
			call_user_func_array($this->onBeforeCall, array($method, $args));
				
		if (isset($this->authorize)) {
			$accept = $this->authorize->checkAccess($method, $args);
			if (is_bool($accept) === false)
				throw new JsonRpcException("Method checkAccess must return boolean");
			if ($accept === false)
				throw new JsonRpcException("Unauthorized access");
		}		
		if (!isset($this->callbacks[$method]))
			throw new JsonRpcException("Method with name {$method} not exists");			
			
		$ref = $this->getReflection($method); 
		if ($ref->getNumberOfRequiredParameters() > count($args))
			throw new JsonRpcException("Called method {$method} except " . $ref->getNumberOfRequiredParameters() . ", but " . count($args) . " accepted.");
			
		$return = call_user_func_array($this->callbacks[$method], $args);
		
		if (isset($this->onSuccessCall)) 
			call_user_func_array($this->onSuccessCall, array($method, $args, $return));	
		
		return $return;
	}

	/**
	 * Get reflection of method
	 * @param string $method
	 * @return \Nette\Reflection\*
	 */
	private function getReflection($method)
	{
		if (is_array($this->callbacks[$method]))
			return new \Nette\Reflection\Method($this->callbacks[$method][0], $this->callbacks[$method][1]);
		if (is_string($this->callbacks[$method])) 
			return new \Nette\Reflection\Method($this->callbacks[$method]);
		return new \Nette\Reflection\GlobalFunction($this->callbacks[$method]);
	}

	/**
	 * Magic method
	 * @param $method string Name of method
	 * @param $callback callable
	 */
	public function __set($method, $callback)
	{
		$events = array("onSuccessCall","onBeforeCall", "onExceptionCall");
		if (in_array($method, $events)) {
			$this->checkCallback($method, $callback);
			$this->$method = $callback;
		}
		$this->add($method, $callback);
	}
	
	/*
	 * Get list of methods of server
	 * @return array
	 */
	private function getListOfMethod()
	{
		$r = array();
		foreach ($this->callbacks as $name => $cb) {			
			$ref = $this->getReflection($name);
			$r[$name] = array();
			$r[$name]["parameters"] = $ref->getParameters();
			if ($this->allowAnnotation && method_exists($ref, "getAnnotations"))
				$r[$name]["annotation"] = $ref->getAnnotations();
		}
		$r["getListOfMethod"]["annotation"]["description"][0] = "Get list of methods of server";
		$r["getListOfMethod"]["annotation"]["return"][0] = "array";
		return $r;
	}
	
	/**
	 * Check callback if is correct
	 * @param string $method
	 * @param mixed $callback 
	 */
	private function checkCallback($method, $callback)
	{
		if (!is_callable($callback))
			throw new JsonRpcException("Callback {$callback} is not callable");
		if (isset($this->callbacks[$method]))
			throw new JsonRpcException("Method with name {$method} is already exists");
		if ($this->executed)
			throw new JsonRpcException("Cannot add {$method} after execute it");		
	}
	
	
}


