<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 01-Jun-2009 17:43:08
 */
abstract class SOSO_Base_Data_Reader{
	/**
	 * ��¼����,ÿ�������൱��һ������
	 *
	 * @var SOSO_Base_Data_Record
	 */
	public $recordType;
	public $meta;
	protected $inputEncoding = 'utf-8';
	protected $outputEncoding = 'utf-8';

	
	public function __construct($meta, $columns){
		if (is_string($meta)) {
			$meta = array('record'=>$meta);
		}
		$this->meta = $meta;
		$this->recordType = is_array($columns) ? 
								SOSO_Base_Data_Record::create($columns) : $columns; 
	}
	/**
	 * �����������ݶ�ȡ��
	 *
	 * @param array $meta
	 * @param SOSO_Base_Data_Record/array $columns
	 * @return SOSO_Base_Data_Reader
	 */
	public function reconfigure($meta=null,$columns=null){
		if ($meta){
			$this->meta = $meta;
		}
		if ($columns){
			$this->recordType = is_array($columns) ? 
								SOSO_Base_Data_Record::create($columns) : $columns; 
		}
		return clone($this);
	}
	public function __destruct(){
		foreach ($this as $k=>$v){
			$this->$k = null;
			unset($this->$k);	
		}
	}
	public function copy(){
		return clone($this);
	}

	/**
	 * ��ڷ������������ݵļ�顢����
	 *
	 * @param mixed $response
	 * @param SOSO_Base_Data_Store $store
	 */
	public abstract function read($response,$store=null);

	/**
	 * ��������
	 *
	 * @param mixed $param
	 * @param SOSO_Base_Data_Store $store
	 */
	public abstract function readRecords($param,$store=null);

}
?>