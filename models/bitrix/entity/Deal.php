<?php

namespace app\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;

class Deal extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $title;
    public $stage_id;
    public $company_id; // ID Компании
    public $contact_id; // ID Контакта

    use \app\components\bitrix\Deal;

    const MAP_FIELDS = [
        "ID" => "id",
        "TITLE" => "title",
        "CONTACT_ID" => "contact_id",
        "COMPANY_ID" => "company_id",
        "STAGE_ID" => "stage_id",
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }
}