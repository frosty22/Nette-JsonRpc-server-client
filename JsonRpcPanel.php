<?php

namespace JsonRpc;

/**
 * Json Rpc panel for debugbar
 *
 * @author frosty
 */
class JsonRpcPanel extends \Nette\Object implements \Nette\Diagnostics\IBarPanel {

	/**
	 * Data of list methods
	 * @var array
	 */
	private $data;
	
	/**
	 * Set list of methods
	 * @param array $methods 
	 */
	public function __construct($methods)
	{
		$this->data = $methods;
	}

	/**
	 * Return tab
	 * @return string
	 */
	public function getTab()
	{
		return '<span title="RpcServer Panel">
				<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAkJJREFUeNqMU01IFVEU/u7M6BsdzEdIRYKFf2hBy/7BFm3auGhe0MpVQbpp1yISiiCCWrXRbdBKfHtBhIgIlCCSpBKy5wv7sVePDGVm7l/n3nlvngvF7nDnzpx7vu9855x7mdYajDHcn5yeps9QQ6M+mHkYiuM3CgXsMCzWvO4R+GjX4fDggQ5r1BYMS/xjvYJS+Wvx7uiVwk4EnvngCQ/Di6cgEkHGhgPh4Z3owcPJqRC7DEuglEZM4IX3ZcNrpdd1nBzson2FPQgkhJToyO+Hw2qhCW9gxi7lXgTkLITE7MsFOA6z+WurAhgZPg+u9K4Etoi3Hj3VYyPDqG7CEhjpklT9/FbC2loJSupUlSXWpNKZuHb18lhWREXGhKTOvHidKmAOAmyg+0gnzp4+B89rytoqpMBqeXV04tnUEJmO12qgkCQcly6csTVgjou5mSIG+wewXvlN6fFGzkTW39uHj8vLx7IamN5xoTD7at723iECxRXiaAt93Z0WxIVAk+sRmUDlV5UCiu1dUIgoykDvoYzg7fwnfC5/wXc6SEFrG4KgFVEUY3PrL+I4AqeUGwQ0k5jbTpgWuC5sRNd1iMyxdUnX2iS7FKkCB6kEJARu8dspG4WW3D5wzuGSc53EKEv/XVpd2t9GoCmqaVvMN6xDlPxBW3sei+8+QHCJXC4H38/R2kzKJN4sLpHiZCU7B9dvP37e7AdDun4RWCrZBycHDq1UdsGMnYo79+TBnZvkv8Rq17mH9vL4/1GluWKw/wQYABKpIQVMFZToAAAAAElFTkSuQmCC" />
				RpcServer
				</span>';
	}

	/**
	 * Return panel
	 * @return string
	 */
	public function getPanel()
	{ 
		$tpl = new \Nette\Templating\FileTemplate(__DIR__ . "/list-method.latte");
		$tpl->registerFilter(new \Nette\Latte\Engine);
		$tpl->methods = $this->data; 
		ob_start();
		$tpl->render();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	
}

