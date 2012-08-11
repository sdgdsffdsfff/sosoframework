<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_ORM
 * @package    SOSO_ORM
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0 15-四月-2008 16:59:21
 * 
 * 基于 SOSO_ORM_TableObject 的相应Table的页面管理类，
 * 对应Table必须有主键，支持单一主键或多主键 multiprime key
 */
/**
 * TODO: 
 *   1.plenty of things to be done!!
 *   2. see #1
 */
class SOSO_ORM_TableAdmin extends SOSO_View_Page {

	public $mMappingTableObject;
	public $mConfigFile;
	public $mTableConfig = array();
	public $mAction = 'list';
	public $mPage = 1;
	public $mPagesize = 50;
	private $mDBIndex = 0;
	public $mTableName;

	public $mTemplateFile;
	public $mUploadFolder = "D:/";
	public $mActionFunctions = array();
	/**
	 * 0 代表常规模式;1代表ajax模式
	 *
	 * @var integer
	 */
	public $mMode = 0;
	/**
	 * 字段中文描述
	 *
	 * @var string
	 */
	public $mDesc;
	public $mActions = array('listall'=>"list",'search'=>"search",'create'=>"insert",
	"update"=>"update","delete"=>"delete","select"=>"select",
	/*"searchselect"=>"searchselect"*/);
	public $mReserve = array("_action","_search","_page","_order","_group");
	public $mPageTitle;
	public $mMetaData = array();
	/**
	 * 代理类
	 *
	 * @var SOSO_ORM_TableObjectProxy
	 */
	public $mProxy;
	/**
	 * 分页信息是否已经分配
	 *
	 * @var bool
	 */
	protected $mPageRendered = false;
	/** @pdOid f753bc99-ac48-4942-913a-179aa4bad791 */
	private $mObservers;
	/** @pdOid bbbc6446-2c2b-4b96-aa46-a91c2048cbca */
	private $mState;
	/** @pdOid 50d7a693-1725-4576-9a12-2d81446ed3a6 */
	protected $mTemplateSets = array('list'=>'');

	protected $mDefaultCharset = 'gbk';	
	const MODE_TRADITIONAL = 0;
	const MODE_AJAX = 1;
	protected $mOptions = array('up'=>array(array('updateResult'=>true,'updateResultMsg'=>'Update failed!'),
		array('updateResult'=>true,'updateResultMsg'=>'Update Successfull!')));
	/**
	 * 构造函数
	 * @access public
	 * 
	 * @param SOSO_ORM_TableObject|string $pTableName 表名或表映射类
	 * @param integer $pDBConfig 数据库索引
	 */
	public function __construct($pTableName, $pDBConfig = 0){
		parent::__construct();
		$this->mTableName = $pTableName;
		if ($pTableName instanceof SOSO_ORM_TableObject) {
			$this->mTableName = $pTableName->getTable();
			$this->mProxy = new SOSO_ORM_TableObjectProxy($pTableName,$this->mCurrentUser);
		}elseif($pTableName instanceof SOSO_ORM_Table){
			$this->mTableName = $pTableName->getTable();
			$this->mProxy = new SOSO_ORM_TableObjectProxy($pTableName,$this->mCurrentUser);
		}else if (class_exists($pTableName)){
			$this->mProxy = new SOSO_ORM_TableObjectProxy(new $pTableName($pTableName,$pDBConfig),$this->mCurrentUser);
			$this->mTableName = $this->mProxy->getTable();
		}else{
			//$this->mProxy = new SOSO_ORM_TableObjectProxy(new SOSO_ORM_TableObject($pTableName,$pDBConfig),$this->mCurrentUser);
			$this->mProxy = new SOSO_ORM_TableObjectProxy(new SOSO_ORM_Table($pTableName,$pDBConfig),$this->mCurrentUser);
		}
		$this->mDBIndex = $pDBConfig;
		
		$this->initialize();
	}

	/**
	 * 程序初始化，检测模板和配置文件
	 * @access protected
	 */
	final public function initialize() {
		@umask(0000);
		$config_path = SOSO_Frameworks_Registry::getInstance()->get('root_path').'/temp/cache';
		$tFileFormat = "%s/%s_%s_config.serialize.log";
		$this->mConfigFile = sprintf($tFileFormat,$config_path,$this->mDBIndex,trim($this->mTableName));
		$tCharsetFormat = "%s/%s_%s_charset.log";
		$tCharsetFile = sprintf($tCharsetFormat,$config_path,$this->mDBIndex,trim($this->mTableName));
		
		if (file_exists($tCharsetFile)) {
			$tChar = @file_get_contents($tCharsetFile);
			if ($tChar == false) {
				$this->mDefaultCharset = str_ireplace('utf8','utf-8',$this->mProxy->getCharset());
			}else{
				$this->mProxy->mSQLCommand->setCharset($tChar);
				$this->mDefaultCharset = str_ireplace('utf8','utf-8',$tChar);
			}
		}else{
			$this->mDefaultCharset = str_ireplace('utf8','utf-8',$this->mProxy->getCharset());
		}
		//header("Content-type:text/html; charset=".$this->mDefaultCharset);
		
		$this->meta($this->mDesc);
		$this->registObservers();
	}
	/**
	 * 定义模板文件名的函数
	 * @access protected
	 */
	public function generateTemplate($overwrite=false){
		$tPath = SOSO_Frameworks_Config::getSystemPath('template').'/tableadmin/';
		if (!file_exists($tPath)) {
			echo "initializing ...";
			ob_start();
			$tHelper = new SOSO_Helper_Builder();
			$tHelper->install();
			//$contents = ob_get_contents();
			ob_get_clean();
			SOSO_Util_Util::redirect($_SERVER['REQUEST_URI'],2);
		}
		$table_configs = array();
		$this->mTemplateFile = sprintf("%s/tpl.%s_%s_%s_%s.html",$tPath,$this->mTableName,$this->mDBIndex,$this->mMode ? 'ext' : 'base',$this->mAction);
		if (!$overwrite && file_exists($this->mTemplateFile)) {
			//copy($this->mTemplateFile,$this->mTemplateFile."_bak");
			return true;
		}
		$smarty = new Smarty();
		$smarty->template_dir = dirname(dirname(__FILE__)).'/Helper/template/';
		$template_c = SOSO_Frameworks_Config::getSystemPath('temp').'/template_c';
		$smarty->template_c = $template_c;
		$smarty->compile_dir = $template_c;
		$smarty->config_dir = $template_c;
		$smarty->cache_dir = $template_c;
		$prefix = (1 == $this->mMode ? "ext/" : "base");
		foreach($this->mTableConfig as $config) {
			$config['file'] = "base/tpl.input.{$config['type']}.{$this->mAction}.htm";
			if(!file_exists(SOSO_Frameworks_Config::getSystemPath('template')."/{$config['file']}")) {
				$config['file'] = "ext/tpl.input.{$config['type']}.htm";
			}
			$table_configs[] = $config;
		}
		$smarty->assign('_action',$this->mAction);
		$smarty->assign('config',$table_configs);
		$smarty->assign('charset',$this->mDefaultCharset);
		$smarty->assign('action_template',$prefix."/tpl.{$this->mAction}.htm");
		$smarty->assign('key',$this->mProxy->getPrimaryKey());
		$tHtml = $smarty->fetch("$prefix/tpl.tableAdmin.htm");
		file_put_contents($this->mTemplateFile,$tHtml);
	}

	/**
	 * 页面初始化，处理外界变量，$_GET，$_POST
	 * @access protected
	 */
	final private function page_init(){
		if(isset($_GET['_search']) && get_magic_quotes_gpc() == 1) {
			$_GET['_search'] = stripslashes($_GET['_search']);
		}
		$this->mGET = array_filter($_GET, array($this, "arguments_filter"));
		$this->mPOST = array_filter($_POST, array($this, "arguments_filter"));
		if (!(isset($_REQUEST['_action'])
		&&	in_array($_REQUEST['_action'],$this->mActions))) {
			$this->mAction = current($this->mActions);
		}else{
			$this->mAction = $_REQUEST['_action'];
		}

		if(isset($_REQUEST['_page'])) {
			$this->mPage = $_REQUEST['_page'];
		}
	}
	/**
	* 参数过滤函数
	* @access protected
	*/
	public function arguments_filter($var) {
		return ($var!="");
	}
	
	/**
	 * 程序逻辑入口点
	 */
	final public function run(){
		$this->page_init();
		$this->generateTemplate();
		$this->clear_all_assign();
		$tMethod = array_search($this->mAction,$this->mActions);
		if ($tMethod === false || !method_exists($this,$tMethod)) {
			SOSO_Util_Util::redirect("?",20);
		}
		if ($this->fireEvent($this->mAction,'before') !== false) {
			$tRes = $this->$tMethod();
			$this->fireEvent($this->mAction,'after',$tRes);
		}else{
			SOSO_Util_Util::redirect("?");
		}

		$this->assignUrl();
		$this->assign("_table", $this->mTableName);
		$this->assign('page_title', $this->mPageTitle);
		$this->assign('charset',$this->mDefaultCharset);
		if( !$this->mPageRendered ){
			$this->renderPagination();
		}
		
		$this->assign('_action',$this->mAction);
		if (isset($_SESSION)) $this->assign('_user',$_SESSION);
		$this->display($this->mTemplateFile);
	}
	
	/**
	 * 传递分页信息
	 *
	 * @param SOSO_Util_Pagination $pagination
	 */
	protected function renderPagination($pagination=null){
		$tPagination = $pagination instanceof SOSO_Util_Pagination ? $pagination : $this->mProxy->_getPagination();
		if ($tPagination instanceof SOSO_Util_Pagination) {
			if ($this->mMode == self::MODE_AJAX) {
				$this->assign('pageinfo_json',json_encode(get_object_vars($tPagination)));
			}else{
				$this->assign('pageinfo',$tPagination->getPage());
			}
			$this->assign('page_htc',$this->get_page_htc($tPagination));
			$this->mPageRendered = true;
		}
	}
	/**
	 * 生成页面调用的 url 的函数
	 * @access protected
	 */
	public function assignUrl(){
		$search = $url = array();
		foreach($this->mGET as $key=>$value) {
			if(substr($key,0,1)!='_' && $this->mAction=="search") {
				$search[$key] = $value;
			}
			if(substr($key,0,1)=='_' && $key != "_page") {
				$url[] = "{$key}=".rawurlencode($value);
			}
		}
		$_url = implode('&',$url);

		if(count($search)>0) {
			$_url .= "&_search=".base64_encode(serialize($search));
		}
		$this->assign("_url",$_url);
		return $this;
//		if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
//			$qsa = preg_replace(array("/_action=[^&]*?&*?/U","/&{2,}/"),array('','&'),$_SERVER['QUERY_STRING']);
//			if (substr($qsa,-1) == '&') {
//				$qsa = substr($qsa,0,-1);
//			}
//			$this->assign('_url',$qsa);
//		//	$this->assign('qsa',$qsa);
//		}
	}

	/**
	 * 跳转到列表页
	 * @access protected
	 */
	public function goList(){

	}

	/**
	 * 供上传文件时调用
	 * @access protected
	 */
	public function doUpload(){
	}
	
	/**
	 * 获得分页参数
	 */
	public function get_page_htc(SOSO_Util_Pagination $page){
		$param = array();
 
		$keys = array('mPage','mNextPage','mPageCount','mFirstPage','mPreviousPage');
		$keys = array_merge($keys,array('mPageSize','mLastPage','mRecordCount','mStartRecord','mEndRecord'));
		$vals = array($page->mCurrentPage,$page->mNextPage,$page->mTotal,1,$page->mPrePage);
		$tStartRecord = ($page->mCurrentPage-1)*$page->mPagesize+1;
		$vals = array_merge($vals,array($page->mPagesize,$page->mTotal,$page->mTotalResult,$tStartRecord,$page->mTotalResult));
		
		$param = array_combine($keys,$vals);
		$page_htc = str_replace('&',' ',http_build_query($param));
		return $page_htc;
	}
	
	public function listall() {
		$tOrder = isset($this->mGET['_order'])?$this->mGET['_order']:NULL;
		/*if($tOrder){
			$tOrderArr = explode(",",$tOrder);
			foreach ($tOrderArr as $index => $tOrderColumn){
				$arr = explode(" ",$tOrderColumn);
				$arr[0] = sprintf("`%s`",$arr[0]);
				$tOrderArr[$index] = join(' ',$arr);
			}
			$tOrder = join(",",$tOrderArr);
		}
		var_dump($tOrder);
		*/
		if (!is_null($tOrder)){
			$this->assign('_order',$tOrder);
		}
		if (method_exists($this,'_list')) {
			$tTemp = $this->_list($this->mPage,$this->mPagesize,$tOrder);
		}else{
			$tTemp = $this->mProxy->_list($this->mPage,$this->mPagesize,$tOrder);
		}
		if(method_exists($this,'filtrateList')) {
			$tTemp = $this->filtrateList($tTemp);
		}elseif (method_exists($this,'onList')){
			$tTemp = $this->onList($tTemp);
		}

		$this->assign('list',$tTemp);
		$tPrimaryKeys = array_merge(array(),$this->mProxy->getPrimaryKey());
		if (!empty($tPrimaryKeys)) {
			$this->assign('primary_key',json_encode($tPrimaryKeys));
		}
			
		if ($this->mMode == self::MODE_AJAX) {
			$tRes = array();

			$tNeedconvert = strpos($this->mDefaultCharset,'utf') === false;
			if (!empty($this->mMetaData)) {
				for($i=0,$len=count($tTemp);$i<$len;$i++){
					foreach ($tTemp[$i] as $k=>$v){
						if (!in_array($k,$tPrimaryKeys) && 1 != $this->mTableConfig[$k]['list']) {
							continue;
						}
						$key = iconv("GB18030",'UTF-8',$this->mMetaData[$k]);
						if ( $tNeedconvert ){
							$tRes[$i][$key] = iconv("GB18030",'UTF-8',$v);
						}else{
							$tRes[$i][$key] = $v;
						}
					}
				}
			}else{
				for($i=0,$len=count($tTemp);$i<$len;$i++){
					foreach ($tTemp[$i] as $k=>$v){
						if ( $tNeedconvert ){
							$tRes[$i][$k] = iconv("GB18030",'UTF-8',$v);
						}else{
							$tRes[$i][$k] = $v;
						}
					}
				}
			}
			$this->assign('list_json',json_encode($tRes));
		}
		
		return $tTemp;
	}

	/** @pdOid c30c8a45-a864-45ac-8907-8f88b01da3e3 */
	public function select() {
		if($this->setValue($this->mGET) > 0) {
			$tTemp = $this->mProxy->_list(1,1,NULL,0);
			if(method_exists($this,'filtrateList')) {
				$tTemp = $this->filtrateList($tTemp);
			}
			$this->assign('list', $tTemp);
			$this->mProxy->_reset();
			$this->setSearchValue();
			$pagination = new SOSO_Util_Pagination($this->mPage,1,$this->mProxy->_count());
			$this->renderPagination($pagination);
		}
		else {
			$this->mPagesize = 1;
			$this->search();
		}
		return null;
	}

	/** @pdOid 4297fa86-6175-428b-917a-a1b5b4b78924 */
	public function update() {
		$tRes = true;
		$offset = $this->mPage;
		if($this->setValue($this->mGET)==0) {
			$this->setSearchValue();
		}else{
			$offset = 1;
		}
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (method_exists($this,'_update')) {
				$tRes = $this->_update();
			}else{
				
				$tInnerTableObject = $this->mProxy->mObject;
				
				if (get_magic_quotes_gpc()) {
					$this->mPOST = array_map('stripslashes',$this->mPOST);
				}
				
				if ($tInnerTableObject instanceof SOSO_ORM_TableObject){
					$tInnerTableObject->fillObjectData($this->mPOST);
					$tRes = $tInnerTableObject->_update();
				}else{
					$tPK = $this->mProxy->getPrimaryKey();
					if ($tPK){
						foreach ($tPK as $pk){
							if(isset($this->mPOST[$pk]))
								$tInnerTableObject->add($pk,$this->mPOST[$pk]);
						}
					}
					$tRes = $tInnerTableObject->update($this->mPOST);
				}
				
				$this->assign($this->mOptions['up'][!!$tRes]);
				foreach($this->mPOST as $key => $value) {
					$this->assign($key,$value);
				}
			}
		}else{
			if($this->mProxy->_select($offset)) {
				foreach($this->mProxy as $key => $value) {
					$this->assign($key,get_magic_quotes_gpc() ? stripslashes($value) : $value);
				}
			}
		}
		$this->mProxy->_reset();
		$oProxy = clone($this->mProxy->mObject);
		$pagination = new SOSO_Util_Pagination($this->mPage,1,$oProxy->_count());
		$this->renderPagination($pagination);
		return $tRes;
	}
	
	// TODO: implement
	/** @pdOid 69de3a67-1c71-4f44-8b59-4971b2be99df */
	public function delete() {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$this->assign('refer',$_SERVER['HTTP_REFERER']);
		}
		if($this->setValue($this->mGET) > 0) {
			return $this->mProxy->_delete();
		}
		return 0;
	}

	/** @pdOid 3664446b-6310-4e30-bf49-6b09813e702c */
	public function across() {
		// TODO: implement
		return 0;
	}

	/** @pdOid ee63292f-607f-4aa8-b61f-d69ac386058f */
	public function create() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
			$this->setValue($_POST);
			//$this->setSearchValue($_POST);
			return $this->mProxy->_insert();
		}

		return 0;
	}
	/**
	 * 翻页时回填查找参数
	 * @access protected
	 */
	public function setSearchValue(){
		if(!empty($this->mGET['_search'])) {
			//print_r(rawurldecode($this->mGET['_search']));
			if($this->setValue(unserialize(base64_decode($this->mGET['_search'])))>0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 回填参数
	 * @access protected
	 * 
	 * @param pArray
	 */
	public function setValue($pArray){
		$tAttr = $pArray;
		$tRes = array();
		$tCount = 0;
		foreach ($tAttr as $k=>$v){
			if (strlen($v)){
				$this->assign($k,$v);
				$this->mProxy->setObjectData($k,$v) && $tCount++;
			}
		}

		return $tCount;
	}

	public function search(){
		$this->setValue($this->mGET);
		$this->setSearchValue();
		
		$this->listall();
	}
	
	/**
	 * 设置字段中文描述
	 * @param (colum=>描述)格式的数组描述，e.g:'name'=>'姓名'
	 * @return array()
	 */
	protected function meta($pDesc=array()) {
		if (file_exists($this->mConfigFile)) {
			$this->mTableConfig = unserialize(file_get_contents($this->mConfigFile));
			if (!empty($this->mTableConfig)) {
				$this->mTableConfig = SOSO_Util_Util::Array2Hash($this->mTableConfig,'name');
				foreach ($this->mProxy as $key=>$val){
					$this->mMetaData[$key] = isset($this->mTableConfig[$key])
					? $this->mTableConfig[$key]['comment']
					: $key;
				}
			}
		}

		if (empty($pDesc)) {
			return $this->mMetaData;
		}
		//$tMeta = $this->mProxy->mMapHash;
		foreach ($this->mProxy as $key=>$val){
			$this->mMetaData[$key] = isset($pDesc[strtolower($key)])
			? $pDesc[strtolower($key)]
			: $key;
		}
		return $this->mMetaData;
	}
	/**
	 * 调用已注册的函数
	 * @access protected
	 * 
	 * @param pAction
	 * @param pStatus
	 */
	public function fireEvent($pAction,$pPos,$pData=''){
		if (isset($this->mObservers[$pAction])
		&& isset($this->mObservers[$pAction][$pPos])) {
			$tListner = $this->mObservers[strtolower($pAction)][strtolower($pPos)];
			return $this->$tListner($pData);
		}
		return true;
	}

	private function registObservers(){
		$tMethods = get_class_methods(get_class($this));
		$tHaystack = array_map('strtolower',$tMethods);
		$tTrans = array_combine($tHaystack,$tMethods);
		foreach(array('before','after') as $prefix){
			foreach ($this->mActions as $act){
				$tNeedle = $prefix.strtolower($act);
				if (array_search($tNeedle,$tHaystack) !== false) {
					$this->observe($act,$prefix,$tTrans[$tNeedle]);
				}
			}
		}
	}
	/** @pdOid 21c84575-540c-42a2-992e-54cfc5ae525d */
	public function observe($pEvent,$pPos,$pCallback) {
		$this->mObservers[strtolower($pEvent)][strtolower($pPos)] = $pCallback;
		return array($this,$pCallback);
	}
}
?>
