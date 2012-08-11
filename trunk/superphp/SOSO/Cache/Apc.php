<?php
/**
 * @author moonzhang
 * @version 1.0 2009-10-23
 * 
 * APC ��װ��
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
	 * ����/�������ݻ���
	 *
	 * @param string $k      keyֵ
	 * @param string $v      ����
	 * @param mixed $expire  ����ʱ�䣬���Ϊnull����ʹ��cachetime��ֵ
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
	 * ��������,ִ��ʱ����ܻ�ϳ�
	 *
	 * @param bool $force �Ƿ�ǿ�ƽ��л���
	 * @return mixed
	 */
	public function gc($maxlifetime){
		return true;
	}
}
