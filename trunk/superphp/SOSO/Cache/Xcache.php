<?php
/**
 * @author moonzhang
 * @version 1.0 2009-10-23
 * 
 * xcache ��װ��
 */
class SOSO_Cache_Xcache extends SOSO_Cache {

	public function flush(){
		xcache_clear_cache('user');
	}
	
	public function read($key){
		return xcache_get($key);
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
	 * ��������,ִ��ʱ����ܻ�ϳ�
	 *
	 * @param bool $force �Ƿ�ǿ�ƽ��л���
	 * @return mixed
	 */
	public function gc($maxlifetime){}
}