<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Filter
 * @package    SOSO_Filter
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version v 1.0 2005/10/18
 * @created 15-кдтб-2008 17:12:08
 */
class SOSO_Filter_Session extends SOSO_Filter_Abstract {
	/**
	 * 
	 * @param context
	 */
	public function doPreProcessing(SOSO_Frameworks_Context $context){
		if (class_exists('User')) {
			if(empty($_SESSION['currentUser'])) {
				$_SESSION['currentUser'] = new User();
			}
		}
		$context->mCurrentUser = array_key_exists('currentUser',$_SESSION) ? $_SESSION['currentUser'] : null;
	}

	/**
	 * 
	 * @param context
	 */
	public function doPostProcessing(SOSO_Frameworks_Context $context) {
	}

}
?>