<?php
/**
 * 
 * @author pennywang
 */
class SOSO_Base_Data_XMLReaderTest extends PHPUnit_Framework_TestCase {
    public function test1(){
        $xml=<<<END
<!doctype html>
<html>
    <head>
    </head>
    <body>
        <div id="total">4</div>
        <div id="list">
            <ul>
                <li id="10">
                    <div class="key">key0</div><div class="value">value0</div>
                    <div class="name">
                        <div class="firstName">firstName1</div>
                        <div class="lastName">lastName1</div>
                    </div>
                </li>
                <li id="11">
                    <div class="key">key1</div><div class="value">value1</div>
                    <div class="name">
                        <div class="firstName">firstName1</div>
                        <div class="lastName">lastName1</div>
                    </div>
                </li>
                <li id="12"><div class="key">key2</div><div class="value">value2</div></li>
                <li id="13"><div class="key">key3</div><div class="value">value3</div></li>
                <li id="14"><div class="key">key4</div><div class="value">value4</div></li>
            </ul>
        </div>
    </body>
</html>
END;
        $xmlReader=new SOSO_Base_Data_XMLReader(array(
                    'totalRecords'=>"div[@id='total']",
                    'record'=>"div[@id='list'] ul li",
                    'id'=>'@id',
                    'fields'=>SOSO_Base_Data_Record::create(array(
                            array('name'=>'id','mapping'=>"@id"),
                            array('name'=>'key','mapping'=>'div[@class="key"]'),
                            array('name'=>'value','mapping'=>'div[@class="value"]'),
                            array('name'=>'name','mapping'=>array(array(
                                    'record'=>'div[@class="name"]','column'=>SOSO_Base_Data_Record::create(array(
                                        array('name'=>'firstName','mapping'=>'div[@class="firstName"]'),
                                        array('name'=>'lastName','mapping'=>'div[@class="lastName"]'),
                                        ))
                                    ))),
                            )
                    )));
        $result=$xmlReader->read($xml);
        $this->assertEquals(5,count($result['records']));
        foreach ($result['records'] as $i=>$record){
            $data= $record->getData();
            $this->assertEquals(10+$i,$data['id']);
            $this->assertEquals("key$i",$data['key']);
            $this->assertEquals("value$i",$data['value']);
        }
        $data=$result['records'][0]->getData();
        $this->assertEquals(array(array(array('firstName'=>'firstName1','lastName'=>'lastName1'))),$data['name']);
        $this->assertEquals(4,$result['totalRecords']);
        $xmlReader->__destruct();
    }
}
