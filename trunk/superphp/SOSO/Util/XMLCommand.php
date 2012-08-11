<?php
/**
 * SOSO Framework
 * 
 * @package SOSO_DB
 * @description XML�����֧࣬��DOMDocument/Simplexml���ֲ�����ʽ
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:20
 */
class SOSO_Util_XMLCommand {

	public $mDOM;
	private $mXMLFile;
	public static $instance;
	/**
	 * ��ȡDomDocument����
	 * 
	 * @param string $pUsername �û���
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
	 * ����XML�ļ�
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
	 * ָ���ڵ㴦����ӽڵ�
	 *
	 * @param string $nodeName   �ڵ�����
	 * @param string $nodeValue  �ڵ�ֵ
	 * @param array $attributes  �ڵ�����
	 * @param DomElement $pAppendto ֵΪnullʱ���ڸ��ڵ������
	 * @param array $attributes  �ڵ�����
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
	 * ָ��·��$pPath������ļ�
	 *
	 * @param string $pPath       ָ����Ҫ��ӽڵ��λ��
	 * @param string $pFileNode   �ڵ�����
	 * @param string $pNodevalue  �ڵ�ֵ
	 * @param array $attributes   �ڵ�����
	 * @access public 
	 * @return void
	 */
	public function addNode($pPath,$pFileNode,$pNodevalue=null,$attributes=array(),$pItem=0){
		$nodes = $this->xpath($pPath);$nodes->length;
		return $this->addChildNode($pFileNode,$pNodevalue,$nodes->item($pItem),$attributes);
	}
	
	/**
	 * ��ȡ�ڵ�
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
	 *  ��ӽڵ�����,setAttribute�ı���
	 * @param DomElement $pNode DomElement����
	 * @param array  $attributes
	 * @return object
	 */
	public function addAttribute($pNode,$attributes){
		return $this->setAttribute($pNode,$attributes);
	}
	
	/**
	 *  ���ýڵ�����,��������Բ����ڽ�����
	 * @param DomElement $pNode DomElement����
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
	 * ���ؽڵ���������
	 * @param DOMNode $pNode DOMNode����
	 * @access public
	 * @return mixed
	 */
	public function attributes(DOMNode $pNode){
		$pNode = simplexml_import_dom($pNode);
		return $pNode->attributes();
	}
	
	/**
	 * ȡ�õ�ǰ�ڵ��������ӽڵ���������
	 *
	 * @param mixed $pData  ��ΪDOMNodeList/SimpleXMLElement/DOMNode�ȶ���
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
	 * ��xpath�õ��ڵ����
	 * 
	 * @param string $pPath xpath���
	 * @return Array() SimplayXMLObject ����
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
	 * ��ȡָȡ�ڵ��������ӽڵ㡣
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
	 * ������¼��ѯ
	 *
	 * @param string $pXpath
	 * @param string $pCondtion
	 * @param boolean $pChangeNode �����Ƿ񷵻ؽڵ����Լ��Ƿ����ԭxpath����
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
	 * �������飬������ѯ�ڵ��������ӽڵ�
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
	 * ���ر�ɾ�����Ǹ��ڵ㣬��FileNode��ʽ���أ��Ա���������������
	 *
	 * @param mixed $pNodeData �ڵ���� ���� xpath �ַ���
	 * @param integer $pNodeSeq �ڵ�λ�ã���$pNodeDataΪ��xpathʱ�����ã���ָ�����λ��(-1)������false;0Ϊ��һ��
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
			 * ɾ��Ԫ��
			 */
			if (get_class($ele) === 'DOMElement') {
				try{
					$ele->parentNode->removeChild($ele);
					return $ele;
				}catch(Exception $e){
					return false;
				}
			}elseif (get_class($ele) === 'DOMNode'){ //ɾ���ڵ�
				$root->removeChild($ele);
				return $ele;
			}
		}elseif (is_string($pNodeData) && $pNodeSeq != -1){ //��xpath���в��ң�ɾ����$pNodeSeq+1���ڵ� 
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