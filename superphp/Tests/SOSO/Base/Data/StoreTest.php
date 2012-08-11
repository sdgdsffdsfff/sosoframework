<?php
/**
 * 
 * @author pennywang
 */
class SOSO_Base_Data_StoreTest extends PHPUnit_Framework_TestCase {
    public function init(){
        $this->writer=$this->getMock("SOSO_Base_Data_Writer",array(), array(),'', false);
        $this->writer->expects($this->exactly(3))
            ->method('save')
            ->with($this->arrayHasKey('col1'),$this->anything(),$this->anything())
            ->will($this->returnValue(true));
        $record=$this->record=SOSO_Base_Data_Record::create(array(
                            array('name'=>'col1','mapping'=>"col1mapping"),
                            array('name'=>'col2','mapping'=>'col2mapping'),
                            ));

        $this->reader=$this->getMock("SOSO_Base_Data_Reader",array(), array(),'', false);
        $this->reader->expects($this->once())
            ->method('read')
            ->with($this->equalTo('this is test data'),$this->anything(),$this->anything())
            ->will($this->returnValue(array(
                            'records'=>array(
                                $this->record0=$record->instance( array('col1'=>'val0','col2'=>'val20'),1),
                                $this->record1=$record->instance( array('col1'=>'val1','col2'=>'val21'),2),
                                $this->record2=$record->instance( array('col1'=>'val2','col2'=>'val22'),3),
                                )
                            )));

        $this->proxy=$this->getMock("SOSO_Base_Data_Connection",array(), array(),'', false);
        $this->proxy->expects($this->once())
            ->method('request')
            ->with($this->equalTo('this is test url'),$this->anything())
            ->will($this->returnValue("this is test data"));
        $this->proxy->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue("200"));

    }
    public function test1(){
        $this->init();
        $store=new SOSO_Base_Data_Store(array(
                    'baseParams'=>array(),
                    'writer'=>$this->writer,
                    'reader'=>$this->reader,
                    'url'=>'this is test url',
                    'proxy'=>$this->proxy,
                    ));
        $store->load(array('option'=>'load option1','option'=>'load option2'));
        $store->save();
    }
    public function test2(){
        $this->init();
        $store=new SOSO_Base_Data_Store(array(
                    'baseParams'=>array(),
                    ));
        $store->proxy=$this->proxy;
        $store->setWriter($this->writer);
        $store->setReader($this->reader);
        $store->setUrl('this is test url');
        $store->load(array('option'=>'load option1','option'=>'load option2'));
        $store->save();
        foreach($store as $i=>$record){
            $tmp="record$i";
            $this->assertEquals($this->$tmp->getData(),
                    $record);
        }
        foreach($store as $i=>$record){
            $tmp="record$i";
            $this->assertEquals($this->$tmp->getData(),
                    $record);
        }
        $store->remove($this->record1);
        $this->assertEquals(2,$store->getCount());
        $store->removeAll();
        $this->assertEquals(0,$store->getCount());
    }
    public function test3(){
        $this->init();
        $store=new SOSO_Base_Data_Store(array(
                    'baseParams'=>array(//这个是传给load的参数
                        'callback'=>'callback',
                        'scope'=>$this,
                    ),
                    'writer'=>$this->writer,
                    'reader'=>$this->reader,
                    'url'=>'this is test url',
                    'proxy'=>$this->proxy,
                    ));
        $store->load(array(
                    'option'=>'load option1',
                    'option'=>'load option2',
                    'params'=>array("add"=>true),
                    ));
        $this->assertTrue($this->_callback);
        $i=0;
        $store->each(function() use(&$i){
                    $i++;
                });
        $this->assertEquals(3,$i);
        $store->save();
        $this->assertEquals(3,$store->getTotalCount());
    }
    public function callback(){
        $this->_callback=true;
    }

    public function testModeComplex(){
        $this->init();
        $store=new SOSO_Base_Data_Store(array(
                    'writer'=>$this->writer,
                    'reader'=>$this->reader,
                    'url'=>'this is test url',
                    'proxy'=>$this->proxy,
                    'mode'=>SOSO_Base_Data_Store::MODE_COMPLEX,
                    ));
        $store->load(array(
                    'option'=>'load option1',
                    'option'=>'load option2',
                    'params'=>array("add"=>true),
                    ));
        $store->save();
        $rec=$store->getById(3)->getData();
        $this->assertEquals('val2',$rec['col1']);
        
        $record=$this->record=SOSO_Base_Data_Record::create(array(
                            array('name'=>'col1','mapping'=>"col1mapping"),
                            array('name'=>'col2','mapping'=>'col2mapping'),
                            ));
        $store->insert(1,array(
                        $record->instance( array('col1'=>'val3','col2'=>'val23'),3),
                        $record->instance( array('col1'=>'val4','col2'=>'val24'),4),
                        $record->instance( array('col1'=>'val5','col2'=>'val25'),5),
                    ));
        $this->assertEquals(6,$store->getCount());
        //var_dump($store->indexOf($this->record2));
        $store->each(function($record) use(&$i){
                if(is_object($record)){
                    //var_dump($record->getData());
                }else{
                    //var_dump($record);
                }
                });
        $this->assertEquals(5,$store->indexOf($this->record2));
        $this->assertEquals(6,$store->getCount());
        $this->assertEquals(1,$store->indexOfId(3));

    }
    public function testLoadData(){
        $record=$this->record=SOSO_Base_Data_Record::create(array(
                            array('name'=>'col1','mapping'=>"col1mapping"),
                            array('name'=>'col2','mapping'=>'col2mapping'),
                            ));
        $inlineData="aaa";
        $this->reader=$this->getMock("SOSO_Base_Data_Reader",array(), array(),'', false);
        $this->reader->expects($this->once())
            ->method('readRecords')
            ->with($this->equalTo($inlineData),//XMLReader里，这个参数是dom对象
                    $this->anything())
            ->will($this->returnValue(false));
        $store=new SOSO_Base_Data_Store(array(
                    'baseParams'=>array(//这个是传给load的参数
                        'callback'=>'callback',
                        'scope'=>$this,
                    ),
                    'reader'=>$this->reader,
                    'data'=>$inlineData,
                    ));
        //$store->loadData($inlineData);
        
    }

}
