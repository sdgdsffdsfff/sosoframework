<?php
require_once 'Logger/IProcessor.php';
require_once 'Logger/IFormatter.php';
require_once 'Logger/Abstract.php';
/**
 * 
 * 日志类
 * @author zhangyong
 * $Id$
 */
class SOSO_Log {
    
    const DEBUG = 100;

    const INFO = 200;

    const WARNING = 300;

    const ERROR = 400;

    const CRITICAL = 500;

    const FATAL = 550;

    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'FATAL',
        
        'DEBUG'=>100,
        'INFO'=>200,
        'WARNING'=>300,
        'ERROR' => 400,
        'CRITICAL' => 500,
        'FATAL' => 550
    );

    protected $name;

    /**
     * The logger stack
     *
     * @var SOSO_ObjectStorage
     */
    protected $loggers = array();

    /**
     *
     * @var SOSO_ObjectStorage[]
     */
    protected $processors = array();
    
    protected static $loggings = array();

    /**
     * @param string $name
     */
    public function __construct($name){
        $this->name = $name;
//        $this->loggers = new SOSO_ObjectStorage();
//        $this->processors = new SOSO_ObjectStorage();
    }

    /**
     * 
     * factory
     * @param string $name
     */
    public static function getLogging($name='default'){
    	if (isset(self::$loggings[$name])) return self::$loggings[$name];
    	return self::$loggings[$name] = new self($name);
    }
    
    /**
     * 
     * 配置logging的简易办法
     * @param array $config
     * @return SOSO_Log
     */
    public function basicConfig($config=array()){
    	$len = count($this->loggers);
    	if (0 != $len){
    		return false;
    	}
    	$level = SOSO_Log::WARNING;
    	if (isset($config['level'])) $level = $config['level'];
    	
    	if (isset($config['filename']) && strlen($config['filename'])){
    		$filename = $config['filename'];
    		$mode = isset($config['filemode']) ? $config['filemode'] : 'a';
    		$stream = fopen($filename,$mode);
    	}else{
    		$stream = isset($config['stream']) ? $config['stream'] : fopen('php://stderr','a');
    	}
    	
    	$logger = new SOSO_Logger_Stream($stream, $level);
    	
    	if (isset($config['format'])){
    		$logger->getFormatter()->setFormat($config['format']);
    	}
    	
    	if (isset($config['datefmt'])){
    		$logger->getFormatter()->setDateFormat($config['datefmt']);
    	}
    	$this->addLogger($logger);
    	return $this;
    }
    
    /**
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * 
     * @param SOSO_Log_AbstractLogger $logger
     * @return SOSO_Log
     */
    public function addLogger(SOSO_Logger_Abstract $logger/*,$loggerName=null*/) {
    	if (!$this->loggers) $this->loggers = new SOSO_ObjectStorage();
        //is_null($loggerName) ? array_unshift($this->loggers, $logger) : $this->loggers[$loggerName]=$logger;
        $this->loggers->contains($logger) || $this->loggers->attach($logger);
        return $this;
    }

    /**
     *
     * @return SOSO_Logger_Abstract
     */
    public function popLogger() {
        if (!$this->loggers || !$this->loggers->count()) {
            return false;
        }
        return $this->loggers->pop();
    }
    
    /**
     * 
     * Enter description here ...
     * @param $index
     * @return SOSO_Logger_Abstract
     */
    public function getLogger($index=0){
    	if($this->loggers)
    		return $this->loggers->getAt($index);
    	return false;
    }

    public function addProcessor(SOSO_Logger_IProcessor $processor){
    	if (!$this->processors) $this->processors = new SOSO_ObjectStorage();
        $this->processors->contains($processor) || $this->processors->attach($processor);
        return $this;
    }

    /**
     * 
     * @return SOSO_Logger_IProcessor
     */
    public function popProcessor(){
        if (!$this->processors || !$this->processors->count()) {
            return false;
        }
        return $this->processors->pop();
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $info
     * @param unknown_type $level
     * @param unknown_type $context
     * @return SOSO_Logger_Message
     */
    protected function makeMessage($info,$level,$context){
    	return new SOSO_Logger_Message($this->name, $info, $level, $context);
    }

    /**
     * 
     * 写一条log
     * @param unknown_type $level
     * @param unknown_type $info
     * @param array $context
     */
    public function addMessage($level, $info, array $context = array()) {
        if (!$this->loggers) {
            //$this->addLogger(new SOSO_Logger_Stream('php://stderr', self::DEBUG));
            $this->basicConfig(array('level'=>self::WARNING));
        }
        $message = $this->makeMessage($info,$level,$context);
        
    	foreach ($this->processors as $processor) {
           $message2 = $processor->process($message);
           $message = $message2 instanceof SOSO_Logger_Message ? $message2 : $message;
        }
        
        $ret = false;
        foreach ($this->loggers as $index=>$logger){
        	if ($logger->accept($message)) {
        		$ret = $logger->handle(clone($message));
        		if ($ret === true) break;
        	}
        }
        
        return $ret;
    }

    public static function getLevelName($level){
        return self::$levels[$level];
    }
    
    public static function addLevelName($level,$levelName){
    	self::$levels[$level] = $levelName;
    	self::$levels[$levelName] = $level;
    }

	public function alert($message, array $context = array()){
        return $this->addMessage(self::FATAL, $message, $context);
    }
    
    public function debug($message, array $context = array()){
        return $this->addMessage(self::DEBUG, $message, $context);
    }

    public function info($message, array $context = array()){
        return $this->addMessage(self::INFO, $message, $context);
    }

    public function notice($message, array $context = array()){
        return $this->addMessage(self::INFO, $message, $context);
    }

    public function warn($message, array $context = array()){
        return $this->addMessage(self::WARNING, $message, $context);
    }

    public function err($message, array $context = array()){
        return $this->addMessage(self::ERROR, $message, $context);
    }

    public function crit($message, array $context = array()){
        return $this->addMessage(self::CRITICAL, $message, $context);
    }

    public function fatal($message, array $context = array()){
        return $this->addMessage(self::FATAL, $message, $context);
    }

    public function emerg($message, array $context = array()){
        return $this->addMessage(self::FATAL, $message, $context);
    }
}
