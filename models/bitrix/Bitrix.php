<?php

namespace app\models\bitrix;

use App\Bitrix24\Bitrix24API;
use app\models\logger\DebugLogger;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

abstract class Bitrix extends Model
{
    public function __construct($fields, $map_fields)
    {
        if(!empty($fields)) {
            foreach ($fields as $field_id => $value) {
                if (array_key_exists($field_id, $map_fields) && $this->canGetProperty($map_fields[$field_id])) {
                    $var = $map_fields[$field_id];
                    $this->$var = $value;
                }
            }
        }

        parent::__construct();
    }

    public static function BX24init()
    {
        $webhook = new Bitrix24API(Yii::$app->params['bitrix']['webhook_url']);

        $action = Yii::$app->controller->action->id;
        $logger = DebugLogger::instance($action);

        $webhook->setLogger($logger);

        return $webhook;
    }
}
