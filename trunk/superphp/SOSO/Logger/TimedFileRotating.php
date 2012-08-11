<?php
/**
 * @author moonzhang
 * @verion 1.0 2012-07-05
 * 
 * 用来向一组文件写日志：以一定的时间周期
 */
require_once "Rotating.php";

class SOSO_Logger_TimedFileRotating extends SOSO_Logger_Rotating{
	protected $when;
	//protected $fileCount = 0;
	protected $rotateTime;
	protected $interval;
	protected $format;
	protected $pattern;
	protected $dayOfWeek = 0;
	
	public function __construct($filename, $when='h', $interval=1 ,$level = SOSO_Log::DEBUG, $bubble = true,$buffering=false){
		parent::__construct($filename,$level,$bubble,$buffering);
		//$this->setFilecount($fileCount);
        $this->when = strtoupper($when);
        
        $config = array(
        	/*秒*/
        	'S'=>array(1,"%Y-%m-%d_%H-%M-%S","#^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$#"),
        	/*分*/
        	'M'=>array(60,'%Y-%m-%d_%H_%M','#^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}$#'),
        	/*时*/
        	'H'=>array(60*60,'%Y-%m-%d_%H','#^\d{4}-\d{2}-\d{2}_\d{2}$#'),
        	/*天*/
        	'D'=>array(60*60*24,'%Y-%m-%d','#^\d{4}-\d{2}-\d{2}$#'),
        	/* 周 W{0-6},0表示第一天　待实现 */
        	'W'=>array(60*60*24*7,'%Y-%m-%d','#^\d{4}-\d{2}-\d{2}$#')
        );
        
        $when = substr($this->when,0,1);
        if (!isset($config[$when])){
        	throw new Exception("What're you doing??",250);
        }
        
        $data = $config[$when];
       // if ($when == 'W') $this->dayOfWeek = int(substr($this->when,1));
        $this->interval = $data[0] * $interval;
        $this->format = $data[1];
        $this->pattern = $data[2];
        
        if(file_exists($this->url)){
        	$time = filemtime($this->url);
        }else{
        	$time = time();
        }
        $this->rotateTime = $this->computeRotateTime($time);
	}
	
	/**
	 * 
	 * 根据指定时间计算时间
	 * @param unknown_type $currentTime
	 */
	public function computeRotateTime($currentTime){
		$result = $currentTime + $this->interval;
		return $result ;
	}
	
	/*public function setFilecount($num){
		$this->fileCount = (int)$num;
		return $this;
	}*/
	
	public function canRotate(SOSO_Logger_Message $message){
		$time = time();
        return $time >= $this->rotateTime;
	}	
	
	public function doRotate(){
		if ($this->stream) $this->close();
		$time = $this->rotateTime - $this->interval;
		$toFile = $this->url . '.' . strftime($this->format,$time);
		if (file_exists($toFile)) unlink($toFile);
		file_exists($this->url) && rename($this->url, $toFile);
		
		/*if ($this->fileCount > 0){
			//todo 删除文件逻辑
		}*/
		
		$currentTime = time();
		$newRotateTime = $this->computeRotateTime($currentTime);
		while($newRotateTime <= $currentTime){
			$newRotateTime += $this->interval;
		}
		$this->rotateTime = $newRotateTime;
	}
} 