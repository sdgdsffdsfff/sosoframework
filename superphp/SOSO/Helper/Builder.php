<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2008-05-06
 * Updates:
 * 1.prepareMapHash改为basetable类inline方式输入，避免使用外部cache文件
 */
/**
 * 辅助工具::项目生成器
 */
class SOSO_Helper_Builder extends SOSO_View_Page {
	protected $step = 1;
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
		!headers_sent($f,$l) && header("Cache-Control: max-age=300");
		umask(0000);
	}
	protected function initSmarty(){
		parent::initSmarty();

		$template_path = dirname(__FILE__).'/template';
		$this->instance->template_dir = $template_path;
		$this->mRoot = SOSO_Frameworks_Registry::getInstance()->get('root_path');
		foreach ($this->mPath as $v){
			if (!file_exists($this->mRoot . $v)) {
				mkdir($this->mRoot . $v,0777,true);
			}
		}
		ob_implicit_flush();
	}

	public function run(){
		if (isset($this->mGET['step']) && is_numeric($this->mGET['step'])) {
			$this->step = $this->mGET['step'];
		}
		$this->assign('step',"第{$this->step}步");
		//$this->display('tpl.install.html');
		if (!isset($this->mGET['_action'])) {
			$this->install();
		}else{
			if (method_exists($this,$this->mGET['_action'])) {
				$this->{$this->mGET['_action']}();
			}
		}
		//print_r(SOSO_Frameworks_Context::getInstance());
	}

	public function install(){
		$tDatabases = SOSO_Frameworks_Registry::getInstance()->get('databases');
		$docRoot = SOSO_Frameworks_Registry::getInstance()->get('root_path');

		$tPath = dirname($this->mPath['template']);
		$tSourcePath = dirname(__FILE__).'/template/';
		$tExtFiles = array('ext/tpl.extheader.htm','ext/tpl.ext_guide.htm','ext/tpl.ext_footer.htm');
		$tNormalFiles = array('base/tpl.base.admin_footer.htm','base/tpl.guide.htm','base/tpl.admin_header.htm');
		foreach(array_merge($tExtFiles,$tNormalFiles) as $v){
			$tFile = $docRoot.'/'.$tPath."/".basename($v);
			$tSource = $tSourcePath . $v;
			if (!file_exists($tFile)) {
				copy($tSource,$tFile);
			}
		}
		echo str_replace("{\$step}","",file_get_contents($tSourcePath."/tpl.helper_header.htm"));
		foreach ($tDatabases as $index=>$info){
			echo "<div style='margin-top:5px'>installing {$info['database']} :</div>\n";
			echo "\t<div class='table_wrapper'>\n\t\t";
			$this->generate($index,$info);
			echo "</div>";
		}
		echo "</div>\n";
	}

	protected function doConfig($table=null,$index=0){
		SOSO_Util_Util::nocache_headers();
		try{
			$tTable = new SOSO_ORM_TableObject($table,$index);
		}catch(Exception $e){
			echo $e->getMessage();
			return false;
		}

		$tFileFormat = "%s/%s_%s_config.serialize.log";
		$tFile = sprintf($tFileFormat,$this->mRoot.$this->mPath['cache'],$index,trim($table));
		if(!file_exists($tFile)) {
			foreach($tTable->getIterator() as $key => $value) {
				$config[] = array (	'name' => $key,'comment' => $key,
				'type' => 'text',
				'insert' => '1','update' => '1',
				'select' => '1',
				'list' => '1',
				'search' => '1');
			}
			$this->file_put_contents($tFile,serialize($config));
		}
		$tCharsetFormat = "%s/%s_%s_charset.log";
		$tCharsetFile = sprintf($tCharsetFormat,$this->mRoot.$this->mPath['cache'],$index,trim($table));
		
		if (file_exists($tCharsetFile)) {
			$tCharset = file_get_contents($tCharsetFile);
		}else{
			$tCharset = $tTable->getCharset();	
		}
		if($_SERVER['REQUEST_METHOD']=="POST") {
			$tConfig = $_POST['table_config'];
			$p = "#[\x80-\xff]{2,}#i";
			foreach ($tConfig as $k=>$v){
				if (strlen($tConfig[$k]['comment']) && preg_match_all($p,$tConfig[$k]['comment'],$m)) {
					//$tConfig[$k]['comment'] = SOSO_Util_String::any2unicode($tConfig[$k]['comment']);
					$tConfig[$k]['comment'] = str_replace($m[0],array_map(array('SOSO_Util_String','any2unicode'),$m[0]),$tConfig[$k]['comment']);
				}else{
					$tConfig[$k]['comment'] = (strlen(trim($tConfig[$k]['comment'])) == 0) ? $tConfig[$k]['name'] : $tConfig[$k]['comment'];
				}
			}
			$this->file_put_contents($tFile,serialize($tConfig));
			$this->file_put_contents($tCharsetFile,$_POST['charset']);
			$tCharset = $_POST['charset'];
			if (isset($this->mPOST['overwrite']) && $this->mPOST['overwrite']) {
				$tAdmin = new SOSO_ORM_TableAdmin($table,$index);
				foreach ($tAdmin->mActions as $action){
					$tAdmin->mAction = $action;
					$tAdmin->generateTemplate(true);
				}
				$this->mSmarty->assign('overwrite',true);
			}
		}
		$this->mSmarty->assign('charset',$tCharset);
		return unserialize(file_get_contents($tFile));
	}
	
	private function config(){
		if (!(isset($this->mGET['index'])&&is_numeric($this->mGET['index'])) || !isset($this->mGET['table'])) {
			echo "<div class='tables'><a href='SOSO_Helper_Builder.php'>参数不合法,".
				 "调用方式应为?_action=config&table=表名&index=索引</a></div>";
			return false;
		}
		$table_config = $this->doConfig($this->mGET['table'],$this->mGET['index']);
		$this->mSmarty->assign('list', $table_config);
		$this->mSmarty->assign('_action', 'config');
		$this->mSmarty->assign('types', array('text'=>'单行文本','textarea'=>'多行文本','file'=>'文件','password'=>'密码','select'=>'列表','date'=>'日期'));
		$this->mSmarty->assign('yesno', array('1'=>'是','0'=>'否'));
		$this->mSmarty->assign('charsets',array('gbk'=>'gbk','utf8'=>'utf-8'));
		//$this->mSmarty->assign('charset',$tCharset);
		$this->mSmarty->assign('admin_class',"Admin_".substr(SOSO_Util_Util::magicName($this->mGET['table']),1));
		$this->mSmarty->display("tpl.config.htm");
	}

	public function generate($index,$config){
		try{
			$tDB = SOSO_DB_SQLCommand::getInstance($index);
			$tCharset = 'gbk';
			if(isset($config['charset']) && strlen($config['charset'])){
				$tCharset = $config['charset'];
			}
		}catch (Exception $e){
			return false;
		}
		$tDB->setCharset($tCharset);
		$tRes = $tDB->ExecuteArrayQuery("SHOW TABLES FROM ".$config['database'],0,0,'num');
		$class_directory = SOSO_Frameworks_Config::getSystemPath('class');
		$base_directory = $class_directory.'/Base';
		if (!file_exists($base_directory)){
			mkdir($base_directory,0777,true);
		}
		$fields = array();
		$len = count($tRes);
		if ($len == 0) {
			echo "无数据表";
		}
		for ($i=0;$i<$len;$i++){
			$this->clear_all_assign();
			$table = $tRes[$i][0];
			$property = array();
			$class_name = substr(SOSO_Util_Util::magicName($table),1);

			$this->assign('class_name',$class_name);
			$this->assign('table_name',$table);
			$this->assign('db_offset',$index);
			
			//生成meta信息
			$this->doConfig($table,$index);
			
			$tClassFile = $class_directory."/{$class_name}.php";
			$tBaseClass = $base_directory."/{$class_name}.php";
			$tAdminFile = $this->mRoot.$this->mPath['class']."/{$class_name}.php";
			$text = "<div class='tables'><a href='?_action=config&table=$table&index=$index&step=2' title='配置表$table'>配置</a>    $table</div>\n\t";
//		if (file_exists($tClassFile)) {
//				echo $text;
//				if (!file_exists($tAdminFile)) {
//					$this->file_put_contents($tAdminFile,$this->fetch('tpl.class.manager.htm'));
//				}
//				continue;
//			}
			
			try{
				//$fields[$table] = $tDB->getTableFields($table);
				$fields = $tDB->getTableFields($table);
			}catch(Exception $e){
				echo $e->getMessage();
				continue;
			}
			
			if (!empty($fields['Fields'])) {
				$isUTF8 = strtolower($fields['charset']) == 'utf8';
					
				foreach (new ArrayObject($fields['Fields']) as $k=>$v){
					$v['name'] = $k;
					unset($v['Privileges'],$fields['Fields'][$k]['Privileges']);
					$v['property'] = SOSO_Util_Util::magicName($k);
					if (strlen($v['Comment']) && $isUTF8){
						if (extension_loaded('mbstring')){
							if ('utf-8' == mb_detect_encoding($v['Comment'],array('gbk','utf-8')))
								$fields['Fields'][$k]['Comment'] = $v['Comment'] = mb_convert_encoding($v['Comment'],'gbk','utf-8');
						}else{
							$fields['Fields'][$k]['Comment'] = $v['Comment'] = iconv('utf-8','gbk',$v['Comment']);
						}
					}
					$property[] = $v;
				}
				
				$tFieldSource = var_export($fields,true);
				$tFieldSource = join("\n\t\t",explode("\n",$tFieldSource));
				$this->assign('tableFieldHash',$tFieldSource);
				$fields = array();
				$this->assign('properties',$property);
				$this->assign('date',date("Y-m-d H:i:s A"));
				$baseClassDef = $this->fetch('tpl.base_mapping.html');
				$classDef = $this->fetch('tpl.mapping.html');
				if ($isUTF8)
					$baseClassDef = mb_convert_encoding($baseClassDef,'utf-8','gbk');
					
				$this->file_put_contents($tBaseClass,$baseClassDef);
				
				if (!file_exists($tClassFile)) {
					$this->file_put_contents($tClassFile,$classDef);
				}
				if (!file_exists($tAdminFile)) {
					$this->file_put_contents($tAdminFile,$this->fetch('tpl.class.manager.htm'));
				}
				echo $text;
			}
		}
	}

	public function createProject(){
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$pDirectories = array("WEB-INF","images","scripts","WEB-INF/class","WEB-INF/class/Page",'WEB-INF/template','WEB-INF/temp','WEB-INF/temp/template_c','WEB-INF/temp/cache');
			print_r($pDirectories);
		}else{
			$this->display('tpl.create_project_config.html');
		}
	}

	/**
	 * 生成数据表映射类
	 * buggy : 1.暂不支持多库同名表
	 *
	 */
	public function createTableMappingObject(){
		$databases = SOSO_Frameworks_Registry::getInstance()->get('databases');
		if (empty($databases)) {
			exit('无数据库相关配置信息');
		}
		$class_directory = SOSO_Frameworks_Config::getSystemPath('class');
		if (!file_exists($class_directory)) {
			mkdir($class_directory,'0777',true);
		}
		foreach ($databases as $dbIndex=>$config) {
			$tSQLCommand = SOSO_DB_SQLCommand::getInstance($dbIndex);
			$res = $tSQLCommand->ExecuteArrayQuery("SHOW TABLES");
			$fields = array();
			for ($i=0,$len=count($res);$i<$len;$i++){
				$table = current($res[$i]);
				$property = array();
				$class_name = substr(SOSO_Util_Util::magicName($table),1);

				try{
					$fields[$table] = $tSQLCommand->getTableFields($table);
				}catch(Exception $e){
					echo $e->getMessage();
					continue;
				}
				
				if (!empty($fields[$table]['Fields'])) {
					foreach (new ArrayObject($fields[$table]['Fields']) as $k=>$v){
						$v['name'] = $k;
						$v['property'] = SOSO_Util_Util::magicName($k);
						$property[] = $v;
					}
					unset($fields[$table]);
					$this->assign('properties',$property);
					$this->assign('table_name',$table);
					$this->assign('db_offset',$dbIndex);
					$this->assign('class_name',$class_name);
					$this->assign('date',date("Y-m-d H:i:s A"));
					$classDef = $this->fetch('tpl.mapping.html');
					if (!file_exists($class_directory."/{$class_name}.php")) {
						$this->file_put_contents($class_directory."/{$class_name}.php",$classDef);
					}
					$this->clear_all_assign();
				}
			}

		}
	}
	
	private function file_put_contents($file,$data='',$flag=null){
		if (file_put_contents($file,$data,$flag)){
			@chmod($file,0777);
		}
	}
}
