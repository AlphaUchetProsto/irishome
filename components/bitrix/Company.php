<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Company
{
    public static function findByPhone($phone)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchCompanyList(["PHONE" => $phone], array_keys(self::MAP_FIELDS));

        foreach ($generator as $companies)
        {
            if(!empty($companies)) {
                return new static($companies[0]);
            }
        }

        return false;
    }

    public static function findByEmail($email)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchCompanyList(["EMAIL" => $email], array_keys(self::MAP_FIELDS));

        foreach ($generator as $companies)
        {
            if(!empty($companies)) {
                return new static($companies[0]);
            }
        }

        return false;
    }

    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $result = $bx24->getCompany($id);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new static($result);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchCompanyList($filter, array_keys(self::MAP_FIELDS));

        $list = [];

        foreach($generator as $companies)
        {
            foreach($companies as $key => $company)
            {
                $list[] = new static($company);
            }
        }

        return $list;
    }

    public static function getFields()
    {
        $bx24 = Bitrix::BX24init();

        return $bx24->getContactFields();
    }

    public function getCollectFields()
    {
        $fields = [];

        foreach($this->getAttributes() as $var => $value)
        {
            $fieldID = array_search($var, self::MAP_FIELDS);

            if($fieldID !== false && $var !== "id")
            {
                $fields[$fieldID] = $value;
            }
        }

        return $fields;
    }

    public function save()
    {
        $fields = $this->getCollectFields();

        if(!empty($this->id) && !empty($fields))
        {
            $bx24 = Bitrix::BX24init();
            $bx24->updateCompany($this->id, $fields);

            return true;
        }

        return false;
    }
}
