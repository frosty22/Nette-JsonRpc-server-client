<?php

namespace JsonRpc;

/**
 *
 * @author frosty
 */
interface IJsonRpcAuthorize {
	
	/**
	 * Check access to method
	 * @param $method string name of method
	 * @param $args array array of arguments
	 * @return bool if access is accepted
	 */
	public function checkAccess($method, $args = null);
	
}


