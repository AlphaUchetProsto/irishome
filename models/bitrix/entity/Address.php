<?php

namespace app\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;

class Address extends Bitrix implements GeneralBitrixInterface
{
    use \app\components\bitrix\Address;

    /*
    const MAP_FIELDS = [
        "TYPE_ID" => "type_id",
        "ENTITY_TYPE_ID" => "entity_type_id",
        "ENTITY_ID" => "entity_id",
        "ADDRESS_1" => "address_1",
        "ADDRESS_2" => "address_2",
        "CITY" => "city",
        "POSTAL_CODE" => "postal_code",
        "REGION" => "region",
        "PROVINCE" => "province",
        "COUNTRY" => "country",
        "ANCHOR_ID" => "anchor_id",
    ];
    */

    public function __construct($fields)
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }
}
