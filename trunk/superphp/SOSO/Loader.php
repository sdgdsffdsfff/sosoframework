<?php
/**
 * SOSO Framework
 *
 * @category   SOSO
 * @author moonzhang
 * $Id: Loader.php 382 2012-06-12 14:01:37Z moonzhang $
 */

class SOSO_Loader{public static $times = array('core'=>array(),'cached'=>array());
	const CORE = 'CORE_PATH';
	const FRAMEWORK = 'FRM_PATH';
	const COMPONENT = 'CMP_PATH';
	const APPCLASS = 'CLASS_PATH';

	public static $prependFilesCache ;
	public static $loadedFilesHash = array();
	public static $new_class_found = false;
	/**
	 * ������
	 *
	 * @var SOSO_Cache
	 */
	public static $cacher;

	private static function getClassNames($class){
		$classLower = strtolower($class);
		$classUpper = strtoupper($class);
		$ret = array();
		$ret[] = $class;
		$ret[] = ucfirst($class);
		$ret[] = $classLower;
		$ret[] = $classUpper;
		return array_unique($ret);
	}

	public static function cacheAutoload($package){
		if (isset(SOSO_Loader::$loadedFilesHash[strtolower($package)])){
			require_once(SOSO_Loader::$loadedFilesHash[strtolower($package)]);
			return true;
		}

		$tFile = SOSO_Loader::loadCachedClass($package);
		if (false !== $tFile) {
			SOSO_Loader::$loadedFilesHash[strtolower($package)] = $tFile;
			require_once($tFile);
			return true;
		}
		return false;
	}

	public static function coreAutoload($package){
		$param = explode("_",$package);
		$first = array_shift($param);
		//$last = array_pop($param);

		$first = strtoupper($first);
		if(!in_array($first,array('SOSO','PHPUNIT'))) return false;
		return self::loadClass($package,self::FRAMEWORK);
	}

	protected static function loadClass($package,$pathType){
		$param = explode("_",$package);
		$first = '';
		$ds = DIRECTORY_SEPARATOR;
		$last = array_pop($param);

		if($pathType == self::FRAMEWORK){
			$first = array_shift($param);
			$first = 'SOSO' == strtoupper($first) ? 'SOSO' : 'PHPUnit';
			$last = ucfirst($last);
		}

		$tConfigedPath = SOSO_Frameworks_Context::getInstance()->get($pathType);
		$pathes = array();
		$pathes[] = implode($ds,$param);
		//$param = array_map(create_function('$v','return ucfirst(strtolower($v));'),$param);
		$data = array('uc'=>array(),'uclower'=>array(),'upper'=>array(),'lower'=>array());
		foreach ($param as $k=>$v){
			$data['uc'][] = ucfirst($v); 
			$data['uclower'][] = ucfirst(strtolower($v));
			$data['upper'][] = strtolower($v);
			$data['lower'][] = strtoupper($v);
		}
		foreach ($data as $arr){
			$pathes[] = implode($ds,$arr);
		}
//		$pathes[] = implode($ds,array_map(create_function('$v','return ucfirst($v);'),$param));
//		$pathes[] = implode($ds,array_map(create_function('$v','return ucfirst(strtolower($v));'),$param));
//		$pathes[] = implode($ds,array_map(create_function('$v','return strtolower($v);'),$param));
//		$pathes[] = implode($ds,array_map(create_function('$v','return strtoupper($v);'),$param));
		$pathes = array_unique($pathes);

		$classes = self::getClassNames($last);
		$format = "%s/%s.php";
		foreach ($pathes as $path) {
			$tPath = implode($ds,array_filter(array($tConfigedPath,$first,$path)));
			foreach ($classes as $class){
				$file = sprintf($format,$tPath,$class);
				if (file_exists($file)){
					$tCacher = SOSO_Loader::getStorage();
					$tCacher instanceof SOSO_Cache && $tCacher->set($tCacher->getKey(),$file);
					self::$new_class_found = true;
					self::$loadedFilesHash[strtolower($package)] = $file;
					require_once $file;
					return true;
				}
			}
		}

		return false;
	}

	public static function cmpAutoload($package){
		return self::loadClass($package, self::COMPONENT);
	}

	public static function appAutoload($package){
		return self::loadClass($package, self::APPCLASS);
	}

	/**
	 * �Զ���������ļ������Լ����û��ļ�
	 * @param string $package
	 *
	 * updates:
	 *   2008-04-11 �Ż�����,ִ��ʱ����0.04s��0.013s
	 *   2008-05-11 �ٴ��Ż������Ӽ���cache,������������ȫ������ʱ��(autoloading)ƽ����:
	 * 				һ. cacheǰ5-20ms����
	 * 				��. cache�� < 0.0001s ( < 1ms )
	 *   2009-10-23 ���ʹ��cache����(apc/xcache),�����������ݽ��л��棬����(Ĭ��)ԭ�ļ�cache
	 *
	 *  btw: ˫��ѭ��(O(N2))��ʹ��@include_onceЧ��Ҫ�ߣ����������ΪPHP�ڲ�Ҫ��̫�����
	 *       ����ı����йأ�
	 */
	public static function _autoload($package){
		$tPackageName = strtolower($package);
		if (isset(self::$loadedFilesHash[$tPackageName]) && file_exists(self::$loadedFilesHash[$tPackageName])) {
			return require_once(self::$loadedFilesHash[$tPackageName]);
		}

		$searchList = array();
		$paths = explode(PATH_SEPARATOR,get_include_path());
		$param = explode("_",$package);
		$searchList[] = implode(DIRECTORY_SEPARATOR,$param);
		$searchList[] = strtolower(end($searchList));
		$searchList[] = implode(DIRECTORY_SEPARATOR,array_map('ucfirst',$param));
		$tmp = array_pop($param);
		array_push($param,strtolower($tmp));
		$searchList[] = implode(DIRECTORY_SEPARATOR,$param);
		array_push($param,sprintf("class.".array_pop($param)));
		$searchList[] = join(DIRECTORY_SEPARATOR,$param);
		$searchList = array_unique($searchList);

		foreach ($paths as $path){
			foreach ($searchList as $file){
				$tFile = sprintf("%s.php",$path.DIRECTORY_SEPARATOR.$file);
				if (file_exists($tFile)) {
					self::$new_class_found = true;
					self::$loadedFilesHash[$tPackageName] = $tFile;
					$return = require_once($tFile);
					return $return;
				}
			}
		}
		return false;
	}
	/**
	 * Enter description here...
	 *
	 * @return SOSO_Cache_Apc
	 */
	public static function getStorage(){
		if (!is_null(self::$cacher)) {
			return self::$cacher;
		}
		if (extension_loaded('apc')) {
			require_once 'Cache.php';
			require_once(dirname(__FILE__).'/Cache/Apc.php');
			return self::$cacher = new SOSO_Cache_Apc();
		}else if (extension_loaded('xcache')) {
			require_once 'Cache.php';
			require_once(dirname(__FILE__).'/Cache/Xcache.php');
			return self::$cacher = new SOSO_Cache_Xcache();
		}else{
			return self::$cacher = false;
			//return null;//self::$cacher = new LoaderCacher();
		}
	}
	/**
	 * ����Ƿ���Ԥ�����б���
	 * ����������xcache/apcʵ��
	 *
	 * @param string $pRequest
	 * @param bool $autoload
	 * @return string
	 */
	public static function isLoaded($pRequest,$autoload=true){
		$bool = isset(self::$loadedFilesHash[strtolower($pRequest)]);
		if ($bool && $autoload) {
			require_once(SOSO_Loader::$loadedFilesHash[strtolower($pRequest)]);
			return $bool;
		}
	}
	/**
	 * Enter description here...
	 *
	 * @param string $pClass
	 * @return boolean
	 */
	public static function loadCachedClass($pClass,$type='al'){
		$tCacher = self::getStorage();
		if( $tCacher instanceof SOSO_Cache ){
			$format = "%s:%s";
			$hash = md5(SOSO_Frameworks_Context::getInstance()->get(self::APPCLASS));
			//$u_name = (0 === strpos($pClass,'SOSO') ? 'core' : str_replace(' ','_',APP_NAME).':'.$type).":".$pClass;
			$first = 0 === strpos(strtoupper($pClass),'SOSO') ? 'core' : "$type:$hash";
			$last = $pClass;
			$u_name = sprintf($format,$first,$last);
			$u_name = strtolower($u_name);
			$tPageClass = $tCacher->read($u_name);
			$tCacher->setOption('cachekey',$u_name);

			if ($tPageClass !== null && strlen($tPageClass)) {
				return $tPageClass;
			}

			return false;
		}
		return false;
	}

	/**
	 * �Զ������ض���
	 * @param string $pPackageName ������,�԰�����Ŀ¼��Ϊ׼
	 * @return void
	 */
	public static function loadPackage($pPackageName='.'){
		$files = array_map('realpath',glob(dirname(__FILE__)."/{$pPackageName}/*.php"));
		$included = get_included_files();
		$toLoad = array_diff($files,$included);
		foreach ($toLoad as $v){
			require_once($v);
		}
	}

	/**
	 * ע��Autoloading
	 * @throws SOSO_Exception
	 */
	public static function registerAutoload(){
//		spl_autoload_register(array('SOSO_Loader','allAutoload'));
//		spl_autoload_register(array('SOSO_Loader','cacheAutoload'));
//		spl_autoload_register(array('SOSO_Loader','coreAutoload'));
//		spl_autoload_register(array('SOSO_Loader','appAutoload'));
//		spl_autoload_register(array('SOSO_Loader','cmpAutoload'));
		spl_autoload_register(array('SOSO_Loader','_autoload'));
		spl_autoload_register(array('SOSO_Loader','cmpAutoload'));
	}

	/**
	 * Ԥ��������ļ�
	 * ��������apc/xcache֮�����չʱ��ʹ���ļ�Ԥ����,ʹЧ������
	 * �ļ�Ԥ���ػ�������cacher���ڵ�����
	 */
	public static function prepend(){
		if (PHP_SAPI == 'cli') return true;
//		if (self::getStorage() instanceof SOSO_Cache) {
//			return true;
//		}
		$cache_path = SOSO_Util_Util::getTempDir();
		$tMask = umask();
		umask(0000);
		if (file_exists($cache_path) && is_writable($cache_path)) {
		    $project = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : defined('APP_NAME')?APP_NAME:'SOSOProject';
		    $cache_path = $cache_path."/{$project}";
		    $path = dirname($_SERVER['PHP_SELF']);
		    if (strlen($path) > 1) {
		    	$cache_path .= $path;
		    }
		    if (!file_exists($cache_path)) {
		    	mkdir($cache_path,0777,true);
		    }
		    
			self::$prependFilesCache = $cache_path.'/'.md5($project.$_SERVER['PHP_SELF']);
		}
		if (!is_null(self::$prependFilesCache) && file_exists(self::$prependFilesCache)) {
			$tHash = unserialize(file_get_contents(self::$prependFilesCache));
			//$tHash = array_combine(array_map('strtolower',array_keys($tHash)),array_values($tHash));
			//$tkeys = array_diff(array_keys($tHash),array_keys(self::$loadedFilesHash));
			//$tkeys = array_unique($tkeys);
			self::$loadedFilesHash = array_merge(self::$loadedFilesHash,$tHash);
//			foreach ($tkeys as $class) {
//				if (file_exists($tHash[$class])) {
//					self::$loadedFilesHash[$class]=$tHash[$class];
//					require_once($tHash[$class]);
//				}
//			}
			unset($tHash);
		}
		umask($tMask);
	}

	/**
	 * �����ļ�
	 */
	public static function append(){
		if (PHP_SAPI == 'cli') return true;
//		if (self::getStorage() instanceof SOSO_Cache) {
//			return true;
//		}

		if (SOSO_Loader::$new_class_found && !is_null(SOSO_Loader::$prependFilesCache)) {	
			$tMask = umask();
			umask(0000);		
			$ok = @file_put_contents(SOSO_Loader::$prependFilesCache,serialize(SOSO_Loader::$loadedFilesHash));
			umask($tMask);
			if ($ok){
				@chmod(self::$prependFilesCache,0777);
			}
		}
	}
}
?>
