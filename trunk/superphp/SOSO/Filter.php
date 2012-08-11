<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO
 * @description 过滤器类
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version v 1.0 2005/11/11
 * @created 15-四月-2008 16:59:20
 */
final class SOSO_Filter extends SOSO_Filter_Abstract {

	protected $mFilterConfig;
	private static $instance=null;

	/**
	 * 
	 * @param pFilterConfig
	 */
	private function __construct($pFilterConfig = NULL){
		$this->mFilterConfig = $pFilterConfig;
	}
	private function __clone(){
		
	}
	
	/**
	 * 获得filter对象
	 *
	 * @param mixed $pFilterConfig
	 * @return SOSO_Filter
	 */
	public static function getInstance($pFilterConfig=NULL){
		if (is_null(SOSO_Filter::$instance)) {
			self::$instance = new SOSO_Filter($pFilterConfig);
		}
		return self::$instance;
	}
	
	public function getFilterConfig(){
		return $this->mFilterConfig;
	}

	/**
	 * 
	 * @param context
	 * @param chain
	 */
	public final function doFilter(SOSO_Filter_Abstract $filter, SOSO_Controller_Abstract $chain){
		$context = SOSO_Frameworks_Context::getInstance();
		$filter->doPreProcessing($context);
		$chain->dispatch();
		$filter->doPostProcessing($context);
	}

	public function doPreProcessing(SOSO_Frameworks_Context $context){
		
	}

	public function doPostProcessing(SOSO_Frameworks_Context $context){
	}

}
?>