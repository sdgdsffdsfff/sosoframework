<?php
class SOSO_Base_Data_DataFormatNode
{
	// 支持的数据类型
	// set int string date time datetime timestamp float url
	private $name;
	private $alias;  //别名 type
	private $dataType; //当constraint为空时为type的值，不为空时为simpleType/restriction/base的值。注：必须有xs前缀
	private $valConstraints = array();//节点或属性值的约束条件
									//enumeration,  fractionDigits, length, maxExclusive, maxInclusive,  maxLength,
									//minExclusive, minInclusive, minLength, pattern, totalDigits, whiteSpace 
	private $nodeConstraints = array();//节点本身的约束条件：maxOccurs, minOccurs, default，fixed，mixed，	
	private $nodes = array();
	private $attributes = array();
	//对节点的值的限制条件
	static $valConsKeys = array("enumeration",  "fractionDigits", "length", "maxExclusive", "maxInclusive",  "maxLength", 
							"minExclusive", "minInclusive", "minLength", "pattern", "totalDigits", "whiteSpace" );
	//对节点本身的限制条件
	static $nodeConsKeys = array("maxOccurs",  "minOccurs", "default", "fixed", "mixed");
							  
	function __construct($name, $dataType='string'){
		$this->name = $name;
		$this->dataType = $dataType;
	}
	
	public function setName($name){
		$this->name = $name;
	}

	public function setAlias($alias){
		$this->alias = $alias;
	}

	public function setDataType($dataType){
		$this->dataType = $dataType;
	}
	
	//设置值的限制条件，支持数组，要求$key和$val中项目相同且对应
	public function setValConstraint($key, $val){
		$this->valConstraints = $this->filterConstraint(self::$valConsKeys, $key, $val);
	}
	
	//设置节点的限制条件，支持数组，要求$key和$val中项目相同且对应
	public function setNodeConstraint($key, $val){
		$this->nodeConstraints = $this->filterConstraint(self::$nodeConsKeys, $key, $val);
	}
	
	//设置值或节点的限制条件，支持数组，要求$key和$val中项目相同且对应
	//$flag = true值的限制条件， false节点的限制条件
	public function filterConstraint($refKey, $key, $val){
		$temp = array();
		
		//过滤掉不支持的限制条件
		if(!is_array($key) && !is_array($val) && in_array($key, $refKey)){
			$temp[$key] = $val;
		}
		if(is_array($key) && is_array($val) && (count($key) == count($val))){
			$refKey = array_fill_keys($refKey, '');
			$temp = array_intersect_key(array_combine($key, $val), $refKey);
		}
		return $temp;
	}
	
	//如果有别名就返回别名
	public function getName(){
		return empty($this->alias) ? $this->name : $this->alias;
	}
	
	public function getOrgName(){
		return $this->name;
	}
	
	public function getAlias(){
		return $this->alias;
	}
	
	public function getDataType(){
		return $this->dataType;
	}
	
	public function getValConstraints(){
		return $this->valConstraints;
	}
	
	public function getNodeConstraints(){
		return $this->nodeConstraints;
	}
	
	public function getAttributes(){
		return $this->attributes;
	}
	
	public function getNodes(){
		return $this->nodes;
	}
	
	public function addNode($node){
		$this->nodes[] = $node;
	}
	
	public function addAttribute($node){
		$this->attributes[] = $node;
	}
	
	public function addValConstraint($key, $val){
		$this->valConstraints = array_merge($this->valConstraints, $this->filterConstraint(self::$valConsKeys, $key, $val));
	}

	public function addNodeConstraint($key, $val){
		$this->nodeConstraints = array_merge($this->nodeConstraints, $this->filterConstraint(self::$nodeConsKeys, $key, $val));
	}	
	
	public function hasNodes(){
		return count($this->nodes) > 0;
	}
	
	public function hasAttributes(){
		return count($this->attributes) > 0;
	}
	
	public function hasDataType(){
		return !empty($this->dataType) && $this->dataType != 'container';
	}
	
	public function hasValConstraints(){
		return count($this->valConstraints) > 0;
	}
	
	public function hasNodeConstraints(){
		return count($this->nodeConstraints) > 0;
	}

	public function getNodeConstraint($key){
		$result = isset($this->nodeConstraints[$key]) ? $this->nodeConstraints[$key] : false;
		return $result;
	}

	public function getValConstraint($key){
		$result = isset($this->valConstraints[$key]) ? $this->valConstraints[$key] : false;
		return $result;
	}
}
?>