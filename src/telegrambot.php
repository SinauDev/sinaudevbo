<?php

namespace telegram;

class Bot {

	private static $params=[];
	private static $token;
	private static $isWebhook = false;
	private static $update_id = 0;
	private static $query_id = 0;
	
	
	public static function setToken($token){
		self::$token = $token;
	}
	
	public static function setParam($param=[]){
		self::$params = array_merge(self::$params,$param);
	}

	public static function getMe(){
		return self::send(array('cmd' => 'getMe'));
	}

	public static function webHookInfo(){
		return $webHookInfo = self::botSend(array('cmd' => 'getWebhookInfo'));
	}
		

	private static function runWebHook($callback){

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json')
			{
				$ret = json_decode(file_get_contents('php://input'), true);
				if (is_null($ret)) throw new Exception('Error invalid JSON format');
				
				self::setParam(array('chat_id' => isset($ret['message']['chat']['id'])?$ret['message']['chat']['id']:''));
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
				$params = array(
					'offset' => self::$update_id,
					'limit' => 100,
					'timeout' => 0);
				$result['error'] = true;
				$ret = self::botSend(array('cmd' => 'getUpdates', 'params' => $params));
				$results = (isset($ret['result']))?$ret['result']:'';

				if (!empty($results)){
					foreach ($results as $key) {
						$result['error'] = false;
						$update_id = $key['update_id'];
						$result = array_merge($result,$key);
						
						if (!isset(self::$params['chat_id'])){
							$chat_id = isset($key['message']['chat']['id'])?$key['message']['chat']['id']:'';
							self::setParam(array('chat_id' => $chat_id));
						} 
												
						$callback($result);			
						
					}
					self::$update_id = $update_id + 1;
				} else{		
					$result = array_merge($result,$ret);
					$callback($result);
					if (isset($ret['error_code']))
					{
						if ($ret['error_code']==404){break;}
					}
				}	
				
			}
		}
	}

	/** Send a Command
	 * 
	 *  @param string $command
	 *  @param string or array $req (required)
	 *  @param array $params (additional command)
	 * 
	 *  @return array
	*/
	public static function send($command,$req,$params=[])
	{
		$command = strtolower($command);
		$params = array_merge(self::$params,$params);
		
		$toFile = function ($fileName){
			// set realpath
			$filename = realpath($fileName);
			// check a file
			if (!is_file($filename))
				throw new Exception('File does not exists');
			// PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
			// See: https://wiki.php.net/rfc/curl-file-upload
			if (function_exists('curl_file_create'))
				return curl_file_create($filename);
			// Use the old style if using an older version of PHP
			return "@$filename";};
		
		switch ($command)
		{
		
			case 'message':
				if (!isset($params['parse_mode'])) $params['parse_mode'] = 'HTML';
				$params['text'] = $req;	
				$result = self::botSend(array('cmd' => 'sendMessage', 'params' => $params));
				break;	
			
			case 'forward':
				if (!is_array($req)) throw new Exception('Parameter must be an array');
				
				if (!isset($req['from_chat_id'])) throw new Exception('Parameter of from_chat_id must be set');
				if (!isset($req['message_id'])) throw new Exception('Parameter of message_id must be set');
				
				$params['from_chat_id'] = $req['from_chat_id'];
				$params['message_id'] = $req['message_id'];
				$result = self::botSend(array('cmd'=>'forwardMessage','params'=> $params));
				break;
			
			case 'photo':
			case 'document':
			case 'audio':
			case 'video':
			case 'voice':
			case 'sticker':
				$params[$command] = $toFile($req);
				$result = self::botSend(
							array( 'cmd' => 'send'.ucfirst($command),
								   'sendFile' => true,
								   'params' => $params));			   
				break;
				
			case 'location':
			case 'venue':
				
				if (!is_array($req)) throw new Exception('Parameter must be an array');
				
				if (!isset($req['latitude'])) throw new Exception('Required a number of Latitude');
				if (!isset($req['longitude'])) throw new Exception('Required a number of Longitude');
				
				$params['latitude'] = $req['latitude'];
				$params['longitude'] = $req['longitude'];
				
				if ($command=='venue')
				{
					
					if (!isset($req['title'])) throw new Exception('Required a title name');
					if (!isset($req['address'])) throw new Exception('Required an address name');
					
					$params['title'] = $req['title'];
					$params['address'] = $req['address'];
					
				}
				
				$result = self::botSend(array('cmd' => 'send'.ucfirst($command),'params' => $params));
				break;
				
			case 'contact':
				
				if (!is_array($req)) throw new Exception('Parameter must be an array');
				
				if (!isset($req['phone_number'])) throw new Exception('Required a phone number');
				if (!isset($req['first_name'])) throw new Exception('Required a first name');
			
				
				$params['phone_number'] = $req['phone_number'];
				$params['first_name'] = $req['first_name'];
				
				
				$result = self::botSend(array('cmd'=>'sendContact','params'=>$params));
				break;		
			
		}
		
		return $result;
	}

	public static function answerInlineQuery($query_id,$results){
		$params = self::$params;
		$params['inline_query_id'] = $query_id;
		$params['results'] = $results;
		return self::botSend(array('cmd'=>'answerInlineQuery', 'params'=>$params));
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


		if ($sendFile)
			$config[CURLOPT_HTTPHEADER] = array('Content-Type: multipart/form-data');
		
			
		if (!empty($params))
			$config[CURLOPT_POSTFIELDS] = $params;

		curl_setopt_array($ch, $config);
		$result = curl_exec($ch);
		curl_close($ch);

		// return and decode to JSON
		return !empty($result) ? json_decode($result, true) : false;
	}
}
