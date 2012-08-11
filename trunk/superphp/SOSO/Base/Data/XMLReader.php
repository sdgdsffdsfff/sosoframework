<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 01-����-2009 17:43:08
 */
require_once(dirname(__FILE__)."/Reader.php");
/**
 * Updates :
 * 	@ 2009-06-09 : ֧�ּ������������������ã�
 * $columns = array(
			array('name'=>'SuperCompany','mapping'=>'company'),'model','b_city','b_airdrome','btime','land_city','e_airdrome',
			'etime',
			array('name'=>'discount',    
				  'mapping'=>array('discount'=>array('record'=>'item','column'=>array('@date','@discount')))
			)
		);
 *
 * mappingΪ�������Ϊ����������keyΪ��ѭ���ڵ㣬keyָ����ֵΪ�µ��������idҲ������id��ֵ
 * ���ѭ��keyָ���Ľڵ��ڲ����ֽڵ㣬��Ҫ��ָ��record�������ָ������ֻ��key����ѭ�����磺
 * array('name'=>'discount',    
				  'mapping'=>array('discount'=>array('column'=>array('@date','@discount')))
			)
 */
class SOSO_Base_Data_XMLReader extends SOSO_Base_Data_Reader {
	public $xmlData;
	private $mNeedConvert = false;
	/**
	 * ���캯�������ڳ�ʼ��"��"meta��Ϣ,��(fields)��Ϣ
	 *
	 * @param array $meta
	 * @param array/SOSO_Base_Data_Record $recordType
	 */
	public function __construct($meta,$recordType=null){
		if (is_null($recordType) && isset($meta['fields'])){
			$recordType = $meta['fields'];
		}
		parent::__construct($meta,$recordType);
	}
	
	function __destruct() {
		$this->recordType = null;
		$this->xmlData = '';
	}
	
	/**
	 * Enter description here...
	 *
	 * @param  string $string
	 * @return string
	 */
	public function repaireContent(&$string){
		if (!extension_loaded('tidy')) {
			return $string;
		}
		
		if (strtolower(mb_detect_encoding($string,'euc-cn,utf-8')) != 'utf-8') {
			$string = mb_convert_encoding($string,'utf-8',mb_detect_encoding($string,'euc-cn,utf-8'));	
		}
		$string = preg_replace("#<!--.*-->#U",'',$string);
		$string = preg_replace("#<(script|style)[^>]*>(.*)</\\1>#isU","",$string);
		$config = array('indent' => TRUE,'output-xhtml' => TRUE,'wrap' => 200);
		$string = tidy_repair_string($string,$config,'utf8');
		$string = str_replace("&nbsp;","",$string); 
		return $string;
	}
	
	/**
	 * ��ȡ(����)�������
	 *
	 * @param String $response XML����
	 * @param SOSO_Base_Data_Store $store ��������
	 * @param String $encoding �������,��$store�Ĳ������ȣ���δָ������ʹ��xmlĬ�ϵı���������
	 * @return array(SOSO_Base_Data_Record) ����
	 */
	public function read($response,$store=null,$encoding=null) {
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$response = trim($response);
		$first_line = substr($response,0,strpos($response,'>'));
		$b = preg_match_all("#encoding=(['\"]?)([^'\"]+)\\1#isU",$first_line,$m);
		$tInputEncoding = $b ? strtolower($m[2][0]) : strtolower(mb_detect_encoding($response,'gbk,utf-8'));
		$this->inputEncoding = $this->outputEncoding = $tInputEncoding;
		if ($tInputEncoding !== 'utf-8') {
			$response = str_replace($first_line,str_ireplace($tInputEncoding,'utf-8',$first_line),$response);
			$response = $this->convert($response,$tInputEncoding,'utf-8');	
		}
		
		$res = $dom->loadXML($response);
		if (!$res) {
			$response = $this->repaireContent($response);
			$res = $dom->loadXML($response);
		}
		if (!$res){
			$tError = libxml_get_last_error();
			$tMsg = sprintf("Line:%d	Message:%s",'',$tError->message);
			$tMessage = "XMLReader.read: XML Document not available(".$tMsg.')';
			libxml_clear_errors();
			throw new Exception($tMessage,112233);
		}
		
		if (!strlen($encoding)) {
			$encoding = $this->outputEncoding;
		}
		if ('utf-8' != strtolower($encoding) ) {
			$this->mNeedConvert = true;
			$this->outputEncoding = $encoding;
		}
		
		return $this->readRecords($dom,$store);
	}
	/**
	 * ���ݱ���ת��
	 *
	 * @param string $res
	 * @param string $fromCharset
	 * @param string $toCharset
	 * @return string
	 */
	protected function convert($res,$fromCharset,$toCharset){
		if (is_array($res)) {
			foreach ($res as $k=>$v){
				$res[$k] = $this->convert($v,$fromCharset,$toCharset);
			}
			return $res;
		}
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($res,$toCharset,$fromCharset);	
		}
		return iconv($fromCharset,$toCharset,$res);
	}
	/**
	 * �����ݱ�load/read��ԭʼXML�ĵ����Ա����⴦��
	 * @param DOMDocument|DOMNode|DOMElement $doc
	 */
	public function readRecords($doc,$store=null){		
		$root = $doc;
		$recordType = $this->recordType;
		$fields = $recordType->fields;
		$sid = isset($this->meta['id']) ? $this->meta['id'] : null;
		$totalRecords = 0;
		$success = true;
		
		if (isset($this->meta['totalRecords'])){
			$totalRecords = SOSO_Base_Util_XMLQuery::selectValue($this->meta['totalRecords'],$root);
		}
		
		$records = array();
		$ns = SOSO_Base_Util_XMLQuery::select($this->meta['record'],$root);
		$len=count($ns);
		for($i=0;$i<$len;$i++){
			$node = $ns[$i];
			$values = array();
			$id = null;
			if(!is_null($sid)){
				$id = SOSO_Base_Util_XMLQuery::selectValue($sid,$node);
			}
		
			for($j=0,$jlen=$fields->length;$j<$jlen;$j++){
				$field = $fields->items[$j];			
				if (isset($field->mapping)){
					$sel = $field->mapping;
					if (is_array($field->mapping)){
						$rec = key($field->mapping);
						$config = $field->mapping[$rec];
						if (!isset($config['record'])){
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
						$subNodes = SOSO_Base_Util_XMLQuery::select($rec,$node);
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
							$values[$field->name] = $v;
							continue;
						}

					}else{
						$v = SOSO_Base_Util_XMLQuery::selectValue($sel,$node,$field->defaultValue);
					}
				}else{
					$v = SOSO_Base_Util_XMLQuery::selectValue($field->name,$node,$field->defaultValue);	
				}
		
				if (is_null($v)) {
					$v = $field->defaultValue;
				}elseif ($this->mNeedConvert){
					$v = $this->convert($v,'utf-8',$this->outputEncoding);
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