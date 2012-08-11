<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:19
 */
interface SOSO_Controller_Abstract {
	/**
	 * 执行指定页面类，由具体子类实现
	 * 
	 * @param page
	 */
	function dispatch($request=null);

}
?>