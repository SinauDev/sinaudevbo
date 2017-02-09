<?php

function DirExists($dir){
	return (file_exists($dir) && is_dir($dir)); 
}

function call_rekap($update){
	date_default_timezone_set('Asia/Jakarta');
	$message = isset($update['message'])?$update['message']:'';
	
	$rekapFile = 'start_rekap';
	$rekapDir = __DIR__ . '/rekap/';
	$dateStr = date('YmdHis');
		
	if (!empty($message)){
		
		if ($message['text'] == '!rekap'){
			if (!DirExists($rekapDir)) mkdir($rekapDir);
			file_put_contents($rekapDir . $rekapFile,$dateStr);
		}
		
		if ($message['text'] == '!stop'){
			if (file_exists($rekapDir . $rekapFile)) unlink($rekapDir . $rekapFile);
		}
		
		if (file_exists($rekapDir . $rekapFile))
		{
			if ($message['text'] != 'rekap')
			{
				$rekapDate = file_get_contents($rekapDir . $rekapFile);
				//Jika di reply	
				$inReply = isset($message['reply_to_message'])?$message['reply_to_message']:false;
						
				if ($inReply == false)
				{
					$pesan = $message['from']['first_name'].', ['. date('Y-m-d H:i',$message['date'])."]\n". $message['text']."\n";
				} else {
					
					$pesan = $message['from']['first_name'].', ['. date('Y-m-d H:i',$message['date'])."]\n";
					$pesan .= '[In reply to '. $message['reply_to_message']['from']['first_name'] ."]\n";
					$pesan .=  $message['text']."\n";
					
				}
				
				$myfile = file_put_contents($rekapDir . $rekapDate.'-rekap.txt', $pesan.PHP_EOL , FILE_APPEND | LOCK_EX);		
			}	
		}
				
	}
}
