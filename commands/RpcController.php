<?php

namespace app\commands;

use mamatveev\yii2rabbitmq\RabbitComponent;
use yii\console\Controller;

class RpcController extends Controller
{

	public function actionRabbitServer()
	{
		/** @var RabbitComponent $rpc */
		$rpc = \Yii::$app->rpc;

		$rpcServer = $rpc->initServer('test');

		$callback = function($msg){
			$result = "Got message from client: " . print_r($msg, true);
			return $result;
		};

		$rpcServer->setCallback($callback);
		$rpcServer->start();
	}

	public function actionRabbitClient()
	{
		/** @var RabbitComponent $rpc */
		$rpc = \Yii::$app->rpc;

		// init a client
		$rpcClient = $rpc->initClient('test');

		for ($i = 0; $i < 20; $i++) {
			$rpcClient->addRequest("message number {$i}, getReplies() with callback");
		}

		// use callback for responses
		$rpcClient->getReplies(function($msg) {
			echo "Server response is {$msg}\n";
		});
	}
}