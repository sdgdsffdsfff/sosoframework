<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Util
 * @package    SOSO_Util
 * @description ������::��ҳ����
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @version 1.0 alpha 2006-03-02 zyfunny@gmail.com
 * 
 * @todo ��������Ӧ
 * @todo �ع�?
 */
class SOSO_Util_Pagination{
/**
	 * ��ҳ��
	 * @var integer
	 */
	public $mTotal;        
	/**
	 * ��ǰҳ��
	 * @var integer
	 */
	public $mCurrentPage;  
	/**
	 * ǰһҳ
	 * @var integer
	 */
	public $mPrePage ;    
	/**
	 * ��һҳ
	 * @var integer
	 */
	public $mNextPage;
	/**
	 * ��ҳ��ʽ
	 * @var array
	 */
	public $mStyle = array('none','normal','normal'=>'normal','none'=>'none');
	/**
	 * �����ʾ���ָ��� 
	 * @var integer
	 */
	public $mLength = 9;
	/**
	 * �Ƿ���ʾ [��ǰҳ],[���ҳ] ����,trueΪ��ʾ
	 * @var boolean
	 */
	public $showFirst = true;

	/**
	 * ��������
	 *
	 * @var string
	 */
	public $mParam;
	/**
	 * ��¼����
	 * @var integar
	 */
	public $mTotalResult;
	/**
	 * Ĭ������
	 */
	public $mLink = "<a href='%s'>%s</a>&nbsp;";
	/**
	 * ���ɵķ�ҳ����
	 *
	 * @var unknown_type
	 */
	public $mPage;
	public $mPagesize ;
	/**
	 * ���캯������ʼ������
	 *
	 * @param integer $pPageNo     //��ǰҳ��
	 * @param integer $pPageSize   //ҳ�ߴ�
	 * @param integer $total��     //�ܼ�¼��
	 * @param string  $psType      //��ҳ��ʽ
	 */
	public function __construct($pPageNo=1,$pPageSize=30,$total,$getPage=false,$pStype=0,$pParamName='page'){
		$this->mParam = ($pParamName=='')?'page':$pParamName;
		$this->mTotalResult = $total;
		if (isset($_GET[$this->mParam])){
			$this->mCurrentPage = intval($_GET[$this->mParam]);
		}else{
			$this->mCurrentPage = intval($pPageNo);
		}
		$this->mPagesize = $pPageSize;
		$this->mCurrentPage <=0 && $this->mCurrentPage = 1;
		$this->mTotal       = ($total)?ceil($total/$pPageSize):0;
		$this->mNextPage    = ($this->mCurrentPage+1 > $this->mTotal)?$this->mTotal:$this->mCurrentPage+1;
		$this->mPrePage     = ($this->mCurrentPage-1>0)? $this->mCurrentPage-1 : 1;
		if ($getPage){
			$this->getPage($getPage);
		}
	}

	/**
	  * ���ɷ�ҳЧ��,����Javascript���ɴ�JS��$pScript����
	  * ���磺$this->getPage("none","alert(%d)");
	  * ���:<a href=javascript:viewPage(1)>��һҳ</a> <a href=javascript:alert(2)>��һҳ</a>
	  *  e.g:�ɴ���Ը��ӵ�JS������������ֻ��%d�������滻 (δ���ƣ��ݲ��Ƽ�ʹ��)
	  *	��ҳ��ʽ:none   : ��һҳ ��һҳ
	  * 		normal : ��ǰҳ ��һҳ 1 2 3 4 5 6 7 8 9 ��һҳ ���ҳ
	  * @param string $pStyle none:normal
	  */
	public function getPage($pStyle='none',$pScript=''){
		if ($this->mTotal === 1){
			return $this->mPage = "�� 1 ҳ";
		}else{
			if ($this->mTotal == 0) return false;
			$startNum = 1;
			if ($startNum+floor($this->mLength/2) < $this->mCurrentPage){
				$startNum = $this->mCurrentPage-floor($this->mLength/2)-1;
			}
			if ($startNum+$this->mLength <=$this->mTotal){
				$endNum = $startNum+$this->mLength;
			}else{
				$endNum = $this->mTotal;
				//$startNum = $this->mTotal - $this->mLength;
			}
			$first = '';
			$end   = '';
			$main  = '';
			$prefix = '?';
			if (isset($_GET[$this->mParam])) {
				$_GET[$this->mParam] = '';
				unset($_GET[$this->mParam]);
			}
			$prefix .= empty($_GET) ? '' : http_build_query($_GET).'&';
			$link = "{$this->mParam}=%d";
			if ($this->showFirst){
				$first = sprintf($this->mLink,$prefix.sprintf($link,1),"��ǰҳ");
				$end   = sprintf($this->mLink,$prefix.sprintf($link,$this->mTotal),"���ҳ");
			}
			for($i=$startNum;$i<=$endNum;$i++){
				$link = $prefix.$this->mParam."=$i";
				$main .= $this->mCurrentPage == $i ? '['.$i.']&nbsp;' : "<a href='$link'>$i</a>&nbsp;";
			}
			$this->mPage = $first.$main.$end;
		}
		return $this->mPage;
	}

}
?>