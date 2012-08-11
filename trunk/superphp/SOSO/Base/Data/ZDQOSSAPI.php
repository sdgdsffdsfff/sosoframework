<?php
/**************************************************************************************
					  OSS API �ͻ��˽ӿ��ࣨ��װ��xmlЭ�飩

							���ֱ���������뷢��
						
						���¶����뷽ʽ
							1.���������ؼ��ʡ���չ��ȫ������������
							2.����������ȫ������
							3.������չ��������ȫ������
							4.������������ȫ������

						��ӷ�������
							1.ȫ�������������ؼ���
							2.ȫ����������������
							3.ȫ������������������

							@ evanhe
							@ version 1.3
							
*******************************************************************************************/
/*ʹ��˵����������ʷ��¼

			ʹ������
			1 addRecord����addRecords,������addAssistkw
			2 setUpdate
			3 addDeploy
			4 action
			
			2009.9.24 ����
			1.���ͻ��ˡ�һЩ�ط�cidΪ�յ��ж�
			2.���ͻ��ˡ���ӵıȽ����Ƶ�ע��
			3.���ͻ��ˡ���ӷ���xml��������
		
			2009.9.28 ����
			1.������ˡ����ͻ��ˡ�deploy���ӷ���Ŀ������
			2.���ͻ��ˡ�������Ϣ�ֶ����ƺͽṹ������debug����
			3.������ˡ�ʵ����mcache�࣬���ӷ���˵�cache����

			2009.11.23 ����bug	
			1.���ͻ��ˡ��޸�addRecords�����жԸ��²������ж�
			2.���ͻ��ˡ����ӹ��캯����licence����
			3.������ˡ��޸ļ��뷢���ؼ������Ϻ�Ԥ�����Ժ������bug,�޸��漰���������mainģ���࣬�������ֵ�����
			4.������ˡ�����cid����licence���,��д����֤��ķ���
			5.������ˡ�mcache�����ӹ��캯���л�����������

*/


//�������ӿڵ�ַ����
define("OSS_PUBLISH_URL", "http://zdqdev.isoso.com/interface/OSSAPI/index.php");//oss ���ϻ���



class  SOSO_Base_Data_ZDQOSSAPI
{
	var $_catid;
	var $_postdata;		//�ؼ���������ݣ����ݣ��ؼ��ʣ���չ��
	var $_assistdat;	//�������б�
	var $_actionset;	//�����б�

	var $_xmlparser='default';	//ָ�������࣬Ĭ��Ϊdefault,����Ϊ�Ժ������չ
	var $_licence='8000';		//���ÿͻ���licence������Ϊ�Ժ��ⲿ����
	var $_process='main';		//���ô�����main,����Ϊ�˽ӿڿ��Կ�����������
	
	
	function __construct($catid,$licence=false)
	{
		if(empty($catid)||!is_numeric($catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			exit;
		}
		$this->_catid=$catid;

		if(!empty($licence))
			$this->_licence=$licence;
	}
	
	
	
	
	/**************************************************************************
	��Ҫ������				����ֱ��������(�����ؼ��ʶ�Ӧ����)
	
	$keyword �ؼ���

	$data	������һ����¼�ֶ�=>����ֵ
	
	�����ָ��kid,��Ҫʹ��ȫ�����£��������������
	
	$extkw	��չ�ؼ��ʣ�����������

	$arg
	���������£�1xx	1ȫ�ֶθ���- 2�������ֶθ��� 3�������м�¼�������Թؼ���Ϊkey��
	�ؼ��ʸ��£�x1x	0��������״̬ 1ǿ����Ч 2�ùؼ��ʲ����
	��չ�ʸ��£�xx1 0�������¸ùؼ�����չ�� 1ȫ�����¸ùؼ�����չ��

	******************************************************************************/
	
	public function addRecord($keyword,$data=null,$extkw=null,$arg=100)
	{

		if(empty($this->_catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			return false;
		}
		if(empty($keyword)) 
		{
			echo "�ؼ��ʲ���Ϊ��\n";
			return false;
		}
		if(empty($data)&&empty($extkw))
		{
			echo "���ݺ���չ�ʲ���ͬʱΪ��\n";
			return false;
		}

		if($this->checkParameter($arg)===false) return false;

		if(!empty($data))
			$this->_postdata[$keyword]['datset']=$data;
		
		if(!empty($extkw))
		{
			if(is_array($extkw))
			$this->_postdata[$keyword]['ekwset']=$extkw;
			else $this->_postdata[$keyword]['ekwset'][]=$extkw;
		}
		if(!empty($arg))
			$this->_postdata[$keyword]['arg']=$arg;
		
		return true;
	}

	/**************************************************************************
	��Ҫ������				����ֱ��������(����ؼ��ʶ�Ӧ����)
	
	���ݼ�������
	$record= array(
		'�ؼ���1'=>array(
					'datset'=>array('field1'=>'����1','field2'=>'����2'),
					'ekwset'=>array('��չ1','��չ2'),
					),
		'�ؼ���2'=>array(
					'datset'=>array('field1'=>'����1','field2'=>'����2'),
					'ekwset'=>array('��չ1','��չ2'),
					'arg'=>100,
					),			
				);
	$arg
	���������£�1xx	1ȫ�ֶθ���- 2�������ֶθ��� 3�������м�¼�������Թؼ���Ϊkey��
	�ؼ��ʸ��£�x1x	0��������״̬ 1ǿ����Ч 2�ùؼ��ʲ����
	��չ�ʸ��£�xx1 0�������¸ùؼ�����չ�� 1ȫ�����¸ùؼ�����չ��
	******************************************************************************/

	public function addRecords($record)
	{
		if(empty($this->_catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			return false;
		}
		if(empty($record)) 
		{
			echo "���¼�¼����Ϊ��\n";
			return false;
		}
		foreach($record as $kw => $v)
		{
			if(!empty($kw))
			{
				if(empty($v['datset'])&&empty($v['ekwset']))  continue;
				
				if(!empty($v['datset']))
					$this->_postdata[$kw]['datset']=$v['datset'];
				if(!empty($v['ekwset']))
					$this->_postdata[$kw]['ekwset']=$v['ekwset'];
				$this->_postdata[$kw]['arg']=empty($v['arg'])?100:$v['arg'];
				if($this->checkParameter($this->_postdata[$kw]['arg'])===false) return false;//change 2009.11.23
			}
		}
		if(empty($this->_postdata)) 		
		{
			echo "���ݼ�Ϊ��\n";
			return false;
		}
		else return true;
	}


	/**************************************************************************
	��Ҫ������				��Ӹ�����
	$assistkw �����ʣ�������һ������
	$settabid bool �Ƿ�ָ���ø�����ָ������ֱ����(�ؼ���server����ͨ������������������ĳ��ֱ����)
	******************************************************************************/
	public function addAssistkw($assistkw,$settabid=false)
	{
		if(empty($this->_catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			return false;
		}
		if(empty($assistkw)) 
		{
			echo "�����ʲ���Ϊ��\n";
			return false;
		}

		if(is_array($assistkw))
		{
			if($settabid===false) 
			{
				foreach($assistkw as $v){
				$this->_assistdat['notabid'][]=$v;
				}
			}
			else
			{
				foreach($assistkw as $v){
					$this->_assistdat['tabid'][]=$v;
					}
			}
		}
		else
		{
			if($settabid===false) 
				$this->_assistdat['notabid'][]=$assistkw;
			else 
				$this->_assistdat['tabid'][]=$assistkw;

		}

		return true;
	}


	/****************************************************************************
		���ø��·�ʽ
		�����������͸����ʵĸ��·�ʽ
		
		�ؼ��ʺ���չ�ʸ��º����������·�ʽһ����һ�µģ����Ӹ��·�ʽ��Ҫ����$arg����

		$uprecordtype  
		ȫ�� updateall
		���� update��Ĭ�ϣ�
		ȫ������������ updateareaonly�������¹ؼ��ʡ�

		$upassisttype �����ʸ�������
		ȫ�� updateall
		���� update��Ĭ�ϣ�
	*****************************************************************************/
	public function setUpdate($uprecordtype='update',$upassisttype='update')
	{
		if(in_array($uprecordtype,array('update','updateall','updateareaonly')))
			$this->_actionset['dataarea']=$uprecordtype;
		else
			return false;

			if(in_array($upassisttype,array('update','updateall')))
			$this->_actionset['assist']=$upassisttype;
		else
			return false;

		return true;
	}

	/****************************************************************************
		��ӷ�������
		
		��������
		'content','keyword','extendkw','assist'
		������ʽ
		'reload','incremental'
		����Ŀ��
		'online','preonline'
	*****************************************************************************/
	public function addDeploy($depobject,$deptype='reload',$deptarget='online')
	{
		if(empty($this->_catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			return false;
		}
		$_depobject=trim($depobject);
		$_deptype=trim($deptype);
		$_deptarget=trim($deptarget);
		if(empty($_depobject)||empty($_deptype)) 
		{
			echo "��������ͷ�����ʽ����Ϊ��\n";
			return false;
		}
		$_depobject_list = array('content','keyword','extendkw','assist');
		if(!in_array($_depobject,$_depobject_list)) return false;

		$_deptype_list = array('reload','incremental');
		if(!in_array($_deptype,$_deptype_list)) return false;

		$_deptarget_list = array('online','preonline');
		if(!in_array($_deptarget,$_deptarget_list)) return false;

		$this->_actionset['deploy'][$_deptarget][$depobject]=$deptype;
		return true;
	}



	//����������
	private function checkParameter($arg)
	{
		if(!in_array($arg,array('100','110','120','200','210','220','101','111','121','201','211','221','301','311','321')))
		{
			echo "arg error\n";
			return false;
		}
		else return true;
	}



	//��װpostxml
	private function makeRequest()
	{
		
		if(empty($this->_catid)) 
		{
			echo "����Ҫָ��ֱ�������ͺ�\n";
			return false;
		}
		if(empty($this->_postdata)&&empty($this->_actionset['deploy'])&&empty($this->_assistdat))
		{
			echo "no task to action��������ָ��һ�������������������������չ��\n
			���¸����ʣ�������ӷ�������";
			return false;
		}
		
		if(!isset($this->_actionset['dataarea'])) $this->_actionset['dataarea']='update';
		if(!isset($this->_actionset['assist'])) $this->_actionset['assist']='update';

		$xmls = array();
		$xmls[] = "<?xml version='1.0' encoding='gbk'?>";
		$xmls[] = "<response>";
		$xmls[] = "<info>";
		$xmls[] = "<catid><![CDATA[".$this->_catid."]]></catid>";
		$xmls[] = "<licence><![CDATA[".$this->_licence."]]></licence>";
		$xmls[] = "<cobject><![CDATA[".$this->_process."]]></cobject>";
		$xmls[] = "</info>";
		
		//���������ؼ��ʺ���չ��
		if(!empty($this->_postdata))
		{
		$dataarea_op = $this->_actionset['dataarea'];
		if(empty($dataarea_op)) return false;
		
		$xmls[] = "<oplist type=\"".$dataarea_op."\">";
		foreach($this->_postdata as $kw => $item){
			$xmls[] = "<item>";
			$xmls[] = "<keyword><![CDATA[$kw]]></keyword>";
			
			if(!empty($item['datset']))
			{
				$xmls[] = "<dfield>";
				foreach($item['datset'] as $key => $value){
					$xmls[] = "<$key><![CDATA[$value]]></$key>";
				}
				$xmls[] = "</dfield>";
			}
			if(!empty($item['ekwset']))
			{
				$xmls[] = "<extendkw>";
				foreach($item['ekwset'] as $value){
					$xmls[] = "<pi><![CDATA[$value]]></pi>";
				}
				$xmls[] = "</extendkw>";
			}
			$arg= empty($item['arg'])?0:$item['arg'];
			$xmls[] = "<arg><![CDATA[".$arg."]]></arg>";
			$xmls[] = "</item>";
		}
		$xmls[] = "</oplist>";
		}

		//���¸�����
		if(!empty($this->_assistdat))
		{
			$assist_op = $this->_actionset['assist'];
			$xmls[] ="<assistkw type=\"$assist_op\">";
			if(!empty($this->_assistdat['tabid']))
			{
				$cid = $this->_catid;
				$xmls[] ="<tabid id=\"$cid\">";
				foreach($this->_assistdat['tabid'] as $v)
				{
				$xmls[] ="<item><![CDATA[$v]]></item>";
				}
				$xmls[] ="</tabid>";
			}

			if(!empty($this->_assistdat['notabid']))
			{
				$xmls[] ="<notabid>";
				foreach($this->_assistdat['notabid'] as $v)
				{
					$xmls[] ="<item><![CDATA[$v]]></item>";
				}
				$xmls[] ="</notabid>";
			}
			$xmls[] ="</assistkw>";
		}
		
		
		//��������
		if(!empty($this->_actionset['deploy']))
		{
			$xmls[] = "<deploylist>";
			foreach($this->_actionset['deploy'] as $targ => $object)
			{
				$xmls[] = "<target type=\"$targ\">";
				foreach($object as $ob => $tp)
				{
					$xmls[] = "<item type=\"$tp\"><![CDATA[$ob]]></item>";
				}
				$xmls[] = "</target>";
			}
			$xmls[] = "</deploylist>";
		}
		$xmls[] = "</response>";
		$xml = implode("\n", $xmls);
		
		if($this->debug)
			file_put_contents('postcontent.xml',$xml);
		
		return $xml;
	}



	//�������ؽ��
	private function parserResult($xmlstr)
	{
		if(!empty($xmlstr))
		$xml = @simplexml_load_string($xmlstr);
		else 	
		{
		echo "return xml content is empty\n";
		return false;
		}
		//var_dump($xml);
		if($xml===false) 
		{
		echo "return xml is not right\n";
		return false;
		}
		else
		{
		$result=array();
		//if(!isset($xml->resultlist->children())) 
		//{echo "xml����";return false;}
		
		foreach ($xml->resultlist->children() as $child)
		{
			$item = $child->getName();
			
			
			if('fatal_error'==$item)
			{
				foreach ($child as $pchild)
				{	
					$item = $pchild->getName();
					$result['fatal_error'][$item]=(string)$pchild->result;
				}
			}

			if('comm_error'==$item)
			{
				foreach ($child as $pchild)
				{	
					$item = $pchild->getName();
					$result['comm_error'][$item]=(string)$pchild->result;
				}
			}
			if('update_task'==$item)
			{
				foreach ($child as $pchild)
				{	
					$item = $pchild->getName();
					$result['update_task'][$item]=(string)$pchild->result;
				}
			}			
			if('deploy_task'==$item)
			{	
				foreach ($child as $pchild)
				{	
					$item = $pchild->getName();
					$result['deploy_task'][$item]=(string)$pchild->result;
				}
			}
			
			if('stats_task'==$item)
			{	
				foreach ($child as $pchild)
				{	
					$pitem = $pchild->getName();
					foreach ($pchild as $ppchild)
					{
					$ppitem = $ppchild->getName();
					$result['stats_task'][$pitem][$ppitem]=(string)$ppchild->num;
					}
				}
			}
			
			if('usetime'==$item) $result[$item]=(string)$child;
			if('datetime'==$item) $result[$item]=(string)$child;
		}
		if($this->debug) var_dump($result);
		if(empty($result)) return false;
		else return $result;
		}
	}



	/************************************************
		������������
		
		������post��ʱ
		debug���� ��¼�·��͵�xml,���ýű��ĵ�ǰĿ¼
	*************************************************/
	public function action($outtime=180,$debug=false)
	{
		$this->debug = $debug;
		$xml = $this->makeRequest();
		if($xml===false) return false;
		$fetcher = new Fetcher();
		$fetcher->setOpt(CURLOPT_TIMEOUT,$outtime);
		$dataAry = array('Parser_'.$this->_xmlparser => $xml);
		$ret = $fetcher->post(OSS_PUBLISH_URL, $dataAry);
		
		if($debug) file_put_contents('respact.xml',$ret);
		if(empty($ret)) return false;
		else
		 return $this->parserResult($ret);
	}
	

}







/**
 * post ������
 * @version 1.0
 * @created 2009-9-24 17:43:07
 */
class Fetcher
{
	private $ch;
	private $headers = array();
	private $opts = array();

	function __construct()
	{
		// default headers
		$this->headers['Pragma'] = 'no-cache'; 
		$this->headers['Cache-Control'] = 'no-cache'; 
		$this->headers['Accept'] = '*/*'; 
		$this->headers['Accept-Language'] = 'zh-cn'; 
		$this->headers['Connection'] = 'Keep-Alive';
		$this->headers['User-Agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MAXTHON 2.0)';
		// default opts
		$this->opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$this->opts[CURLOPT_NOPROGRESS] = 1;
		$this->opts[CURLOPT_FOLLOWLOCATION] = 1;
		$this->opts[CURLOPT_MAXREDIRS] = 15;
		$this->opts[CURLOPT_RETURNTRANSFER] = 1;
		$this->opts[CURLOPT_CONNECTTIMEOUT] = 15;
		$this->opts[CURLOPT_TIMEOUT] = 30;
		$this->opts[CURLOPT_VERBOSE] = 0;
		// init
		$this->ch = curl_init();
	}
	
	function __destruct()
	{
		if($this->ch)
			curl_close($this->ch);
	}
	
	public function setHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}

	public function setOpt($key, $value)
	{
		$this->opts[$key] = $value;
	}
	
	public function get($url)
	{
		$this->opts[CURLOPT_HTTPGET] = 1;
		$this->opts[CURLOPT_POST] = 0;
		return $this->doRequest($url);
	}
	
	public function post($url, $dataAry)
	{
		$postData = '';
		foreach($dataAry as $key => $value){
			if(!empty($postData)) $postData .= "&";
			$postData .= urlencode($key) . "=" . urlencode($value);
		}
		$this->opts[CURLOPT_POST] = 1;
		$this->opts[CURLOPT_HTTPGET] = 0;
		$this->opts[CURLOPT_POSTFIELDS] = $postData;
		return $this->doRequest($url);
	}
	
	public function getInfo($infoId)
	{
		return curl_getinfo($this->ch, $infoId);
	}
	
	public function getCode()
	{
		return intval($this->getInfo(CURLINFO_HTTP_CODE));
	}
	
	public function getError()
	{
		return curl_error($this->ch);
	}
	
	private function format_url($url)
	{
		$url = preg_replace('/#.*$/', '', $url);
		$url = str_replace('\\', '', $url);
		if(strpos($url, "http://") === false) $url = "http://$url";
		return $url;
	}
	
	private function doRequest($url)
	{
		$url = $this->format_url($url);
		$urls = parse_url($url);
		$this->headers['host'] = $urls['host'];
		$this->opts[CURLOPT_URL] = $url;
		// header
		$headerAry = array();
		foreach($this->headers as $key => $value){
			if(empty($value)) continue;
			$headerAry[] = "$key: $value";
		}
		$this->opts[CURLOPT_HTTPHEADER] = $headerAry;
		// exec
		foreach($this->opts as $optKey => $optValue){
			if(empty($optValue)) continue;
			curl_setopt($this->ch, $optKey, $optValue);
		}
		return curl_exec($this->ch);
	}
}
