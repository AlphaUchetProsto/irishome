<?php

namespace app\modules\chat_bot\models;

use yii\base\Model;
use app\modules\chat_bot\models\ChatBot;
use app\modules\chat_bot\models\Message;

class EntityDialog extends Model
{
    public $id;
    public $chatId;
    public $responsibleId;
    public $responsibleName;
    public $amountMessage;
    public $timeout;

    public function rules()
    {
        return [
            [['responsibleId', 'amountMessage', 'timeout'], 'default', 'value' => 0],
            [['responsibleId', 'amountMessage', 'timeout', 'id', 'chatId', 'responsibleName'], 'safe'],
        ];
    }

    public static function findById(Message $message)
    {
        $bot = new ChatBot();
        $model = new static();

        ['result' => $result] = $bot->request('entity.item.get', [
            'ENTITY' => 'data_chat',
            'filter' => [
                '=NAME' => $message->chatId,
                '>DATE_CREATE' => date('Y-m-d 00:00'),
                '<=DATE_CREATE' => date('Y-m-d 23:59'),
            ],
        ]);

        if(empty($result))
        {
            $batch['create_item'] = $bot->buildCommand('entity.item.add', [
                'ENTITY' => 'data_chat',
                'NAME' => $message->chatId,
            ]);

            $batch['get_item'] = $bot->buildCommand('entity.item.get', [
                'ENTITY' => 'data_chat',
                'filter' => [
                    '=NAME' => $message->chatId,
                    '>DATE_CREATE' => date('Y-m-d 00:00'),
                    '<=DATE_CREATE' => date('Y-m-d 23:59'),
                ],
            ]);

            ['result' => ['result' => ['get_item' => $result]]] = $bot->batchRequest($batch);
        }

        $model->id = $result[0]['ID'];
        $model->responsibleId = $result[0]['PROPERTY_VALUES']['responsibleId'];
        $model->amountMessage = $result[0]['PROPERTY_VALUES']['amountMessage'];
        $model->timeout = $result[0]['PROPERTY_VALUES']['timeout'];
        $model->chatId = $message->chatId;
        $model->responsibleName = $result[0]['PROPERTY_VALUES']['responsibleName'];

        $model->validate();

        return $model;
    }

    public function updateTimeout(Message $message)
    {
        $bot = new ChatBot();
        ['result' => $result] = $bot->request('im.dialog.messages.get', ['DIALOG_ID' => "chat{$this->chatId}"]);

        $extranetUserIndex = collect($result['users'])->search(function ($item){
            return $item['extranet'];
        });

        $extranetUser = collect($result['users'])->get($extranetUserIndex);

        $messages = collect($result['messages'])->filter(function ($item) use($extranetUser) {
            return $item['author_id'] != 0;
        })->values()->toArray();

        $perLastMessage = $messages[1];

        if($perLastMessage['author_id'] == $extranetUser['id'])
        {
            $lastExtranetUserMessage = collect($result['messages'])->filter(function ($item) use($extranetUser) {
                return $item['author_id'] == $extranetUser['id'];
            })->values()->get(0);

            if(date('j', $message->ts) !== date('j', strtotime($lastExtranetUserMessage['date'])))
            {
                $timeout = ($message->ts - strtotime($lastExtranetUserMessage['date'])) - (3600 * 11);
            }
            else
            {
                $timeout = $message->ts - strtotime($lastExtranetUserMessage['date']);
            }

            $this->timeout += $timeout;
            $this->responsibleId = $message->authorId;
            $this->responsibleName = $message->name;
            $this->amountMessage++;

            return $this->save();
        }

        return false;
    }

    public function save()
    {
        $bot = new ChatBot();

        return $bot->request('entity.item.update', [
            'ENTITY' => 'data_chat',
            'ID' =>  $this->id,
            'PROPERTY_VALUES' => [
                'amountMessage' => $this->amountMessage,
                'timeout' => $this->timeout,
                'responsibleId' => $this->responsibleId,
                'responsibleName' => $this->responsibleName,
            ],
        ]);
    }
}