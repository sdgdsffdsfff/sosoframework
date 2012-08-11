<?php
class SOSO_Bingo_Access {
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	protected static $keywordServiceHelper = 'SOSO_Bingo_Access_Helper_Kwsvr';

	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	protected static $contentServiceHelper = '';

	/**
	 * �ؼ���Ԥ����
	 * ����ȥ��ȫ/��ǿո�,������ȫ�Ƿ���ת���
	 * 
	 * @param string $keyword ����ؼ���
	 * @return string �����Ĺؼ���
	 */
	protected static function keywordPreprocess($keyword){
		$valKeyword = '';
		for($i = 0; $i < strlen($keyword);){
			if(ord($keyword[$i]) > 127) // ����ȫ�ǿո�ȫ�Ƿ���ת���
			{
				$ch = substr($keyword, $i, 2);
				$valKeyword .= ($ch == "\xa1\xa1"/*'��'*/) ? '' : preg_replace('/\xa3([\xc1-\xda\xe1-\xfa\xb0-\xb9\xa1\xa5\xa8\xa9\xab\xae\xaf\xad])/e', 'chr(ord(\1)-0x80)', $ch);
				$i += 2;
				continue;
			}
			else if($keyword[$i] == ' ')	// ���˿ո�
			{
				$i++;
				continue;
			}
			$valKeyword .= $keyword[$i];
			$i++;
		}
		return mb_strtoupper( $valKeyword, "GBK" );
	}

	/**
	 * �ؼ��ʷ������ӿ�
	 *
	 * @param string $keyword �ؼ���
	 * @return bool false ʧ��
	 * @return array ֱ������Ϣ
	 */
	public static function keywordQuery($keyword)
	{
		$keyword = self::keywordPreprocess( $keyword );
		if(class_exists(self::$keywordServiceHelper))
		{
			$helper = new self::$keywordServiceHelper;
			return $helper->process($keyword);
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param array $kwCate
	 */
	public static function contentQuery($kwCate){
		try {
			$config = BeanFinder::get('config');
			$helperName = "Bingo_Access_Helper_".ucfirst($config['base']['moduleName']);
		} catch (Exception $e) { // for transition
			if(!isset($GLOBALS[BINGO_ACCESS_CONFIG_VAR][__CLASS__]))
			return false;
			$conf = $GLOBALS[BINGO_ACCESS_CONFIG_VAR][__CLASS__];
			if(!isset($conf['helper'][$kwCate['type']]))
			return false;
			$helperName = $conf['helper'][$kwCate['type']];
		} // end

		if(!class_exists($helperName))
		return false;
		$helper = new $helperName;

		return $helper->process($kwCate['term']);
	}
}