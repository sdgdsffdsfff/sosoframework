<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 01-六月-2009 17:43:07
 * 完全模拟XMLReader实现
 */
/**
 * 
 * @author beatashao
 *
 */
class SOSO_Base_Data_JSONReader extends SOSO_Base_Data_Reader
{
	private $mNeedConvert = false;
	function __construct($meta,$recordType=null)
	{
		if (is_null($recordType) && isset($meta['fields'])){
			$recordType = $meta['fields'];
		}
		parent::__construct($meta,$recordType);		
	}

	function __destruct()
	{
	}



	/**
	 * 
	 * @param response
	 */
	public function read($response,$store=null,$encoding=null)
	{
		if($encoding){
			if($encoding != $this->outputEncoding){
				$this->mNeedConvert=true;//需要编码转换
				$this->outputEncoding = $encoding ;
			}
		}
		$j = json_decode($response);
		if((count($j)<=0)&&(!($j instanceof stdClass ))){
			throw new Exception("JsonReader.read: json string not available",332211);
		}
		//$this->xmlData = $dom;
		return $this->readRecords($j,$store);		
	}
	protected function convert($res,$fromCharset,$toCharset){
		if(!is_string($res)) 
		{
			$res = (array)$res;
			foreach ($res as $key=>$val)
			{
				$res[$key] = $this->convert($val, $fromCharset,$toCharset);
			}
		}
		else
		{
			if (function_exists('mb_convert_encoding')) {
				$res=mb_convert_encoding($res,$toCharset,$fromCharset);	
			}
			else{
				$res=iconv($fromCharset,$toCharset,$res);
			}
		}		
		return $res;		
	}
	/**
	 * 
	 * @param param
	 */
	public function readRecords($jsobj,$store=null)
	{
		$root = $jsobj;
		$recordType = $this->recordType;
		$fields = $recordType->fields;
		$sid = isset($this->meta['id']) ? $this->meta['id'] : null;
		$totalRecords = 0;
		$success = true;
		
		if (isset($this->meta['totalRecords'])){
			$totalRecords = SOSO_Base_Util_JsonQuery::selectValue($this->meta['totalRecords']);
		}
		
		$records = array();
		$ns = SOSO_Base_Util_JsonQuery::select($this->meta['record'],$root);
		$len=count($ns);
		for($i=0;$i<$len;$i++){
			$node = $ns[$i];
			$values = array();
			$id = null;
			if(!is_null($sid)){
				$id = SOSO_Base_Util_JsonQuery::selectValue($sid,$node);
			}
		
			for($j=0,$jlen=$fields->length;$j<$jlen;$j++){
				$field = $fields->items[$j];			
				if (isset($field->mapping)){
					$sel = $field->mapping;
					if (is_array($field->mapping)){
						$rec = key($field->mapping);
						$config = $field->mapping[$rec];
						if (!isset($config['record'])){
							//如果mapping是数组，且未指定record，那么mapping数组的key就是要查看的节点
							$meta = array('record'=>$rec);
							$rec .= ':parent';
						}else{
							$meta = array('record'=>$config['record']);
						}

						//$meta = array('record'=>isset($config['record']) ? $config['record'] : $rec);
						if (isset($config['id'])){
							$meta['id'] = $config['id'];
						}
						$column = $config['column'];
						if(strpos($rec,':parent')!==false){//如果mapping是数组，且未指定record，那么mapping数组的key就是要查看的节点
							$subReader = $this->copy();
							$subReader->reconfigure($meta,$column);					
							$r = $subReader->readRecords($node,$store);
							$data = array();
							if (isset($r['records']) && !empty($r['records'])){
								foreach ($r['records'] as $record){
									$data[] = $record->getData();
								}
							}
							$v = $data;
						}
						else{
							$subNodes = SOSO_Base_Util_JsonQuery::select($rec,$node);
							$subLen = count($subNodes);
							$v = array();
							if ($subLen){
								$subReader = $this->copy();
								$subReader->reconfigure($meta,$column);					
								foreach ($subNodes as $subnode){
									$r = $subReader->readRecords($subnode,$store);
									$data = array();
									if (isset($r['records']) && !empty($r['records'])){
										foreach ($r['records'] as $record){
											$data[] = $record->getData();
										}
									}
									$v[] = $data;
								}
							}
						}					
					}else{
						$v = SOSO_Base_Util_JsonQuery::selectValue($sel,$node,$field->defaultValue);
					}
				}else{
					$v = SOSO_Base_Util_JsonQuery::selectValue($field->name,$node,$field->defaultValue);	
				}
		
				if (is_null($v)) {
					$v = $field->defaultValue;
				}
				if ($this->mNeedConvert){
					$v = $this->convert($v,$this->inputEncoding,$this->outputEncoding);
				}
				$values[$field->name] = $v;
			}
			//$records = array($values);
			//print_r($values);
			$record = $recordType->instance($values,$id);
			$record->node = $node;
			$records[] = $record;
		}
//		echo "<PRE>";
//		print_r($records);
		return array('success'=>$success,'records'=>$records,'totalRecords'=>$totalRecords?$totalRecords:count($records));		
	}

}
?>