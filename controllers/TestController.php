<?php

namespace app\controllers;

use yii\web\Controller;
use app\commands\MultithreadController;
use mamatveev\yii2rabbitmq\RabbitComponent;
use Yii;

class TestController extends Controller
{
	private $serverName = "multithread";

	public function actionHello($name = "World"){
		$message = "Hello, {$name}";

		//(new MultithreadController)->actionAddTask("php yii multithread/wait");

		return $this->render("index", ["message" => $message]);
	}

	public function actionAddTask($command = NULL)
	{
		/** @var RabbitComponent $rpc */
		$rpc = Yii::$app->rpc;
		// init a client
		$rpcClient = $rpc->initClient($this->serverName);

		for ($i = 1; $i <= 10; $i++) {
			$command = "Client1";

			$task = [
				"taskID" => uniqid(),
				"command"   => $command."_{$i}",
				"timestamp" => time(),
			];

			$rpcClient->addRequest(json_encode($task));
		}

		// use callback for responses
		try{
		$response = $rpcClient->getReplies(function ($msg) {
			//echo "Server response is {$msg}\n";
			if (!empty($msg)){
				throw new \Exception($msg, 12);
			}
			//return $this->render("index", ["message" => "Server response is {$msg}\n"]);
		});
		}catch (\Exception $e){
			if ($e->getCode() == 12){
				//exec("supervisor kill threads");
				return $this->render("index", ["message" => $e->getMessage()]);
			}
		}

		$rpcClient->addRequest(json_encode($task));
		try{
			$response = $rpcClient->getReplies(function ($msg) {
				//echo "Server response is {$msg}\n";
				if (!empty($msg)){
					throw new \Exception($msg, 12);
				}
				//return $this->render("index", ["message" => "Server response is {$msg}\n"]);
			});
		}catch (\Exception $e){
			if ($e->getCode() == 12){
				//exec("supervisor kill threads");
				return $this->render("index", ["message" => $e->getMessage()]);
			}
		}

		return $this->render("index", ["message" => "No answer"]);
		//return $response;
	}
}