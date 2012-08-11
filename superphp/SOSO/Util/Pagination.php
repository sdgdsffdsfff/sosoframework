<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Util
 * @package    SOSO_Util
 * @description 工具类::分页程序
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @version 1.0 alpha 2006-03-02 zyfunny@gmail.com
 * 
 * @todo 编码自适应
 * @todo 重构?
 */
class SOSO_Util_Pagination{
/**
	 * 总页数
	 * @var integer
	 */
	public $mTotal;        
	/**
	 * 当前页数
	 * @var integer
	 */
	public $mCurrentPage;  
	/**
	 * 前一页
	 * @var integer
	 */
	public $mPrePage ;    
	/**
	 * 下一页
	 * @var integer
	 */
	public $mNextPage;
	/**
	 * 分页样式
	 * @var array
	 */
	public $mStyle = array('none','normal','normal'=>'normal','none'=>'none');
	/**
	 * 最多显示数字个数 
	 * @var integer
	 */
	public $mLength = 9;
	/**
	 * 是否显示 [最前页],[最后页] 开关,true为显示
	 * @var boolean
	 */
	public $showFirst = true;

	/**
	 * 参数名称
	 *
	 * @var string
	 */
	public $mParam;
	/**
	 * 记录总数
	 * @var integar
	 */
	public $mTotalResult;
	/**
	 * 默认链接
	 */
	public $mLink = "<a href='%s'>%s</a>&nbsp;";
	/**
	 * 生成的分页链接
	 *
	 * @var unknown_type
	 */
	public $mPage;
	public $mPagesize ;
	/**
	 * 构造函数，初始化参数
	 *
	 * @param integer $pPageNo     //当前页码
	 * @param integer $pPageSize   //页尺寸
	 * @param integer $total的     //总记录数
	 * @param string  $psType      //分页样式
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
	  * 生成分页效果,兼容Javascript，可传JS给$pScript变量
	  * 例如：$this->getPage("none","alert(%d)");
	  * 结果:<a href=javascript:viewPage(1)>上一页</a> <a href=javascript:alert(2)>上一页</a>
	  *  e.g:可传相对复杂的JS进来，本方法只对%d作数字替换 (未完善，暂不推荐使用)
	  *	分页样式:none   : 上一页 下一页
	  * 		normal : 最前页 上一页 1 2 3 4 5 6 7 8 9 下一页 最后页
	  * @param string $pStyle none:normal
	  */
	public function getPage($pStyle='none',$pScript=''){
		if ($this->mTotal === 1){
			return $this->mPage = "共 1 页";
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
				$first = sprintf($this->mLink,$prefix.sprintf($link,1),"最前页");
				$end   = sprintf($this->mLink,$prefix.sprintf($link,$this->mTotal),"最后页");
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