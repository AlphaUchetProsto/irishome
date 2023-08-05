<?php

namespace app\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;

class Requisite extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $preset_id;
    public $company_full_name;
    public $last_name;
    public $name;
    public $second_name;
    public $director;
    public $company_name;

    const MAP_FIELDS = [
        "ID" => "id",
        "PRESET_ID" => "preset_id",
        "RQ_COMPANY_FULL_NAME" => "company_full_name",
        "RQ_DIRECTOR" => "director",
        "RQ_COMPANY_NAME" => "company_name",
    ];

    use \app\components\bitrix\Requisite;

    public function __construct($fields)
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }
}