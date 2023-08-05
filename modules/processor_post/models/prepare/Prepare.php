<?php
namespace app\modules\processor_post\models\prepare;

use mysql_xdevapi\Exception;

class Prepare
{
    public $post_data;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_content;
    public $utm_term;
    public $url = 'https://malvinabeauty.ru/up/web/processor-post/main/index';
    public $token = '69fa2371-d8c1-42c7-ba4f-383ba77b98b6';
    public $name = 'WordPress';

    public function __construct($post_data, $get_data, $cookie_data)
    {
        $this->setUTM("UTM_SOURCE", $get_data['utm_source'] ?? $cookie_data['utm_source'] ?? null);
        $this->setUTM("UTM_MEDIUM", $get_data['utm_medium'] ?? $cookie_data['utm_medium'] ?? null);
        $this->setUTM("UTM_CAMPAIGN", $get_data['utm_campaign'] ?? $cookie_data['utm_campaign'] ?? null);
        $this->setUTM("UTM_CONTENT", $get_data['utm_content'] ?? $cookie_data['utm_content'] ?? null);
        $this->setUTM("UTM_TERM", $get_data['utm_term'] ?? $cookie_data['utm_term'] ?? null);
        if(isset($get_data['utm_source']) ||isset($get_data['utm_medium']) ||isset($get_data['utm_campaign']) ||isset($get_data['utm_content']) || isset($get_data['utm_term']) ||
            isset($cookie_data['utm_source']) ||isset($cookie_data['utm_medium']) ||isset($cookie_data['utm_campaign']) ||isset($cookie_data['utm_content']) ||isset($cookie_data['utm_term'])){
            $istok = 'Реклама';
        }else{
            $istok = 'СЕО';
        }
        $this->setDefaultPostData();
        $comment = '';
        foreach ($post_data as $key => $item)
        {
            if($key == 'form'|| $key == 'your-subject')
                $comment .= "Тема заявки: {$item}<br>";
            if($key == 'page')
                $comment .= "Страница: {$item}<br>";
            if($key == 'phone'|| $key == 'your-phone')
                $this->setPostData('PHONE', $item, 1);
            if($key == 'name' || $key == 'your-name')
                $this->setPostData('NAME', $item, 1);
            if($key == 'email' || $key == 'your-email')
                $this->setPostData('EMAIL', $item, 1);
            if(($key == 'message' || $key == 'your-message') && $item != '')
                $comment .= "Комментарии: {$item}<br>";
//            if($key == 'page')
//                $comment .= "Страница: {$item}<br>";
        }
        $this->setPostData('COMMENTS', $comment, 1);
        $this->setPostData('UF_CRM_1670587279710', $istok, 1);
    }

    public function setDefaultPostData()
    {
        $this->setPostData('SOURCE_ID', 4, 1);
        $this->setPostData('TITLE', 'Заявка с сайта', 1);
    }
    public function setPostData($field_id, $value, $entity_type_id)
    {
        if($value == null) return false;
        $this->post_data[] =
            [
                'value' => $value,
                'entity_type_id' => $entity_type_id,
                'field_id' => $field_id,
            ];
    }

    public function setUTM($field_id, $value)
    {
        if($value == null) return false;
        $this->post_data[] =
            [
                'value' => $value,
                'entity_type_id' => 1, // лид,
                'field_id' => $field_id,
            ];
    }

    public function sending()
    {
        $url = "{$this->url}?token={$this->token}&name={$this->name}";
        $cmd = "curl -X POST -H 'Content-Type: application/x-www-form-urlencoded'";

        $cmd.= " -d '" . http_build_query($this->post_data) . "' '" . $url . "'";

//$cmd.= "'  --insecure";

        $cmd .= " > /dev/null 2>&1 &";
        exec($cmd);
    }

}