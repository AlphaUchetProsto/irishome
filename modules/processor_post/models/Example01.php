<?php
$array = array(
    'url' => 'https://foodnative.ru/CRM/web/processor-post/main/index',
    'get' =>
        array(
        "token" => "aedf15db-9654-46da-8cb6-a1ddbfa961ca",
        "name" =>  "WordPress",
        ),

    'post' =>
        array(
            'Адрес' => array(
                'value' => "г. Москва, Комсомольская площадь, 5",
                'entity_type_id' => 2, // сделка,
                'field_id' => 'UF_CRM_1669799465',
            ),
            'Имя' => array(
                'value' => "Иван",
                'entity_type_id' => 3, // контакт,
                'field_id' => 'NAME',
            ),
            'Фамилия' => array(
                'value' => "Иванов",
                'entity_type_id' => 3, // контакт,
                'field_id' => 'LAST_NAME',
            ),
            'Компания' => array(
                'value' => "Рога и копыта",
                'entity_type_id' => 2, // сделка,
                'field_id' => 'UF_CRM_1669799053771',
            ),
            'Телефон' => array(
                'value' => "+79999999998",
                'entity_type_id' => 3, // контакт,
                'field_id' => 'PHONE',
                'duplicate' => true,
            ),
            'Почта' => array(
                'value' => "123@mail.ru",
                'entity_type_id' => 3, // контакт,
                'field_id' => 'EMAIL',
                'duplicate' => false,
            ),
            'Комментарии' => array(
                'value' => "Какие то комментарии",
                'entity_type_id' => 2, // сделка,
                'field_id' => 'COMMENTS',
            ),
            'Название сделки' => array(
                'value' => "Тестовая сделка",
                'entity_type_id' => 2, // сделка,
                'field_id' => 'TITLE',
            ),
            'products' => array(
                array(
                    "name"=> "35 хафтаси",
                    "quantity"=> "2",
                ),
                array(
                    "name"=> "Акшам детокс",
                    "quantity"=> "5",
                    'price' => '1000',
                ),
                array(
                    "name"=> "Зейтин #1",
                    "quantity"=> "10",
                    'price' => '1000',
                    'amount' => '4000',
					),
                array(
                    "name"=> "Акшам детоксcc",
                    "quantity"=> "5",
                    'price' => '1000',
                ),
                array(
                    "name"=> "Зейтин #1",
                    'price' => '1000',
                    'amount' => '4000',
                ),

            ),
        )
);

$url = "{$array['url']}?token={$array['get']['token']}&name={$array['get']['name']}";
$cmd = "curl -X POST -H 'Content-Type: application/x-www-form-urlencoded'";

$cmd.= " -d '" . http_build_query($array['post']) . "' '" . $url . "'";

//$cmd.= "'  --insecure";

$cmd .= " > /dev/null 2>&1 &";
exec($cmd);
