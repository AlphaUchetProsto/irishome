<?php

namespace app\models\bitrix;

use yii\base\Exception;
use yii\base\Model;

class DocumentGenerator extends Bitrix
{
    public $id;
    public $title;
    public $number;
    public $template_id;
    public $entity_id;
    public $entity_type_id;
    public $public_url;
    public $download_url_machine;
    public $pdf_url_machine;

    const MAP_FIELDS = [
        "id" => "id",
        "title" => "title",
        "number" => "number",
        "templateId" => "template_id",
        "entityId" => "entity_id",
        "entityTypeId" => "entity_type_id",
        "downloadUrlMachine" => "download_url_machine",
        "pdfUrlMachine" => "pdf_url_machine",
    ];

    public function __construct($fields)
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }

    public static function getList(array $filter)
    {
        $bx24 = self::BX24init();

        $generator = $bx24->getList("crm.documentgenerator.document.list", [
            "filter" => $filter,
        ]);

        $list = [];
        $commands = [];

        foreach ($generator as $documents)
        {
            foreach($documents["documents"] as  $document)
            {
                $document = new static($document);
                $commands[] = $bx24->buildCommand("crm.documentgenerator.document.enablepublicurl", ["id" => $document->id, "status" => 1]);
                $list[] = $document;
            }
        }

        $public_urls = $bx24->batchRequest($commands);

        foreach ($list as $key => $document)
        {
            $document->public_url = $public_urls[$key]["publicUrl"];
        }

        return empty($list) ? false : $list;
    }

    public static function getTemplates()
    {
        $bx24 = self::BX24init();

        return $bx24->request("crm.documentgenerator.template.list");
    }

    public static function generate($template_id, $entity_id, $entity_type_id, $values = [])
    {
        $params = [
            "templateId" => $template_id,
            "entityTypeId" => $entity_type_id,
            "entityId" => $entity_id,
            "values" => $values,
        ];

        $bx24 = self::BX24init();
        $response = $bx24->request("crm.documentgenerator.document.add", $params);

        return new static($response["document"]);
    }
}