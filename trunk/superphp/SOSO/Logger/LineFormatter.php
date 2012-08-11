<?php
/**
 * 将log格式化为一行
 */
class SOSO_Logger_LineFormatter implements SOSO_Logger_IFormatter{
    const FORMAT = "[%datetime%]\t[%category%.%level_name%]:\t%message%\t%context%\t%extra%\n";
    const DATE = "Y-m-d H:i:s";

    protected $format;
    protected $dateFormat;

    /**
     * @param string $format 日志格式
     * @param string $dateFormat 日期格式
     */
    public function __construct($format = null, $dateFormat = null){
        $this->setFormat($format);
        $this->setDateFormat($dateFormat);
    }
    
    public function setFormat($format){
    	$this->format = $format ?: self::FORMAT;
    	return $this;
    }
    
    public function setDateFormat($dateFormat){
    	$this->dateFormat = $dateFormat ?: self::DATE;
    	return $this;
    }

    public function format(SOSO_Logger_Message $message){
        $vars = $message->getArrayCopy();
		$vars['datetime'] = $message->getDatetime()->format($this->dateFormat);
        $output = $this->format;
        $pattern = "#(%(.+)%)#U";
		if (!preg_match_all($pattern,$this->format,$match)){
			return "";
		}
		
		$matchedKeys = $match[2];
		$placeholder = $match[1];
		
		foreach ($vars['extra'] as $var=>$val){
			$key = 'extra.'.$var;
			$vars[$key] = $val;
		}
		
		foreach ($vars['context'] as $key=>$val){
			if (!array_key_exists($key, $vars) && false !== array_search($key, $matchedKeys)){
				$vars[$key] = $val;
				unset($vars['context'][$key]);
			}
		}
		
		$noValue = '-';
		$values = array();
		foreach ($matchedKeys as $key){
			if (array_key_exists($key, $vars)){
				$values[$key] = $this->convertToString($vars[$key]);
			}else{
				$values[$key] = $noValue;
			}
		}

        return str_replace($placeholder, $values, $this->format);
    }

    protected function convertToString($data){
        if (null === $data || is_scalar($data)) {
            return (string) $data;
        }

        return $this->normalize($data);
    }

    protected function normalize($data){
        if (null === $data || is_scalar($data)) {
            return $data;
        }

        if (is_array($data) || $data instanceof Traversable) {
        	if (count($data) == 0) return "-";
        	
            $normalized = array();

            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value);
            }

            return stripslashes(json_encode($normalized));
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return sprintf("[object] (%s: %s)", get_class($data), json_encode($data));
    }
}
