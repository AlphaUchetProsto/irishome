<?php

namespace app\modules\chat_report\models;

use Tightenco\Collect\Support\Collection;
use yii\base\Model;
use app\modules\chat_bot\models\ChatBot;
use app\modules\chat_bot\models\EntityDialog;

class FilterModel extends Model
{
    public $dateFrom;
    public $dateTo;
    public $responsibleIds;

    public function attributeLabels()
    {
        return [
            'dateFrom' => 'Дата С',
            'dateTo' => 'Дата По',
        ];
    }

    public function rules()
    {
        return [
            [['dateFrom', 'dateTo', 'responsibleIds'], 'string'],
        ];
    }

    public function filter()
    {
        $filter = [];

        if(!empty($this->dateFrom)) $filter['>DATE_CREATE'] = $this->dateFrom;
        if(!empty($this->dateTo)) $filter['<=DATE_CREATE'] = $this->dateTo;

        $bot = new ChatBot();

        $entityId = 0;
        $finish = false;
        $data = [];

        while (!$finish)
        {
            $filter['>ID'] = $entityId;

            ['result' => $result] = $bot->request('entity.item.get', [
                'ENTITY' => 'data_chat',
                'filter' => $filter,
                'sort' => ['ID' => 'ASC'],
                'start' => -1,
            ]);

            if(count($result) > 0)
            {
                $entityId = $result[count($result) - 1]['ID'];

                foreach ($result as $item)
                {
                    if(!empty($item['PROPERTY_VALUES']['responsibleName']))
                    {
                        $model = new EntityDialog();
                        $model->responsibleId = $item['PROPERTY_VALUES']['responsibleId'];
                        $model->amountMessage = $item['PROPERTY_VALUES']['amountMessage'];
                        $model->timeout = $item['PROPERTY_VALUES']['timeout'];
                        $model->responsibleName = $item['PROPERTY_VALUES']['responsibleName'];

                        $data[] = $model;
                    }
                }
            }
            else
            {
                $finish = true;
            }
        }

        $data = collect($data)->groupBy(function ($item){
            return $item->responsibleName;
        })->toArray();

        foreach ($data as $name => &$dialogs)
        {
            $sumTimeout = collect($dialogs)->sum('timeout');
            $sumMessages = collect($dialogs)->sum('amountMessage');

            $dialogs = $sumTimeout / $sumMessages;
        }

        return $data;
    }
}
