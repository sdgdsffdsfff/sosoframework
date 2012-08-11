<?php
/**
 * SOSO Framework
 * 
 * @package SOSO_DB
 * @description XML操作类，支持DOMDocument/Simplexml二种操作方式
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:20
 */
class SOSO_Util_XMLCommand {

	public $mDOM;
	private $mXMLFile;
	public static $instance;
	/**
	 * 获取DomDocument对象
	 * 
	 * @param string $pUsername 用户名
	 * @access public
	 * @return DomDocument
	 */
	public function __construct($pXMLFile){
		$this->mDOM = new DOMDocument();
		$this->mDOM->preserveWhiteSpace = false;
		$this->mDOM->load($pXMLFile);
		$this->mXMLFile = $pXMLFile;
	}
	public function __destruct(){
		$this->mDOM = null;
	}
	public static function &getInstance($pXMLFile=NULL){
		if (is_null(self::$instance)) {
			self::$instance = new self($pXMLFile);
		}
		return self::$instance;
	}
	
	/**
	 * Delegator
	 *
	 * @param string $pMethod
	 * @param mixed $pArgs
	 * @return boolean
	 */
	public function __call($pMethod,$pArgs){
		if (method_exists($this->mDOM,$pMethod)) {
			call_user_func_array(array($this->mDOM,$pMethod),$pArgs);
		}else{
			return false;
		}
	}
	/** 
	 * 保存XML文件
	 *
	 * @return boolean
	 */
	public function saveXML(){
		$this->mDOM->formatOutput = true;
		$xml = $this->mDOM->saveXML();
		return @file_put_contents($this->mXMLFile,$xml);
	}
	
	public function setFile($pFile){
		$this->mXMLFile = $pFile;
	}
	
	/**
	 * 指定节点处添加子节点
	 *
	 * @param string $nodeName   节点名称
	 * @param string $nodeValue  节点值
	 * @param array $attributes  节点属性
	 * @param DomElement $pAppendto 值为null时，在根节点下添加
	 * @param array $attributes  节点属性
	 * @return mixed
	 */
	public function addChildNode($nodeName,$nodeValue=null,$pAppendto=null,$attributes=array()){
		$appendTo = (!is_object($pAppendto))?$this->mDOM->documentElement:$pAppendto;
		try {
			$node = $this->mDOM->createElement($nodeName,$nodeValue);
		}catch (Exception $e){
			return false;
		}
		if (!empty($attributes)) {
			$this->setAttribute($node,$attributes);
		}
		return $appendTo->appendChild($node);
	}

	/**
	 * 指定路径$pPath下添加文件
	 *
	 * @param string $pPath       指定的要添加节点的位置
	 * @param string $pFileNode   节点名称
	 * @param string $pNodevalue  节点值
	 * @param array $attributes   节点属性
	 * @access public 
	 * @return void
	 */
	public function addNode($pPath,$pFileNode,$pNodevalue=null,$attributes=array(),$pItem=0){
		$nodes = $this->xpath($pPath);$nodes->length;
		return $this->addChildNode($pFileNode,$pNodevalue,$nodes->item($pItem),$attributes);
	}
	
	/**
	 * 获取节点
	 *
	 * @param string $pXpath
	 * @param integer $pItem
	 * @return mixed
	 */
	public function getNode($pXpath,$pItem=0){
		$tNodelist = $this->xpath($pXpath);
		if ($tNodelist->length-1 < $pItem) {
			return false;
		}else{
			return $tNodelist->item($pItem);
		}		
	}
	
	/**
	 *  添加节点属性,setAttribute的别名
	 * @param DomElement $pNode DomElement对象
	 * @param array  $attributes
	 * @return object
	 */
	public function addAttribute($pNode,$attributes){
		return $this->setAttribute($pNode,$attributes);
	}
	
	/**
	 *  设置节点属性,如果该属性不存在将创建
	 * @param DomElement $pNode DomElement对象
	 * @param array  $attributes
	 * @return mixed
	 */
	public function setAttribute(&$pNode,$attributes){
		foreach($attributes as $k=>$v){
			try{
				$pNode->setAttribute($k,"$v");
			}catch(Exception $e){
				return false;
			}
		}
		return $pNode;
	}
	
	/**
	 * 返回节点对象的属性
	 * @param DOMNode $pNode DOMNode对象
	 * @access public
	 * @return mixed
	 */
	public function attributes(DOMNode $pNode){
		$pNode = simplexml_import_dom($pNode);
		return $pNode->attributes();
	}
	
	/**
	 * 取得当前节点下所有子节点属性数组
	 *
	 * @param mixed $pData  可为DOMNodeList/SimpleXMLElement/DOMNode等对象
	 * @return array()
	 */
	public function attributes2Array($pData){
		$return = array();
		if ($pData instanceof DOMNodeList) {
			$len = $pData->length;
			for($i=0;$i<$len;$i++){
				$return[$i] = current((array)$this->attributes($pData->item($i)));
			}
		}elseif ($pData instanceof DOMNode){
			$return[] = current((array)$this->attributes($pData));
		}elseif ($pData instanceof SimpleXMLElement){
			$pMixed = (array)$pData->attributes();
			$return[] = current($pMixed);
		}
		return $return;
	}
	
	/**
	 * 由xpath得到节点对象
	 * 
	 * @param string $pPath xpath语句
	 * @return Array() SimplayXMLObject 数组
	 */
	public function getNodeByPath($pPath){
		
		$xml = simplexml_import_dom($this->mDOM);
		return $xml->xpath($pPath);
	}
		
	/**
	 * xpath query
	 *
	 * @param string $pPath
	 * @return DOMNodeList
	 */
	public function xpath($pPath){
		$pPath = $this->chop($pPath);
		$xpath = new DOMXPath($this->mDOM);
		return $xpath->query($pPath);
	}
	
	/**
	 * 获取指取节点下所有子节点。
	 *
	 * @param string $pXpath
	 * @return array
	 */
	public function fetchAll($pXpath,$pCondition=""){
		if (strlen($pCondition) > 0) {
			$pCondition = '['.$pCondition.']';
		}
		$pXpath = $this->chop($pXpath).'/*'.$pCondition;
		$tList = $this->xpath($pXpath);
		$return = array();
		if ($tList && $tList->length > 0) {
			for ($i=0;$i<$tList->length;$i++){
				$return[$i]['nodeName']  = $tList->item($i)->nodeName;
				$return[$i]['nodeValue'] = $tList->item($i)->nodeValue;
				foreach ($tList->item($i)->attributes as $k=>$v) {
					$return[$i][$k] = $v->value;
				}
			}
		}
		return $return;
	}
	
	/**
	 * 单条记录查询
	 *
	 * @param string $pXpath
	 * @param string $pCondtion
	 * @param boolean $pChangeNode 控制是否返回节点属性及是否更改原xpath开关
	 * @return mixed
	 */
	public function _select(&$pXpath,$pCondition='',$pChangeNode=true){
		if (strlen($pCondition) > 0) {
			$pCondition = '['.$pCondition.']';
		}
		//$tXpath = dirname($this->chop($pXpath)).'/*'.$pCondition;
		$tXpath = preg_replace('/(\w+\/[^\[]+)(?:\[.+\])?/si','\\1',$pXpath).$pCondition;
		$tList = $this->xpath($tXpath);
		$return = array();
		if (1 != $tList->length){
			return false;
		}else{
			if (!$pChangeNode) {
				foreach ($tList->item(0)->attributes as $k=>$v) {
					$return[$k] = $v->value;
				}
				return $return;
			}else{
				$pXpath = $tXpath;
				return true;
			}
		}
	}
	
	/**
	 * 返回数组，包含查询节点下所有子节点
	 *
	 * @param string $XPath
	 * @return array()
	 */
	public function listSubnode($XPath){
		$return = array();
		$tRes = $this->getNodeByPath($XPath);
		foreach ($tRes as $k=>$v){
			$attr = $v->attributes();
			$v = (array)$v;
			$return[$k]['attributes'] = current((array)$attr);
			$return[$k]['nodeValue'] = isset($v[0])?$v[0]:'';
			$return[$k]['subNodes'] = $this->listSubnode($XPath.'/*');
		}
		return $return;
	}
	
	/**
	 * 返回被删除的那个节点，以FileNode形式返回，以便做后续操作处理
	 *
	 * @param mixed $pNodeData 节点对象 或者 xpath 字符串
	 * @param integer $pNodeSeq 节点位置，当$pNodeData为了xpath时起作用，不指定结点位置(-1)，返回false;0为第一个
	 * @return mixed
	 */
	public function removeNode($pNodeData,$pNodeSeq=-1){
		$root = $this->mDOM->documentElement;
		if (is_object($pNodeData)) {
			if ($pNodeData instanceof SimpleXMLElement) {
				$ele = dom_import_simplexml($pNodeData);
			}elseif ($pNodeData instanceof DOMNode){
				$ele = $pNodeData;
			}else{
				return false;
			}
			/**
			 * 删除元素
			 */
			if (get_class($ele) === 'DOMElement') {
				try{
					$ele->parentNode->removeChild($ele);
					return $ele;
				}catch(Exception $e){
					return false;
				}
			}elseif (get_class($ele) === 'DOMNode'){ //删除节点
				$root->removeChild($ele);
				return $ele;
			}
		}elseif (is_string($pNodeData) && $pNodeSeq != -1){ //按xpath进行查找，删除第$pNodeSeq+1个节点 
			$arr = $this->xpath($pNodeData);
			return ($pNodeSeq > $arr->length)?false:$this->removeNode($arr->item($pNodeSeq),$pNodeSeq);
		}else{
			return false;
		}
	}  
	
	private function chop($pPath){
		if (strrchr($pPath,'/') == '/') {
			$pPath = substr($pPath,0,strlen($pPath)-1);
		}
		return $pPath;
	}
}
?>