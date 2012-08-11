<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-04)
* @version v 1.1 2010-02-04
* @package SOSO_Util
*/
require_once("SOSO/Util/Pagination.php");
class SOSO_Util_PaginationTest extends PHPUnit_Framework_TestCase{
	public $mTotal;
	public $mCurrentPage;
	public $mPrePage;
	public $mNextPage;
	public $mStyle;
	public $mLength;
	public $showFirst;
	public $mParam;
	public $mTotalResult;
	public $mLink;
	public $mPage;
	public $mPagesize;
		
	/**
	 * @dataProvider provider
	 * 
	 * @param int $pPageNo 当前页
	 * @param int $pPageSize 页大小
	 * @param int $total 总记录数
	 */
	public function testConstruct($pPageNo=1,$pPageSize=30,$total){
		$pagination = new SOSO_Util_Pagination($pPageNo,$pPageSize,$total);
		
		$this->assertEquals($total, $pagination->mTotalResult);
		$this->assertEquals($pPageSize, $pagination->mPagesize);
		$this->assertEquals($pPageNo, $pagination->mCurrentPage);
		$this->assertEquals(ceil($total/$pPageSize), $pagination->mTotal);
		
	}
	
	public function provider(){
		return array(
          array(1, 10, 100),
          array(1, 20, 1000),
          array(1, 40, 2345),
          array(1, 25, 5001)
        );
	}
	
	public function testGetPage(){
		//do test
	}
		
}