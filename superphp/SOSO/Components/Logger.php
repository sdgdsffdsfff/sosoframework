<?php
class Logger
{
	public static function getInstance()
	{
		if(!isset(self::$_instance))
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function startTime($item)
	{
		$this->_segmentTime[$item]['start'] = gettimeofday(true);
	}
	
	public function endTime($item)
	{
		if(!isset($this->_segmentTime[$item]['start']))
		{
			$this->_segmentTime[$item]['start'] = gettimeofday(true);
		}
		$this->_segmentTime[$item]['end'] = gettimeofday(true);
	}
	
	public function makePair($key, $value)
	{
		$this->_pairs[$key] = $value;
	}
	
	public function getPairValue($key)
	{
		if(isset($this->_pairs[$key]))
		{
			return $this->_pairs[$key];
		}
		return null;
	}
	
	public function doLog()
	{
		$record = 'abstime='.$this->_abstime;
		if(isset($this->_pairs))
		{
			foreach ($this->_pairs as $key => $value)
			{
				$record .= '||'.$key.'='.$value;
			}
		}
		if(isset($this->_segmentTime))
		{
			foreach ($this->_segmentTime as $key => $value)
			{
				if(!isset($value['end']))
					$value['end'] = gettimeofday(true);
				$record .= '||'.$key.'='.($value['end'] - $value['start']);
			}
		}
		file_put_contents(self::$_logPath.'/'.date("YmdH")."00_".$_SERVER['SERVER_ADDR'].".log", $record."\n", FILE_APPEND | LOCK_EX);
	}

	protected function __construct()
	{
		$this->_abstime = date("Y-m-d H:i:s");
	}
	
	function __destruct()
	{
		if($this->_pairs['kwsvr_ret'] != "ok" && $this->_pairs['kwsvr_ret'] != "not found")
		{
			$this->doLog();
		}
		else
		{
			$script_tv = floatval($this->_segmentTime['script_tv']['end'] - $this->_segmentTime['script_tv']['start']);
			if($script_tv > 0.05)
			{
				$this->doLog();
			}		
		}
	}
	
	protected $_segmentTime;
	
	protected $_pairs;
	
	protected $_abstime;
	
	protected static $_instance;
	
	protected static $_logPath = '/data/bingo/log';
}
?>
