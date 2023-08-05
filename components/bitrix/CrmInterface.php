<?php

namespace app\components\bitrix;

interface CrmInterface
{
    public static function findByPhone($phone);
    public static function findByEmail($email);
}
