<?php
/**
 * @author moonzhang
 * @version	0.0.0.1 (2012-03-08)
 * 
 * Happy women's Day..
 */

class SOSO_ErrorHandler {
    private $levels = array(
        E_WARNING           => 'Warning',
        E_NOTICE            => 'Notice',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
    );

    private $level;
    protected $logger;

    /**
     * 注册错误处理函数
     *
     * @param integer $level 报警级别，null为使用error_reporting值，０为关闭报警处理,交由PHP自行处理
     * @return ErrorHandler
     */
    static public function register($level = null,$logger=null) {
        $handler = new self();
        $handler->setLevel($level)
				->setLogger($logger);
        set_error_handler(array($handler, 'handle'));
        return $handler;
    }

    public function setLogger($logger){
    	$this->logger = $logger;
    	return $this;
    }
    
    public function setLevel($level){
        $this->level = null === $level ? error_reporting() : $level;
        return $this;
    }

    public function handle($level, $message, $file, $line, $context){
        if (0 === $this->level) {
            return false;
        }
		
        if ($this->level & $level && error_reporting() & $level) {//&&
            $msg = sprintf('%s: %s in %s line %d', isset($this->levels[$level]) ? $this->levels[$level] : $level, $message, $file, $line);
        	if($this->logger) $this->logger->log($msg,'Error');
        }

        return true;
    }
}
