<?php

namespace app\modules\chat_bot\models;


use app\models\bitrix\Bitrix;
use Yii;
use function Symfony\Component\String\u;
use app\modules\chat_bot\models\ChatBot;

class Message
{
    public $message;
    public $messageId;
    public $SourceChat;
    public $numberLine;
    public $extranet;
    public $chatId;
    public $dialogId;
    public $chat_entity_data_2;
    public $authorId;
    public $chatAuthorId;
    public $ts;
    public $name;
    public $last_name;


    public function __construct($data)
    {
        $this->message = $data['data']['PARAMS']['MESSAGE'];
        $this->messageId = $data['data']['PARAMS']['MESSAGE_ID'];
        $this->SourceChat = explode('|', $data['data']['PARAMS']['CHAT_ENTITY_ID'])[0];
        $this->numberLine = explode('|', $data['data']['PARAMS']['CHAT_ENTITY_ID'])[1];
        $this->dialogId = explode('|', $data['data']['PARAMS']['CHAT_ENTITY_DATA_1'])[5];
        $this->extranet = $data['data']['USER']['IS_EXTRANET'];
        $this->chatId = $data['data']['PARAMS']['CHAT_ID'];
        $this->chat_entity_data_2 = $data['data']['PARAMS']['CHAT_ENTITY_DATA_2'];
        $this->authorId = $data['data']['PARAMS']['AUTHOR_ID'];
        $this->chatAuthorId = $data['data']['PARAMS']['CHAT_AUTHOR_ID'];
        $this->ts = $data['ts'];
        $this->name = $data['data']['USER']['NAME'];
        $this->last_name = $data['data']['USER']['LAST_NAME'];
    }

    public function getAll()
    {
        return [
            $this->message,
            $this->messageId,
            $this->SourceChat,
            $this->numberLine,
            $this->extranet,
            $this->chatId,
            $this->dialogId,
            $this->authorId,
            $this->chatAuthorId,
            $this->ts,
            $this->name,
            $this->last_name,
        ];
    }

    public function getTextMessage()
    {
        return $this->message;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getSourceChat()
    {
        return $this->SourceChat;
    }

    public function getNumberLine()
    {
        return $this->numberLine;
    }

    public function getChatId()
    {
        return $this->chatId;
    }
    public function getDialogId()
    {
        return $this->dialogId;
    }
    public function getChatEntityData2()
    {
        return $this->chat_entity_data_2;
    }

    public function isClient()
    {
        return $this->extranet == "Y";
    }

    public function isAuthorChat()
    {
        return $this->chatAuthorId == $this->authorId;
    }

    public function getAuthorId()
    {
        return $this->authorId;
    }

    public function getChatAuthorId()
    {
        return $this->chatAuthorId;
    }

    public function getTimestamp()
    {
        return $this->ts;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getDeal()
    {
        if($this->chat_entity_data_2 == "") return null;
        $entity_data = explode('|', $this->chat_entity_data_2);
        if($entity_data[7] == 0) return null;
        return $entity_data[7];
    }

    public function explodeText(string $message) :array
    {
        $message = collect(explode(" ", $message));

        return  $message->filter(function ($item){return !empty(trim($item));})->toArray();
    }

    public function isFreeGuid()
    {
        $templateArray = collect($this->explodeText(Yii::$app->params['modules']['chat_bot']['message_template']['Бесплатный гайд']));
        $messageArray = $this->explodeText($this->message);

        $intersect = $templateArray->intersect($messageArray);

        $intersectPercent = ($intersect->count() / $templateArray->count()) * 100;

        return $intersectPercent > 85;
    }

    public function getContactID()
    {
        return u($this->chat_entity_data_2)->after('CONTACT|')->before("|")->toString();
    }

    public function getDealID()
    {
        return u($this->chat_entity_data_2)->after('DEAL|')->before("")->toString();
    }

    public function getContactName()
    {
        $webhook = Bitrix::BX24init();
        $result = $webhook->request('crm.contact.get', ['ID' => $this->getContactID()]);

        return $result['NAME'];
    }

    public function getContactResponsibleId()
    {
        $webhook = Bitrix::BX24init();
        $result = $webhook->request('crm.contact.get', ['ID' => $this->getContactID()]);

        return $result['ASSIGNED_BY_ID'];
    }
}