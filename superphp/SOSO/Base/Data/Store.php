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
 * 虚拟表 - 面向连接与记录
 *
 * todo : 
 *  	支持多表分级？
 */
class SOSO_Base_Data_Store extends SOSO_Base_Util_Observable implements IteratorAggregate {
	/**
	 * 数据集对象
	 *
	 * @var SOSO_Base_Data_Collection
	 */
	public $data;
	public $url;
	/**
     * 基础参数表,作为每个HTTP请求的参数
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
	 * 记录总数
	 *
	 * @var int
	 */
	public $totalLength= 0;
	/**
	 * 数据解析对象
	 *
	 * @var SOSO_Base_Data_Reader
	 */
	protected $reader;
	/**
	 * 数据保存对象的容器
	 *
	 * @var SOSO_Base_Data_Writer
	 */
	protected $writer;
	protected $lastOptions = array();
	/**
	 * 数据模式：	simple => 简单模式，一组数据对应一个表
	 	complex=> 复杂模式，1 对 多 | 多 对 多
	 	custom => 正定义：自定义
	 * @var 数据处理模式
	 */
	protected $mode = 'simple';
	private $errors = array();
	const MODE_SIMPLE = 'simple';
	const MODE_COMPLEX= 'complex';
	const MODE_CUSTOM = 'custom';
	
	/**
	 * 构造函数
	 * 
	 * @param array $config 参数config用于初始化对象,它主要包含如下选项(不限)
     * @cfg {String} url 如果指定了url,将使用一个proxy用来获取内容
     * @cfg {Boolean/Object} autoLoad 如果传了此参,store会在创建后自动执行load方法
     * @cfg {SOSO_Base_Data_Connection} proxy 代理对象,用于请求数据
     * @cfg {Array} data 内联数据,供store初始化时进行加载
     * @cfg {SOSO_Base_Data_Reader} reader 数据读取器对象,用于处理数据，
     * 它返回一个k-v数组:值是SOSO_Base_Data_Record对象，值是它的id属性值
     * @cfg {Object} baseParams 基础参数表,作为每个HTTP请求的参数
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
	 * 可以在运行时随意更改reader，建议最好重新构造新的store对象
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
	 * 获得数组数据
	 */
	public function getData(){
		$tData = array();
		for($i=0,$len=$this->getCount();$i<$len;$i++){
			$tData[] = $this->getAt($i)->getData();
		}
		return $tData;
	}
	
	/**
	 * 设置writer，支持多writer（如果append为true，为附加模式，false为排他模式）
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
	 * 将配置项装配给store，可控制是否覆盖现有属性
	 *
	 * @param {Mixed} $options
	 * @param {Boolean} $override 控制是否覆盖
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
   * 向store中添加数据集，并触发add事件
   * @param {SOSO_Base_Data_Record[]} records 待添加的数据集
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
     * 删除某一元素，触发remove事件
     * @link #remove
     * @param {SOSO_Base_Data_Record} record 要删除的结果（SOSO_Base_Data_Record）.
     */
	public function remove(SOSO_Base_Data_Record $record){
		$index = $this->data->indexOf($record);
		$this->data->removeAt($index);
		$this->fireEvent('remove',$this,$record,$index);
	}

	/**
	 * 清除所有记录
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
	 * 获得指定主键的记录的索引(index)值
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
     * 调用指定函数应用到每一个Record上
     * 
     * @param {Function} fn 要调用的函数.SOSO_Base_Data_Record 会是此函数的第一个参数.
     * 函数如果返回 false 将中止迭代
     * @param {Object} scope (optional) 函数的作用域，默认使用迭代子（Record）。
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
	 * 从配置的代理(Reader)中获取结果
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
	 * 装载数据集
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
     * 通过传递的数据加载数据。Reader必须通过构造函数配置才可定义数据格式/意义
     * 
     * @param {Object} data 要加载的数据集.其格式依赖于已经配置的Reader的类型并且需要与Reader->readerRecords的参数保持一致
     * @param {Boolean} append (Optional) True 将附加新的数据，false会替换掉原数据.
     */
	public function loadData($data,$append=false){
		$r = $this->reader->readRecords($data,$this);
		$this->loadRecords($r,array('add'=>$append),true);
	}

	public function getIterator(){
		return new ArrayIterator($this->getData());
	}
	
	/**
	 * 保存数据入口函数
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