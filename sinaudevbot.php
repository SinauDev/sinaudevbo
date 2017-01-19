<?php

require __DIR__ . '/config.php';
require __DIR__ . '/telegrambot.php';

use \telegram\Bot;

$webHookInfo = Bot::webHookInfo();
$isWebhook = empty($webHookInfo['url'])?false:true;

//long polling method	
if ($isWebhook == false)
{
		Bot::runLongPoll(function($update)
		{
				$message = $update['message'];
				if ($message['text'] == 'ping')
				{
					$send = Bot::sendMessage('<b>PONG</b>', array('chat_id' => $message['chat']['id'], 
								 		 	   'reply_to_message_id' => $message['message_id'])
									);
				}
		});
}