<?php
/**
 *
 * @package    SOSO_View
 * @author moonzhang
 * @version 1.0
 * $Id: Page.php 401 2012-07-05 10:55:33Z moonzhang $
 */

/**
 *
 *
 * @todo isSessionStart �ŵ�application����
 *
 */
class SOSO_View_Page /*extends SOSO_View_Abstract*/ implements SOSO_Interface_Runnable{

	/**
	 * Ĭ��ģ����
	 */
	public $mDefault = 'Smarty';
	/**
	 * ģ���������б����������
	 * @var array
	 */
	public $mTypeArray = array('smarty'=>'Smarty','phplib'=>'Template','smarttemplate'=>'SmartTemplate');
	/**
	 * �Զ���ģ������
	 * @var string
	 */
	public $mType;
	/**
	 * ģ��ʵ��
	 * @var Smarty
	 */
	public $instance;
	/**
	 * Enter description here...
	 *
	 * @var User
	 */
	public $mCurrentUser = null;
	public $mGET;
	public $mPOST;
	public $mRequest;


	/**
	 * ���캯������ʼ��ģ��������û��Զ���Ϊ������ζ�ȡ�����ļ�
	 *
	 * @param string $pTYPE ģ������,Ĭ��Ϊsmarty
	 */
	public function __construct($pTYPE = 'smarty'){
		if (array_key_exists(strtolower($pTYPE),$this->mTypeArray)) {
			$this->mType = $this->mDefault = $this->mTypeArray[strtolower($pTYPE)] ;
		}
		$this->mGET = &$_GET;
		$this->mPOST = &$_POST;

		$tProject = SOSO_Frameworks_Registry::getInstance()->get('project');

		if (isset($tProject['session']) && strval($tProject['session']) != 'disable' && SOSO_Session::isStarted()){
			if (array_key_exists('currentUser',$_SESSION)) {
				$this->mCurrentUser = &$_SESSION['currentUser'];
			}elseif (array_key_exists('username',$_SESSION)){
				$this->mCurrentUser = &$_SESSION['username'];
			}else{
				$_SESSION['currentUser'] = '';
				$this->mCurrentUser = &$_SESSION['currentUser'];
			}
		}
	}

	/**
	 * ����ģ�����ļ����� ���ط�����1.���뱾ҳ��ͬ��Ŀ¼�´�����ģ��������ͬ���ļ��������ļ��м������ģ���ࣻ 2.
	 * ���뱾ҳ��ͬ��Ŀ¼�´�����ģ��������ͬ���ļ��У������ļ����´�����Ϊclass.����.php�ļ�
	 *
	 * @param pType
	 */
	private function loadTemplateFile($pType){
	}

	/**
	 * ��ʼ��SMARTY����
	 * @param Smarty $this->$param
	 */
	protected function initSmarty(){
		if (class_exists($this->mDefault)) {
			$template_path = SOSO_Frameworks_Config::getSystemPath('template');
			$template_c_path =  SOSO_Frameworks_Config::getSystemPath('temp').'/template_c';
			$param = 'm'.$this->mDefault;
			require_once("Smarty/Smarty.class.php");
			$this->instance = $this->$param = new $this->mDefault();
			$this->$param->template_dir = $template_path;
			$this->$param->compile_dir = $template_c_path;
			$this->$param->config_dir = $template_c_path;
			$this->$param->cache_dir = $template_c_path.'/cache';
		}else{
			throw new SOSO_View_Exception('���ܼ���'.$this->mDefault.'ģ������');
		}
	}

	/**
	 *
	 * @param pKey
	 * @param pVal
	 */
	public function assign($pKey, $pVal=null){
		$this->initTemplate();
		return $this->instance->assign($pKey,$pVal);
	}

	/**
	 *
	 * @param resource_name
	 * @param cache_id
	 * @param compile_id
	 * @param display
	 */
	public function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false){
		$this->initTemplate();
		return $this->instance->fetch($resource_name, $cache_id, $compile_id, $display);
	}

	/**
	 *
	 * @param pFile
	 */
	public function display($pFile){
		$this->initTemplate();
		return $this->instance->display($pFile);
	}

	/**
	 * ��ʼ��ģ��ʵ������
	 */
	public function initTemplate(){
		if (is_object($this->instance)) {
			return $this;
		}
		$toInitMethod = sprintf("init%s",$this->mDefault);
		if (method_exists($this,$toInitMethod)) {
			$this->{$toInitMethod}();
		}
	}

	/**
	 * @ ��̬���ز�Ʒ($this->ins)֧�ֵķ���
	 * @desc ��Ȩģʽ
	 * @param string $pMethod
	 * @param mixed $pParams
	 */
	public function __call($pMethod, $pParams){
		$this->initTemplate();
		return call_user_func_array(array($this->instance,$pMethod),$pParams);
	}

	/**
	 * ��ʾ��Ϣ For Smarty Use
	 *
	 * @param string $pMsg		 ����
	 * @param string $pName		 ��ťֵ
	 * @param boolean $pBackUrl  ���ص�ַ
	 * @param string $pFile      ģ���ļ���ַ
	 */
	public function showMessage($pMsg = '',$pButtons=array(array('name' => 'ȷ��', 'url' => '/')), $pFile = 'tpl.msg.html'){
		$this->initTemplate();
		$this->instance->assign('message',$pMsg);
		$this->instance->assign('buttons',$pButtons);
		$this->instance->display($pFile);
	}

	public function get(){
		$args = func_get_args();
		return call_user_func_array(array($this,'run'), $args);
	}

	public function post(){
		$args = func_get_args();
		return call_user_func_array(array($this,'run'), $args);
	}

	public function put(){
		$args = func_get_args();
		return call_user_func_array(array($this,'run'), $args);
	}

	public function delete(){
		$args = func_get_args();
		return call_user_func_array(array($this,'run'), $args);
	}

	/**
	 *
	 * ����URL
	 * @param unknown_type $args
	 * @param unknown_type $defaultArgs
	 * @param boolean $flag ���Ϊ�棬��ʹ�����д˷��ʵ�regexp��������
	 */
	public function getUrl($args=array(),$defaultArgs=array(),$flag=true){
		$app = SOSO_Application::getInstance();
		$pattern = $app->getPattern();
		$class = strtolower(get_class($this));
		if (!$pattern) return str_ireplace(array("Page_User_","Page_",''),'',$class);
		$currentArgs = $app->getArgs();
		$callArgs = $defaultArgs ? $defaultArgs : $currentArgs;
		$minParams = count($args);
		
		if($flag){
			$hitRegExp = $app->get_registered_handler_by_name($class);
			$num = $pattern[$class][$hitRegExp];
			if (0 == $num && !$args && !$callArgs) return $_SERVER['SCRIPT_NAME'];
			$num_args = max(0,$num-$minParams);
			$merge_args = array_merge(array_slice($callArgs,0,$num_args) , $args);
			try{
				$url = $this->reverseUrl($hitRegExp, $merge_args);
				return $url;
			}catch(Exception $e){
			}
		}

		foreach ($pattern[$class] as $regexp=>$num){
			if ($num < $minParams) continue;

			$num_args = max(0,$num-$minParams);
			$merge_args = array_merge(array_slice($callArgs,0,$num_args) , $args);
			
			try{
				if (substr($regexp,0,1) != '#'){
					continue;
				}
				$url = $this->reverseUrl($regexp, $merge_args);
				$url = str_replace(array('\\','?'),'',$url);
				return $url;
			}catch(Exception $e){
				continue;
			}
		}
		return false;
	}

	protected function reverseUrl($pattern,$args){
		$reg = '#\(([^)]+)\)#';

		$GLOBALS['__app_args'] = $args;
		$func = create_function('$match','
			$args = $GLOBALS["__app_args"];
			static $tGroupIndex = 0;
			$group = $match[1];
			if (isset($args[$tGroupIndex])){
				$value = $args[$tGroupIndex++];
			}else{
				throw new RuntimeException("Not enough arguments in url tag",201);
			}
			
			if (!preg_match("#$group$#",$value)){
				throw new RuntimeException(sprintf("Value (%s) doesn\'t match (%s)",$value,$group),202);
			}
				
			return $value;
		');
		/*if (class_exists('closure',false)){
			$func = function($match) use ($args){
				static $tGroupIndex = 0;
				$group = $match[1];
				if (isset($args[$tGroupIndex])){
					$value = $args[$tGroupIndex++];
				}else{
					throw new RuntimeException('Not enough arguments in url tag',201);
				}

				if (!preg_match("#$group$#",$value)){
					throw new RuntimeException(sprintf("Value (%s) doesn't match (%s)",$value,$group),202);
				}

				return $value;
			};
		}else{
			$GLOBALS['__app_args'] = $args;
			$func = create_function('$match','
				$args = $GLOBALS["__app_args"];
				static $tGroupIndex = 0;
				$group = $match[1];
				if (isset($args[$tGroupIndex])){
					$value = $args[$tGroupIndex++];
				}else{
					throw new RuntimeException("Not enough arguments in url tag",201);
				}
				
				if (!preg_match("#$group$#",$value)){
					throw new RuntimeException(sprintf("Value (%s) doesn\'t match (%s)",$value,$group),202);
				}
				
				return $value;
			');
		}*/

		try{
			$delim = substr(trim($pattern),0,1);
			$pieces = explode($delim,$pattern);
			array_shift($pieces);
			array_pop($pieces);
			$pattern = join($delim,$pieces);
			$result = preg_replace_callback($reg,$func,$pattern);
			if(isset($GLOBALS['__app_args'])) unset($GLOBALS['__app_args']);
			$result = str_replace(array("^",'$'),"",$result);
		}catch(Exception $e){
			throw $e;
		}

		return $result;
	}

	public function run(){
		$trace = debug_backtrace();
		print_r($trace);
		throw new SOSO_View_Exception('����ʵ�� run ����');
	}

	public function isLogin($pAuth='SOSO_Auth_Oss',$session_key='currentUser'){
		$tAuth = new SOSO_Auth_Decorator(new $pAuth);
		return $tAuth->isAuthorized($session_key);
	}

	public function redirect($pUrl='/',$pDelay=0){
		return SOSO_Util_Util::redirect($pUrl,$pDelay);
	}

	public function login($pAuth='SOSO_Auth_Oss',$pUsername='',$pPassword=''){
		$tAuth = new SOSO_Auth_Decorator(new $pAuth);
		return $tAuth->login($pUsername,$pPassword,$_SERVER['PHP_SELF']);
	}

	public function logout($pAuth='SOSO_Auth_Oss',$pBackurl='/'){
		$this->mCurrentUser = null;
		SOSO_Session::destroy(false);
		$tAuth = new SOSO_Auth_Decorator(new $pAuth);
		return $tAuth->logout($pBackurl);
	}
}
?>
