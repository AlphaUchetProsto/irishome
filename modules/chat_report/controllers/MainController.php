<?php

namespace app\modules\chat_report\controllers;

use app\modules\chat_report\models\FilterModel;
use app\modules\chat_report\models\Settings;
use yii\base\BaseObject;
use yii\web\Controller;
use app\modules\chat_report\models\App;
use app\modules\chat_report\models\SalaryReport;
use yii\web\Response;

class MainController extends Controller
{
    public $layout  = 'main';
    public $enableCsrfValidation = false;
    
    public function actionIndex()
    {
        $model = new FilterModel();

        if(\Yii::$app->request->isPost && $model->load(\Yii::$app->request->post()) && $model->validate())
        {
            return $this->render('index', ['model' =>  $model, 'report' => $model->filter()]);
        }

        return $this->render('index', ['model' =>  $model]);
    }

    public function actionInstall()
    {
        return $this->render('install');
    }
}
