<?php

namespace app\modules\processor_post\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Deal extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $title;
    public $stage_id;
    public $company_id;
    public $contact_id;
    public $assigned_by_id = 10;
    public $method_delivery;
    public $source_id = "STORE";

    use \app\components\bitrix\Deal;

    const MAP_FIELDS = [
        "ID" => "id",
        "TITLE" => "title",
        "CONTACT_ID" => "contact_id",
        "COMPANY_ID" => "company_id",
        "STAGE_ID" => "stage_id",
        "ASSIGNED_BY_ID" => "assigned_by_id",
        "UF_CRM_630714E8D10F5" => "method_delivery",
        "SOURCE_ID" => "source_id"
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, static::MAP_FIELDS);
    }

    public static function setProductRow($dealId, $products)
    {
        $webhook = Bitrix::BX24init();
        if(!empty($products))
        {
            $products_response = [];
            foreach ($products as $product)
            {
                if(!isset($product['sku'])) continue;
                $product_in_bitrix = $webhook->request("crm.product.list", ["filter" => ['PROPERTY_512' => $product['sku']]]);
                if($product_in_bitrix == []) continue;
                $products_response[] = [
                    "PRODUCT_ID" => $product_in_bitrix[0]['ID'],
                    //"PRODUCT_NAME" => $product_in_bitrix[0]["NAME"],
                    "PRICE" => $product["price"],
                    "QUANTITY" => $product["quantity"],
                ];
            }
//            $products = array_map(function ($item) {
//                return [
//                    "PRODUCT_NAME" => $item["name"],
//                    "PRICE" => $item["price"],
//                    "QUANTITY" => $item["quantity"],
//                ];
//            }, $products);

            //$webhook = Bitrix::BX24init();
            $webhook->request("crm.deal.productrows.set", ["ID" => $dealId, "rows" => $products_response]);

            return true;
        }

        return false;
    }
}