<?php

namespace app\models\collector;

use app\models\bitrix\Company;
use app\models\bitrix\Contact;
use Yii;
use yii\base\Model;

class CollectorRole
{
    public static function init($user)
    {
        $service = Yii::$app->session->get("service");

        if(method_exists(CollectorRole::class, $service)) {
            $user = self::$service($user);
        }

        return true;
    }

    /*
    public static function tyreFitter($user)
    {
        $auth = Yii::$app->authManager;
        $tyre_fitter = $auth->getRole("tyre_fitter");

        if(empty($auth->getAssignments($user->id)))
        {
            $auth->assign($tyre_fitter, $user->id);
        }

        return true;
    }
    */
}
