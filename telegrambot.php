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


	public static function runLongPoll($callback){

		while (true) {
			
			sleep(3);

			$update_file = 'last_update_id';
			$update_id = file_exists($update_file)?(int)file_get_contents($update_file):0;
			$params = array(
				'offset' => $update_id,
				'limit' => 100,
				'timeout' => 0);
		
			$ret = self::botSend(array('cmd' => 'getUpdates', 'params' => $params));
			$results = $ret['result'];

			if (!empty($results)){
				foreach ($results as $key) {
					$update_id = $key['update_id'];
					$callback($key);		
				}
			}		

			file_put_contents($update_file, $update_id + 1);
			
		}

	}

	public static function send($args){
		return self::botSend($args);
	}

	public static function sendMessage($msg,$params){
		$params['text'] = $msg;
		if (!isset($params['parse_mode'])) $params['parse_mode'] = 'HTML';
		return self::send(array('cmd' => 'sendMessage', 'params' => $params));
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
