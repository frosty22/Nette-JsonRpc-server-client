<?php

namespace JsonRpc;

/**
 * Base of JsonRpc
 *
 * @author frosty
 */
class JsonRpc {	

	/**
	 * Name of POST index of request
	 */
	const POST_JSON_DATA = "json-data";	
	
	/**
	 * Name of POST index of authorization
	 */
	const POST_AUTHORIZE = "json-authorize";
	
	/**
	 * Decode data from json
	 * @param string $json
	 * @return array
	 */
	public function decodeJson($json)
	{  
		$array = json_decode($json, true);
		switch (json_last_error()) {
			case 0: break;
			case JSON_ERROR_DEPTH: throw new \Exception("Limit of maximum stack depth exceeded");
			case JSON_ERROR_STATE_MISMATCH: throw new \Exception("Underflow or the modes mismatch");
			case JSON_ERROR_CTRL_CHAR: throw new \Exception("Unexpected control character found");
			case JSON_ERROR_SYNTAX: throw new \Exception("Syntax error, malformed JSON");
			case JSON_ERROR_UTF8: throw new \Exception("Malformed UTF-8 characters, possibly incorrectly encoded");
			default: throw new \Exception("Parse json request error");
		}
	
		return $array;
	}
	
	/**
	 * Encode data to JSON
	 * @param array $array
	 * @return string
	 */
	public function encodeJson(array $array)
	{
		return json_encode($array);
	}
	
	
}


