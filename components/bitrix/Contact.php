<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Contact
{
    public static function findByPhone($phone)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchContactList(["PHONE" => $phone], array_keys(self::MAP_FIELDS));

        foreach ($generator as $contacts)
        {
            if(!empty($contacts)) {
                return new static($contacts[0]);
            }
        }

        return null;
    }

    public static function findByEmail($email)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchContactList(["EMAIL" => $email], array_keys(self::MAP_FIELDS));

        foreach ($generator as $contacts)
        {
            if(!empty($contacts)) {
                return new static($contacts[0]);
            }
        }

        return false;
    }

    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $result = $bx24->getContact($id);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new static($result);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchContactList($filter, array_keys(self::MAP_FIELDS));

        $list = [];

        foreach($generator as $contacts)
        {
            foreach($contacts as $key => $contact)
            {
                $list[] = new static($contact);
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

            if($fieldID !== false && $var !== "id" && !is_null($value) && !empty($value))
            {
                $fields[$fieldID] = $value;
            }
        }

        return $fields;
    }

    public static function create($contact)
    {
        $duplicate = static::findDuplicate($contact->getPhone(0)["VALUE"]);

        if(!empty($duplicate))
        {
            return static::findById($duplicate[0]);
        }

        $fields = $contact->getCollectFields();

        try {
            $bx24 = Bitrix::BX24init();
            $response = $bx24->addContact($fields);
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
            $bx24->updateContact($this->id, $fields);

            return true;
        }

        return false;
    }

    public static function findDuplicate($phone)
    {
        $webhook = self::BX24init();

        $webhook->request("crm.duplicate.findbycomm", [
            "entity_type" => "CONTACT",
            "type" => "PHONE",
            "values" => [$phone]
        ]);

        return $webhook->getLastResponse()["result"]["CONTACT"];
    }
}
