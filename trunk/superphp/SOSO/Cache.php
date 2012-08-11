<?php
/**
 * 
 * 所有cache实现类的基类,理论上可以存储任意数据
 * 
 * @author moonzhang (  zyfunny@gmail.com )
 * @version 1.0 2008-07-24
 * @package Cache
 */
abstract class SOSO_Cache {
	/**
	 * 是否启用cache
	 *
	 * @var bool
	 */
	protected $mCacheing = true;
	
	protected $mUseEncode = true;
	
	/**
	 * 指定启用垃圾回收的概率值N（[1，100]），
	 * 如果为0，则永不做回收，否则执行回收的几率为N%
	 *
	 * @var tinyint
	 */
	protected $mGcProbability = 1;
	
	/**
	 * 缓存文件的最大保存时间 (2592000 = 30天的秒数）
	 *
	 * @var int
	 */
	protected $mGcMaxlifetime = 2592000;
	
	/**
	 * 缓存时间，单位是秒（<= 0 为永不过期）
	 *
	 * @var int
	 */
	protected $mCacheTime = 86400;
	
	private static $mStorage=null;
	
	protected $mCachekey;
	
	/**
	 * 允许传递给构造函数的参数
	 * cache_time 		 指定缓存时间
	 * gc_probability 	 指定是否启用垃圾回收
	 * use_encode 		 是否使用数据加密
	 * caching			 是否启用缓存
	 * @see $mCacheTime,$mGcProbability,$mUseEncode,$mCaching
	 * @var array
	 */
	protected $mAllowOptions = array('cache_time','gc_probability','use_encode','caching','gc_maxlifetime');
	
	/**
	 * 支持file|db|memcache作为存储介质
	 *
	 * @var array
	 */
	private static $mStorages = array('file'=>'SOSO_Cache_File','db'=>'SOSO_Cache_DB','memcache'=>'SOSO_Cache_Memcache',
		'apc'=>'SOSO_Cache_Apc','xcache'=>'SOSO_Cache_Xcache','x'=>'SOSO_Cache_Xcache'
	);
	
	private static $instances = array();
	
	public static function factory($storage='file',$pOptions=array()){
		
		if (!$storage instanceof SOSO_Cache) {
			$tClass = '';
			
			if (array_key_exists($storage,self::$mStorages)) {
				$tClass = self::$mStorages[$storage];
			}else{
				throw new Exception("基于[$storage]的 cache类不存在",080);
			}
			if (class_exists($tClass)) {
				$storage = new $tClass($pOptions);
			}else{
				throw new Exception("cache类[$tClass]不存在",081);
			}
		}
		$tHash = md5($storage.serialize($pOptions));
		if (!isset(self::$instances[$tHash])) {
			return self::$instances[$tHash] = $storage;
		}
		return self::$instances[$tHash];
	}
	
	public function __toString(){
		return __CLASS__;
	}
	
	public static function getInstances(){
		if (empty(self::$instances)) {
			return null;
		}
		if (is_null(self::$mStorage)) {
			self::$mStorage = new SOSO_ObjectStorage();		
			foreach (self::$instances as $storage) {
				self::$mStorage->attach($storage);
			}
		}
		return self::$mStorage;
	}
	
	public function __destruct(){
		if ($this->mGcProbability <= 0){
			return false;
		}
		
		$this->garbageCollection();
	}
	/**
	 * 生成唯一的key,如果参数为空，则返回当前key
	 *
	 * @param mixed $sand 如果参数为空，则返回当前key
	 * @return string
	 */
	public function getKey($sand=''){
		if ($sand === '') {
			return $this->mCachekey;
		}
		return $this->mCachekey = md5(serialize($sand));
	}
	
	/**
	 * 设置缓存时间
	 *
	 * @param int $pUnixTime 参数为0或过期截止时间的时间戳的值
	 */
	public function setCachingTime($pUnixTime=0){
		$this->mCacheTime = $pUnixTime;
	}
	
	/**
	 * write的快捷键
	 *
	 * @param string $k      key值
	 * @param string $v      数据
	 * @param mixed $expire  过期时间，如果为null，则使用cachetime的值
	 * @return mixed
	 */
	public function set($k,$v,$expire=null){
		return $this->write($k,$v,$expire);
	}
	
	/**
	 * 是否缓存的开关
	 *
	 * @param bool $stat
	 */
	public function setCaching($stat){
		$this->mCacheing = $stat;
	}
	
	public function encode($data){
		if ($this->mUseEncode) {
			return base64_encode(serialize($data));	
		}
		return serialize($data);
	}
	
	public function decode($data){
		if ($this->mUseEncode) {
			return unserialize(base64_decode($data));
		}
		return unserialize($data);
	}
	/**
	 * 为成员属性赋值
	 *
	 * @param array $options
	 * @param array $allowed
	 * @return void
	 */
	protected function setOptions($options,$allowed=array()){
		if (!is_array($options) || empty($options)) {
			return false;
		}
		if (empty($allowed)) {
			$allowed = $this->mAllowOptions;
		}
		foreach ($allowed as $v){
			if (isset($options[$v]) /*&& strlen(trim($options[$v]))*/) {
				$this->setOption($v,$options[$v]);
			}
		}
	}
	public function setOption($key,$value){
        if(!class_exists('SOSO_Util_Util',false)){
            require_once(dirname(__FILE__).'/Util/Util.php');
        }
		$key = SOSO_Util_Util::magicName($key);
		$this->$key = $value;
	}
	/**
	 * 获得属性的值
	 *
	 * @param string $key  属性/参数的值，支持形如cahce_dir或mCacheDir二种方式
	 * @return mixed
	 */
	public function getOption($key){
		if (isset($this->$key)) {
			return $this->$key;
		}
		$key = SOSO_Util_Util::magicName($key);
		if (isset($this->$key)) {
			return $this->$key;
		}
		return null;
	}
	public function garbageCollection($force = false){
		static $last_run = 0;
        if (!$this->mCacheing) {
            return false;
        }
        // 概率基于时间及概率配置（ time（）& gc_probability)
        if (($force) /* ||($last_run&&$last_run < time()+$this->mGcTime)*/ || (rand(1, 100) <= $this->mGcProbability)) {
			$this->gc($this->mGcMaxlifetime);
            $last_run = time();
        }
	}
	
	
	/**
	 * 计算缓存截止日期
	 * 支持秒数、YY(YY)-mm-dd( HH:ii:ss) 、YYYYmmddHHiiss、timestamp四种方式
	 * 不推荐使用时间戳(timestamp)方式
	 * @param string $timestamp
	 * @return int
	 */
	public function calExpireTime($seconds=0){
		if ($seconds <= 0) {
			return $seconds;
		}
		$p = "#(\d{2}|\d{4})\-\d{1,2}\-\d{1,2}\s*(?=\d{1,2}:\d{1,2}:\d{1,2})#s";
		if ($seconds[0] == '+' || $seconds < 946681200) {
			//strtotime('1999-12-31') == 946681200
			return time() + $seconds;	
		}elseif (preg_match($p,$seconds)){
			return strtotime($seconds);
		}else{//yyyymmddhhiiss
			$year = substr($seconds, 0, 4);
            $month = substr($seconds, 4, 2);
            $day = substr($seconds, 6, 2);
            $hour = substr($seconds, 8, 2);
            $minute = substr($seconds, 10, 2);
            $second = substr($seconds, 12, 2);
            if (!$ret=mktime($hour, $minute, $second, $month, $day, $year)){
            	return $seconds;
            }
            return $ret;
		}
	}
	
	/**
	 * 清空此对象的cache_dir下所有cache文件
	 *
	 * @return void
	 */
	abstract function flush();
	
	abstract function read($key);
	
	/**
	 * 创建/更新数据缓存
	 *
	 * @param string $k      key值
	 * @param string $v      数据
	 * @param mixed $expire  过期时间，如果为null，则使用cachetime的值
	 * @see set
	 * @return bool
	 */
	abstract function write($key,$data,$expire=null);
	
	abstract function isCached($key);
	
	abstract function delete($key);
	
	abstract function isExpired($key);
	/**
	 * 垃圾回收,执行时间可能会较长
	 *
	 * @param bool $force 是否强制进行回收
	 * @return mixed
	 */
	abstract function gc($maxlifetime);
}
