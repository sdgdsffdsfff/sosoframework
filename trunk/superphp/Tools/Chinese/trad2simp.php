<?php
/**
 * www.soso.com��ҳ�������gb2312,���һЩgbk����ķ����ֵ�չ�ֻ�������룬Ϊ�˿˷�ʵ���л�������������⣬
 * ʵ����������������ֵ�������ת������
 *
 */
class Tools_Chinese_Trad2simp{
	private static $dict=array();
	private static $mapfile;//gbk�а����ķ����ֵ������ֵĶ��ձ�
	const DEFAULT_IENCODE='gbk';//Ĭ�����������gbk
	const DEFAULT_OENCODE='gb2312';//Ĭ�����������gb2312	
	private $option=array('iencode'=>self::DEFAULT_IENCODE,'oencode'=>self::DEFAULT_OENCODE);//Ĭ��ѡ��
	/**
	 * ���캯������ʼ�������������ѡ��
	 *
	 * @param array $option
	 * @return trad2simp
	 */
	public function Tools_Chinese_Trad2simp($option=array()){
		//��ʼ����ת���ӳ���ļ���
		self::$mapfile=dirname(__FILE__).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'tradi-simp.table';
		$this->option=array_merge($this->option,$option);
	}
	/**
	 * ������ķ�����ת��Ϊ��Ӧ�ļ�����
	 *
	 * @param string $input
	 * @return string ��ת�� ת���������
	 */
	public function translate($input){
		self::loadDict();
		if($this->option['iencode']!=self::DEFAULT_IENCODE){
			$input=mb_convert_encoding($input,'gbk',$this->option['iencode']);//������ת��Ϊgbk����
		}
		$len=strlen($input);
		$output='';
		for($i=0;$i<$len;$i++){
			if(ord($input[$i])>127){//�������֣���ȡ�����ַ�
				$ch_character=$input[$i].$input[$i+1];
				if(isset(self::$dict[$ch_character])){//�Ƿ����֣�ת��
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
			$output=mb_convert_encoding($output,$this->option['oencode'],'gb2312');//������ת��Ϊgbk����
		}		
		return $output;
	}
	/**
	 * ���뷱ת��Ķ��ձ�
	 *
	 * @return array
	 */
	private static function loadDict(){
		if (empty(self::$dict)){
			$lines=file(self::$mapfile);//������ձ���һ��utf-8������ļ�
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