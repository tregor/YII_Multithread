<?php

namespace app\commands;

use mamatveev\yii2rabbitmq\RabbitComponent;
use yii\console\Controller;
use Yii;

class MultithreadController extends Controller
{

	private $serverName = "multithread";
	private $countThreads = 10;

	public function actionStartTaskServer()
	{
		//TODO: Убивать или проверять наличие старых "потоков"
		//TODO: Заменить nohup на supervisord
		for ($i = 1; $i <= $this->countThreads; $i++) {
			exec("nohup php yii multithread/task-server > taskLog.txt 2>1 &");
			echo "Started task server thread #{$i}" . PHP_EOL;
		}
	}

	public function actionTaskServer()
	{
		/** @var RabbitComponent $rpc */
		$rpc = Yii::$app->rpc;

		$rpcServer = $rpc->initServer($this->serverName);

		$callback = function ($msg) {
			$task = json_decode($msg, TRUE);
			var_dump($task);

			$sleepTime = rand(2, 20);
			sleep($sleepTime);
			//exec($task['command'], $output, $code);
			//$result = "Executed command \"{$task['command']}\", response code is {$code}, body: ";
			//$result .= print_r($output, TRUE);
			$result = json_encode([
				"PID" => $task['threadID'],
				"sleepTime" => $sleepTime,
				"task" => $task,
			]);

			return $result;
		};

		$rpcServer->setCallback($callback);
		$rpcServer->start();
	}

	public function actionWait($data = NULL)
	{
		$sleepTime = rand(10, 100);
		if (empty($data)) {
			$data = md5(time());
		}

		sleep($sleepTime);

		$result = json_encode([
			"sleepTime" => $sleepTime,
			"someData"  => $data,
		]);

		echo $result . PHP_EOL;

		return $result;
	}

	public function actionAddTask($command = NULL)
	{
		/** @var RabbitComponent $rpc */
		$rpc = Yii::$app->rpc;
		// init a client
		$rpcClient = $rpc->initClient($this->serverName);
		$threadID = uniqid();

		for ($i = 1; $i <= 10; $i++) {

			$task = [
				"PID" => $threadID,
				"taskID" => uniqid(),
				"command"   => $command."_{$i}",
				"timestamp" => time(),
			];

			$rpcClient->addRequest(json_encode($task));
		}

		// use callback for responses
		$response = $rpcClient->getReplies(function ($msg) {
			echo "Server response is {$msg}\n";

			return $msg;
		});

		return $response;
	}
}