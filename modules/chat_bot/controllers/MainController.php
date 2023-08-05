<?php

namespace app\modules\chat_bot\controllers;

use app\models\logger\CleanerLogger;
use Tightenco\Collect\Support\Collection;
use yii\web\Controller;
use Yii;
use app\models\logger\DebugLogger;
use app\models\bitrix\Bitrix;
use app\modules\chat_bot\models\Message;
use app\modules\chat_bot\models\ChatBot;
use app\modules\chat_bot\models\EntityDialog;

class MainController extends Controller
{
    public function beforeAction($action)
    {
        CleanerLogger::clear();

        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function actionInstall()
    {
        $post_data = Yii::$app->request->post();
        $chat_bot = new ChatBot($post_data["auth"]);

        $result = $chat_bot->request("imbot.register", [
            'CODE' => 'uchet_bot',
            'TYPE' => 'O',
            'EVENT_MESSAGE_ADD' => "https://sekvid.ru/for_del/webhook_irishome_7QXVWi/web/chat-bot/main/add-message?token=098a420d-3548-eeae-5329-c004f43e93d6",
            'EVENT_WELCOME_MESSAGE' => "https://sekvid.ru/for_del/webhook_irishome_7QXVWi/web/chat-bot/main/join-chat?token=098a420d-3548-eeae-5329-c004f43e93d6",
            "EVENT_BOT_DELETE" => "https://sekvid.ru/for_del/webhook_irishome_7QXVWi/web/chat-bot/main/close-chat?token=098a420d-3548-eeae-5329-c004f43e93d6",
            'OPENLINE' => 'Y',
            'PROPERTIES' => [
                'NAME' => "UchetBot",
                'WORK_POSITION' => "Помощник руководителя",
                'COLOR' => "RED",
            ]
        ]);

        $logger = DebugLogger::instance("install");
        $logger->save($_REQUEST, $chat_bot, "Пришедшие данные");
        $logger->save($result, $result, "Зарегистрированный бот");
    }

    public function actionJoinChat()
    {
        $post_data = Yii::$app->request->post();

        $logger = DebugLogger::instance("join_chat");
        $logger->save($post_data , $post_data, "actionJoinChat");
    }

    public function actionIndex()
    {
        $post_data = Yii::$app->request->post();

        $logger = DebugLogger::instance("join_chat");
        $logger->save($post_data , $post_data, "actionJoinChat");
    }


    public function actionAddMessage()
    {
        $post_data = Yii::$app->request->post();

        $logger = DebugLogger::instance("add_message");
        $logger->save($post_data, $post_data, "Данные запроса");

        $bot = new ChatBot();
        $message = new Message($post_data);

        if ($bot->getCountMember($message->chatId) == 2)
        {
            $logger->save("Переводить нужно", "Переводить нужно", "Нужно ли переводить?");

            $responsibleId = $message->getContactResponsibleId();

            $logger->save($responsibleId, $responsibleId, "ID ответственного");

            if($responsibleId == 13128 || $responsibleId == 15010)
            {
                $logger->save("В очередь", "В очередь", "Куда переводить?");
                $response = $bot->transferQueue($message->chatId);
                $logger->save($response, $response, "Ответ на перевод");
            }
            else
            {
                $logger->save("На ответственного", "На ответственного", "Куда переводить?");
                $response = $bot->transferToResponsible($message->chatId, $responsibleId);
                $logger->save($response, $response, "Ответ на перевод");
            }
        }

        if(!$message->isClient())
        {
            $dialog = EntityDialog::findById($message);
            $dialog->updateTimeout($message);
        }

        return 200;
    }



    public function actionCloseChat()
    {
        $bot = new ChatBot();
        $response = $bot->request('entity.item.get', ['ENTITY' => 'data_chat']);
        dd($response);

//        $dialog = EntityDialog::findById($message);
//        $dialog->updateTimeout($message);
//        $dialog->updateCountMessage();
//        dd($dialog);


//        $response = $bot->request('entity.add', ['ENTITY' => 'data_chat', 'NAME' => 'Данные чатов']);
//        dd($response);

//        $response = $bot->request('entity.item.property.add', [
//            'ENTITY' => 'data_chat',
//            'PROPERTY' => 'responsibleId',
//            'NAME' => 'Ответственный',
//        ]);
//
//        $response = $bot->request('entity.item.property.add', [
//            'ENTITY' => 'data_chat',
//            'PROPERTY' => 'amountMessage',
//            'NAME' => 'Количество сообщений',
//        ]);
//
//        $response = $bot->request('entity.item.property.add', [
//            'ENTITY' => 'data_chat',
//            'PROPERTY' => 'timeout',
//            'NAME' => 'Ожидание ответа',
//        ]);

/*        dd($response);*/
    }
}
