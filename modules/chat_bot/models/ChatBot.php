<?php

namespace app\modules\chat_bot\models;

use App\HTTP\HTTP;
use Yii;
use yii\base\Model;
use app\modules\chat_bot\models\Message;
use app\modules\chat_bot\models\bitrix\Lead;
use ClickHouse\Client as ClickHouse;
use function Cassandra\Type;
use function Symfony\Component\String\u;

class ChatBot extends Model
{
    private $access_token;
    private $refresh_token;
    private $client_endpoint;
    private $http;
    private $client_id;
    private $client_secret;

    private static $config_path = '/chat_bot/config/config.php';

    public function __construct($params = [])
    {
        static::$config_path = Yii::getAlias("@modules") . static::getConfigPath();
        $appsConfig = static::getConfig();

        $params = collect($params)->mapWithKeys(function ($item, $key){
            return [mb_strtolower($key) => $item];
        });

        $this->http = new HTTP();
        $this->http->throttle = 2;
        $this->http->useCookies = false;

        if($params->has("auth_id"))
        {
            $this->access_token = $params["auth_id"] ?? $appsConfig["Доступы"]["access_token"];
            $this->refresh_token = $params["refresh_id"] ?? $appsConfig["Доступы"]["refresh_token"];
        }
        else
        {
            $this->access_token = $params["access_token"] ?? $appsConfig["Доступы"]["access_token"];
            $this->refresh_token = $params["refresh_token"] ?? $appsConfig["Доступы"]["refresh_token"];
        }

        $this->client_id = $appsConfig["Доступы"]["client_id"];
        $this->client_secret = $appsConfig["Доступы"]["client_secret"];
        $this->client_endpoint = Yii::$app->params['bitrix']["rest_url"];

        parent::__construct();
    }

    public static function getConfigPath()
    {
        return '/chat_bot/config/config.php';
    }

    private static function getConfig()
    {
        return require static::$config_path;
    }

    public function request($method, $params = [])
    {
        $url = "{$this->client_endpoint}/{$method}.json";
        $params["auth"] = $this->access_token;

        $response = $this->http->request($url, "POST", $params);

        if(isset($response["error"]) && $response["error"] == "expired_token")
        {
            $this->refreshToken();
            $params["auth"] = $this->access_token;

            $response = $this->http->request($url, "POST", $params);
        }

        return $response;
    }

    public function buildCommand($method, $params = [])
    {
        $command = "{$method}";

        if(!empty($params))
        {
            $command .= "?" . http_build_query($params);
        }

        return $command;
    }

    public function batchRequest($commands, $halt = true)
    {
        $url = "{$this->client_endpoint}/batch";

        $response = $this->http->request($url, "POST", ["cmd" => $commands, "halt" => $halt, 'auth' => $this->access_token]);


        if(isset($response["error"]) && $response["error"] == "expired_token")
        {
            $this->refreshToken();

            $response = $this->batchRequest($commands, $halt);
        }

        return $response;
    }

    public function refreshToken():void
    {
        $params = [
            "grant_type" => "refresh_token",
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "refresh_token" => $this->refresh_token,
        ];

        $response = $this->http->request("https://oauth.bitrix.info/oauth/token/", "POST", $params);

        $this->refresh_token = $response["refresh_token"];
        $this->access_token = $response["access_token"];

        $this->updateConfig();
    }

    public function updateConfig()
    {
        $appsConfig = static::getConfig();

        if(!empty($appsConfig))
        {
            foreach ($appsConfig["Доступы"] as $key => &$value)
            {
                if($this->canGetProperty($key))
                {
                    $appsConfig["Доступы"][$key] = $this->$key;
                }
            }

            $appsConfig = var_export($appsConfig, true);

            file_put_contents(static::$config_path, "<?php\n return {$appsConfig};\n");
        }
    }

    public function getDateStartDialog($chat_id, $authorId)
    {
        $prev_id = null;
        $finish = false;
        while(!$finish) {
            $messages = $this->request("im.dialog.messages.get", ["DIALOG_ID" => "chat{$chat_id}", 'LIMIT' => 50, 'LAST_ID' => $prev_id]);
            if($messages['result']['messages'] == [])  $finish = true;
            foreach ($messages['result']['messages'] as $message) {
                if ($message['text'] == "[USER={$authorId}][/USER] начал работу с диалогом" || $message['text'] == "[USER={$authorId}][/USER] начала работу с диалогом" ||
                strrpos($message['text'],"переадресовал диалог на [USER={$authorId}][/USER]") || strrpos($message['text'],"переадресовалa диалог на [USER={$authorId}][/USER]"))
                    return strtotime($message['date']);
                if($prev_id == $message['id'])
                    $finish = true;
                $prev_id = $message['id'];
            }
        }
        return null;
    }

    public function getDateStartDialog2($chat_id, $authorId)
    {
        $prev_id = null;
        $finish = false;
        while(!$finish) {
            $messages = $this->request("im.dialog.messages.get", ["DIALOG_ID" => "chat{$chat_id}", 'LIMIT' => 50, 'LAST_ID' => $prev_id]);
            if($messages['result']['messages'] == [])  $finish = true;
            foreach ($messages['result']['messages'] as $message) {
                if($prev_id == $message['id'])
                    $finish = true;
                $prev_id = $message['id'];
            }
        }
        return null;
    }

    public function searchStartMessage($chat_id, $authorId, $message_id)
    {
        $first_id = 0;
        $finish = false;
        while(!$finish) {
            $messages = $this->request("im.dialog.messages.get", ["DIALOG_ID" => "chat{$chat_id}", 'LIMIT' => 20, 'FIRST_ID' => $first_id]);
            foreach ($messages['result']['messages'] as $message) {
                if ($message['id'] == $message_id )
                    return false;
                if ($message['author_id'] == $authorId)
                    return true;
                if($first_id == $message['id'])
                    $finish = true;
                $first_id = $message['id'];
            }
        }
        return null;
    }

    public function setEntityData($message)
    {

        $paided = 'false';

        //$deal = $this->request('crm.deal.get', ['ID' => $message->getDeal()]);
        $dateStart = $this->getDateStartDialog($message->getChatId(), $message->getChatAuthorId());

//        echo '<pre>';
//        print_r($deal);
//        die;
//        if(isset($deal['result']) && $deal['result']['STAGE_SEMANTIC_ID'] == 'S')
//        {
//
//            $deal_finish = strtotime($deal['result']['MOVED_TIME']);
//            if($deal_finish < $dateStart)
//                $paided = 'true';
//
//
//        }
        $result = $this->request('imbot.dialog.get', ['DIALOG_ID' => 'chat'.$message->getchatId()]);
        if($result['result']['entity_data_2'] != "")
        {
            $contact = explode('|',$result['result']['entity_data_2']);
            if(isset($contact[5]) && $contact[5] != 0)
            {
                $deals = $this->request('crm.deal.list', ['filter' => ['CONTACT_ID' => $contact[5]]]);
                if($deals['result'] != [])
                    foreach ($deals['result'] as $deal)
                    {
                        if($deal['STAGE_SEMANTIC_ID'] == 'S')
                        {
                            $deal_finish = strtotime($deal['MOVED_TIME']);
                            if($deal_finish < $dateStart)
                                $paided = 'true';
                        }
                    }
            }
        }
//        print_r($paided);
//        die;

        $message_id = $message->getMessageId();
        $chat_id = $message->getChatId();
        $dialog_id = $message->getDialogId();
        $chatAuthorId = $message->getChatAuthorId();
        $authorId = $message->getAuthorId();
        $date = $message->getTimestamp();
//        $name = $message->getName();

        $result = $this->request('entity.item.add', ['ENTITY' => 'report_chat', 'NAME' => $message_id,
           'PROPERTY_VALUES' => [
               'chat_id' => $chat_id,
               'dialog_id' => $dialog_id,
               'responsible_id' => $chatAuthorId,
               'paided'=> $paided,
               'date_dispatch' => $date,
               'author_id' => $authorId,
               'start_dialog' => $dateStart,
           ]
        ]);


    }

    public function getHistory($chat_id, $params = [])
    {
        if(!isset($params['COUNT']))
            $params['COUNT'] = 20;
        if(!isset($params['LAST_ID']))
            $params['LAST_ID'] = null;
        if(!isset($params['FIRST_ID']))
            $params['FIRST_ID'] = null;
        if(!isset($params['LIMIT'])) {
            if($params['COUNT'] <= 50)
                $params['LIMIT'] = $params['COUNT'];
            else
                $params['LIMIT'] = 50;
        }
        $count = 0;
        if($params['COUNT'] < 1) return [];
        $answer = [];
        $finish = false;
        while(!$finish)
        {
            $messages = $this->request("im.dialog.messages.get", ["DIALOG_ID" => "chat{$chat_id}", 'LIMIT' => $params['LIMIT'],
                "LAST_ID" => $params['LAST_ID'], 'FIRST_ID' => $params['FIRST_ID']]);

            if($messages['result']['messages'] == []) return $answer;

            foreach ($messages['result']['messages'] as $message)
            {
                if($count == $params['COUNT'])
                {
                    $finish = true;
                    break;
                }

                if($params['LAST_ID'] != null && $params['FIRST_ID'] != null) {
                    $params['FIRST_ID'] = $message['id'];
                    if($params['FIRST_ID'] == $params['LAST_ID'])
                    {
                        $finish = true;
                        break;
                    }
                }
                else
                {
                    if ($params['LAST_ID'] != null)
                        $params['LAST_ID'] = $message['id'];
                    if ($params['FIRST_ID'] != null)
                        $params['FIRST_ID'] = $message['id'];
                    if($params['LAST_ID'] == null && $params['FIRST_ID'] == null)
                        $params['LAST_ID'] = $message['id'];
                }
                $answer[] = $message;
                $count++;
            }
            if($params['LAST_ID'] != null && $params['FIRST_ID'] != null)
                $finish = true;
        }
        return $answer;
    }

    public function setOperator($message)
    {
        if($message->getNumberLine() == '50')
        {
            return $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
        }
        if($message->getSourceChat() == 'wz_whatsapp_c098a420d3548eeae5329c004f43e93d6')
        {
            $deal = $this->request('crm.deal.get', ['ID' => $message->getDeal()]);
            //Если нет сделки переводим на оператора или сделка не из воронки "тест"
            if(!isset($deal['result']) || $deal['result']['CATEGORY_ID'] != 8)
            {
                $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
            }
            //Если есть "Исходящее сообщение, автор: Битрикс24"
            elseif(preg_match('/Исходящее сообщение, автор: Битрикс24/',$message->getTextMessage())) {
                $PCREpattern  =  '/\r\n|\r|\n/u';
                $text = preg_replace($PCREpattern, '', $message->getTextMessage());
                $text = str_replace(' ','',$text);
                $have_message = false;
                $start_messages = $this->request('lists.element.get', ['IBLOCK_TYPE_ID' => 'lists', 'IBLOCK_ID' => 44]);
                foreach ($start_messages['result'] as $start_message)
                {
                    $start_text = str_replace(' ','',str_replace('\n','',$start_message['NAME']));
                    //Если это стартовое сообщение
                    if(strrpos($text, $start_text) !== false)
                    {
                        //Проверяем есть ли до этого сообщения ещё сообщения от пользователя
                        //Если это первое сообщение в чате - закрываем диалог и сделку переводим на стадию "ждём"
                        if(!$this->searchStartMessage($message->getChatId(), $message->getAuthorId(), $message->getMessageId()))
                        {
                            $this->request("imopenlines.bot.session.finish", ["CHAT_ID" => $message->getChatId()]);
                            $this->request("crm.deal.update", ['ID' => $deal['result']['ID'], 'fields' => ['STAGE_ID' => 'C8:UC_1CXAUF']]);
                        }
                        //Если уже есть сообщение в чате от пользователя - Перекинуть сделку, привязанную к этому чату на стадию "Взять в работу" воронки "тест"
                        else
                        {
                            //if($deal['result']['STAGE_ID'] != 'C8:UC_3VIZL8' && $deal['result']['STAGE_ID'] != 'C8:UC_ZD4DQH' && $deal['result']['STAGE_ID'] != 'C8:UC_TPF3P1')
                            {
                                if (preg_match('/russian/', $deal['result']['TITLE'])) {
                                    $this->request("crm.deal.update", ['ID' => $deal['result']['ID'], 'fields' => ['STAGE_ID' => 'C8:UC_ZD4DQH']]); //"Взять в работу
                                } else {
                                    $this->request("crm.deal.update", ['ID' => $deal['result']['ID'], 'fields' => ['STAGE_ID' => 'C8:UC_TPF3P1']]); //"Взять в работу World"
                                }
                            }

                            //!!!!
                            $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
                            //!!!!
                        }
                        $have_message = true;
                        break;
                    }
                }
                //Если не выполнено ни одно условие выше
                if(!$have_message)
                {
                    //Если сообщение начинается с "Исходящее сообщение, автор: Битрикс24" и сделка находится в стадии "ждём" воронки "тест" - закрыть диалог
                    if($deal['result']['STAGE_ID'] == 'C8:UC_1CXAUF')
                    {
                        $this->request("imopenlines.bot.session.finish", ["CHAT_ID" => $message->getChatId()]);
                    }
                    //Если сообщение начинается с "Исходящее сообщение, автор: Битрикс24" и сделка находится в стадии "в работе" воронки "тест" - Перевести диалог на ответственного за сделку
                    elseif($deal['result']['STAGE_ID'] == 'C8:UC_3VIZL8')
                    {
                        if($deal['result']['ASSIGNED_BY_ID'] != '1')
                            $this->request("imopenlines.bot.session.transfer", ["CHAT_ID" => $message->getChatId(), 'USER_ID' => $deal['result']['ASSIGNED_BY_ID'], 'LEAVE' => 'N']);
                        else
                            $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);

                    }
                    else
                    //!!!!
                    $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
                    //!!!!
                }
            }
            else
            {
                if($deal['result']['STAGE_ID'] == 'C8:UC_1CXAUF' || $deal['result']['STAGE_ID'] == 'C8:NEW')
                {
                    if (preg_match('/russian/', $deal['result']['TITLE'])) {
                        $this->request("crm.deal.update", ['ID' => $deal['result']['ID'], 'fields' => ['STAGE_ID' => 'C8:UC_ZD4DQH']]); //"Взять в работу
                    } else {
                        $this->request("crm.deal.update", ['ID' => $deal['result']['ID'], 'fields' => ['STAGE_ID' => 'C8:UC_TPF3P1']]); //"Взять в работу World"
                    }
                    $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
                }
                elseif($deal['result']['STAGE_ID'] == 'C8:UC_ZD4DQH')
                {
                    $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
                }
                else
                //!!!!
                $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
                //!!!!
            }

        }
        else
        {
            return $this->request("imopenlines.bot.session.operator", ["CHAT_ID" => $message->getChatId()]);
        }
    }

    public function userAdd($chatID, $userIds)
    {
        return $this->request('imbot.chat.user.add', ['CHAT_ID' => $chatID, 'USERS' => $userIds]);
    }

    public function userList($chatID)
    {
        ['result' => $result] = $this->request('imbot.chat.user.list', ['CHAT_ID' => $chatID]);

        return $result;
    }

    public function getCountMember($chatID)
    {
        return count($this->userList($chatID));
    }

    public function answerDialog($chatID)
    {
        return $this->request('imopenlines.operator.answer', ['CHAT_ID' => $chatID]);
    }

    public function takeDialog($chatID)
    {
        return $this->request('imopenlines.session.intercept', ['CHAT_ID' => $chatID]);
    }

    public function closeChat($chatID)
    {
        return $this->request('imopenlines.operator.finish', ['CHAT_ID' => $chatID]);
    }

    public function sendMessage($chatID, $message)
    {
        return $this->request('imbot.message.add', ['DIALOG_ID' => $chatID, 'MESSAGE' => $message]);
    }

    public function transferQueue($chatId)
    {
        return $this->request('imopenlines.bot.session.operator', ['CHAT_ID' => $chatId]);

    }

    public function transferToResponsible($chatId, $userId)
    {
        return $this->request('imopenlines.bot.session.transfer', ['CHAT_ID' => $chatId, 'USER_ID' => $userId, "LEAVE" => "N"]);
    }
}