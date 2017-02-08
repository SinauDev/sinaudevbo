<?php

function call_rekap($update){
	$message = isset($update['message'])?$update['message']:'';
	if (!empty($message)){
		$pesan = $message['from']['first_name'].': '. $message['text'];
		$myfile = file_put_contents('rekap.txt', $pesan.PHP_EOL , FILE_APPEND | LOCK_EX);		
	}
}
