<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 15-04-2009 20:45:05
 */
class SOSO_Base_TaskRunner {

	/**
	 * ��������״̬
	 */
	private $mRunning = false;
	private $mTasks;
	protected $mPID;
	private $mPIDFile = '';
	private $mInterval;
	public static $Registry;
	protected $mSeed;
	/**
	 * 
	 * �Ƿ�ʹ��APP_NAME��Ϊtoken��Ĭ��ʹ��pid
	 * @var unknown_type
	 */
	protected $mUseAppName = false;
    /**
     * max num for (main) processes 
     */
    protected $mServerLimit = 1;
    /**
     * ÿ�����̵���ಢ����
     *
     * @var int
     */
    protected $mThreadLimit = 50;
    protected $mDuration = 300;
	protected $mWaits=5;
    protected $mWait = 5;
    /**
     * �߳���
     *
     * @var string
     */
    protected $mTask;
    protected static $mTaskPool = array();
    protected $mBlocking = false;
    protected $mLogFile = '/dev/null';
    
	/**
	 * ���ݸ��ӽ��̵Ĳ�����
	 *
	 * @var unknown_type
	 */
	const SUB_THREAD_VAR = '__SeedNameFromTaskRunner__';
	
	const TASK_PID_KEY = '__TASKRUNNER_KEY__';
	
	function __construct(){
		$this->mPID = getmypid();
		if (!$this->serverLimit()) {
			$tMsg = "Too many processes! \nCheck Server Limit Configuration";
        	$tMsg.= " or check Server PID files in WEB-INF/temp";
        	$tMsg.= " or check your ENVIRONMENT!";
            throw new Exception($tMsg);
            exit(1); 
		}
		//$this->mSeed = self::seed(true,$this->mPID,get_class($this));
		$this->logPID();
//        if(!$this->plimit('',$this->mServerLimit)){
//        	$tMsg = "Too many processes! \nCheck Server Limit Configuration";
//        	$tMsg.= " or check Server PID files in WEB-INF/temp";
//        	$tMsg.= " or check your ENVIRONMENT!";
//            throw new Exception($tMsg);
//            exit(1); 
//        }
		$this->initialize();
	}
	
	/**
	 * 
	 * ����token�ѡ��ʹ��APP_NAME��pid
	 * @param unknown_type $flag
	 */
	public function useAppName($flag=true){
		$this->mUseAppName = !!$flag;
	}
	/**
	 * ��ʼ��������Ϣ
	 *
	 */
	protected function initialize(){
		$tConfig = array();
		$tPHPBin = SOSO_Frameworks_Config::getPath('//phpbin');
		if (!$tPHPBin || !strlen(trim($tPHPBin))) {
			$tPHPBin = '/usr/local/bin/php';
		}
		if (!is_executable($tPHPBin)) {
			throw new Exception(" Please specify PHP-bin Path in web.xml ! \n e.g: <phpbin>/usr/local/bin/php</php>",1234);
		}
		$tConfig['PHP_BIN_PATH'] = $tPHPBin.' ';
		$tConfig['ROOT_PATH'] = SOSO_Frameworks_Config::document_root_path().DIRECTORY_SEPARATOR;
		//$tConfig['ENTRY_PATH'] = $tConfig['ROOT_PATH'].'WEB-INF'.DIRECTORY_SEPARATOR.'entry.php ';
		$tConfig['ENTRY_PATH'] = 'WEB-INF'.DIRECTORY_SEPARATOR.'entry.php ';
		chdir($tConfig['ROOT_PATH']);
		self::$Registry = new ArrayObject($tConfig);
	}

	/**
	 * ��������
	 *
	 */
    function __destruct(){
		if (!file_exists($this->mPIDFile)) {
			return true;
		}
		$this->removePID($this->mPIDFile);
	}
	
	protected function removePID($log){
		if (!strlen($log) || $log == '/') {
			return;
		}
		system("rm -rf $log");
        @unlink($log);
		return !file_exists($log);
	}
	/**
	 * ƴװִ������
	 *
	 * @param string $pScript   ִ���ļ�������
	 * @param array $pParams    �������
	 * @param boolen $pBlocked  �Ƿ�����ģʽ
	 * @param string $pRedirect �ض����ļ���Ĭ����
	 * @return string
	 */
	public function getCommand($pScript,$pParams=array(),$pBlocked=false,$pRedirect='/dev/null'){
		$tParam = ' '.str_replace("&",' ',http_build_query($pParams,'','&'));
		$pBlocked == false && $tParam .= $this->getToken() ;
		$tRedirect = ' ';
		if (strlen($pRedirect) && $pRedirect!='/dev/null'){
			$redirector = ">".($pBlocked?'':'>');
			$tRedirect.=  $redirector . trim($pRedirect);
			// avoids more the 2 '>' in the command to be executed
			$tRedirect = preg_replace("#(>{3,})#U",">>",$tRedirect);
		}else{
			$tRedirect.= ">" . $pRedirect;
		}
		
		$tCommand  = self::$Registry->offsetGet('PHP_BIN_PATH') . self::$Registry->offsetGet('ENTRY_PATH');
		$tCommand .= " ".$pScript . " " . $tParam . $tRedirect;
		$pBlocked  == false && $tCommand .= " &";
		return $tCommand ;
	}
	
	/**
	 * 
	 * ���������߳�����Ĭ�ϰ����̷���
	 */
	protected function getToken(){
		return sprintf(" %s=%s",self::TASK_PID_KEY,$this->mUseAppName 
			? str_replace(' ', '_', constant('APP_NAME')) 
			: $this->mPID) ;
	}
	
	/**
	 * ƴװִ������
	 *
	 * @deprecated
	 * @param string $pScript   ִ���ļ�������
	 * @param array $pParams    �������
	 * @param boolen $pBlocked  �Ƿ�����ģʽ
	 * @param string $pRedirect �ض����ļ���Ĭ����
	 * @return string
	 */
	public function getCommand2($pScript,$pParams=array(),$pBlocked=false,$pRedirect='/dev/null'){
		$tParam = ' '.str_replace("&",' ',http_build_query($pParams));
		$pBlocked == false && $tParam .= " ".SOSO_Base_TaskRunner::SUB_THREAD_VAR."={$this->mSeed} ";
		$tParam .= SOSO_Base_TaskWrapper::getFragment($pScript);
		$pScript = "SOSO_Base_TaskWrapper";
		$tRedirect = ' ';
		if (strlen($pRedirect) && $pRedirect!='/dev/null'){
			$redirector = ">".($pBlocked?'':'>');
			$tRedirect.=  $redirector . trim($pRedirect);
			// avoids more the 2 '>' in the command to be executed
			$tRedirect = preg_replace("#(>{3,})#U",">>",$tRedirect);
		}else{
			$tRedirect.= ">" . $pRedirect;
		}
		
		$tCommand  = self::$Registry->offsetGet('PHP_BIN_PATH') . self::$Registry->offsetGet('ENTRY_PATH');
		$tCommand .= " ".$pScript . " " . $tParam . $tRedirect;
		$pBlocked  == false && $tCommand .= " &";
		return $tCommand ;
	}
	/**
	 * �����߳�
	 *
	 * @param string $pScript   ִ���ļ�������
	 * @param array $pParams    �������
	 * @param boolen $pBlocked  �Ƿ�����ģʽ,Ĭ��false
	 * @param string $pRedirect �ض����ļ���Ĭ����
	 * @param integer $pMaxThread ������ģʽ�µ���󲢷���
	 * @return unknown
	 */
	public function startThread($pScript,$pParams=array(),$pBlocked=false,$pRedirect='/dev/null',$pMaxThread=null){
		$pMaxThread = is_numeric($pMaxThread) ? $pMaxThread : $this->mThreadLimit;
		while(!$this->limit($pScript,$pMaxThread)){
			sleep($this->mWaits);
		}

		$tCommand = $this->getCommand($pScript,$pParams,$pBlocked,$pRedirect);
		
		$fp = popen($tCommand,'r');
		pclose($fp);
		usleep($this->mDuration);
		return true;
	}
	
	/**
	 *  �����ļ��Ĳ������ƣ��������Բ�����������ʹ�ã�
	 *
	 * @param string $pScript   ִ���ļ�������
	 * @param array $pParams    �������
	 * @param boolen $pBlocked  �Ƿ�����ģʽ,Ĭ��false
	 * @param string $pRedirect �ض����ļ���Ĭ����
	 * @param integer $pMaxThread ������ģʽ�µ���󲢷���
	 * @return unknown
	 */
	public function startThread2($pScript,$pParams=array(),$pBlocked=false,$pRedirect='/dev/null',$pMaxThread=null){
        $pMaxThread = $pMaxThread ? $pMaxThread : $this->mThreadLimit;
		while(!$this->plimit($pScript,$pMaxThread)){
			sleep($this->mWaits);
		}
		$tCommand = $this->getCommand2($pScript,$pParams,$pBlocked,$pRedirect);
		$fp = popen($tCommand,'r');
		pclose($fp);
		usleep($this->mDuration);
		return true;
	}
	
	/**
	 * ��tempĿ¼�£�����һ���ԡ�����_PID��Ϊ���ֵ�Ŀ¼
	 *
	 * @return SOSO_Base_TaskRunner
	 */
	protected function logPID(){
		if (strlen($this->mPIDFile)) {
			return;
		}
		$tTemp = SOSO_Frameworks_Config::getSystemPath('temp');
		$tPIDPath = get_class($this);
		if (!file_exists($tTemp."/".$tPIDPath)) {
			mkdir($tTemp."/".$tPIDPath,0777,true);
		}
		
		$tSeed = $tPIDPath."/Worker_".$this->mPID;
		$tPath = $tTemp.DIRECTORY_SEPARATOR.$tSeed;
		$return = true;
		if (!file_exists($tPath)) {
			$return = mkdir($tPath,0777,true);
			chmod($tPath,0777);
		}
		if ($return) {
			$this->mPIDFile = $tPath;
		}
		return $return;
	}
	/**
	 * ��tempĿ¼�£�����һ���ԡ�����_PID��Ϊ���ֵ�Ŀ¼
	 * 
	 * @param bool $isMain �Ƿ�Ϊ������
	 * @param int  $pid    ����pid
	 * @param string $name ������
	 *
	 * @return SOSO_Base_TaskRunner
	 */
	public static function seed($isMain=true,$pid=null,$name=__CLASS__){
		$pid = is_null($pid) ? getmypid() : $pid;
		$tTemp = SOSO_Frameworks_Config::getSystemPath('temp');
		if(true != $isMain){
            $tSeed = $name."_".$pid;
            $tPath = $tTemp.'/'.$tSeed;
            if(!file_exists(dirname($tPath))) return ''; 
			file_put_contents($tTemp.'/'.$tSeed,'1',FILE_APPEND);
			return $tSeed;
		}
		if (!file_exists($tTemp."/".$name)) {
			mkdir($tTemp."/".$name,0777,true);
		}
		
		$tSeed = $name."/".("Worker_").$pid;
		$tPath = $tTemp.DIRECTORY_SEPARATOR.$tSeed;
		if (!file_exists($tPath)) {
			mkdir($tPath,0777,true);
			chown($tPath,0777);
		}
		return $tSeed;
	}
	
	/**
	 * check whether or not exceed the ServerLimit 
	 * 
	 *
	 * @return bool ����������������ServerLimitʱ��Ϊ��;����Ϊ��
	 */
	protected function serverLimit(){
		clearstatcache();
		$tTemp = SOSO_Frameworks_Config::getSystemPath('temp');
		$tPIDPath = get_class($this);
		if (!file_exists($tTemp."/".$tPIDPath)) {
			return true;
		}
		$tPattern = $tTemp."/".$tPIDPath."/Worker_*";
		$res = glob($tPattern);
		$cmd = 'ps axo "%p" | tail +2';
	
		if (PHP_OS == 'Darwin'){
			$cmd = 'ps axo "pid" | tail +2';
		}
		$f=popen($cmd,'r');
		$ret='';
		while(!feof($f)) $ret.=fread($f,8192);
		pclose($f);
		if(strlen($ret) && preg_match_all("#(\d+)#",$ret,$m)){
			$tPIDList = $m[1];
		}
		$tTotal = count($res);
		if ($this->mServerLimit <= $tTotal){
            $tDeadPIDFiles = array();
			foreach ($res as $file){
				$tPID = substr(strrchr($file,'_'), 1);
				if (false === array_search($tPID,$tPIDList)) {
					$done = $this->removePID($file);
                    $done ? --$tTotal : array_push($tDeadPIDFiles,$file);
				}
			}
            if (count($tDeadPIDFiles)) {
                $tMsg = "\n";
                $tMsg.= "You need clear these files manually:\n";
                $tMsg.= "\t".join("\n\t",$tDeadPIDFiles);
                $tMsg.= "\n-------------------------------------------\n";
                if ($this->mServerLimit > $tTotal){
                    echo $tMsg;
                }else{
                    throw new Exception($tMsg,date("Ymd"));
                }
            }
		}
		return $this->mServerLimit - $tTotal;
	}
	
	/**
	 * [������ʹ��] another implemetation for limit
	 * @deprecated
	 * @param string $pScript
	 * @param int $MaxThread
	 */
	protected function plimit($pScript='',$MaxThread){
		clearstatcache();
		$temp = SOSO_Frameworks_Config::getSystemPath('temp');
		$path = $temp.'/'.$this->mSeed.(strlen($pScript) ? "/" : '').$pScript;
		$pattern = preg_replace("#_\d+$#","_*",$path);
		if( substr($pattern,-1) !== '*') $pattern .= '*';
		$res = glob($pattern);
		return $MaxThread > count($res);
	}

    /**
     * fork new process
     * @deprecated
     * @return SOSO_Base_TaskRunner
     */
    public function fork($millisec=500,$param=array()){
        if(!$this->plimit('',$this->mThreadLimit)){
            return false;
        }
        $this->startThread(get_class($this),$param);
    }
	/**
	 * ���ָ���ű��������Ƿ񳬹�������ƣ�
	 * ����������Ʒ���true�����򷵻�false
	 *
	 * @param string $pScript
	 * @param integer $pMaxThread
	 * @return boolean
	 */
	protected function limit($pScript,$pMaxThread=null){
		$pMaxThread = is_numeric($pMaxThread) ? $pMaxThread : $this->mThreadLimit;
		//$pNeedle = sprintf("%s=%s",SOSO_Base_TaskRunner::TASK_PID_KEY,$this->mPID);
		$pNeedle = $this->getToken();
		// the sequence below is supposed to be kept as it be
		$tCommands = array();
		$tCommands[] = "ps -ef";
		$tCommands[] = "grep $pNeedle";
		$tCommands[] = "grep $pScript";
		$tCommands[] = "grep -v '/bin/sh -c'";
		$tCommands[] = "grep -v 'grep'";
		$tCommands[] = "wc -l";
		$tCommandLine = join("|",$tCommands);
		$f = popen($tCommandLine,'r');
		$num = intval(fread($f,1024));
		pclose($f);
		return $pMaxThread - $num > 0;
	}
	
	protected function getPID(){
		return $this->mPID;
	}
	/**
	 * 
	 * @param $task
	 */
	private function removeTask($task){
	}

	private function runTasks(){
		
	}
	public function setOptions($options){
		if (!is_array($options) || empty($options)) {
			return false;
		}
		$tOptions = array('thread_limit','duration','waits','task','log_file','blocking');
		foreach ($tOptions as $v){
			if (isset($options[$v]) /*&& strlen(trim($options[$v]))*/) {
				$this->setOption($v,$options[$v]);
			}
		}
	}
	
	public function setOption($key,$value){
        if(!class_exists('SOSO_Util_Util',false)){
            require_once(dirname(dirname(__FILE__)).'/Util/Util.php');
        }
		$key = SOSO_Util_Util::magicName($key);
		$this->$key = $value;
		return $this;
	}
	
	public function setBlocking($blocking){
		return $this->setOption('blocking',$blocking);
	}
	public function setLogFile($pFile='/dev/null'){
		return $this->setOption('log_file',$pFile);
	}
	public function setThreadLimit($num){
		return $this->setOption('thread_limit',$num);
	}
	public function setTask($pClass){
		$this->mTask = $pClass;
		return $this->pushTask($pClass);
	}
	
	/**
	 * �������
	 *
	 * @param unknown_type $pClass
	 * @param unknown_type $pParames
	 * @param unknown_type $pAppend
	 * @return unknown
	 */
	public function pushTask($pClass,$pParames=array(),$pOverwrite=true){
		$tTpl = array('params'=>array(),'blocking'=>$this->mBlocking,'log'=>$this->mLogFile,'thread_limit'=>$this->mThreadLimit);
		
		if (is_array($pClass)) {
			if ($pOverwrite) {
				self::$mTaskPool = array();
			}
			
			foreach ($pClass as $task=>$options){
				self::$mTaskPool[$task][] = (object)array_merge($tTpl,$options);
			}
			//var_dump(self::$mTaskPool);
			return $this;
		}
		self::$mTaskPool[$pClass][] = (object)array_merge($tTpl,$pParames);
		return $this;
	}
	
	public function addTask($pClass,$pParames=array(),$pOverwrite=true){
		return $this->pushTask($pClass,$pParames,$pOverwrite);
	}

	public function start()	{
		if (!self::$mTaskPool && strlen($this->mTask)) {
			$this->startThread($this->mTask,array(),$this->mBlocking,$this->mLogFile,$this->mThreadLimit);
			return $this;
		}
		foreach (self::$mTaskPool as $task=>$array){
			foreach($array as $options){
				$this->startThread($task,$options->params,$options->blocking,$options->log,$options->thread_limit);
			}
		}
		return $this;
	}
	
	public function startAll(){
		
	}

	/**
	 * 
	 * @todo �źŲ�׽/ģ��
	 */
	public function stop(){
	}

	public function stopAll()	{
		
	}

}
?>
