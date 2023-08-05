<?php

namespace app\models\bitrix;

use app\components\bitrix\CrmInterface;
use app\components\bitrix\GeneralBitrixInterface;

class Lead extends Bitrix implements GeneralBitrixInterface
{
    const MAP_FIELDS = [];

    use \app\components\bitrix\Lead;
}
