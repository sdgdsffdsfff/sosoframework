<?php
/**
 * @author moonzhang
 * @version 1.0 2009-10-23
 * 
 * xcache 封装类
 */
class SOSO_Cache_Xcache extends SOSO_Cache {

	public function flush(){
		xcache_clear_cache('user');
	}
	
	public function read($key){
		return xcache_get($key);
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
		$key = trim($key);
		if (!strlen($key)) {
			$this->mCachekey = $this->getKey();
		}
		if (is_null($expire) || trim($expire) == "" || $expire <= 0) {
			return xcache_set($key,$data);
		}
		return xcache_set($key,$data,$expire);
	}
	
	public function isCached($key){
		return xcache_isset($key);
	}
	
	public function delete($key){
		return xcache_unset($key);
	}
	
	public function isExpired($key){
		return xcache_isset($key);
	}
	/**
	 * 垃圾回收,执行时间可能会较长
	 *
	 * @param bool $force 是否强制进行回收
	 * @return mixed
	 */
	public function gc($maxlifetime){}
}