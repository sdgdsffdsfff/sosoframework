<?php
/**
 * @author moonzhang
 * @verion 1.0 2012-07-05
 * 
 * 用来向一组文件写日志：使用指定文件大小来控制
 */
require_once "Rotating.php";

class SOSO_Logger_FileRotating extends SOSO_Logger_Rotating{
	protected $maxFilesize = 0;
	protected $fileCount = 0;
	
	public function __construct($file,$maxBytes=0, $fileCount=0,$level = SOSO_Log::DEBUG, $bubble = true,$buffering=false){
		parent::__construct($file,$level,$bubble,$buffering);
		$this->setFilecount($fileCount);
		$this->setFilesize($maxBytes);
		if (null === $this->stream) {
            if (!$this->url) {
                throw new LogicException('Missing stream url');
            }
            $this->stream = @fopen($this->url, 'a');
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new UnexpectedValueException(sprintf('The stream or file "%s" could not be opened; it may be invalid or not writable.', $this->url));
            }
        }
	}
	
	public function setFilecount($num){
		$this->fileCount = (int)$num;
		return $this;
	}
	
	public function setFilesize($bytes){
		$this->maxFilesize = (int)$bytes;
		return $this;
	}
	
	public function canRotate(SOSO_Logger_Message $message){
		if ($this->maxFilesize > 0){
			$msgString = $message->getFormatted();
			fseek($this->stream, 0,2);
			return ftell($this->stream) + strlen($msgString) >= $this->maxFilesize;
		}
		return false;
	}	
	
	public function doRotate(){
		if ($this->stream) $this->close();
		$fmt = "%s.%d";
		if ($this->fileCount > 0){
			foreach (range($this->fileCount-1,0,-1) as $num){
				$fromFile = sprintf($fmt,$this->url,$num);
				$toFile = sprintf($fmt,$this->url,$num + 1);
				if (file_exists($fromFile)){
					if (file_exists($toFile) && is_writable($toFile)){
						unlink($toFile);
					}
					rename($fromFile, $toFile);
				}
			}
			$toFile = $this->url . '.1';
			if (file_exists($toFile)) unlink($toFile);
			rename($this->url,$toFile);
		}else{
			$fromFile = $this->url;
			$toFile = sprintf($fmt,$this->url,date("YmdHis"));
			rename($fromFile, $toFile);		
		}
		//lock + w-mode?
		if (file_exists($this->url)) unlink($this->url);
	}
} 