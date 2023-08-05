<?php

namespace app\models\bitrix;

use app\components\bitrix\CrmInterface;
use app\components\bitrix\GeneralBitrixInterface;

class Company extends Bitrix implements GeneralBitrixInterface, CrmInterface
{
    public $id;
    public $title;
    public $type;
    public $phone;

    use \app\components\bitrix\Company;

    const MAP_FIELDS = [
        "ID" => "id",
        "TITLE" => "title",
        "PHONE" => "phone",
        "COMPANY_TYPE" => "type",
    ];

    public function __construct($fields)
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }
}
