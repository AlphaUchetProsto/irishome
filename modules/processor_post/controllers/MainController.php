<?php

namespace app\modules\processor_post\controllers;

use Yii;
use yii\web\Controller;
use app\models\logger\CleanerLogger;
use app\models\logger\DebugLogger;
use app\models\bitrix\Bitrix;
use app\modules\processor_post\models\distributor\Distributor;
//use app\models\bitrix\entity\Contact;
use app\modules\processor_post\models\bitrix\Deal;
use app\modules\processor_post\models\bitrix\Contact;
use app\modules\processor_post\models\bitrix\Fields;

class MainController extends Controller
{

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        CleanerLogger::clear();

        return parent::afterAction($action, $result);
    }

    public function actionIndex()
    {
        $post_data = \Yii::$app->request->post();
        $get_data = \Yii::$app->request->get();
        $distributor = new Distributor($post_data, $get_data);

        if(($url = $distributor->checkToken()) === null) return false;
        if(!$distributor->checkTelegramIdAndLog()) {
            $distributor->StartParser($url);
            return 200;
        }
        $distributor->checkUser();

        return 200;
    }

    public function actionAddEntity()
    {
        $logger = DebugLogger::instance('add-entity');
        $logger->save(Yii::$app->request->post(), Yii::$app->request, 'Данные сущностей');

        header('Content-Type: text/html; charset=utf-8');

        $get_data = \Yii::$app->request->get();
        $post_data = \Yii::$app->request->post();

        $fields = new Fields($post_data);
        $contact_id = $fields->checkDublicate();
        $fields->setFieldsDeals($contact_id);
        $fields->setFieldsLeads($fields);

    }

    public function actionPrepare()
    {
        $get_data = \Yii::$app->request->get();
        $post_data = \Yii::$app->request->post();
        $cookie = $_COOKIE;
        $prepare = new Prepare($post_data,$get_data, $cookie);
        $prepare->sending();
    }

    public function actionTest()
    {
        dd(1);
        $get_data = \Yii::$app->request->get();
        $post_data = \Yii::$app->request->post();
//        $logger = DebugLogger::instance("install");
//        $logger->save($post_data, $post_data, "Post данные");
//        $logger->save($get_data, $get_data, "Get данные");
    }

}
