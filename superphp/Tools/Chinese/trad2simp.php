<?php
/**
 * www.soso.com的页面编码是gb2312,因而一些gbk编码的繁体字的展现会出现乱码，为了克服实际中会遇到的这个问题，
 * 实现了下面这个繁体字到简体字转换的类
 *
 */
class Tools_Chinese_Trad2simp{
	private static $dict=array();
	private static $mapfile;//gbk中包含的繁体字到简体字的对照表
	const DEFAULT_IENCODE='gbk';//默认输入编码是gbk
	const DEFAULT_OENCODE='gb2312';//默认输出编码是gb2312	
	private $option=array('iencode'=>self::DEFAULT_IENCODE,'oencode'=>self::DEFAULT_OENCODE);//默认选项
	/**
	 * 构造函数，初始化输入输出编码选项
	 *
	 * @param array $option
	 * @return trad2simp
	 */
	public function Tools_Chinese_Trad2simp($option=array()){
		//初始化繁转简的映射文件名
		self::$mapfile=dirname(__FILE__).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'tradi-simp.table';
		$this->option=array_merge($this->option,$option);
	}
	/**
	 * 对输入的繁体字转换为对应的简体字
	 *
	 * @param string $input
	 * @return string 繁转简 转换后的内容
	 */
	public function translate($input){
		self::loadDict();
		if($this->option['iencode']!=self::DEFAULT_IENCODE){
			$input=mb_convert_encoding($input,'gbk',$this->option['iencode']);//把输入转换为gbk编码
		}
		$len=strlen($input);
		$output='';
		for($i=0;$i<$len;$i++){
			if(ord($input[$i])>127){//遇到汉字，读取两个字符
				$ch_character=$input[$i].$input[$i+1];
				if(isset(self::$dict[$ch_character])){//是繁体字，转换
					$output.=self::$dict[$ch_character];
				}
				else{
					$output.=$ch_character;
				}
				$i++;
					
			}
			else{
				$output.=$input[$i];
			}
		}
		if($this->option['oencode']!=self::DEFAULT_OENCODE){
			$output=mb_convert_encoding($output,$this->option['oencode'],'gb2312');//把输入转换为gbk编码
		}		
		return $output;
	}
	/**
	 * 载入繁转简的对照表
	 *
	 * @return array
	 */
	private static function loadDict(){
		if (empty(self::$dict)){
			$lines=file(self::$mapfile);//繁简对照表是一个utf-8编码的文件
			foreach($lines as $line){
				$line=trim($line);
				list($trad,$simp)=explode("\t",$line);
				self::$dict[$trad]=$simp;
			}
		}
		return self::$dict;		
	}
}
?>