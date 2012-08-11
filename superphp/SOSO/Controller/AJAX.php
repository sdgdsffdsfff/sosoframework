<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-ËÄÔÂ-2008 16:59:19
 */
class SOSO_Controller_AJAX /*extends SOSO_Object*/ implements SOSO_Controller_Abstract {
	public function __construct(){
		//parent::__construct();
		header('Content-Type: application/x-javascript');
	}
	
	public function dispatch($pClass=null){
		$class_name = strlen($pClass) ? $pClass : SOSO_Frameworks_Context::getInstance()->get('page_class');
		require_once("Tools/JPSpan/Server/PostOffice.php");
		$server = new JPSpan_Server_PostOffice();
		if (isset($_REQUEST['encoding']) && in_array($_REQUEST['encoding'],array('php','xml'))) {
			$server->RequestEncoding = $_REQUEST['encoding'];
		}
		
		$server->addHandler(new $class_name());
		$mode = SOSO_Frameworks_Config::getMode();
		if ($mode === 'online') {
			define('JPSPAN_INCLUDE_COMPRESS',TRUE);
		}else if ($mode === 'debug') {
			if (!defined('JPSPAN_ERROR_DEBUG')) {
				define('JPSPAN_ERROR_DEBUG',true);
			}
		}
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$server->displayClient();
		} else {
			require_once JPSPAN . 'ErrorHandler.php';
			$server->serve();
		}
	}
}