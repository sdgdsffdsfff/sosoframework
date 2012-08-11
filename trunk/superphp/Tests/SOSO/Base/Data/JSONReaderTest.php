<?php
/**
 * 
 * @author pennywang
 */
class SOSO_Base_Data_JSONReaderTest extends PHPUnit_Framework_TestCase {
    public function test1(){
        $json=json_encode(array('test'=>array(
                    array('col1'=>'val11','col2'=>'val21','name'=>array('rec'=>array("first"=>'f11',"last"=>'f21')),'id'=>3),
                    array('col1'=>'val12','col2'=>'val22','name'=>array('rec'=>array("first"=>'f12',"last"=>'f22')),'id'=>4),
                    array('col1'=>'val13','col2'=>'val23','name'=>array('rec'=>array("first"=>'f13',"last"=>'f23')),'id'=>5),
                    ),
                    'total'=>2,
                ));
        $jsonReader=new SOSO_Base_Data_JSONReader(array(
                    'totalRecords'=>"[total]",
                    'record'=>"[test][NUMERIC]",
                    'id'=>'[id]',
                    'fields'=>SOSO_Base_Data_Record::create(array(
                            array('name'=>'id','mapping'=>"[id]"),
                            array('name'=>'v1','mapping'=>'[col1]'),
                            array('name'=>'v2','mapping'=>'[col2]'),
                            array('name'=>'name','mapping'=>array('[name]'=>array(
                                    'record'=>'[rec]','column'=>SOSO_Base_Data_Record::create(array(
                                        array('name'=>'firstName','mapping'=>'[first]'),
                                        array('name'=>'lastName','mapping'=>'[last]'),
                                        ))
                                    ))),
                            )
                    )));
        $result=$jsonReader->read($json,$store=null,'gbk');
        $this->assertEquals(3,count($result['records']));
        foreach ($result['records'] as $i=>$record){
            $data= $record->getData();
            $this->assertEquals(3+$i,$data['id']);
            $this->assertEquals("val1".($i+1),$data['v1']);
            $this->assertEquals("val2".($i+1),$data['v2']);
        }
        $data=$result['records'][0]->getData();
        $this->assertEquals(array(array(array('firstName'=>'f11','lastName'=>'f21'))),$data['name']);
        $this->assertEquals(2,$result['totalRecords']);
        $jsonReader->__destruct();
    }
}
