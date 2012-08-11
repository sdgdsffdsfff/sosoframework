<?php
/*
* @author pennywang
* 输出到syslog, $config里面ident是前缀，facility是虚拟设备
* 
* @modified by moon
*/
class SOSO_Logger_Syslog extends SOSO_Logger_Abstract{
    private $__is_open=false;
    protected $facility;
    protected $priority;
    private $_config=array(
            //'facility'=>LOG_LOCAL7,
            'option'=>LOG_ODELAY,
            'ident'=>'',
            );
    private static $level2priority = array(
            SOSO_Log::DEBUG=>LOG_DEBUG,
            SOSO_Log::INFO=>LOG_INFO,
            SOSO_Log::WARNING=>LOG_WARNING,
            SOSO_Log::ERROR=>LOG_ERR,
            SOSO_Log::CRITICAL=>LOG_CRIT,
            SOSO_Log::FATAL=>LOG_EMERG,
            );  

	protected $priority_names = array(
        "alert"=>    LOG_ALERT,
        "crit"=>     LOG_CRIT,
        "critical"=> LOG_CRIT,
        "debug"=>    LOG_DEBUG,
        "emerg"=>    LOG_EMERG,
        "err"=>      LOG_ERR,
        "error"=>    LOG_ERR,        #  DEPRECATED
        "info"=>     LOG_INFO,
        "notice"=>   LOG_NOTICE,
        "warning"=>  LOG_WARNING
	);
	
	protected $priority_map = array(
		LOG_ALERT => SOSO_Log::FATAL,
		LOG_CRIT  => SOSO_Log::CRITICAL,
		LOG_DEBUG => SOSO_Log::DEBUG,
		LOG_EMERG => SOSO_Log::FATAL,
		LOG_ERR   => SOSO_Log::ERROR,
		LOG_INFO  => SOSO_Log::INFO,
		LOG_NOTICE=> SOSO_Log::INFO,
		LOG_WARNING=>SOSO_Log::WARNING
	);

	protected $facility_names = array(
        "auth"=>     LOG_AUTH,
        "authpriv"=> LOG_AUTHPRIV,
        "cron"=>     LOG_CRON,
        "daemon"=>   LOG_DAEMON,
        "ftp"=>      LOG_FTP,
        "kern"=>     LOG_KERN,
        "lpr"=>      LOG_LPR,
        "mail"=>     LOG_MAIL,
        "news"=>     LOG_NEWS,
        "syslog"=>   LOG_SYSLOG,
        "user"=>     LOG_USER,
        "uucp"=>     LOG_UUCP,
        "local0"=>   LOG_LOCAL0,
        "local1"=>   LOG_LOCAL1,
        "local2"=>   LOG_LOCAL2,
        "local3"=>   LOG_LOCAL3,
        "local4"=>   LOG_LOCAL4,
        "local5"=>   LOG_LOCAL5,
        "local6"=>   LOG_LOCAL6,
        "local7"=>   LOG_LOCAL7
	);
	
	public function __construct($config,$facility=LOG_USER, $level = LOG_DEBUG, $bubble = true,$buffering=false){
        $this->_config=array_merge($this->_config,$config);
        if (!is_numeric($facility)){
        	$facility = isset($this->facility_names[$facility]) ? $this->facility_names[$facility] : LOG_USER;	
        }
        $this->facility = $facility;

        $tLevel = $level;
        if (array_key_exists($level, $this->priority_map)){
        	$tLevel = $this->priority_map[$level];
        }
        parent::__construct($tLevel,$bubble,$buffering);
	}
	
	protected function open(){
		if(!$this->__is_open){
            openlog($this->_config['ident'],
                    $this->_config['option'],
                    $this->facility);
            $this->__is_open=true;
        }
        return $this;
	}
	
	protected function log(SOSO_Logger_Message $message){
        $this->open();
        syslog($this->getPriority($message->getLevel()),strval($message->getFormatted()));
	}
	
	protected function getPriority($level){
		if(array_key_exists($level, $this->priority_map)){
			return $this->level2priority[$level];
		}
		return LOG_WARNING;
	}

    public function close(){
        if($this->__is_open){
            closelog();
        }
    }
}