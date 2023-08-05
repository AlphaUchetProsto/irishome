<?php

namespace app\models\user;

use app\models\collector\CollectorRole;
use Yii;
use yii\base\Model;


class LoginForm extends Model
{
    public $phone;
    public $password;

    private $_user = false;

    public function rules()
    {
        return [
            [['phone', 'password'], 'required'],
            ["phone", "filter", "filter" => function($value){
                return preg_replace('/[^0-9+]/', '', $value);
            }],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'phone' => 'Телефон',
            'password' => 'Пароль',
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неправильный логин или пароль.');
            }
        }
    }

    public function login()
    {
        if ($this->validate() && $this->getUser())
        {
            CollectorRole::init($this->getUser());

            return Yii::$app->user->login($this->getUser(), 3600*24*30);
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByPhone($this->phone);
        }

        return $this->_user;
    }
}
