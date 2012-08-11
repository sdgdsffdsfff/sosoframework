<?php
/**
 * 基于memcache的缓存类实现
 * 
 * options: ['servers'=>array(array("host"=>'localhost','port'=>11211)),'memcache'=>'','host'=>'','port'=>'','timeout'=>1,'persistent'=>false] 
 * 
 * @example $tCache = new SOSO_Cache_Memcache(array('servers'=>array(array("host"=>'localhost','port'=>11211)),'memcache'=>'','host'=>'','port'=>'','timeout'=>1,'persistent'=>false));
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2008-07-24
 * @package cache
 *
 */
class SOSO_Cache_Memcache extends SOSO_Cache implements iterator,Countable {
	
	protected $mServers = array();
	private $mLinks;
	/**
	 * MEMCACHE_COMPRESSED => 2
	 *
	 * @var int
	 */
	protected $mCompress = 0;
	/**
	 * Enter description here...
	 *
	 * @var Memcache
	 */
	protected $mMemcache;
	protected $mHost = 'localhost';
	protected $mPort = 11211;
	protected $mTimeout = 1;
	protected $mPersistent = false;
	protected $mStats;
	protected $mItems;
	protected $mKeys = array();
	private $_Cached=false;
	
	public function __construct($options=array()){
		$this->mMemcache = new Memcache();
		if (!empty($options)) {
			$this->initialize($options);
		}
	}
	
	public function initialize($options){
		$this->mAllowOptions = array_merge($this->mAllowOptions,array('servers','memcache','port','host','timeout'));
		$this->setOptions($options,$this->mAllowOptions);
		
		if (isset($options['memcache']) && $options['memcache'] instanceof Memcache) {
			$this->mMemcache = $options['memcache'];
		}
		if (empty($this->mServers)) {
			$method = $this->mPersistent ? 'pconnect' : 'connect';
	        if (!$this->mMemcache->$method($this->mHost, $this->mPort,$this->mTimeout)){
	          throw new Exception(sprintf('Unable to connect to the memcache server (%s:%s).', $this->mHost,$this->mPort));
	        }
		}else{
			$this->_Cached = false;
			foreach ($this->mServers as $server){
				$port = isset($server['port']) ? $server['port'] : 11211;
				if (!$this->mMemcache->addServer($server['host'], $port, isset($server['persistent']) ? $server['persistent'] : true)) {
					throw new Exception(sprintf('Unable to connect to the memcache server (%s:%s).', $server['host'], $port));
				}
			}
		}
	}
	
	public function addServer($host,$port=11211){
		if (is_array($host)) {
			foreach ($host as $server){
				 call_user_func_array(array($this->mMemcache,'addServer'),$server);
			}
		}else{
			$this->mMemcache->addServer($host,$port);
		}
		$this->_Cached = false;
	}
	
	public function flush(){
		return $this->mMemcache->flush();
	}
	
	public function read($key){
		return $this->mMemcache->get($key);
	}
	
	/**
	 * 返回数据条目总数
	 *
	 * @return unknown
	 */
	public function count(){
		$this->getKeys();
		return count($this->mKeys);
	}
	/**
	 * 支持increase/decrease 等memcache原生操作
	 *
	 * @param 函数名 $method
	 * @param 参数 $args
	 * @return mixed
	 */
	public function __call($method,$args=array()){
		if (method_exists($this->mMemcache,$method)) {
			return call_user_func_array(array($this->mMemcache,$method),$args);
		}
	}
	
	protected function getStats($type=null,$slabid=null,$limit=null){
		$args = array_filter(func_get_args(),'strlen');
		return call_user_func_array(array($this->mMemcache,'getExtendedStats'),$args);
	}
	
	/**
	 * 获取已连接的服务器列表
	 *
	 */
	public function getServers(){
		if ($this->_Cached) return $this->mServers;
		$this->mServers = array();
		$stats = $this->getStats('items');
		foreach ($stats as $server=>$items){
			$this->mServers[] = $server;
			foreach (array_keys($items['items']) as $slab){
				$res = $this->getStats("cachedump",$slab,0);
				$this->mKeys = array_merge($this->mKeys,array_keys(current($res)));
			}
		}
		$this->_Cached = true;
		return $this->mServers;
	}
	
	/**
	 * 获取所有键
	 *
	 * @return array()
	 */
	public function getKeys(){
		$this->getServers();
		return $this->mKeys;
	}
	
	/**
	 * 创建/更新数据缓存
	 *
	 * @param string $k      key值
	 * @param string $v      数据
	 * @param mixed $expire  过期时间，如果为null，则使用cachetime的值
	 * @see set
	 * @return bool
	 */
	public function write($key,$data,$expire=null){
		if (is_null($expire) || strlen(trim($expire)) == 0) {
			$expire = $this->calExpireTime($this->mCacheTime);
		}else{
			$expire = $this->calExpireTime($expire);
		}
		$this->mMemcache->set($key,$data,$this->mCompress,$expire);
	}
	
	public function isCached($key){
		return $this->read($key);
	}
	
	public function delete($key){
		if(false !== ($index=array_search($key,$this->mKeys)) ){
			unset($this->mKeys[$index]);
		}
		return $this->mMemcache->delete($key);
	}
	
	public function isExpired($key){
		return $this->mMemcache->get($key) === false;
	}
	/**
	 * 垃圾回收,执行时间可能会较长
	 *
	 * @param bool $force 是否强制进行回收
	 * @return mixed
	 */
	public function gc($maxlifetime){
		return true;
	}
	
	public function next(){
		next($this->mKeys);
	}
	public function valid(){
		return each($this->mKeys) !== false;
	}
	
	public function key(){
		return current($this->mKeys);
	}
	
	public function current(){
		return $this->mMemcache->get($this->key());
	}
	
	public function rewind(){
		$this->getServers();
		reset($this->mKeys);
	}
}