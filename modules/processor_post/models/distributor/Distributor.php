<?php
namespace app\modules\processor_post\models\distributor;

use mysql_xdevapi\Exception;
use TelegramBot\Api\Client as TelegramClient;

class Distributor
{
    public $post_data;
    public $get_data;
    public $token;
    public $log;
    public $name;
    public $telegramID;

    public function __construct($post_data, $get_data)
    {
        $this->post_data = $post_data;
        $this->get_data = $get_data;
        $this->token = $get_data['token'] ?? null;
        $this->log = $get_data['log'] ?? null;
        $this->name = $get_data['name'] ?? null;
        $this->telegramID = $get_data['telegramID'] ?? null;
    }

    public function checkToken()
    {
        $tokens = require \Yii::getAlias("@processor_post") . "/config/TokenConfig.php";
        if(!isset($tokens[$this->token])) return null;
        return $tokens[$this->token];
    }

    public function checkTelegramIdAndLog()
    {
        return $this->log != null && $this->telegramID != null;
    }

    public function checkUser()
    {
        $url = "https://sekvid.ru/up/web/webhook/main/check-user?telegramID=$this->telegramID";
        if(!$this->sending($url, true)) return false;

        $users = require \Yii::getAlias("@processor_post") . "/config/LogConfig.php";

        $users[$this->token][$this->name][$this->telegramID] =['log' => $this->log,'time' => time()];

        $users = var_export($users, true);
        file_put_contents(\Yii::getAlias("@processor_post") . "/config/LogConfig.php", "<?php\n return {$users};\n");
    }

    public function checkLog()
    {
        $users = require \Yii::getAlias("@processor_post") . "/config/LogConfig.php";
        if(!isset($users[$this->token][$this->name])) return false;
        foreach ($users[$this->token][$this->name] as $key => $user)
        {
            if((time() - $user['time']) > 604800)
            {
                $users[$key]['log'] = 'off';
                continue;
            }
            if($user['log'] == 'on')
            {
                $bot = new TelegramClient(\Yii::$app->params["telegram_bots"]["Информатор"]);
                $text['GET'] = $this->get_data;
                unset($text['GET']['module']);
                $text['POST'] = $this->post_data;
                //$text = var_export($text, true);
                $answer = "Пришли данные из $this->name\n\n <code>".print_r($text, true)."</code>";
                try {
                    $bot->sendMessage($key, $answer, 'HTML', null, null);
                }
                catch (Exception $e)
                {
                    continue;
                }
            }
        }
        $users = var_export($users, true);
        file_put_contents(\Yii::getAlias("@processor_post") . "/config/LogConfig.php", "<?php\n return {$users};\n");
    }


    public function sending($url, $waiting = false, $check_ssl = true)
    {
        $cmd = "curl -X POST -H 'Content-Type: application/x-www-form-urlencoded'";
        $cmd.= " -d '" . http_build_query($this->post_data) . "' '" . $url . "'";

        if (!$check_ssl){
            $cmd.= "'  --insecure";
        }

        $cmd .= " > /dev/null 2>&1 &";

        if(!$waiting)
        {
            $this->checkLog();
            exec($cmd);
        }
        else
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
    }

    public function StartParser($url) :void
    {
        $first = true;
        
        foreach ($this->get_data as $key => $item)
        {
            switch ($key) {
                case 'token':
                    $param = "token=$this->token";
                    break;
                case 'log':
                    $param = "log=$this->log";
                    break;
                case 'name':
                    $param = "name=$this->name";
                    break;
                case 'telegramID':
                    $param = "telegramID=$this->telegramID";
                    break;
                default:
                    continue 2;
            }

            if($first)
            {
                $url .= "?";
                $first = false;
            }
            else
                $url .= "&";

            $url .= $param;
        }

        $this->sending($url);
    }
}