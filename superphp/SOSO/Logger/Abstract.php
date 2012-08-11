<?php
abstract class SOSO_Logger_Abstract {
	protected $level = SOSO_Log::DEBUG;
    protected $bubble = false;

    /**
     * @var SOSO_Logger_IFormatter
     */
    protected $formatter;
    protected $processors = array();
    protected $buffering = false;
    protected $buffer = array();
    protected $buffersize = 100;

    /**
     * @param integer $level 最小值，低于此值的log信息不记录
     * @param Boolean $bubble 设置是否冒泡
     * @param Boolean $buffering 设置是否buffer输出
     */
    public function __construct($level = SOSO_Log::DEBUG, $bubble = true,$buffering=false) {
        $this->level = $level;
        $this->bubble = !!$bubble;
        $this->buffering = !!$buffering;
    }
    
    public function accept(SOSO_Logger_Message $message){
        return $message->getLevel() >= $this->level;
    }

    /**
     * Closes the logger.
     *
     */
    public function close(){
    }

    protected function flush(){
    	foreach ($this->buffer as $record){
    		$this->log($record);
    	}
    	
    	$this->buffer = array();
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
        if (!$this->processors->count()) {
            return false;
        }
        return $this->processors->pop();
    }

    public function setFormatter(SOSO_Logger_IFormatter $formatter){
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * 
     * @return SOSO_Logger_IFormatter
     */
    public function getFormatter(){
        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }

        return $this->formatter;
    }
    
    public function setFormat($format){
    	$this->getFormatter()->setFormat($format);
    	return $this;
    }
    
    public function setBuffering($on=true){
    	$this->buffering = !!$on;
    	return $this;
    }
    
    public function getBuffering(){
    	return $this->buffering;
    }
    
    public function setBufferSize($size){
    	$this->buffersize = (int)$size;
    	return $this;
    }
    
    public function getBufferSize(){
    	return $this->buffersize;
    }

    /**
     * 设置可以处理的最小level值
     *
     * @param integer $level
     */
    public function setLevel($level){
        $this->level = $level;
        return $this;
    }

    public function getLevel(){
        return $this->level;
    }

    /**
     * 设置冒泡行为
     *
     * @param Boolean $bubble 
     *                        
     */
    public function setBubble($bubble){
        $this->bubble = $bubble;
        return $this;
    }

    public function getBubble(){
        return $this->bubble;
    }

    public function __destruct(){
        $this->flush();
        $this->close();
    }

    /**
     * Gets the default formatter.
     *
     * @return SOSO_Logger_IFormatter
     */
    protected function getDefaultFormatter(){
        return new SOSO_Logger_LineFormatter();
    }
    
    public function handle(SOSO_Logger_Message $message){
        if ($message->getLevel() < $this->level) {
            return false;
        }

        $this->processMessage($message);

        $message->setFormatted($this->getFormatter()->format($message));
        
        if ($this->buffering){
        	array_push($this->buffer, $message);
        	$buffer = array_splice($this->buffer, $this->buffersize);
        	if ($buffer) {
        		$this->flush();
        		$this->buffer = $buffer;	
        	}
        	return false === $this->bubble;
        }

        $this->log($message);

        return false === $this->bubble;
    }

    /**
     * 将log信息写入logger
     *
     * @param array $record
     * @return void
     */
    abstract protected function log(SOSO_Logger_Message $message);

    /**
     * 处理日志信息
     *
     * @param SOSO_Logger_Message $message
     * @return array
     */
    protected function processMessage(SOSO_Logger_Message $message){
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $message = $processor->process($message);
            }
        }

        return $message;
    }
}

