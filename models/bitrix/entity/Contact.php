<?php

namespace app\models\bitrix;

use app\components\bitrix\CrmInterface;
use app\components\bitrix\GeneralBitrixInterface;

class Contact extends Bitrix implements GeneralBitrixInterface, CrmInterface
{
    public $id;
    public $contact_type;
    public $name;
    public $last_name;
    public $second_name;
    public $phone = [];
    public $email = [];
    public $password;
    public $companyID;

    use \app\components\bitrix\Contact;

    const MAP_FIELDS = [
        "ID" => "id",
        "NAME" => "name",
        "LAST_NAME" => "last_name",
        "SECOND_NAME" => "second_name",
        "PHONE" => "phone",
        "EMAIL" => "email",
        "COMPANY_ID" => "companyID",
        "TYPE_ID" => "contact_type",
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }

    public function getDeals()
    {
        return \app\models\bitrix\Deal::getList([
            "CONTACT_ID" => $this->id,
            ">=DATE_CREATE" => date('Y-m-d', strtotime('first day of this month')),
            "<=END_DATE_PLAN" => date('Y-m-d', strtotime('last day of this month')),
        ]);
    }

    public function getPhone($number = false)
    {
        return $number !== false ? $this->phone[$number] : $this->phone;
    }

    public function addPhone($phone, $type):void
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $this->phone[] = ["VALUE" => $phone, "TYPE" => $type];
    }
}
