<?php
/**
 * @author moonzhang
 * @version 1.0 2009-10-23
 * 
 * APC 封装类
 */
class SOSO_Cache_Apc extends SOSO_Cache {

	public function flush(){
		return apc_clear_cache('user');
	}
	
	/**
	 * alias for apc_add
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @param int $ttl
	 * @return bool
	 */
	public function add($key,$val,$ttl=0){
		return apc_add($key,$val,$ttl);
	}
	public function read($key){
		return apc_fetch($key);
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
			return apc_store($key,$data);
		}
		return apc_store($key,$data,$expire);
	}
	
	public function isCached($key){
		return apc_fetch($key) !== false;
	}
	
	public function delete($key){
		return apc_delete($key);
	}
	
	public function isExpired($key){
		$exists = false;
		apc_fetch($key,$exists);
		return $exists;
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
}
