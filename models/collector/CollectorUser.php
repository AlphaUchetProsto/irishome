<?php

namespace app\models\collector;

use app\models\bitrix\Company;
use app\models\bitrix\Contact;
use app\models\bitrix\Requisite;
use app\models\user\User;
use Yii;
use yii\base\BaseObject;

class CollectorUser
{
    public static function findByPhone($phone)
    {
        $service = Yii::$app->session->get("service");
        $service = $service . "Phone";

        if(method_exists(CollectorUser::class, $service)) {
            $user = self::$service($phone);
        }

        if(!empty($user)) {
            CollectorRole::init($user);
        }

        return $user ?? null;
    }

    public static function findIdentity($id)
    {
        $service = Yii::$app->session->get("service");
        $service = $service . "Identity";
        
        if(method_exists(CollectorUser::class, $service)) {
            $user = self::$service($id);
        }

        if(!empty($user)) {
            CollectorRole::init($user);
        }

        return $user ?? null;
    }

    /*
    public static function tyreFitterPhone($phone)
    {
        $contact = Contact::findByPhone($phone);

        if($contact)
        {
            $user = new User();
            $user->setAttributes($contact->getAttributes(), false);
        }

        return $user ?? null;
    }
    */
}
