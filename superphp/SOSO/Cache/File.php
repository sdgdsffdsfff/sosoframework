<?php
/**
 * 基于文件的cache类实现,支持目录散列，可配置散列规则（深度、目录名长度）
 * options: ['cache_dir'=>,'hash_dirname_len'=>,'hash_level'=>,'auto_hash'=>] 
 * 
 * @example 创建一个文件缓存对象 
 * $tCache = new SOSO_Cache_File(array('cache_dir'=>'/tmp','auto_hash'=>true,'cache_time'=>strtotime("+10 days")));
 * 在/tmp目录下，2级散列目录，有效时间10天
 * 
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2008-07-24
 * @package cache
 *
 */
class SOSO_Cache_File extends SOSO_Cache {
	/**
	 * 是否启动目录散列
	 *
	 * @var bool
	 */
	protected $mAutoHash = false;

	/**
	 * 散列深度
	 *
	 * @var int
	 */
	protected $mHashLevel = 2;

	/**
	 * 散列的目录名的长度，默认为2
	 *
	 * @var int
	 */
	protected $mHashDirnameLen = 2;

	protected $mCacheDir = '';

	private $mFileLock = true;
	/**
	 * 构造函数
	 *
	 * @param array Config options: ['cache_dir'=>,'hash_dirname_len'=>,'hash_level'=>,'auto_hash'=>]
	 */
	public function __construct($options=array()){
		$this->mAllowOptions = array_merge($this->mAllowOptions,array('cache_dir','hash_dirname_len','hash_level','auto_hash'));
		$this->setOptions($options,$this->mAllowOptions);
		if (trim($this->mCacheDir) === '' || realpath($this->mCacheDir) === false) {
			$temp = SOSO_Frameworks_Config::getSystemPath('temp').'/cache/'.trim($this->mCacheDir);
			
			if (file_exists($temp) && is_writable($temp)) {
				$this->mCacheDir = $temp;
			}elseif (strpos(str_replace("\\","/",$temp),str_replace("\\","/",getcwd())) !== false){
				$current_umask = umask(0000);
				if(!file_exists($temp)) mkdir($temp,0755,true);
				umask($current_umask);
				$this->mCacheDir = $temp;
			}else{
				if (('WINNT' == PHP_OS) && getenv('TEMP')) {
					$this->mCacheDir = getenv('TEMP');
				} else {
					$this->mCacheDir = '/tmp';
				}
			}
		}
		if (! in_array($this->mCacheDir{strlen($this->mCacheDir)-1},array('/','\\'))) {
			$this->mCacheDir.= "/";
		}
	}
	
	/**
	 * 读取缓存内容
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function read($key){
		if (!$this->isCached($key) || $this->isExpired($key)) {
			return null;
		}

		$tFile = $this->getHash($key).$key;
		clearstatcache();
		if (0 == ($tFilesize = filesize($tFile))){
			return false;
		}
		if (!($fp = @fopen($tFile,'r'))) {
			return null;
		}
		$tNeedUnload=false;
		if ($this->mFileLock && flock($fp,LOCK_SH)) {
			$tNeedUnload = true;
		}
		
		
		$line = fgets($fp,20);
		fseek($fp,strlen($line));
		$tData = $this->decode(fread($fp,$tFilesize));
		if ($tNeedUnload) {
			flock($fp,LOCK_UN);
		}
		fclose($fp);
		
		@touch($tFile,time());
		return $tData;
	}
	
	/**
	 * 设置缓存
	 *
	 * @param string $key  键
	 * @param mixed $data  值
	 * @param int $expire  过期时间
	 * @return bool
	 */
	public function write($key,$data,$expire=null){
		if (!$this->mCacheing) {
			return true;
		}
		if (is_null($expire) && trim($expire) == "") {
			$expire = $this->calExpireTime($this->mCacheTime);
		}elseif ($expire <= 0){
			$expire = $expire;
		}else {
			$expire = $this->calExpireTime($expire);
		}
		$key = trim($key);
		if (empty($key)) {
			$this->mCachekey = $this->getKey();
		}
		
		if (strlen($key) && $this->mCachekey != $key) {
			$this->mCachekey = $key;
		}
		
		$tPath = $this->getHash($key);
		$tData = $expire."\n";
		$tData .= $this->encode($data);
		$current_umask = umask();
		umask(0000);
		clearstatcache();
		if (!file_exists($tPath)) {
			mkdir($tPath,0755,true);
			
			if (is_writable($tPath)) {
				return file_put_contents($tPath.$key,$tData);
			}
			return false;
		}
		$fp = @fopen($tPath.$key,'wb');
		if (!$fp) {
			return false;
		}
		if ($this->mFileLock && flock($fp,LOCK_EX)) {
			fwrite($fp,$tData);
			flock($fp,LOCK_UN);
		}else{
			fwrite($fp,$tData);
		}
		fclose($fp);
		umask($current_umask);
		return true;
	}
	
	/**
	 * 获得散列的目录结构
	 *
	 * @param string $key 键值
	 * @return string
	 */
	public function getHash($key){
		static $result_cache=array();
		if (isset($result_cache[$key]) && strlen($result_cache[$key])) {
			return $result_cache[$key];
		}
		if ($this->mAutoHash && $this->mHashLevel*$this->mHashDirnameLen > 0) {
			return $result_cache[$key] = $this->mCacheDir.join("/",str_split(substr($key,0,$this->mHashLevel*$this->mHashDirnameLen),$this->mHashDirnameLen)).'/';
		}
		return $result_cache[$key] = $this->mCacheDir;
	}

	public function isCached($key){
		$tPath = $this->getHash($key);
		if (file_exists($tPath.$key)) {
			return true;
		}
		return false;
	}
	/**
	 * 删除指定key的缓存文件
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete($key){
		$tCacheFile = $this->getHash($key).$key;
		if (file_exists($tCacheFile)) {
			$bool = unlink($tCacheFile);
			clearstatcache();
			return $bool;
		}
		return true;
	}

	public function isExpired($key){
		$tFile = $this->getHash($key).$key;
		if (!file_exists($tFile)) {
			return true;
		}
		if (0 === filesize($tFile)) {
			return true;	
		}
		$fp = fopen($tFile,'r');
		$tExpireTime = fgets($fp,12);
		fclose($fp);
		//永不过期
		if ($tExpireTime <= 0){
			return false;
		}
		return $tExpireTime < time();
	}
	
	/**
	 * 清除所有缓存
	 *
	 */
	public function flush(){
		$dir = new RecursiveDirectoryIterator($this->mCacheDir,RecursiveDirectoryIterator::KEY_AS_FILENAME);
		foreach($dir as $k=>$v){
			if (substr($v->getFilename(),0,1) == '.') {
				continue;
			}
			if ($v->isFile()) {
				@unlink($v->getPath().DIRECTORY_SEPARATOR.$v->getFilename());
			}else{
				$this->removeFiles($v);
			}
		}
		clearstatcache();
	}
	private function removeFiles($dir){
		$a = new RecursiveDirectoryIterator($dir);
		foreach ($a as $v1){
			if($v1->isDir()){
				$this->removeFiles($v1);
				@rmdir($v1->getPath().DIRECTORY_SEPARATOR.$v1->getFilename());
			}else{
				unlink($v1->getPath().DIRECTORY_SEPARATOR.$v1->getFilename());
			}
		}
		$b = @rmdir($a->getPath().DIRECTORY_SEPARATOR.$a->getFilename());
	}
	
	/**
	 * 垃圾回收，执行效率做过较大优化，
	 * 使用内建深度目录迭代器，支持无限级散列文件遍历
	 *
	 * @param unknown_type $maxlifetime
	 */
	public function gc($maxlifetime){
		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->mCacheDir,true));
		foreach($dir as $k=>$v){
			if (strpos($k,'.svn') === false) {
				if ($v->getMTime() + $maxlifetime <= time()) {
					@unlink($k);
				}elseif($fp=@fopen($k,'r')){
					$expire = trim(fgets($fp,12));
					if ($expire <= time()) {
						@unlink($k);
					}
					@fclose($fp);
				}
			}
		}
		clearstatcache();
	}
}
