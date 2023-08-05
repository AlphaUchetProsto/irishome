<?php

namespace app\models\user;

use app\models\bitrix\Deal;
use yii\base\Model;

class Balance extends Model
{
    public static function scoreBalance(Array $deals)
    {
        $balance = 0;

        foreach ($deals as $key => $deal)
        {
            if(is_a($deal, "app\models\bitrix\Deal"))
            {
                $balance += $deal->price;
            }
        }

        return $balance;
    }

    public static function scoreFee($balance, $percent_fee)
    {
        return $balance > 0 ? ($balance / 100) * $percent_fee : $balance;
    }
}
