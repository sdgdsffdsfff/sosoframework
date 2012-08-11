<?php
/**
 * 
 * ����cacheʵ����Ļ���,�����Ͽ��Դ洢��������
 * 
 * @author moonzhang (  zyfunny@gmail.com )
 * @version 1.0 2008-07-24
 * @package Cache
 */
abstract class SOSO_Cache {
	/**
	 * �Ƿ�����cache
	 *
	 * @var bool
	 */
	protected $mCacheing = true;
	
	protected $mUseEncode = true;
	
	/**
	 * ָ�������������յĸ���ֵN��[1��100]����
	 * ���Ϊ0�������������գ�����ִ�л��յļ���ΪN%
	 *
	 * @var tinyint
	 */
	protected $mGcProbability = 1;
	
	/**
	 * �����ļ�����󱣴�ʱ�� (2592000 = 30���������
	 *
	 * @var int
	 */
	protected $mGcMaxlifetime = 2592000;
	
	/**
	 * ����ʱ�䣬��λ���루<= 0 Ϊ�������ڣ�
	 *
	 * @var int
	 */
	protected $mCacheTime = 86400;
	
	private static $mStorage=null;
	
	protected $mCachekey;
	
	/**
	 * �����ݸ����캯���Ĳ���
	 * cache_time 		 ָ������ʱ��
	 * gc_probability 	 ָ���Ƿ�������������
	 * use_encode 		 �Ƿ�ʹ�����ݼ���
	 * caching			 �Ƿ����û���
	 * @see $mCacheTime,$mGcProbability,$mUseEncode,$mCaching
	 * @var array
	 */
	protected $mAllowOptions = array('cache_time','gc_probability','use_encode','caching','gc_maxlifetime');
	
	/**
	 * ֧��file|db|memcache��Ϊ�洢����
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
				throw new Exception("����[$storage]�� cache�಻����",080);
			}
			if (class_exists($tClass)) {
				$storage = new $tClass($pOptions);
			}else{
				throw new Exception("cache��[$tClass]������",081);
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
	 * ����Ψһ��key,�������Ϊ�գ��򷵻ص�ǰkey
	 *
	 * @param mixed $sand �������Ϊ�գ��򷵻ص�ǰkey
	 * @return string
	 */
	public function getKey($sand=''){
		if ($sand === '') {
			return $this->mCachekey;
		}
		return $this->mCachekey = md5(serialize($sand));
	}
	
	/**
	 * ���û���ʱ��
	 *
	 * @param int $pUnixTime ����Ϊ0����ڽ�ֹʱ���ʱ�����ֵ
	 */
	public function setCachingTime($pUnixTime=0){
		$this->mCacheTime = $pUnixTime;
	}
	
	/**
	 * write�Ŀ�ݼ�
	 *
	 * @param string $k      keyֵ
	 * @param string $v      ����
	 * @param mixed $expire  ����ʱ�䣬���Ϊnull����ʹ��cachetime��ֵ
	 * @return mixed
	 */
	public function set($k,$v,$expire=null){
		return $this->write($k,$v,$expire);
	}
	
	/**
	 * �Ƿ񻺴�Ŀ���
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
	 * Ϊ��Ա���Ը�ֵ
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
	 * ������Ե�ֵ
	 *
	 * @param string $key  ����/������ֵ��֧������cahce_dir��mCacheDir���ַ�ʽ
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
        // ���ʻ���ʱ�估�������ã� time����& gc_probability)
        if (($force) /* ||($last_run&&$last_run < time()+$this->mGcTime)*/ || (rand(1, 100) <= $this->mGcProbability)) {
			$this->gc($this->mGcMaxlifetime);
            $last_run = time();
        }
	}
	
	
	/**
	 * ���㻺���ֹ����
	 * ֧��������YY(YY)-mm-dd( HH:ii:ss) ��YYYYmmddHHiiss��timestamp���ַ�ʽ
	 * ���Ƽ�ʹ��ʱ���(timestamp)��ʽ
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
	 * ��մ˶����cache_dir������cache�ļ�
	 *
	 * @return void
	 */
	abstract function flush();
	
	abstract function read($key);
	
	/**
	 * ����/�������ݻ���
	 *
	 * @param string $k      keyֵ
	 * @param string $v      ����
	 * @param mixed $expire  ����ʱ�䣬���Ϊnull����ʹ��cachetime��ֵ
	 * @see set
	 * @return bool
	 */
	abstract function write($key,$data,$expire=null);
	
	abstract function isCached($key);
	
	abstract function delete($key);
	
	abstract function isExpired($key);
	/**
	 * ��������,ִ��ʱ����ܻ�ϳ�
	 *
	 * @param bool $force �Ƿ�ǿ�ƽ��л���
	 * @return mixed
	 */
	abstract function gc($maxlifetime);
}
