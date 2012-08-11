<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2009-11-18
 * 
 * ×Ó½ø³ÌÈİÆ÷
 */
class SOSO_Base_TaskWrapper implements SOSO_Interface_Runnable {
	const THREAD_NAME_KEY = 'SOSO_Base_TaskWrapper_Key';
    public $mPIDFile ;

	public function __constructor(){
		if (!isset($_REQUEST[self::THREAD_NAME_KEY])) {
			throw new Exception('You should specified THREAD_NAME_KEY first!',1);		
		}
	}
	
	public static function getFragment($script){
		return self::THREAD_NAME_KEY . '=' . $script;
	}
    
    public function __destruct(){
        if(!strlen($this->mPIDFile)) return ;
        $tTemp = SOSO_Frameworks_Config::getSystemPath('temp');
        $path = $tTemp.'/'.$this->mPIDFile; 
        echo "wrapper destructed\n";
        echo "rm -rf $path";
        system("rm -rf $path");     
    }

	public function run(){

		$thread = $_REQUEST[self::THREAD_NAME_KEY];
		$tParentSeed = '';
		$tName = $thread;
		if(isset($_GET[SOSO_Base_TaskRunner::SUB_THREAD_VAR])){
			$tParentSeed = $_GET[SOSO_Base_TaskRunner::SUB_THREAD_VAR];
			$tName = $tParentSeed . '/' . $thread;
		}
		
		$this->mPIDFile = SOSO_Base_TaskRunner::seed(false,getmypid(),$tName);
        sleep(60);
		$tThreadInstance = new $thread();
		$tThreadInstance->run();
	}
}
