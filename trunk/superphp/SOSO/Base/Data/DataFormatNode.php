<?php
class SOSO_Base_Data_DataFormatNode
{
	// ֧�ֵ���������
	// set int string date time datetime timestamp float url
	private $name;
	private $alias;  //���� type
	private $dataType; //��constraintΪ��ʱΪtype��ֵ����Ϊ��ʱΪsimpleType/restriction/base��ֵ��ע��������xsǰ׺
	private $valConstraints = array();//�ڵ������ֵ��Լ������
									//enumeration,  fractionDigits, length, maxExclusive, maxInclusive,  maxLength,
									//minExclusive, minInclusive, minLength, pattern, totalDigits, whiteSpace 
	private $nodeConstraints = array();//�ڵ㱾���Լ��������maxOccurs, minOccurs, default��fixed��mixed��	
	private $nodes = array();
	private $attributes = array();
	//�Խڵ��ֵ����������
	static $valConsKeys = array("enumeration",  "fractionDigits", "length", "maxExclusive", "maxInclusive",  "maxLength", 
							"minExclusive", "minInclusive", "minLength", "pattern", "totalDigits", "whiteSpace" );
	//�Խڵ㱾�����������
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
	
	//����ֵ������������֧�����飬Ҫ��$key��$val����Ŀ��ͬ�Ҷ�Ӧ
	public function setValConstraint($key, $val){
		$this->valConstraints = $this->filterConstraint(self::$valConsKeys, $key, $val);
	}
	
	//���ýڵ������������֧�����飬Ҫ��$key��$val����Ŀ��ͬ�Ҷ�Ӧ
	public function setNodeConstraint($key, $val){
		$this->nodeConstraints = $this->filterConstraint(self::$nodeConsKeys, $key, $val);
	}
	
	//����ֵ��ڵ������������֧�����飬Ҫ��$key��$val����Ŀ��ͬ�Ҷ�Ӧ
	//$flag = trueֵ������������ false�ڵ����������
	public function filterConstraint($refKey, $key, $val){
		$temp = array();
		
		//���˵���֧�ֵ���������
		if(!is_array($key) && !is_array($val) && in_array($key, $refKey)){
			$temp[$key] = $val;
		}
		if(is_array($key) && is_array($val) && (count($key) == count($val))){
			$refKey = array_fill_keys($refKey, '');
			$temp = array_intersect_key(array_combine($key, $val), $refKey);
		}
		return $temp;
	}
	
	//����б����ͷ��ر���
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