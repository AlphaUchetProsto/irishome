<?php

namespace app\models\user;

use app\models\collector\CollectorUser;
use Yii;
use yii\base\Model;
use yii\web\IdentityInterface;

class User extends Model implements IdentityInterface
{
    public $id;
    public $name;
    public $last_name;
    public $second_name;
    public $password;
    public $phone;
    public $email;
    public $percent_fee;

    public static function findIdentity($id)
    {
        return CollectorUser::findIdentity($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByPhone($phone)
    {
        return CollectorUser::findByPhone($phone);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return true;
    }

    public function validateAuthKey($authKey)
    {
        return true;
    }

    public function validatePassword($password)
    {
        return $this->password === $password;
    }
}
