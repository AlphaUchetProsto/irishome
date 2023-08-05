<?php

namespace app\components\bitrix;

use App\Bitrix24\Bitrix24APIException;
use app\models\bitrix\Bitrix;

trait Deal
{
    public static function findById($id)
    {
        $bx24 = Bitrix::BX24init();

        try {
            $result = $bx24->getDeal($id);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return new static($result);
    }

    public static function getList(array $filter)
    {
        $bx24 = Bitrix::BX24init();
        $generator = $bx24->fetchDealList($filter, array_keys(self::MAP_FIELDS));

        $list = [];

        foreach($generator as $deals)
        {
            foreach($deals as $key => $deal)
            {
                $list[] = new static($deal);
            }
        }

        return $list;
    }

    public static function getFields()
    {
        $bx24 = Bitrix::BX24init();

        return $bx24->getDealFields();
    }

    public function getCollectFields()
    {
        $fields = [];

        foreach($this->getAttributes() as $var => $value)
        {
            $fieldID = array_search($var, self::MAP_FIELDS);

            if($fieldID !== false && $var !== "id" && !empty($value))
            {
                $fields[$fieldID] = $value;
            }
        }

        return $fields;
    }

    public static function create($deal)
    {
        $fields = $deal->getCollectFields();

        try {
            $bx24 = Bitrix::BX24init();
            $response = $bx24->addDeal($fields);
        }catch (Bitrix24APIException $e) {
            return false;
        }

        return $response ?? false;
    }

    public function changeStage($stage_name)
    {
        if(array_key_exists($stage_name, self::MAP_STAGES)) {
            $this->stage_id = self::MAP_STAGES[$stage_name];

            return $this->save();
        }

        return false;
    }

    public function save()
    {
        $fields = $this->getCollectFields();

        if(!empty($this->id) && !empty($fields))
        {
            $bx24 = Bitrix::BX24init();
            $bx24->updateDeal($this->id, $fields);

            return true;
        }

        return false;
    }
}
