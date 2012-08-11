<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Filter
 * @package    SOSO_Filter
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-кдтб-2008 17:12:06
 */
class SOSO_Filter_OB extends SOSO_Filter_Abstract {

	public function doPreProcessing(SOSO_Frameworks_Context $context){
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false) {
			ob_start('ob_gzhandler');
		}else{
			ob_start();
		}
		echo str_repeat(" ",255);
	}
	public function doPostProcessing(SOSO_Frameworks_Context $context){
		if (ob_get_level() > 0) {
			$content = ob_get_contents();
			ob_end_flush();	
		}
	}
}
?>