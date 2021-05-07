<?php

namespace app\controllers;

use yii\web\Controller;

class TestController extends Controller
{

	public function actionHello($name = "World"){
		$message = "Hello, {$name}";

		return $this->render("index", ["message" => $message]);
	}
}