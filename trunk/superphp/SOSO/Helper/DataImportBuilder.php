<?php
class SOSO_Helper_DataImportBuilder extends SOSO_View_Page {
	private $step = 1;
	protected $mPath = array(
		'template' => '/template/tableadmin',
		'template_c' => '/temp/template_c',
		'class' => '/class/Page/Admin',
		'config' => '/temp/config',
		'cache' => '/temp/cache',
	);
	protected $mRoot;

	public function __construct(){
		parent::__construct();
		//header("Cache-Control: max-age=300");
		umask(0000);
	}

	protected function initSmarty(){
		parent::initSmarty();

		$template_path = dirname(__FILE__).'/template';
		$this->instance->template_dir = $template_path;
		$this->instance->left_delimiter = '{%';
		$this->instance->right_delimiter = '%}';
		$this->mRoot = SOSO_Frameworks_Config::document_root_path();
		foreach ($this->mPath as $v){
			if (!file_exists($this->mRoot . $v)) {
				mkdir($this->mRoot . $v,0777,true);
			}
		}
		ob_implicit_flush();
	}

	private function getAppConfigPath(){
		return SOSO_Frameworks_Config::getSystemPath('temp') . '/config';
	}
	
	public function run(){
		if (isset($_GET['step']) && is_numeric($_GET['step'])) {
			$this->step = $_GET['step'];
		}
		$this->assign('step',"第{$this->step}步");
		if (!isset($_GET['_action'])) {
			$this->install();
		}else{
			if (method_exists($this,$_GET['_action'])) {
				$this->{$_GET['_action']}();
			}
		}
	}
	
	// 数据格式列表
	private function install(){
		$confs = glob($this->getAppConfigPath() . '/*.conf');
		foreach($confs as &$conf){
			$conf = basename($conf, '.conf');
		}
		$this->mSmarty->assign('confs', $confs);
		$this->mSmarty->display('tpl.dataformat.install.htm');
	}
	
	// 删除数据格式
	private function delete(){
		$conf = isset($_GET['conf']) ? $_GET['conf'] : die();
		$filename = $this->getAppConfigPath() . '/' . $conf;
		@unlink($filename . '.conf');
		@unlink($filename . '.tbl');
		@unlink($filename . '.xml');
		@unlink($filename . '.xsd');
		echo "ok";
	}
	
	// 查看xml或xsd
	private function view(){
		$conf = isset($_GET['conf']) ? $_GET['conf'] : die();
		$type = isset($_GET['type']) ? $_GET['type'] : die();
		$filename = $this->getAppConfigPath() . '/' . $conf . '.' . $type;
		header("Content-Type: text/xml");
		$xml = file_get_contents($filename);
		echo $xml;
	}
	
	// 配置数据格式
	private function config(){
		if(isset($_GET['conf']) && $_GET['conf'] != ''){
			// 从保存的文件载入
			$conf = $_GET['conf'];
			$filename = $this->getAppConfigPath() . '/' . $conf . '.tbl';
			$tables = json_decode(file_get_contents($filename), true);

			$treeString = $this->conf_buildTreeString($tables);
		}
		else{
			$treeString = '';
		}
		
		$tDatabases = SOSO_Frameworks_Registry::getInstance()->get('databases');
		$databases = array();
		foreach ($tDatabases as $index=>$info){
			$dbname = $info['host'] . '@' . $info['database'];
			$databases[] = array('name' => $dbname, 'tables' => $this->config_getTables($index, $dbname));
		}
		
		$data = array('treeString' => $treeString);

		$this->mSmarty->assign('data', $data);
		$this->mSmarty->assign('databases', $databases);
		$this->mSmarty->display('tpl.dataformat.config.htm');
	}
	
	// 定义数据格式
	private function define(){
		$nodeInfos = array();
		if(isset($_GET['conf']) && $_GET['conf'] != ''){
			// 从保存的文件载入
			$conf = $_GET['conf'];
			$filename = $this->getAppConfigPath() . '/' . $conf . '.conf';
			$info = json_decode(file_get_contents($filename), true);
			$this->define_buildNodeInfo($info, $nodeInfos); // 将树形结构整理成平板结构
		}

		$tables = isset($_POST['tables']) ? $_POST['tables'] : '';
		$tables = get_magic_quotes_gpc() ? stripslashes($tables) : $tables;
		$tableAry = json_decode($tables, true);

		$data = array(
			'treeString' => $this->define_displayNodes($tableAry['nodes'], true, 'root', $nodeInfos),
			'tables' => $tables,
		);
		
		$this->mSmarty->assign('data', $data);
		$this->mSmarty->display('tpl.dataformat.define.htm');
	}
	
	// 保存
	private function save(){
		header("Content-Type: text/html; charset=gbk");
		$data = isset($_POST['data']) ? $_POST['data'] : die();
		$tables = isset($_POST['tables']) ? $_POST['tables'] : die();
		$data = get_magic_quotes_gpc() ? stripslashes($data) : $data;
		$tables = get_magic_quotes_gpc() ? stripslashes($tables) : $tables;

		$dataAry = json_decode($data, true);
		
		$rootNode = $this->save_createNode($dataAry);
		foreach($dataAry['nodes'] as $item){
			$this->save_walkNode($item, $rootNode);
		}
		
		$format = new SOSO_Base_Data_DataFormat($rootNode, 'gbk');
		$xml = SOSO_Base_Data_DataFormatBuilder::buildXml($format);
		$xsd = SOSO_Base_Data_DataFormatBuilder::buildXsd($format);
		
		$rootNodeName = $dataAry['name'];
		$xmlFileName = $rootNodeName . '.xml';
		$xsdFileName = $rootNodeName . '.xsd';
		$confFileName = $rootNodeName . '.conf';
		$tblFileName = $rootNodeName . '.tbl';
		
		$path = $this->getAppConfigPath();
		file_put_contents($path.'/'.$xmlFileName, $xml);
		file_put_contents($path.'/'.$xsdFileName, $xsd);
		file_put_contents($path.'/'.$confFileName, $data);
		file_put_contents($path.'/'.$tblFileName, $tables);
		echo "保存成功";
	}
	
	//// config
	
	private function config_getTables($dbinx, $dbname){
		$result = array();
		$cmd = SOSO_DB_SQLCommand::getInstance($dbinx);
		$sql = 'show tables';
		$rs = $cmd->ExecuteQuery($sql, 0, 0, 'num');
		$it = new SOSO_DB_Iterator($cmd, $rs, MYSQL_BOTH);
		foreach($it as $row){
			$row = array_values($row);
			$tableName = $row[0];
			$uniqname = $dbinx . '@' . $dbname . '@' . $tableName;
			$result[] = array('name' => $tableName, 'uniqname' => $uniqname);
		}
		return $result;
	}
	
	private function conf_buildTreeString(&$tables){
		$treeAry = array();
		foreach($tables['nodes'] as $item){
			$this->conf_buildTreeNode($item, $treeAry);
		}
		return implode("\n", $treeAry);
	}

	private function conf_buildTreeNode($node, &$treeAry){
		$treeAry[] = sprintf('<li m_name="%s" m_uniqname="%s">%s <a href="###" onclick="deleteNode(this.parentNode)">X</a>', $node['name'], $node['uniqname'], $node['name']);
		$treeAry[] = '<ul>';
		if(count($node['nodes']) > 0){
			foreach($node['nodes'] as $subNode){
				$this->conf_buildTreeNode($subNode, $treeAry);
			}
		}
		$treeAry[] = '</ul>';
		$treeAry[] = '</li>';
	}
	
	//// define
	
	private function define_buildNodeInfo($node, &$nodeInfos){
		$nodeInfos[$node['uniqname']] = $node;
		foreach($node['nodes'] as $item){
			$this->define_buildNodeInfo($item, $nodeInfos);
		}
	}
	
	private function define_displayNodes($nodes, $isRoot, $parentUniqName, $nodeInfos){
		$ss = array();
		$nodeName = $nodes[0]['name'];
		$uniqname = $parentUniqName . '@' . $nodeName;
		$ss[] = $this->define_buildNodeHtml($nodeName, '', $parentUniqName, $nodeInfos);
		$ss[] = '<ul>';
		if(!$isRoot){ // 非根节点，自动生成item节点
			$ss[] = $this->define_buildNodeHtml('item', '', $uniqname, $nodeInfos);
			$ss[] = '<ul>';
			$uniqname .= '@item';
		}
		foreach($nodes as $node){
			// 字段
			$usedFields = array();
			$fields = $this->define_getFieldsByUniqName($node['uniqname']);
			// 按保存的顺序载入字段
			if(count($nodeInfos) > 0){
				foreach($nodeInfos[$uniqname]['nodes'] as $item){
					$fieldName = $item['name'];
					if(!isset($fields['Fields'][$fieldName])) continue; // 子节点
					$fieldInfo = $fields['Fields'][$fieldName];
					$ss[] = $this->define_buildNodeHtml($fieldName, $fieldInfo['Type'], $uniqname, $nodeInfos, $fieldInfo);
					$usedFields[$fieldName] = 1;
				}
			}
			// 载入数据库里未选择的字段
			foreach($fields['Fields'] as $fieldName => $fieldInfo){
				if(isset($usedFields[$fieldName])) continue;
				$ss[] = $this->define_buildNodeHtml($fieldName, $fieldInfo['Type'], $uniqname, $nodeInfos, $fieldInfo);
			}
			// 子节点
			if(count($node['nodes']) > 0){
				foreach($node['nodes'] as $item){
					$ss[] = $this->define_displayNodes(array($item), false, $uniqname, $nodeInfos);
				}
			}
		}
		if(!$isRoot){
			$ss[] = '</ul></li>';
		}
		$ss[] = '</ul></li>';
		return implode("\n", $ss);
	}
	
	private function define_buildNodeHtml($nodeName, $fieldType, $parentUniqName, $nodeInfos, $fieldInfo=null){
		$uniqname = $parentUniqName . '@' . $nodeName;
		$fields = array(
			'alias'=>'', 'defaultvalue'=>'', 'datatype'=>'', 'asattribute'=>'0', 'maxoccurs'=>'1', 'minoccurs'=>'1',
			'pattern'=>'', 'maxlength'=>'', 'minlength'=>'', 'fixed'=>'');
		foreach($fields as $item => $value){
			$$item = isset($nodeInfos[$uniqname][$item]) ? $nodeInfos[$uniqname][$item] : $value;
		}
		if(count($nodeInfos) == 0){ // 无保存的数据
			$checked = ' checked="checked"';
			if($fieldInfo != null){ // 字段
				$defaultValue = $fieldInfo['Default'];
			}
		}
		else{
			$checked = isset($nodeInfos[$uniqname]) ? ' checked="checked"' : '';
		}
		// 字段类型
		if(empty($datatype))
			$datatype = $this->define_getXsdDataType($fieldType);
		// 
		$fieldAry = array();
		foreach($fields as $itemName => $itemDefault){
			$fieldAry[] = sprintf('m_%s="%s"', $itemName, $$itemName);
		}
		$sFields = implode(' ', $fieldAry);
		$result = sprintf(
			'<li m_name="%s" m_uniqname="%s" %s>'.
			'<input type="checkbox"%s /> <a href="###" onclick="editProperty(this.parentNode)">%s</a> ',
			$nodeName, $uniqname, $sFields,	$checked, $nodeName
		);
		$result .= ' <a href="###" style="text-decoration: none" onclick="moveUp(this.parentNode)">↑</a> <a href="###" style="text-decoration: none" onclick="moveDown(this.parentNode)">↓</a>';
		return $result;
	}
	
	public function define_getXsdDataType($datatype){
		if(preg_match('/char|text|blob|binary/', $datatype))
			$result = 'string';
		elseif(preg_match('/int/', $datatype))
			$result = 'integer';
		elseif(preg_match('/decimal/', $datatype))
			$result = 'decimal';
		elseif(preg_match('/float/', $datatype))
			$result = 'float';
		elseif(preg_match('/double/', $datatype))
			$result = 'double';
		elseif(preg_match('/bool/', $datatype))
			$result = 'boolean';
		elseif(preg_match('/datetime/', $datatype))
			$result = 'dateTime';
		elseif(preg_match('/date/', $datatype))
			$result = 'date';
		elseif(preg_match('/time/', $datatype))
			$result = 'time';
		elseif($datatype == '')
			$result = 'container';
		else
			$result = 'string'; // default
		return $result;
	}

	public function define_getFieldsByUniqName($uniqname){
		$items = explode('@', $uniqname);
		$dbinx = intval($items[0]);
		$tableName = $items[3];
		$cmd = SOSO_DB_SQLCommand::getInstance($dbinx);
		return $cmd->getTableFields($tableName);
	}

	//// save
	
	private function save_walkNode($data, $parent){
		$node = $this->save_createNode($data);
		if($data['asattribute'] == '1')
			$parent->addAttribute($node);
		else
			$parent->addNode($node);
		foreach($data['nodes'] as $subNode){
			$this->save_walkNode($subNode, $node);
		}
	}
	
	private function save_createNode($data){
		// fixed 和 default 不能同时出现，fixed 优先
		if($data['fixed'] != '') $data['defaultvalue'] = '';
		
		$result = new SOSO_Base_Data_DataFormatNode($data['name'], $data['datatype']);
		$result->setAlias($data['alias']);
		$nodeConstraints = array(
			array('default', 'defaultvalue'), 
			array('maxOccurs', 'maxoccurs'), 
			array('minOccurs', 'minoccurs'), 
			'fixed'
		);
		$valConstraints = array(
			'pattern', 
			array('maxLength', 'maxlength'), 
			array('minLength', 'minlength')
		);
		foreach($nodeConstraints as $item){
			if(is_array($item)){
				$xsdName = $item[0];
				$srcName = $item[1];
			}
			else{
				$xsdName = $srcName = $item;
			}
			if(isset($data[$srcName]) && $data[$srcName] != ''){
				$result->addNodeConstraint($xsdName, $data[$srcName]);
			}
		}
		foreach($valConstraints as $item){
			if(is_array($item)){
				$xsdName = $item[0];
				$srcName = $item[1];
			}
			else{
				$xsdName = $srcName = $item;
			}
			if(isset($data[$srcName]) && $data[$srcName] != ''){
				$result->addValConstraint($xsdName, $data[$srcName]);
			}
		}
		return $result;
	}
	
	//////////////////////////////////////////
	
	private function buildXml(){
		header("Content-Type: text/xml");
		$format = $this->getDataFormat();
		$result = SOSO_Base_Data_DataFormatBuilder::buildXml($format);
		echo $result;
	}
	
	private function buildXsd(){
		header("Content-Type: text/xml");
		$format = $this->getDataFormat();
		$result = SOSO_Base_Data_DataFormatBuilder::buildXsd($format);
		echo $result;
	}

	private function getDataFormat(){
		$rootNode = new SOSO_Base_Data_DataFormatNode('flight');
		$rootNode->addAttribute(new SOSO_Base_Data_DataFormatNode('code', 'string'));
		
		$testNode = new SOSO_Base_Data_DataFormatNode('testNode', 'string');
		$testNode->addNodeConstraint('default', 'abcd');
		$testNode->addNodeConstraint('fixed', '1234');
		$testNode->addValConstraint('maxLength', 12);
		
		$rootNode->addNode($testNode);
		
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('airline', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('model', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('depCity', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('depAirport', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('depTime', 'time'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('arrCity', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('arrAirport', 'string'));
		$rootNode->addNode(new SOSO_Base_Data_DataFormatNode('arrTime', 'time'));
		
		$discountsNode = new SOSO_Base_Data_DataFormatNode('discounts');
		$itemNode = new SOSO_Base_Data_DataFormatNode('item', 'anyURI');
		$itemNode->addAttribute(new SOSO_Base_Data_DataFormatNode('date', 'date'));
		$itemNode->addAttribute(new SOSO_Base_Data_DataFormatNode('discount', 'float'));
		$itemNode->addAttribute(new SOSO_Base_Data_DataFormatNode('price', 'int'));
		$discountsNode->addNode($itemNode);
		
		$rootNode->addNode($discountsNode);

		$format = new SOSO_Base_Data_DataFormat($rootNode, 'utf-8');
		return $format;
	}
}
?>