<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Filter
 * @package    SOSO_Filter
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version v 1.0 2005/10/18
 * @created 15-四月-2008 17:12:05
 */
class SOSO_Filter_Cache extends SOSO_Filter_Abstract {

	public $mCacheHandle;
	
	public $mCacheTime = 3600; // 一小时

	public function Cache() {
		
	}

	public function doPreProcessing(SOSO_Frameworks_Context $context) {
		$tCacheTime = $context->get('cacheTime');
		if ($tCacheTime !== false) {
			$this->mCacheTime = $tCacheTime;
		}
		$tOptions = array('auto_hash'=>true,'use_encode'=>false,'cache_time'=>$this->mCacheTime,'cache_dir'=>'page_cache');
		$this->mCacheHandle =  SOSO_Cache::factory('file',$tOptions);
		$this->mCacheHandle->getKey($_SERVER['REQUEST_URI']);
		$tContent = $this->mCacheHandle->read($this->mCacheHandle->getKey());
		if ($tContent && strlen($tContent)) {
			$hash = md5($tContent);
			$headers = getallheaders();
			
			if (isset($headers['If-None-Match']) && ereg($hash, $headers['If-None-Match'])){
		    	header('HTTP/1.1 304 Not Modified');
		    	exit;
		    }
			echo $tContent;
			exit;
		}
		if (ob_get_level() == 0) {
			ob_start();
		}
	}

	public function doPostProcessing(SOSO_Frameworks_Context $context) {
		$tContent = ob_get_contents();
		if (!headers_sent($file,$line)) {
			$hash = md5($tContent);
			header("ETag: \"$hash\"");
		}
		$this->mCacheHandle->set($this->mCacheHandle->getKey(),$tContent,$this->mCacheTime);
	}
	
	public function setHeader($eTag){
		
	}
}
?>