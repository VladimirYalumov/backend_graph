<?php

namespace app\controllers;

use Yii;
use yii\web\Controller

class GraphController extends Controller
{
	public function actionTest(){
		return $this->render('test');
	}

	public function actionIndex(){
		return $this->render('index');
	}

}