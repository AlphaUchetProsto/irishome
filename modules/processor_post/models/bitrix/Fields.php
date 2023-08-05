<?php

namespace app\modules\processor_post\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Fields extends Bitrix
{
    public $fields = [];

    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    public function checkDublicate()
    {
        $webhook = Bitrix::BX24init();
        //$response = $webhook->request('crm.category.list', ['entityTypeId' => 9]);
        //$response = $webhook->request('crm.enum.ownertype');
        $new_contact = false;
        $filteredContact = array_filter($this->fields, function($value){
            return ($value['entity_type_id'] == 3);
        });

        foreach($filteredContact as $field)
        {

            if(($field['field_id'] == 'PHONE' || $field['field_id'] == 'EMAIL') && $field['duplicate'] == true)
            {
                $contact_id = $this->lookForDublicate($field['value'], $field['field_id']);
                if($contact_id) {
                    return $contact_id;
                }

                $new_contact = true;
                break;
            }
        }
        if(!$new_contact)
        {
            return null;
        }
        else
        {
            $params = [];
            foreach($filteredContact as $field) {
                if($field['field_id'] == 'PHONE' || $field['field_id'] == 'EMAIL')
                {
                    $type = 'WORK';
                    if(isset($field['value_type']))
                        $type = $field['value_type'];
                    $params[$field['field_id']] = [['VALUE' => $field['value'], 'TYPE' => $type]];
                }
                else
                    $params[$field['field_id']] =$field['value'];
            }
            $contact_id = $webhook->request('crm.contact.add', ['fields' => $params]);
            return $contact_id;
        }
    }

    public function filterArray($value){
        return ($value == 2);
    }

    public function lookForDublicate($value, $type = 'PHONE')
    {
        $webhook = Bitrix::BX24init();
        $result = $webhook->request("crm.duplicate.findbycomm", [
            "entity_type" => "CONTACT",
            "type" => $type,
            "values" => [$value],
        ]);
        return empty($result) ? false : $result['CONTACT'][0];
    }

    public function setFieldsDeals($contact_id)
    {
        $webhook = Bitrix::BX24init();
        $filteredContact = array_filter($this->fields, function($value){
            return ($value['entity_type_id'] == 2);
        });

        $params['CONTACT_ID'] = $contact_id;
        foreach($filteredContact as $field)
        {
            if(isset($field['base64']))
            {
                $params[$field['field_id']] = ['fileData' => [$field['value'], $field['base64']]];
            }
            else
                $params[$field['field_id']] =$field['value'];
        }

        $dealId = $webhook->request('crm.deal.add', ['fields' => $params]);

        $products_response = [];
        $error = '';
        if(isset($this->fields['products'])) {
            foreach ($this->fields['products'] as $product) {
                $product_in_bitrix = $webhook->request('crm.product.list', ["filter" => ['NAME' => $product['name']]]);
                if ($product_in_bitrix == []) {
                    $error .= $this->setError('Не нашелся продукт', $product);
                    continue;
                }
                if (isset($product['amount'])) {
                    if ($product['quantity'] == 0) {
                        $error .= $this->setError('Не указано количество товара', $product);
                        continue;
                    }
                    $price = $product['amount'] / $product['quantity'];
                } else {
                    if (isset($product['price']) && $product['price'] != $product_in_bitrix[0]['PRICE'])
                        $price = $product['price'];
                    else
                        $price = $product_in_bitrix[0]['PRICE'];
                }

                $products_response[] = [
                    "PRODUCT_ID" => $product_in_bitrix[0]['ID'],
                    "PRICE" => $price,
                    "QUANTITY" => $product["quantity"],
                ];
            }
        }
//        echo '<pre>';
//        print_r($error);
        //$response = $webhook->request("crm.timeline.comment.add", ['fields' => ["ENTITY_ID" => 46, "ENTITY_ID" => 'deal', "COMMENT" => $error]]);
        if($error != '') {
            $response = $webhook->request("crm.livefeedmessage.add", ['fields' => [
                "ENTITYID" => $dealId,
                "ENTITYTYPEID" => 2,
                "MESSAGE" => $error,
                'POST_TITLE' => 'Ошибки']]);
        }
        $response = $webhook->request("crm.deal.productrows.set", ["ID" => $dealId, "rows" => $products_response]);
//        echo '<pre>';
//        print_r($response);
    }

    public function setError($text, $array)
    {
        $answer = "{$text}: \n";
        foreach ($array as $key => $item)
        {
            $answer .= "    {$key} => {$item}\n";
        }
        return  $answer;
    }

    public function setFieldsLeads()
    {
        $webhook = Bitrix::BX24init();
        $filteredContact = array_filter($this->fields, function ($value) {
            return ($value['entity_type_id'] == 1);
        });
        if (empty($filteredContact)) return false;

        foreach ($filteredContact as $field) {
            if($field['field_id'] == 'PHONE' || $field['field_id'] == 'EMAIL')
            {
                $type = 'WORK';
                if(isset($field['value_type']))
                    $type = $field['value_type'];
                $params[$field['field_id']] = [['VALUE' => $field['value'], 'TYPE' => $type]];
            }
            else
                $params[$field['field_id']] = $field['value'];
        }

        $dealId = $webhook->request('crm.lead.add', ['fields' => $params]);
    }
}