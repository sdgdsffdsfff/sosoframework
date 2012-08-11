<?php
class SOSO_Base_Data_DataFormatBuilder
{
	//节点为元素时，节点本身可能的限制条件，是SOSO_Base_Data_DataFormatNode::$nodeConsKeys的子集
	static $elePerKeys = array("type", "default", "fixed", "maxOccurs", "minOccurs");
	//节点为属性时，节点本身可能的限制条件，是SOSO_Base_Data_DataFormatNode::$nodeConsKeys的子集
	static $attriPerKeys = array("type", "default", "fixed", "used");
	private static $DemoDataMap = array(
		'container' => '',
		'string' => 'string',
		'integer' => '365',
		'decimal' => '100',
		'boolean' => 'yes',
		'float' => '12.98',
		'double' => '3.1415',
		'date' => '2009-01-01',
		'time' => '10:00',
		'datetime' => '2009-01-01 10:00',
		'anyURI' => 'http://www.soso.com/',
	);

	public static function buildXml(SOSO_Base_Data_DataFormat $format){
		$result = '<?xml version="1.0" encoding="'.$format->getEncoding().'"?>';
		$result .= "\n";
		$result .= self::buildNodeXml($format->getRootNode());
		return $result;
	}

	private static function buildNodeXml(SOSO_Base_Data_DataFormatNode $node){
		$ss = array();
		if($node->hasAttributes()){
			$attr = self::buildAttributeXml($node);
			$ss[] = sprintf('<%s %s>', $node->getName(), $attr);
		}
		else{
			$ss[] = sprintf('<%s>', $node->getName());
		}
		if($node->hasNodes()){
			foreach($node->getNodes() as $subNode){
				$ss[] = self::buildNodeXml($subNode);
			}
		}
		$ss[] = self::getNodeDemoData($node);
		$ss[] = sprintf('</%s>', $node->getName());
		$result = implode("\n", $ss);
		$minOccurs = $node->getNodeConstraint('minOccurs');
		if(is_numeric($minOccurs) && $minOccurs > 1){
			$result = str_repeat($result, $node->getNodeConstraint('minOccurs'));
		}
		return $result;
	}

	private static function buildAttributeXml(SOSO_Base_Data_DataFormatNode $node){
		$ss = array();
		foreach($node->getAttributes() as $subNode){
			$ss[] = sprintf('%s="%s"', $subNode->getName(), self::getAttributeDemoData($subNode));
		}
		$result = implode(' ', $ss);
		return $result;
	}
	
	private static function getAttributeDemoData($node){
		$fix = $node->getNodeConstraint('fixed');
		if($fix !== false && $fix != '')
			$result = $fix;
		else
			$result = isset(self::$DemoDataMap[$node->getDataType()]) ? self::$DemoDataMap[$node->getDataType()] : $node->getDataType();
		return $result;
	}

	private static function getNodeDemoData($node){
		$result = self::getAttributeDemoData($node);
		switch($node->getDataType()){
			case 'string':
				$result = '<![CDATA['.$result.']]>';
				break;
			case 'anyURI':
				$result = '<![CDATA['.$result.']]>';
				break;
		}
		return $result;
	}

	////////////////////////////////
	public static function buildXsd(SOSO_Base_Data_DataFormat $format){
		$xsd = array();
		$xsd[] = '<?xml version="1.0"?>';
		//$xsd[] = '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" targetNamespace="http://www.w3school.com.cn" xmlns="http://www.w3school.com.cn" elementFormDefault="qualified">';
		$xsd[] = '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">';
		$xsd[] =  self::buildElementXsd($format->getRootNode());
		$xsd[] = '</xs:schema>';
		$result = implode("\n", $xsd);
		return $result;
	}
	
	private static function buildElementXsd($node){
		$elementXsd = '';
		if(!$node->hasNodes() && !$node->hasAttributes()){
			$elementXsd = self::buildSimpleElement($node);
		}else{
			$elementXsd = self::buildComplexElement($node);
		}
		return $elementXsd;
	}

	private static function buildSimpleElement($node){
		return self::buildSimpleNode($node, true);
	}

	private static function buildAttributeXsd($node){
		return self::buildSimpleNode($node, false);
	}

	//构造简单节点，$flag：true 元素； false 属性
	private static function buildSimpleNode($node, $flag){
		$simpleNodeXsd = '';
		$keys = array();
		$keys = $flag ? self::$elePerKeys : self::$attriPerKeys;
		if($node->hasDataType() && $node->hasValConstraints()){//有限定
			$simpleNodeXsd = self::buildFacets($node, $flag, $keys);
		}else{
			//构造含datatype的节点
			$simpleNodeXsd = self::buildBaseNode($node, $flag, $keys, true);
		}
		return $simpleNodeXsd;
	}


	//无限定的简单元素或属性，$flag：true 元素； false 属性
	private static function buildBaseNode($node, $flag, $perKeys, $dataTypeFlag){
		$baseNodeXsd = '';
		$type = $flag ? "element" : "attribute";
		$baseNodeXsd = sprintf('<xs:%s name="%s" ', $type, $node->getName());
		if($dataTypeFlag && $node->hasDataType()){
			$baseNodeXsd .= sprintf('type="xs:%s" ', $node->getDataType());
		}
		if($node->hasNodeConstraints()){
			$perKeys = array_fill_keys($perKeys, '');
			foreach (array_intersect_key($node->getNodeConstraints(), $perKeys) as $k=>$v){
				//maxOccurs, minOccurs值为1时就不显示，因为默认为1
				if((!strcmp($k, "maxOccurs") || !strcmp($k, "minOccurs")) && $v == 1){
					continue;
				}else {
					$baseNodeXsd .= sprintf('%s="%s" ', $k, $v);
				}
			}			
		}
		$baseNodeXsd .= '/>';
		return $baseNodeXsd;
	}

	//带限定的简单元素或属性，$flag：true 元素； false 属性
	private static function buildFacets($node, $flag, $perKeys){
		$facetXsd = array();
		$tmp = '';
		$type = $flag ? "element" : "attribute";
		$tmp = self::buildBaseNode($node, $flag, $perKeys, false);
		$facetXsd[] = str_replace('/>', '>', $tmp);
		$facetXsd[] = '<xs:simpleType>';
		$facetXsd[] = sprintf('<xs:restriction base="xs:%s">', $node->getDataType());
		foreach ($node->getValConstraints() as $k=>$v){
			$facetXsd[] = sprintf('<xs:%s value="%s"/>', $k, $v);
		}
		$facetXsd[] = '</xs:restriction>';
		$facetXsd[] = '</xs:simpleType>';
		$facetXsd[] = sprintf('</xs:%s>', $type);
		return implode("\n", $facetXsd);
	}
	
	//构造复合元素，支持混合文本
	private static function buildComplexElement($node){
		$comEleXsd = array();
		$tmp = '';
		//符合元素节点上不能有fixed属性
		$comPerKeys = array("type", "default", "maxOccurs", "minOccurs");
		$tmp = self::buildBaseNode($node, true, $comPerKeys, false);
		$comEleXsd[] = str_replace('/>', '>', $tmp);
		$comEleXsd[] = '<xs:complexType  mixed="true">'; //支持混合文本
		if(!$node->hasNodes() && $node->hasDataType() && $node->hasAttributes()){//仅含文本的复合元素
			$comEleXsd[] = '<xs:simpleContent>';
			$comEleXsd[] = sprintf('<xs:extension base="xs:%s">', $node->getDataType());
			foreach ($node->getAttributes() as $v){
				$comEleXsd[] = self::buildAttributeXsd($v);
			}
			$comEleXsd[] = '</xs:extension>';
			$comEleXsd[] = '</xs:simpleContent>';
		}else {
			if($node->hasNodes()){
				$comEleXsd[] = '<xs:sequence>';
				foreach ($node->getNodes() as $subNode){
					$comEleXsd[] = self::buildElementXsd($subNode);
				}
				$comEleXsd[] = '</xs:sequence>';
			}
			if($node->hasAttributes()){
				foreach ($node->getAttributes() as $v){
					$comEleXsd[] = self::buildAttributeXsd($v);
				}
			}
		}
		$comEleXsd[] = '</xs:complexType>';
		$comEleXsd[] = '</xs:element>';
		return implode("\n", $comEleXsd);
	}
}
?>