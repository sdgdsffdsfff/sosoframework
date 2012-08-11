<?php
/**
 * 
 * 支持chrome/firebug的控制器输出
 * @author zhangyong
 *
 */
class SOSO_Logger_Browser extends SOSO_Logger_Abstract{
	protected $methods = array(
       'FATAL'    => 'error',
       'CRITICAL' => 'error',
       'ERROR'    => 'error',
       'WARNING'  => 'warn',
       'INFO'     => 'info',
       'DEBUG'    => 'debug');
                    
	public function __construct($level = SOSO_Log::DEBUG, $bubble = true,$buffering=false){
        parent::__construct($level, $bubble,$buffering);
	}

	public function getDefaultFormatter(){
		return new SOSO_Logger_JSONFormatter();
	}
	
	protected function log(SOSO_Logger_Message $message){
		$levelName = $message->getLevelName();
		$method = array_key_exists($levelName, $this->methods) 
			? $this->methods[$levelName]
			: 'debug';
		
		echo '<script type="text/javascript">';
		
		if (count($message->getExtra()) || count($message->getContext()))
			echo sprintf('console.%s(%s);', $method, $message->getFormatted());
		else 
			echo sprintf('console.%s("%s");', $method, addslashes($message->getMessage()));
		echo "</script>\n";
	}
}