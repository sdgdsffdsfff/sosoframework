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
//require_once(dirname(dirname(__FILE__)) . "/Interface/Runnable.php");
$dir = dirname(dirname(__FILE__));
require_once($dir . "/View/Page.php");

class SOSO_Controller_Browser /*extends SOSO_Object*/ implements SOSO_Controller_Abstract {

    public $mName = 'front';

    /**
     * 控制器入口
     * 
     * @return void
     */
    public function dispatch($pClass=null) {
        $class = strlen($pClass) ? $pClass : $this->getClass();
        try{
        	$page = new $class();
        	$app = SOSO_Application::getInstance();
        	return call_user_func_array(array($page,$app->getAction()), $app->getArgs());
        }catch(Exception $error){
            var_dump( $error->getMessage() ); exit;
        	if($mode = SOSO_Frameworks_Config::getMode() == 'debug'){
        		echo $error->getMessage();
        	}else{
        		$oPage = new SOSO_View_Page();
        		$oPage->showMessage($error->getMessage());
        	}
        }
    }

    /**
     * 得到页面类相关信息：类名，filters,action
     * 
     * @return string 返回请求的页面类名
     */
    public function getClass() {
        $context = SOSO_Frameworks_Context::getInstance();
        return $context->get('page_class');
    }

}
