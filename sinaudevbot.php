<?php

require __DIR__ . '/config.php';
require __DIR__ . '/telegrambot.php';

use \telegram\Bot;

$webHookInfo = Bot::webHookInfo();
$isWebhook = empty($webHookInfo['url'])?false:true;

//long polling method	
if ($isWebhook == false)
{
	while (true) 
 	{
		Bot::runLongPoll(function($update)
		{
			$result = $update['result'];
			$update_id = 0;
			if (!empty($result)){
				foreach ($result as $key) {
					$message = $key['message'];
					$update_id = $key['update_id'];		

					if ($message['text'] == 'ping')
					{
						Bot::send(array( 'cmd' => 'sendMessage' ,
										 'params' => array('chat_id' => $message['chat']['id'], 
										 					'text' => 'PONG',
										 					'reply_to_message_id' => $message['message_id'])
							));
					}

					//print_r($message); 
				}

				file_put_contents('last_update_id', $update_id + 1);
			}	
	
		});

		sleep(2);
	}	

}	