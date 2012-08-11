<?php
/**
 * @author moonzhang
 *
 * 
 * to be redsigned as a model 
 */
class SOSO_Base_Alarm {
	public $mListerns = array();
	
	private function __construct(Base_Task $task){
		
	}
	
	public static function doAlarm($pUsers=array(),$subject='',$msg='',$pEncoding="gbk"){
		$tMail = new SOSO_Base_Alarm_Email();
		return $tMail->sendMail($pUsers,$subject,$msg,$pEncoding);
	}
	
	public static function sms($pUsers,$msg){
		$instance = SOSO_Base_Alarm_Mobile::instance();
		if (is_string($pUsers)) {
			$pUsers = array($pUsers);
		}
		
		foreach ($pUsers as $mobile){
			 if (preg_match("#\d{11}#",$mobile))
			 	$instance->sms($mobile,$msg);
			 else{
			 	
			 }
		}
	}
	
	
	
	public function notify($pUser){
		
	}
}