<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */
$tPath = dirname(__FILE__);
require_once(dirname($tPath).'/Util/Observable.php');
require_once($tPath.'/Collection.php');
require_once($tPath.'/Writer.php');
require_once($tPath.'/Connection.php');
/**
 * ����� - �����������¼
 *
 * todo : 
 *  	֧�ֶ��ּ���
 */
class SOSO_Base_Data_Store extends SOSO_Base_Util_Observable implements IteratorAggregate {
	/**
	 * ���ݼ�����
	 *
	 * @var SOSO_Base_Data_Collection
	 */
	public $data;
	public $url;
	/**
     * ����������,��Ϊÿ��HTTP����Ĳ���
     * @var Array
     */
	public $baseParams = array();
	public $paramNames = array();
	public $inlineData = array();
	public $proxy;
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	public $recordType;
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	public $fileds;
	/**
	 * ��¼����
	 *
	 * @var int
	 */
	public $totalLength= 0;
	/**
	 * ���ݽ�������
	 *
	 * @var SOSO_Base_Data_Reader
	 */
	protected $reader;
	/**
	 * ���ݱ�����������
	 *
	 * @var SOSO_Base_Data_Writer
	 */
	protected $writer;
	protected $lastOptions = array();
	/**
	 * ����ģʽ��	simple => ��ģʽ��һ�����ݶ�Ӧһ����
	 	complex=> ����ģʽ��1 �� �� | �� �� ��
	 	custom => �����壺�Զ���
	 * @var ���ݴ���ģʽ
	 */
	protected $mode = 'simple';
	private $errors = array();
	const MODE_SIMPLE = 'simple';
	const MODE_COMPLEX= 'complex';
	const MODE_CUSTOM = 'custom';
	
	/**
	 * ���캯��
	 * 
	 * @param array $config ����config���ڳ�ʼ������,����Ҫ��������ѡ��(����)
     * @cfg {String} url ���ָ����url,��ʹ��һ��proxy������ȡ����
     * @cfg {Boolean/Object} autoLoad ������˴˲�,store���ڴ������Զ�ִ��load����
     * @cfg {SOSO_Base_Data_Connection} proxy �������,������������
     * @cfg {Array} data ��������,��store��ʼ��ʱ���м���
     * @cfg {SOSO_Base_Data_Reader} reader ���ݶ�ȡ������,���ڴ������ݣ�
     * ������һ��k-v����:ֵ��SOSO_Base_Data_Record����ֵ������id����ֵ
     * @cfg {Object} baseParams ����������,��Ϊÿ��HTTP����Ĳ���
     * @return SOSO_Base_Data_Store
     */
	public function __construct($config=array()){
		//$this->proxy = new SOSO_Base_Data_Connection($config);
		$this->data = new SOSO_Base_Data_Collection();
		$this->writer = new SOSO_Base_Data_Writer();
		$this->baseParams = isset($config['baseParams']) ? $config['baseParams'] : array();
		if($config && isset($config['data'])){
			$this->inlineData = $config['data'];
			unset($config['data']);
		}
		if($config && isset($config['writer'])){
			$this->writer->delegate($config['writer']);
			unset($config['writer']);
		}
		$this->apply($config);
		if ($this->url && !$this->proxy){
			$this->proxy = new SOSO_Base_Data_Connection(array_merge(array('try_times'=>0),$this->baseParams));
		}
		//reader passed in
		if ($this->reader){
			if (is_null($this->recordType) && !is_null($this->reader->recordType)){
				$this->recordType = $this->reader->recordType;
			}
		}
		if (!is_null($this->recordType)){
			$this->fields = $this->recordType->fields;
		}
		
		$this->addEvents(array('add','remove','update','clear','beforeload','commit',
			'load','afterload','beforesave','save','aftersave','datachanged','loadexception'));
		$this->on("loadexception",'loadexception',$this);
		if ($this->inlineData){
			$this->loadData($this->inlineData);
		}else if (isset($this->autoLoad)){
			usleep(10000);
			$this->load($this->baseParams);
		}
	}
	
	public function __destruct(){
		$this->destroy();
	}
	
	public function getReader(){
		return $this->reader;
	}

	public function getProxy(){
		return $this->proxy;
	}
	
	/**
	 * ����������ʱ�������reader������������¹����µ�store����
	 *
	 * @param SOSO_Base_Data_Reader $reader
	 * @return $this
	 */
	public function setReader(SOSO_Base_Data_Reader $reader){
		$this->reader = $reader;
		if ($this->reader){
			if (is_null($this->recordType) && !is_null($this->reader->recordType)){
				$this->recordType = $this->reader->recordType;
			}
		}
		if (!is_null($this->recordType)){
			$this->fields = $this->recordType->fields;
		}
		return $this;
	}
	public function getWriter(){
		return $this->writer;
	}
	
	public function getCollection(){
		return $this->data;
	}
	
	/**
	 * �����������
	 */
	public function getData(){
		$tData = array();
		for($i=0,$len=$this->getCount();$i<$len;$i++){
			$tData[] = $this->getAt($i)->getData();
		}
		return $tData;
	}
	
	/**
	 * ����writer��֧�ֶ�writer�����appendΪtrue��Ϊ����ģʽ��falseΪ����ģʽ��
	 *
	 * @param {Object} $writer
	 * @param {Bool} $append
	 */
	public function setWriter($writer,$append=false){
		//$this->writer = new SOSO_Base_Data_Writer($writer);
		$this->writer->delegate($writer,$append);
		return $this;
	}
	/**
	 * ��������װ���store���ɿ����Ƿ񸲸���������
	 *
	 * @param {Mixed} $options
	 * @param {Boolean} $override �����Ƿ񸲸�
	 * @return $this
	 */
	protected function apply($options,$override=true){
		foreach($options as $k=>$v){
			if (!$override && isset($this->$k)) {
				continue;
			}
			$this->$k = $v;
		}
		return $this;
	}
	
	public function setUrl($url){
		$this->url = $url;
		if (!$this->proxy){
			$this->proxy = new SOSO_Base_Data_Connection(array_merge(array('try_times'=>0),$this->baseParams));
		}
		return $this;
	}

	public function destroy(){
		$this->data->clear();
		$this->inlineData = '';
		$this->purgeListeners();
		unset($this->recordType);
		unset($this->fileds);
		unset($this->reader);
		unset($this->writer);
		unset($this->proxy);
		unset($this->data);
	}

	/**
   * ��store��������ݼ���������add�¼�
   * @param {SOSO_Base_Data_Record[]} records ����ӵ����ݼ�
   */
	public function add($records){
		if (!$records || (is_array($records) && 0 == count($records))){
			return;
		}
		$records = !is_array($records) ? array($records) : $records;
		for($i=0,$len=count($records);$i<$len;$i++){
			$records[$i]->join($this);
		}
		
		$index = $this->data->length;
		$this->data->addAll($records);
		$this->fireEvent('add',$this,$records,$index);
	}
	
	 /**
     * ɾ��ĳһԪ�أ�����remove�¼�
     * @link #remove
     * @param {SOSO_Base_Data_Record} record Ҫɾ���Ľ����SOSO_Base_Data_Record��.
     */
	public function remove(SOSO_Base_Data_Record $record){
		$index = $this->data->indexOf($record);
		$this->data->removeAt($index);
		$this->fireEvent('remove',$this,$record,$index);
	}

	/**
	 * ������м�¼
	 */
	public function removeAll(){
		$this->data->clear();
		$this->fireEvent('clear',$this);
	}

	public function insert($index,$records){
		$records = is_array($records) ? $records : array($records);
		for($i=0,$len=count($records);$i<$len;$i++){
			$this->data->insert($index,$records[$i]);
			$records[$i]->join($this);
		}
		$this->fireEvent('add',$this,$records,$index);
		return $this;
	}

	public function indexOf(SOSO_Base_Data_Record $record){
		return $this->data->indexOf($record);
	}
	
	/**
	 * ���ָ�������ļ�¼������(index)ֵ
	 */
	public function indexOfId($id){
		return $this->data->indexOfKey($id);
	}

	public function getById($id){
		return $this->data->key($id);	
	}
	
	public function getAt($index){
		return $this->data->itemAt($index);
	}
	
	public function getCount(){
		return isset($this->data->length) ? $this->data->length : 0 ;
	}
	
	public function getTotalCount(){
		return isset($this->totalLength) ? $this->totalLength : 0 ;
	}
	
	/**
     * ����ָ������Ӧ�õ�ÿһ��Record��
     * 
     * @param {Function} fn Ҫ���õĺ���.SOSO_Base_Data_Record ���Ǵ˺����ĵ�һ������.
     * ����������� false ����ֹ����
     * @param {Object} scope (optional) ������������Ĭ��ʹ�õ����ӣ�Record����
     */
	public function each($fn,$scope=null){
		$this->data->each($fn,$scope);
	}
	
	private function storeOptions($obj){
		if(isset($obj['callback'])) unset($obj['callback']);
		if(isset($obj['scope'])) unset($obj['scope']);
		
		$this->lastOptions = $obj;	
	}

	/**
	 * �����õĴ���(Reader)�л�ȡ���
	 *
	 */
	public function load($options=array()){
		if ($this->fireEvent('beforeload',$this,$options) !== false){
			$this->storeOptions($options);
			$p = array_merge(isset($options['params'])?$options['params']:array(),$this->baseParams);
			try{
				$tResponse = $this->proxy->request($this->url,$p);
			}catch(Exception $e){
				$this->fireEvent('loadexception',$this->proxy,$p,'',$e);
				$this->loadRecords(null,$p,false);
				return false;
			}
			if ($this->proxy->getStatus() != '200'){
				$this->fireEvent('loadexception',$this->proxy,$p,$tResponse,new Exception('request error',1));
				$this->loadRecords(null,$p,false);
				return false;
			}
			$result = '';
			$outputEncoding = null;
			if (isset($options['encoding'])) {
				$outputEncoding = $options['encoding'];
			}elseif (isset($options['charset'])) {
				$outputEncoding = $options['charset'];
			}
			try{
				$result = $this->reader->read($tResponse,$this,$outputEncoding);
			}catch (Exception $e){
				$this->fireEvent('loadexception',$this->proxy,$p,$tResponse,$e);
				$this->loadRecords(null,$p,false);
				return false;
			}

			$this->loadRecords($result,$p,true);
			return true;
		}else{
			return false;
		}
	}
	
	public function reload($options=array()){
        $this->load(array_merge($options,$this->lastOptions));
	}
	/**
	 * װ�����ݼ�
	 *
	 */
	protected function loadRecords($records,$option,$success){
		if (!$records || false === $success){
			if ($success !== false){
				$this->fireEvent('load',$this,array(),$option);
			}
			if(isset($option['callback'])){
				$involker = isset($option['scope']) ? 
							array($option['scope'],$option['callback']) : $option['callback'];
				
				call_user_func_array($involker,array(),$option,false);
			}
			return;
		}
		$r = $records['records'];
		$t = isset($records['totalRecords']) ? $records['totalRecords'] : count($r);
		if (!$option || (!isset($option['add']) || $option['add'] !== true)){
			for($i=0,$len=count($r);$i<$len;$i++){
				$r[$i]->join($this);
			}
			$this->data->clear();
			$this->data->addAll($r);
			$this->totalLength = $t;
			$this->fireEvent('datachanged',$this);
		}else{
			$this->totalLength = max($t,$this->data->length+count($r));
			$this->add($r);
		}
		$this->fireEvent('load',$this,$r,$option);
		if(isset($option['callback'])){
			$involker = isset($option['scope']) ? 
						array($option['scope'],$option['callback']) : $option['callback'];
				
			call_user_func($involker,$r,$option,true);
		}
	}

	/**
     * ͨ�����ݵ����ݼ������ݡ�Reader����ͨ�����캯�����òſɶ������ݸ�ʽ/����
     * 
     * @param {Object} data Ҫ���ص����ݼ�.���ʽ�������Ѿ����õ�Reader�����Ͳ�����Ҫ��Reader->readerRecords�Ĳ�������һ��
     * @param {Boolean} append (Optional) True �������µ����ݣ�false���滻��ԭ����.
     */
	public function loadData($data,$append=false){
		$r = $this->reader->readRecords($data,$this);
		$this->loadRecords($r,array('add'=>$append),true);
	}

	public function getIterator(){
		return new ArrayIterator($this->getData());
	}
	
	/**
	 * ����������ں���
	 *
	 * @param SOSO_Base_Data_Writer $writer
	 */
	public function save($writer=null){
		$this->writer->delegate($writer);
		
		if ($this->fireEvent('beforesave',$this,$this->writer) !== false){
			$record = null;
			for($i=0,$len=$this->getCount();$i<$len;$i++){
				$record = $this->getAt($i);
				$record->commit();
			}
			$this->fireEvent('save',$this,$this->writer,$record);
			return true;
		}
		return false;
	}
	
	public function setMode($mode='complex'){
		$this->mode = $mode;
	}
	/**
	 * @param Mixed $data
	 * @param Mixed $id
	 * @param SOSO_Base_Data_Record $record
	 * @return Boolean
	 */
	public function commit($data,$id=null,$record=null){
		if ($this->mode == self::MODE_SIMPLE ) {
			$this->writer->save($data,$id,$record);
			$this->fireEvent('commit',$this,$this->writer,$data);
			return true;
		}
		$ids = array($id);
		foreach ($this->writer as $writer){
			$rid = $writer->save($data,$id,$record);
			if ($rid > 0) {
				if ($this->mode == self::MODE_COMPLEX) {
					$id = $rid;
				}else {  // custom mode
					$ids[] = $rid;
					$id = $ids;
				}
			}
		}
		$this->fireEvent('commit',$this,$this->writer,$data);
		return true;
	}
	public function __clone(){
		$this->reader = clone($this->reader);
	}
	public function loadexception($a,$b,$c,$d){
		echo "<PRE>";
		print_r($d);
	}
}