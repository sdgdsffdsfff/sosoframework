<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_Frameworks
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:21
 */
/**
 * 需要进一步完善
 *
 */
class SOSO_Frameworks_Config /*extends SOSO_Object*/ {

	/**
	 * 配置初始化
	 */
	public static function initialize($pConfigFile='web.xml'){
		if ($pConfigFile == 'xml'){
			$xmlFile = dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.'web.'.$pConfigFile;
		}else{
			$xmlFile = $pConfigFile;;
		}

		if (!file_exists($xmlFile)) {
			throw new SOSO_Exception("web.xml not exists",-1);
		}
		$xml = simplexml_load_file($xmlFile);
		$registry = SOSO_Frameworks_Registry::getInstance();
		$registry->set('root_path',realpath(dirname($xmlFile)));
		$registry->set('project',$xml);
		$registry->set('system',$xml->system);
		
		$databases = array();
		if ($xml->databases) {
			foreach ($xml->databases->database as $k=>$v){
				$databases[] = current((array)($v->attributes()));
			}
			$registry->set('databases',$databases);
		}
	}
	
	/**
	 * 不建议再使用
	 * @param string $pParam 获得param节点值。
	 * @return string
	 * @deprecated
	 * @param pParam
	 */
	public static function getParam($pParam='',$pParentNode='system'){
		return self::getSystemPath($pParam);
//		$registry = SOSO_Frameworks_Registry::getInstance();
//		if (!(strlen($pParam) && strlen($pParentNode))) {
//			return $registry->get('root_path');
//		}
//		if ($registry->isRegistered($pParentNode)) {
//			return $registry->get('root_path').DIRECTORY_SEPARATOR.strval($registry->get($pParentNode)->$pParam);
//		}
	}
	
	public static function getSystemPath($path=''){
		$registry = SOSO_Frameworks_Registry::getInstance();
		if (!strlen($path)) {
			return $registry->get('root_path');
		}
		return $registry->get('root_path').DIRECTORY_SEPARATOR.strval($registry->get("system")->$path);
	}
	
	public static function document_root_path(){
		$registry = SOSO_Frameworks_Registry::getInstance();
		return dirname($registry->get('root_path'));
	}
	
	public static function getMode(){
		$project = SOSO_Frameworks_Registry::getInstance()->get('project')->attributes();
		return strtolower(strval($project['mode']));
	}
	
	public function getAllParams(){
		return self::getPath("//project/params",1);
	}
	
	public static function getConfigParam($name){
		$params = self::getPath("//project/params");
		if($params) {
	      foreach($params as $row) {
	        if($row["name"]==$name) {
	          return iconv("UTF-8", "GB18030", $row["value"]);
	        }
	      }
	    }
	    return "";
	}
	/**
	 * @param string(XPath) $path 获得符合xpath的数组。
	 * @desc 获得所有子节点属性值.
	 * @param bool $assoc 关联数组形式
	 * @return array()
	 */
	public static function getPath($path,$assoc=false){
		if (!strlen($path)) {
			return array();
		}
		$registry = SOSO_Frameworks_Registry::getInstance();
		$xml = $registry->get('project');
		if (is_null($xml)) {
			return array();
		}
		$elements = $xml->xpath($path);
		
		$return = array();
		$item = array();
		if (count($elements) == 0) {
			return array();
		}
		
		if (count($elements[0]->children()) == 0) {
			$ret = strval($elements[0]);
			if(0 === strlen($ret) && $elements[0]->attributes()){
				$ret = array();
				foreach($elements[0]->attributes() as $k=>$v){
					$ret[$k] = strval($v);
				}
				return $ret;
			}
			return $ret;
		}
		
		foreach ($elements[0] as $key=>$element) {
			if($element->attributes()){
	      		foreach ($element->attributes() as $k => $value) {
	        		$item[$k] = (string)$value;
	      		}
	      		if($assoc){
					$return[$key] = $item;
				}else
					$return[] = $item;
			}else{
				if($assoc){
					$return[$key] = strval($element);
				}else
					$return[] = strval($element);
			}
	  }
	  return $return;
	}
	
	public static function isCached() {
	    $registry = SOSO_Frameworks_Registry::getInstance();
	    $user_config = $registry->offsetGet('project');
	    return (strtolower((string)$user_config->pages['cache'])=='true');
	}
}
