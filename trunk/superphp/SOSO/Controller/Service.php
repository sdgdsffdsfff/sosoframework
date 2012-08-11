<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:20
 */
class SOSO_Controller_Service /*extends SOSO_Object*/  implements SOSO_Controller_Abstract {

	/**
	 * @param View_Page $page 页面类对象
	 * 
	 * @param page
	 */
	public function dispatch($pClass=null){
		$class = strlen($pClass) ? $pClass : SOSO_Frameworks_Context::getInstance()->get('page_class');
		if (!class_exists($class)) {
			throw new Exception('页面类不存在',1025);
		}
		
		$tShow = SOSO_Frameworks_Config::getMode() != 'online';
		
		$server = new SOSO_Proxy_Server(new $class,array(
		   'displayInfo' => $tShow, 
		   'ignoreOutput'=> !$tShow
		 ));
		$server->displayInfo = $tShow;
		$server->service();
	}

}
?>