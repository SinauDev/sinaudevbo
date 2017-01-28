<?php

namespace telegram;

class Bot {

	private static $params;
	private static $token;
	private static $isWebhook = false;
	
	
	public static function setToken($token){
		self::$token = $token;
	}
	
	public static function setParam($param=[]){
		self::$params = array_merge(self::$params,$param);
	}

	public static function getMe(){
		return self::botSend(array('cmd' => 'getMe'));
	}

	public static function webHookInfo(){
		return $webHookInfo = self::botSend(array('cmd' => 'getWebhookInfo'));
	}
		

	private static function runWebHook($callback){

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json')
			{
				$ret = json_decode(file_get_contents('php://input'), true);
				if (is_null($ret)) throw new Exception('Error invalid JSON');
				
				self::$params['chat_id'] = isset($ret['message']['chat']['id'])?$ret['message']['chat']['id']:'';
				return $callback($ret);
			}	
		else {
			return false;
		}
	}


	public static function run($callback){

	 if (self::$isWebhook){
		return self::runWebHook($callback);
	 }
	 else {
		while (true)
			{
			
				sleep(1);

				$update_file = 'last_update_id';
				$update_id = file_exists($update_file)?(int)file_get_contents($update_file):0;
				$params = array(
					'offset' => $update_id,
					'limit' => 100,
					'timeout' => 0);
			
				$ret = self::botSend(array('cmd' => 'getUpdates', 'params' => $params));
				$results = (isset($ret['result']))?$ret['result']:'';

				if (!empty($results)){
					foreach ($results as $key) {
						$update_id = $key['update_id'];
						self::$params['chat_id'] = isset($key['message']['chat']['id'])?$key['message']['chat']['id']:'';
						$callback($key);		
					}
				}		

				file_put_contents($update_file, $update_id + 1);
			
			}
		}
	}

	public static function send($args){
		return self::botSend($args);
	}

	public static function sendMessage($msg,$params=[]){
		$params = array_merge(self::$params,$params);
		$params['text'] = $msg;
		if (!isset($params['parse_mode'])) $params['parse_mode'] = 'HTML';
		return self::send(array('cmd' => 'sendMessage', 'params' => $params));
	}

	public static function answerInlineQuery($query_id,$results){
		$params = self::$params;
		$params['inline_query_id'] = $query_id;
		$params['results'] = $results;
		return self::send(array('cmd'=>'answerInlineQuery', 'params'=>$params));
	}


	private function botSend($args){

		$botCommand = isset($args['cmd'])?$args['cmd']:'';
		$isPost = isset($args['isPost'])?$args['isPost']:true;
		$sendFile = isset($args['sendFile'])?$args['sendFile']:false;
		$params = isset($args['params'])?$args['params']:'';
		$filename = isset($args['filename'])?$args['filename']:'';

		if (!isset(self::$token))
		{	
			if (defined('TOKEN')) {self::$token=TOKEN;}
		}
		
		$ch = curl_init();
		$config = array(
			CURLOPT_URL => 'https://api.telegram.org/bot'. self::$token . '/' . $botCommand,
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
