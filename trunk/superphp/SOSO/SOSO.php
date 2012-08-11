<?php

/**
 * SOSO Framework
 *
 * @category   core
 * @package    SOSO
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:20
 * $Id: SOSO.php 381 2012-06-12 09:14:02Z moonzhang $
 * @todo debugger 
 */
require_once 'Loader.php';
//require_once 'Object.php';
//require_once 'Controller/Abstract.php';
//require_once 'Exception.php';
require_once 'Controller.php';
require_once 'Controller/Browser.php';
require_once 'Frameworks/Config.php';
require_once 'Frameworks/Context.php';
require_once 'Frameworks/Registry.php';
require_once 'Cache.php';
require_once 'Util/Util.php';
require_once 'Application.php';

class SOSO {

	public function __construct($pConfigType='xml') {;
		date_default_timezone_set('Asia/Harbin');
		if (!defined('APP_NAME')) {
			$callstack = debug_backtrace();
			define('APP_NAME', md5($callstack[0]['file']));
		}
		SOSO_Loader::registerAutoload();
		
		$tCorePath = dirname(__FILE__);
		$tComponentPath = $tCorePath.DIRECTORY_SEPARATOR . 'Components';
		$tFrameworkPath = dirname($tCorePath);
		
		try {
			SOSO_Frameworks_Config::initialize($pConfigType);
		} catch (Exception $se) {
			echo "<b>" . $se->getMessage() . "</b>";
			trigger_error($se->getMessage(), E_USER_ERROR);
		}
		$tClassHome = SOSO_Frameworks_Config::getSystemPath('class');
		self::setFrameworkPath($tClassHome);
		self::setFrameworkPath($tFrameworkPath);
		
		$context = SOSO_Frameworks_Context::getInstance();
		//$context->set(SOSO_Loader::CORE, $tCorePath);
		$context->set(SOSO_Loader::COMPONENT, $tComponentPath);
		$context->set(SOSO_Loader::FRAMEWORK, $tFrameworkPath);
		$context->set(SOSO_Loader::APPCLASS, $tClassHome);
		SOSO_Loader::prepend();
	}

	public function __destruct() {
		SOSO_Loader::append();
		SOSO_Util_Util::ob_end_flush_all();
		//restore_include_path();
	}

	private static function setFrameworkPath($pPath, $pAppend=false, $overwrite=false) {
		if ($overwrite) {
			return @set_include_path($pPath);
		}
		$path = $pAppend ?
		get_include_path() . PATH_SEPARATOR . $pPath :
		$pPath . PATH_SEPARATOR . get_include_path();
		return @set_include_path($path);
	}

	/**
	 * 程序入口
	 * 决定控制器以及页页入口(action)
	 */
	public function serve() {
		try {
			$this->registerErrorHandler();			
			$controller = new SOSO_Controller();
			$controller->dispatch();
		} catch (Exception $e) {
			if (SOSO_Frameworks_Config::getMode() === 'debug') {
				echo $e->getMessage();
				//SOSO_Util_Util::redirect('/', 5);
			} else {
				//to be modified
				SOSO_Util_Util::redirect('/');
			}
		}
	}

	private function registerErrorHandler(){
		$project = SOSO_Frameworks_Registry::getInstance()->get('project')->attributes();
		$level = null;
		if(isset($project['logError']) && strval($project['logError']) == 'disable'){
			$level = 0;
		}elseif(isset($project['errorLevel'])){
			$level = strval($project['errorLevel']);
			if (defined($level)) $level = intval(constant($level));
			else $level = intval($level);
		}

		$logger = null;
		$path = dirname(__FILE__);		
		$mode = SOSO_Frameworks_Config::getMode();
		
		if ($mode == 'debug'){
			require_once $path.'/Debugger.php';
			$logger = SOSO_Debugger::instance();
			SOSO_Frameworks_Context::getInstance()->set('debug',true);
			//$level = E_ALL; 
		}elseif(isset($project['errorLogger']) && class_exists(strval($project['errorLogger']))){
			$logger = new strval($project['errorLogger']);
		}

		require_once $path.'/ErrorHandler.php';
		SOSO_ErrorHandler::register($level,$logger);
    }
	
}
//***** interfaces ******/

interface SOSO_Interface_Runnable {
	function run();
}

abstract class SOSO_View_Abstract implements SOSO_Interface_Runnable{
	abstract function showMessage($pMsg = '',$pButtons=array(array('name' => '确定', 'url' => '')), $pFile = 'tpl.msg.html');
	abstract function initTemplate();
}

interface SOSO_Controller_Abstract {
	/**
	 * 执行指定页面类，由具体子类实现
	 * 
	 * @param page
	 */
	function dispatch($request=null);

}
interface SOSO_Interface_Observer extends SplObserver{}

interface SOSO_Interface_Subject extends SplSubject {}

class SOSO_Page extends SOSO_View_Page {
}