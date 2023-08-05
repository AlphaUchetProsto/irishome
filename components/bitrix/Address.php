<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Address
{
    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $address = $bx24->request("crm.address.list", ["filter" => ["=ENTITY_ID" => $id]]);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new \app\models\bitrix\Address($address[0]);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();

        $generator = $bx24->getList("crm.address.list", [
            "filter" => $filter,
            "select" => array_keys(self::MAP_FIELDS),
        ]);

        $list = [];

        foreach($generator as $addresses)
        {
            foreach($addresses as $address)
            {
                $list[] = new \app\models\bitrix\Address($address);
            }
        }

        return $list;
    }

    public static function getFields()
    {
        // TODO: Implement getFields() method.
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
