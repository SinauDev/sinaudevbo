<?php

require __DIR__ . '/config.php';
require __DIR__ . '/helper.php';
require __DIR__ . '/telegrambot.php';

use \telegram\Bot;

//Bot::setToken('');

//print_r(Bot::getMe());
//$webHookInfo = Bot::webHookInfo();
//$isWebhook = empty($webHookInfo['url'])?false:true;
//Bot::setWebhook($isWE)

Bot::run(function($update)
	{

		$message = isset($update['message'])?$update['message']:'';
		$inline = isset($update['inline_query'])?$update['inline_query']:'';
				
		$message_id = isset($message['message_id'])?$message['message_id']:'';
				
		Bot::setParam(array('reply_to_message_id' => $message_id));
		
		if (!empty($message))
		{
			if ($message['text'] == 'ping')
				{
					$send = Bot::sendMessage('<b>PONG</b>');
					//print_r($send);
				}
		}

		//inline method
		if (!empty($inline)){  inlinebot($inline);	}
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