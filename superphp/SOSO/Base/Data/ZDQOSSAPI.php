<?php
/**************************************************************************************
					  OSS API 客户端接口类（封装的xml协议）

							完成直达区更新与发布
						
						更新对象与方式
							1.数据区、关键词、扩展词全量与增量更新
							2.单独数据区全量更新
							3.单独扩展词增量和全量更新
							4.辅助词增量和全量更新

						添加发布任务
							1.全量、增量发布关键词
							2.全量、增量发布内容
							3.全量、增量发布辅助词

							@ evanhe
							@ version 1.3
							
*******************************************************************************************/
/*使用说明与修正历史记录

			使用流程
			1 addRecord或者addRecords,或者是addAssistkw
			2 setUpdate
			3 addDeploy
			4 action
			
			2009.9.24 修正
			1.【客户端】一些地方cid为空的判断
			2.【客户端】添加的比较完善的注释
			3.【客户端】添加返回xml解析函数
		
			2009.9.28 修正
			1.【服务端】【客户端】deploy增加发布目标配置
			2.【客户端】返回信息字段名称和结构调整，debug调试
			3.【服务端】实现了mcache类，增加服务端的cache管理

			2009.11.23 修正bug	
			1.【客户端】修改addRecords函数中对更新参数的判断
			2.【客户端】增加构造函数中licence参数
			3.【服务端】修改加入发布关键词线上和预发属性后引入的bug,修改涉及，解析类和main模块类，任务处理部分的内容
			4.【服务端】增加cid检查和licence检查,重写了验证类的方法
			5.【服务端】mcache类增加构造函数中缓存条数参数

*/


//服务器接口地址定义
define("OSS_PUBLISH_URL", "http://zdqdev.isoso.com/interface/OSSAPI/index.php");//oss 线上环境



class  SOSO_Base_Data_ZDQOSSAPI
{
	var $_catid;
	var $_postdata;		//关键词相关数据：内容，关键词：扩展词
	var $_assistdat;	//辅助词列表
	var $_actionset;	//任务列表

	var $_xmlparser='default';	//指定解析类，默认为default,保留为以后进行扩展
	var $_licence='8000';		//设置客户端licence，保留为以后外部接入
	var $_process='main';		//设置处理类main,保留为此接口可以开发其他内容
	
	
	function __construct($catid,$licence=false)
	{
		if(empty($catid)||!is_numeric($catid)) 
		{
			echo "必须要指定直达区类型号\n";
			exit;
		}
		$this->_catid=$catid;

		if(!empty($licence))
			$this->_licence=$licence;
	}
	
	
	
	
	/**************************************************************************
	主要方法：				更新直达区数据(单个关键词对应数据)
	
	$keyword 关键词

	$data	数据区一条记录字段=>数据值
	
	如何想指定kid,需要使用全量更新，如果是增量更新
	
	$extkw	扩展关键词，可以是数组

	$arg
	数据区更新：1xx	1全字段更新- 2非完整字段更新 3遇到已有记录跳过（以关键词为key）
	关键词更新：x1x	0保持已有状态 1强制有效 2该关键词不入库
	扩展词更新：xx1 0增量更新该关键词扩展词 1全量更新该关键词扩展词

	******************************************************************************/
	
	public function addRecord($keyword,$data=null,$extkw=null,$arg=100)
	{

		if(empty($this->_catid)) 
		{
			echo "必须要指定直达区类型号\n";
			return false;
		}
		if(empty($keyword)) 
		{
			echo "关键词不能为空\n";
			return false;
		}
		if(empty($data)&&empty($extkw))
		{
			echo "内容和扩展词不能同时为空\n";
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
	主要方法：				更新直达区数据(多个关键词对应数据)
	
	数据集的例子
	$record= array(
		'关键词1'=>array(
					'datset'=>array('field1'=>'内容1','field2'=>'内容2'),
					'ekwset'=>array('扩展1','扩展2'),
					),
		'关键词2'=>array(
					'datset'=>array('field1'=>'内容1','field2'=>'内容2'),
					'ekwset'=>array('扩展1','扩展2'),
					'arg'=>100,
					),			
				);
	$arg
	数据区更新：1xx	1全字段更新- 2非完整字段更新 3遇到已有记录跳过（以关键词为key）
	关键词更新：x1x	0保持已有状态 1强制有效 2该关键词不入库
	扩展词更新：xx1 0增量更新该关键词扩展词 1全量更新该关键词扩展词
	******************************************************************************/

	public function addRecords($record)
	{
		if(empty($this->_catid)) 
		{
			echo "必须要指定直达区类型号\n";
			return false;
		}
		if(empty($record)) 
		{
			echo "更新记录不能为空\n";
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
			echo "数据集为空\n";
			return false;
		}
		else return true;
	}


	/**************************************************************************
	主要方法：				添加辅助词
	$assistkw 辅助词，可以是一个数组
	$settabid bool 是否指定该辅助词指定命中直达区(关键词server可以通过辅助词来决定优先某个直达区)
	******************************************************************************/
	public function addAssistkw($assistkw,$settabid=false)
	{
		if(empty($this->_catid)) 
		{
			echo "必须要指定直达区类型号\n";
			return false;
		}
		if(empty($assistkw)) 
		{
			echo "辅助词不能为空\n";
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
		设置更新方式
		设置数据区和辅助词的更新方式
		
		关键词和扩展词更新和数据区更新方式一般是一致的，复杂更新方式需要设置$arg参数

		$uprecordtype  
		全量 updateall
		增量 update（默认）
		全量更新数据区 updateareaonly【不更新关键词】

		$upassisttype 辅助词更新类型
		全量 updateall
		增量 update（默认）
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
		添加发布任务
		
		发布对象
		'content','keyword','extendkw','assist'
		发布方式
		'reload','incremental'
		发布目标
		'online','preonline'
	*****************************************************************************/
	public function addDeploy($depobject,$deptype='reload',$deptarget='online')
	{
		if(empty($this->_catid)) 
		{
			echo "必须要指定直达区类型号\n";
			return false;
		}
		$_depobject=trim($depobject);
		$_deptype=trim($deptype);
		$_deptarget=trim($deptarget);
		if(empty($_depobject)||empty($_deptype)) 
		{
			echo "发布对象和发布方式不能为空\n";
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



	//检查参数设置
	private function checkParameter($arg)
	{
		if(!in_array($arg,array('100','110','120','200','210','220','101','111','121','201','211','221','301','311','321')))
		{
			echo "arg error\n";
			return false;
		}
		else return true;
	}



	//组装postxml
	private function makeRequest()
	{
		
		if(empty($this->_catid)) 
		{
			echo "必须要指定直达区类型号\n";
			return false;
		}
		if(empty($this->_postdata)&&empty($this->_actionset['deploy'])&&empty($this->_assistdat))
		{
			echo "no task to action，请至少指定一项任务，如更新数据区，更新扩展词\n
			更新辅助词，或者添加发布任务";
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
		
		//数据区，关键词和扩展词
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

		//更新辅助词
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
		
		
		//发布任务
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



	//解析返回结果
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
		//{echo "xml错误";return false;}
		
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
		操作启动函数
		
		可设置post超时
		debug设置 记录下发送的xml,调用脚本的当前目录
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
 * post 发布类
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
