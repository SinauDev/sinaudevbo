<?php

require __DIR__ . '/src/config.php';
require __DIR__ . '/src/helper.php';
require __DIR__ . '/src/telegrambot.php';

use \telegram\Bot;

//Set Token
Bot::setToken('');

// Daftar seluruh plugins
$plugin_dir = array_diff(scandir(__DIR__ .'/plugin'), array('..', '.'));
$plugin_name = array();
if (!empty($plugin_dir)){
	foreach ($plugin_dir as $plugin_file) {
		$pInfo = pathinfo($plugin_file);
		if(strtolower($pInfo['extension'])=='php'){
			require __DIR__.'/plugin/'.$plugin_file;
			$plugin_name[] = str_replace('.php', '', $plugin_file);
		}
	}
}



Bot::run(function($update) use (&$plugin_name)
	{
	
	if ($update['error'] == false)
		{
			$message = $update['message'];
			$inline = isset($update['inline_query'])?$update['inline_query']:'';
			$message_id = isset($message['message_id'])?$message['message_id']:'';
		
			$reply = (isset($message['reply_to_message']))?$message['reply_to_message']:'';
			
			if (!empty($reply)){
				if ($message['text']=='kick'){
					Bot::kickMember($reply['chat']['id'],$reply['from']['id']);
				}
			}

			Bot::setParam(array('reply_to_message_id' => $message_id));
			
			// Membaca seluruh plugin dan function nya
			foreach($plugin_name as $plugin){
				if (function_exists('call_'. $plugin))
				{
					$pResult = call_user_func('call_'.$plugin,$update);
				}
			}
						
			
			if (Bot::getChatType($message) == 'text')
			{
				
				
				if ($message['text'] == 'ping')
					{
						
				
						$keyboard = [
                		'inline_keyboard' =>[									
									[
										['text' =>  'Selengkapnya', 'url' => 'http://sinaudev.org'],
										['text' =>  'Gabung Sinaudev', 'url' => 'https://t.me/sinaudev']
									]								
									
									]
                				];
						$param['reply_markup']=json_encode($keyboard);
						$send = Bot::send('message',"<b>PONG</b>\n{$myfile}",$param);

					}
				
				if ($message['text'] == 'photo')
					{
						$send = Bot::send('photo',__DIR__.'/test/stickerTest.png');				
					}	
				
				if ($message['text'] == 'contact')
					{
						$send = Bot::send('contact',array('phone_number'=>'0217977752454','first_name'=>'Test Number'));
					}
				
				if ($message['text'] == 'map')
					{
						//  How to find a coordinates of a location?
						//	1. On your computer, visit Google Maps.
						//	2. Right-click a location on the map.
						//	3. Select "What's here?".
						//	4. A card appears at the bottom of the screen with more info.
	
						//Send a location of The Zimbabwe		
						$send = Bot::send('location',array('latitude'=>-18.389069, 'longitude'=> 29.160894));
					}
				
				
			}

			//inline method
			if (!empty($inline)){  inlinebot($inline);	}
		}
		else{
			if ($update['ok'] == false) print_r($update);
		}
	} 	
);


function inlinebot($inline){

	$query = $inline['query'];
	$id = $inline['id'];
	$from = $inline['from'];
	$answer = [];

	$json = getSinaudevJSON();

	if (!empty($query)){
		$json = getSinaudevPost($query,$json);
	}

	foreach ($json as $key => $value){
		$keyboard = [
                'inline_keyboard' =>[
									
									[
										['text' =>  'Selengkapnya', 'url' => 'http://sinaudev.org'.$value['url']],
										['text' =>  'Gabung Sinaudev', 'url' => 'https://t.me/sinaudev']
									]
									
									
									]
                ];
                
                $textMessage = "<b>{$value['title']}</b>\n\n";
                $textMessage .= $value['description'];

				$content = array('type'=>'article',
							'id' => (string)$key,
							'title' => $value['title'],
							'hide_url' => true,
							'message_text' => $textMessage,
							'parse_mode' => 'HTML',
							'description' => tokenTruncate($value['description'],30),
							'reply_markup'=>$keyboard);
				
				$answer[] = $content;
		}
			
	//print_r($answer);
	$send = Bot::answerInlineQuery($id,json_encode($answer));
	//print_r($send);

}
