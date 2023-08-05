<?php

namespace app\modules\processor_post\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Contact extends Bitrix
{
    public $fields = [];

    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    public function checkDublicate()
    {
        $webhook = Bitrix::BX24init();
        $response = $webhook->request('crm.category.list');
        echo '<pre>';
        print_r($response);
    }

}