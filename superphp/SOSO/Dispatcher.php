<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:20
 */
require_once(dirname(__FILE__).'/'.'Loader.php');
class SOSO_Dispatcher {

	public function __construct(){
		date_default_timezone_set('Asia/Harbin');
		SOSO_Loader::registerAutoload();
	}
	
	public function __destruct(){
		SOSO_Util_Util::ob_end_flush_all();
		restore_include_path();
		SOSO_Loader::append();
	}

	private static function setFrameworkPath($pPath,$pAppend=false,$overwrite=false){
		if ($overwrite) {
			return @set_include_path($pPath);
		}
		$path = $pAppend ? 
					get_include_path().PATH_SEPARATOR.$pPath :
					$pPath.PATH_SEPARATOR.get_include_path(); 
		return @set_include_path($path);
	}
	
	/**
	 * 程序入口
	 * 决定控制器以及页页入口(action)
	 */
	public function serve($pConfigType='xml'){
		self::setFrameworkPath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Components',false,true);
		self::setFrameworkPath(dirname(dirname(__FILE__)));
		try{
			SOSO_Frameworks_Config::initialize($pConfigType);
		}catch (SOSO_Exception $se){
			echo $se->getMessage();
			$page = new SOSO_View_Page();
			$page->showMessage($se->getMessage());
		}
		self::setFrameworkPath(SOSO_Frameworks_Config::getSystemPath('class'),true);
		SOSO_Loader::prepend();
		if (in_array(SOSO_Frameworks_Config::getMode(),array('debug','online'))) {
			set_exception_handler(array('SOSO_Exception','uncaughtExceptionHandler'));
			set_error_handler(array('SOSO_Exception','errorHandler'));	
		}
		try{
			$this->session_start();
		}catch (Exception $e){
			echo $e->getMessage();
		}
		try {
			$controller = new SOSO_Controller();	
			$controller->render();
		}catch (SOSO_Controller_Exception $e){
			if (SOSO_Frameworks_Config::getMode() === 'debug'){
				echo $e->getMessage();
			}else{
				//跳转到首页
				SOSO_Util_Util::redirect('/');
			}
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
	
	public function session_start(){
		$tProject = SOSO_Frameworks_Registry::getInstance()->get('project');
		$tConfig = array();
		if ($tProject->session) {
			$tConfig = current((array)$tProject->session);
		}

		if (isset($tConfig['session_id_var']) 
				&& strlen($tConfig['session_id_var']) 
				&& isset($_REQUEST[$tConfig['session_id_var']])) {
			
			$tConfig['session_id'] = $_REQUEST[$tConfig['session_id_var']];
		}	
		SOSO_Session::start($tConfig);
	}
}
?>
