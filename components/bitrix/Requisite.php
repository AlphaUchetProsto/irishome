<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Requisite
{
    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $requisite = $bx24->request("crm.requisite.get", ["ID" => $id]);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new \app\models\bitrix\Requisite($requisite[0]);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();

        $generator = $bx24->fetchList("crm.requisite.list", [
            "filter" => $filter,
            "select" => array_keys(self::MAP_FIELDS),
        ]);

        $list = [];

        foreach($generator as $requisites)
        {
           foreach($requisites as $requisite)
           {
               $list[] = new static($requisite);
           }
        }

        return $list;
    }

    public static function getFields()
    {
        $bx24 = Bitrix::BX24init();

        return $bx24->request("crm.requisite.fields");
    }

    public function getCollectFields()
    {
        // TODO: Implement getCollectFields() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }
}
