<?php

namespace telegram;

class Bot {


	public static function getMe(){
		return self::botSend(array('cmd' => 'getMe'));
	}

	public static function webHookInfo(){
		return $webHookInfo = self::botSend(array('cmd' => 'getWebhookInfo'));
	}

	public static function runWebHook($callback){

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json')
			{
				$ret = json_decode(file_get_contents('php://input'), true);
				if (is_null($ret)) throw new Exception('Error invalid JSON');
			}	
		

		return $callback($ret);
	}


	public static function runLongPoll($callback,$args=[]){

		$update_file = isset($args['update_file'])?$args['update_file']:'last_update_id';
		$offset = file_exists($update_file)?(int)file_get_contents($update_file):0;
		$params = array(
				'offset' => isset($args['offset'])?$args['offset']:$offset,
				'limit' => isset($args['limit'])?$args['limit']:100,
				'timeout' => isset($args['timeout'])?$args['timeout']:0
		);
	
		$ret = self::botSend(array('cmd' => 'getUpdates', 'params' => $params));
		return $callback($ret);

	}

	public static function send($args){
		return self::botSend($args);
	}

	private function botSend($args){

		$botCommand = isset($args['cmd'])?$args['cmd']:'';
		$isPost = isset($args['isPost'])?$args['isPost']:true;
		$sendFile = isset($args['sendFile'])?$args['sendFile']:false;
		$params = isset($args['params'])?$args['params']:'';
		$filename = isset($args['filename'])?$args['filename']:'';

		$ch = curl_init();
		$config = array(
			CURLOPT_URL => 'https://api.telegram.org/bot'. TOKEN . '/' . $botCommand,
			CURLOPT_POST => $isPost,
			CURLOPT_RETURNTRANSFER => true
		);


		if ($sendFile){
			$filename_ = realpath($filename);
			$config[CURLOPT_HTTPHEADER] = array('Content-Type: multipart/form-data');

			if (!is_file($filename_))
				throw new Exception('File does not exists');
			if (function_exists('curl_file_create'))
				$filename = curl_file_create($filename_);

			$filename = "@$filename_";
		}
			
		if (!empty($params))
			$config[CURLOPT_POSTFIELDS] = $params;

		curl_setopt_array($ch, $config);
		$result = curl_exec($ch);
		curl_close($ch);

		// return and decode to JSON
		return !empty($result) ? json_decode($result, true) : false;
	}
}
