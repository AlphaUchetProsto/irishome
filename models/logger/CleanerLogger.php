<?php

namespace app\models\logger;

use App\DebugLogger\DebugLoggerException;
use Yii;

class CleanerLogger
{
    private static $config_path = __DIR__ . "/../../config/cleaner_logger.php";
    private static $sec_per_day = 86400;

    private static function getConfig()
    {
        return require static::$config_path;
    }

    public static function clear(): void
    {
        $params = static::getConfig();
        $current_datetime = strtotime(date("Y-m-d H:i:s"));
        $difference_time_last_clearing = $current_datetime - strtotime($params["Последняя очистка"]);

        if(!is_null($params["Последняя очистка"]) && $difference_time_last_clearing < static::$sec_per_day)
        {
            return;
        }

        $logs_dir = static::getListFolder();
        $count_day_default = $params["По умолчанию"] ?? 1;

        $setting_folder =  [];

        foreach($params["Каталоги"] as $path => $count_day)
        {
            $setting_folder[Yii::getAlias('@bitrix_logs') . "{$path}"] = $count_day;
        }

        foreach($logs_dir as $key => $path)
        {
            $count_day = array_key_exists($path, $setting_folder) ? $setting_folder[$path] : $count_day_default;

            $list_files = scandir($path);
            $list_files = array_slice($list_files, 2);

            if(!empty($list_files))
            {
                foreach ($list_files as $number => $file_name)
                {
                    $current_element = "{$path}/{$file_name}";

                    if(!is_dir($current_element))
                    {
                        $data_last_update = filectime($current_element);
                        $difference_time = $current_datetime - $data_last_update;

                        if ($difference_time > static::$sec_per_day * $count_day) {
                            unlink($current_element);
                        }
                    }
                }
            }
        }

        $params["Последняя очистка"] = date("d.m.Y H:i:s");
        $contents = var_export($params, true);

        file_put_contents(static::$config_path, "<?php\n return {$contents};\n");
    }

    private static function getListFolder($logs_path = null)
    {
        $logs_dir_path = is_null($logs_path) ? Yii::getAlias('@bitrix_logs') : $logs_path;

        $logs_dir = scandir($logs_dir_path);
        $logs_dir = array_slice($logs_dir, 2);

        $logs_folder = is_null($logs_path) ? [$logs_dir_path] : [];

        foreach($logs_dir as $key => $element)
        {
            $current_element_path = "{$logs_dir_path}/{$element}";

            if(is_dir($current_element_path))
            {
                $logs_folder[] = $current_element_path;
                $logs_folder = array_merge($logs_folder, static::getListFolder($current_element_path));
            }
        }

        return $logs_folder;
    }
}
