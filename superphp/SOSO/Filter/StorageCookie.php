<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Filter
 * @package    SOSO_Filter
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version v 1.0 2005/10/18
 * @created 15-кдтб-2008 17:12:09
 */
class SOSO_Filter_StorageCookie extends SOSO_Filter_Abstract {

	const COOKIE_PART_MAX_LENGTH = 2048;
	public $mCookieName;

	public function doPreProcessing(SOSO_Frameworks_Context $context){
	}

	public function doPostProcessing(SOSO_Frameworks_Context $context){
	}

	/**
	 * 
	 * @param str
	 */
	public function set_part_cookie($str){
	}

}
?>