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
	 * 缓存器
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
	 * 自动加载类库文件，尝试加载用户文件
	 * @param string $package
	 *
	 * updates:
	 *   2008-04-11 优化方法,执行时间由0.04s到0.013s
	 *   2008-05-11 再次优化，增加加载cache,单个请求所用全部加载时间(autoloading)平均在:
	 * 				一. cache前5-20ms左右
	 * 				二. cache后 < 0.0001s ( < 1ms )
	 *   2009-10-23 组合使用cache对象(apc/xcache),对已请求数据进行缓存，代替(默认)原文件cache
	 *
	 *  btw: 双重循环(O(N2))比使用@include_once效率要高，这或许是因为PHP内部要做太多额外
	 *       多余的遍历有关；
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
	 * 检查是否在预加载列表中
	 * 有条件可用xcache/apc实现
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
	 * 自动加载特定包
	 * @param string $pPackageName 包名称,以包所在目录名为准
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
	 * 注册Autoloading
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
	 * 预加载相关文件
	 * 当加载了apc/xcache之类的扩展时不使用文件预加载,使效率最优
	 * 文件预加载基于请求，cacher基于单个类
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
	 * 加载文件
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
