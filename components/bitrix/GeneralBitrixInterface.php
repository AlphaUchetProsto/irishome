<?php

namespace app\components\bitrix;

interface GeneralBitrixInterface
{
    public static function findById($id);
    public static function getList(array $filter);
    public static function getFields();
    public function getCollectFields();
    public function save();
}
