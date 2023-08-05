<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Lead
{
    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $result = $bx24->getLead($id);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new static($result);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchLeadList($filter, array_keys(self::MAP_FIELDS));

        $list = [];

        foreach($generator as $leads)
        {
            foreach($leads as $key => $lead)
            {
                $list[] = new static($lead);
            }
        }

        return $list;
    }

    public static function getFields()
    {
        $bx24 = Bitrix::BX24init();

        return $bx24->getLeadFields();
    }

    public function getCollectFields()
    {
        $fields = [];

        foreach($this->getAttributes() as $var => $value)
        {
            $fieldID = array_search($var, self::MAP_FIELDS);

            if($fieldID !== false && $var !== "id" && !is_null($value) && !empty($value))
            {
                $fields[$fieldID] = $value;
            }
        }

        return $fields;
    }

    public static function create($lead)
    {
        $fields = $lead->getCollectFields();

        try {
            $bx24 = Bitrix::BX24init();
            $response = $bx24->addLead($fields);
            $response = static::findById($response);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return $response ?? false;
    }

    public function save()
    {
        $fields = $this->getCollectFields();

        if(!empty($this->id) && !empty($fields))
        {
            $bx24 = Bitrix::BX24init();
            $bx24->updateLead($this->id, $fields);

            return true;
        }

        return false;
    }

    public static function findDuplicate($phone)
    {
        $webhook = self::BX24init();

        $webhook->request("crm.duplicate.findbycomm", [
            "entity_type" => "LEAD",
            "type" => "PHONE",
            "values" => [$phone]
        ]);

        return $webhook->getLastResponse()["result"]["LEAD"];
    }
}