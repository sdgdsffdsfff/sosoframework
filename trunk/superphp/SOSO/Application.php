<?php
/**
 *
 * Application类，负责应用级URL(action)Router
 * @todo 1、sessions etc 2、调用时传递数组方式
 *
 * @author moonzhang
 *
 * @example
 entry.php:

 $soso = new SOSO();
 $app = SOSO_Application::getInstance(
 array(
 'pure_filename.html'=>'Page_User_Test.html',
 '/read/book/(.+).html/(\d+).html'=>'Page_User_Test',
 '/read/book/(.+).(.*)ml'=>'Page_User_Test.otherMethod'
 )
 );
 try{
 $soso->Serve();
 }catch(Exception $e){
 echo $e->getMessage();
 }

 */

class SOSO_Application {
	protected static $instance;
	const RE_FIND_GROUPS = '#\(.*?\)#';
	protected $_handler_map = array();
	protected $_pattern_map = array();
	protected $_url_mapping = array();
	protected $__debug = false;
	protected $current_request_args = array();
	protected $current_request;
	protected $current_action;
	protected $current_controller;
	protected $postfix = array('php','q','soso');
	protected $logging;

	protected function __construct($pattern,$debug){
		$this->__debug = $debug;
		$this->setURL($pattern);
	}

	public static function getInstance($pattern=array(), $debug=false){
		if(is_null(self::$instance)){
			self::$instance = new SOSO_Application($pattern, $debug);
		}
		return self::$instance;
	}

	public static function init($pattern, $debug=false){
		return self::getInstance($pattern,!!$debug);
	}
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $handlers
	 *
	 * @todo 拆分正则与hash二种模式匹配
	 */
	protected function setURL($handlers){
		$handler_map = array();
		$pattern_map = array();
		$url_mapping = array();
		$url_name = array();

		foreach ($handlers as $regexp=>$handler){
			$pieces = explode('.',$handler);
			$tPageClass = array_shift($pieces);
			$lower = strtolower($tPageClass);
			if ($this->__debug){
				$tPageClass = $this->validateParam($tPageClass,join('',$pieces));
			}/*else{
			$tPageClass = $this->checkClass($tPageClass);
			}*/
				
			//$handler_map[$tPageClass] = $regexp;//$handler;
			//array_push($handler_map,$handler);
			$regexp = trim($regexp);
			$num_groups = 0;
			if ('/' == substr($regexp,0,1)){
				$regexp = sprintf("#^%s$#Ui",$regexp);
				$num_groups = preg_match_all(self::RE_FIND_GROUPS,$regexp,$match);
			}
				
			$handler_map[$lower] = $regexp;//$handler;
			$url_mapping[$regexp] = $handler;
				
			if(!isset($pattern_map[$lower])){
				$pattern_map[$lower] = array();
			}
			$pattern_map[$lower][$regexp] = $num_groups;
		}

		$this->_handler_map = $handler_map;
		$this->_pattern_map = $pattern_map;
		$this->_url_mapping = $url_mapping;
	}

	private function validateParam($class,$method=''){
		$class = $this->checkClass($class);
		if ($method){
			$ref = new ReflectionClass($class);
			if(!$ref->hasMethod($method)){
				throw new RuntimeException("Undefined method {$class}::{$method}()", 102);
			}
			$tRefMethod = $ref->getMethod($method);
			if(!$tRefMethod->isPublic()){
				throw new RuntimeException("Method {$class}::{$method}() is not public", 103);
			}
		}

		return $class;
	}

	public function setPostfix($post){
		array_push($this->postfix, $post);
		$this->postfix = array_unique($this->postfix);
		return $this;
	}

	public function parseRequest($pRequest) {
		if($this->_url_mapping){
			$pattern = "#^/(?:_([^_]+))?_?(.+)\.(?:".join('|',$this->postfix).")$#i";
			$pathinfo = pathinfo($pRequest);
			if($pathinfo['dirname'] != '/'){
				$docroot = $_SERVER['DOCUMENT_ROOT'].'/';
				$appdoc = SOSO_Frameworks_Config::document_root_path().'/';
				$docpath = str_replace('//', '/', strtolower($docroot.$pathinfo['dirname'].'/'));
				$appdoc = strtolower($appdoc);
				if($docpath == $appdoc) $pRequest = '/'.basename($pRequest);
			}
		}else{
			$pattern = "#^(?:_([^_]+))?_?(.+)\.(?:".join('|',$this->postfix).")$#i";
			$pRequest = basename($pRequest);
		}
		
		$prefix = 'SOSO_Controller_';
		$baseController = 'SOSO_Controller_Browser';
		$request = $pRequest;
		if(preg_match_all($pattern, $pRequest, $m)){
			$request = str_replace('/_'.$m[1][0].'_', '/', $pRequest);
			$controller = $prefix . $m[1][0];
			if ($m[1][0] && class_exists($controller)) {
				$baseController = $controller;
			}
		}
		$this->current_controller = $baseController;

		$page = isset($m[2][0]) ? $m[2][0] : '';
		$groups = array();
		$action = 'run';


		foreach($this->_url_mapping as $regexp=>$class){
			$class .= ".$action";
			$first = substr($regexp,0,1);
			if ('#' != $first){
				$regexp = '/' . $regexp;
				if (strtolower($regexp) == strtolower($request)){
					$pieces = explode('.',$class);
					$page = $pieces[0];
					$action = $pieces[1];
					$this->_handler_map[strtolower($page)] = $regexp;
					break;
				}
			}elseif(preg_match_all($regexp,$request,$match,PREG_SET_ORDER)){
				$pieces = explode('.',$class);
				$page = $pieces[0];
				$action = $pieces[1];
				$match = $match[0];
				array_shift($match);
				$groups = $match;
				$this->_handler_map[strtolower($page)] = $regexp;
				break;
			}
		}


		if ($page == '') {
			if($this->__debug) {
				$logging = $this->getLogging();
				if(!isset($logging->__added)){
					$stream = SOSO_Frameworks_Config::getSystemPath('temp').'/app.log';
					$logger = new SOSO_Logger_Stream($stream);
					$logging->addLogger($logger);
					$logging->__added = true;
				}
				$this->logging->warn("Request($pRequest) not found.");
				throw new Exception("Request($pRequest) not found.");
			}
			SOSO_Util_Util::redirect('/');
		}

		$this->current_request_args = $groups;
		$this->current_action = $action;

		try{
			$this->current_request = $this->checkClass($page);
		}catch (Exception $exp) {
			throw $exp;
		}

		return array('request'=>$this->current_request,'action'=>$action,'args'=>$groups,'controller'=>$baseController);
	}

	/**
	 * 确定请求的页面类,获得相关参数
	 *
	 * @param string $pRequest 请求的REQUEST_URI
	 * @return string 确切的页面类名
	 */
	private function checkClass($pRequest) {
		$pRequest = str_ireplace('page_user_','',$pRequest);
		$pages = array('Page_User_' . $pRequest, 'Page_' . $pRequest, $pRequest);
		foreach ($pages as $page) {
			if(!class_exists($page)) continue;
			$interfaces = @class_implements($page);
			if (is_array($interfaces) && in_array('SOSO_Interface_Runnable', $interfaces)) {
				return $page;
			}
		}
		throw new Exception("Page : ({$_SERVER['PHP_SELF']}) not exists.");
	}

	/**
	 *
	 *
	 * @param $handler_name
	 */
	public function get_registered_handler_by_name($handler_name){
		if (isset($this->_handler_map[$handler_name]))
		return $this->_handler_map[$handler_name];
		return null;
	}

	public function getArgs(){
		return $this->current_request_args;
	}

	public function getRequest(){
		return $this->current_request;
	}

	public function getPattern(){
		return $this->_pattern_map;
	}

	public function getHandler(){
		return $this->_handler_map;
	}

	public function getUrlMap(){
		return $this->_url_mapping;
	}
	
	public function getController(){
		return $this->current_controller;
	}

	public function getLogging(){
		if ($this->logging) return $this->logging;
		
		require_once dirname(__FILE__).'/Log.php';
		return $this->logging = new SOSO_Log(defined('APP_NAME') ? constant('APP_NAME') : 'application');
	}
	
	public function getAction(){
		return $this->current_action;
	}
}