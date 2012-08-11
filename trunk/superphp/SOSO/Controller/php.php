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
class SOSO_Controller_PHP /*extends SOSO_Object*/ implements SOSO_Controller_Abstract{
	public $mRequest;
	public $mAction;
	public $mParameters;
	public function __construct(){
		//parent::__construct();
		$class = SOSO_Frameworks_Context::getInstance()->get('page_class');
		if (!class_exists($class)) {
			throw new Exception("{$class} 类页面不存在",1025);
		}
		if (!isset($_REQUEST['method']) || empty($_REQUEST['method'])) {
			throw new Exception("方法不存在或未指定要调用的（{$_REQUEST['method']}） 方法!",1026);
		}
		$this->mAction = trim($_REQUEST['method']);
		$this->mParameters = isset($_REQUEST['parameters']) ? $_REQUEST['parameters'] : '';
		$this->mRequest = new $class();
	}

	public function dispatch($pClass=null){
		$tParam = get_magic_quotes_gpc()? stripslashes($this->mParameters) :$this->mParameters;
		$args = json_decode($tParam);
		$args = is_array($args) ? $args : array($args);
		$data = call_user_func_array(array($this->mRequest,$this->mAction),$args);
		echo serialize($data);
	}
}
?>