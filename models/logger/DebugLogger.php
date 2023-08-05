<?php

namespace app\models\logger;

use App\DebugLogger\DebugLoggerException;
use App\DebugLogger\DebugLoggerInterface;
use DateTime;
use DateTimeZone;
use Yii;

class DebugLogger implements DebugLoggerInterface
{
    public $isActive = true;
    public static $logFileDir = '/../logs/bitrix/';
    public static $uniqIdLength = 7;
    public static $mkdirMode = 0755;
    private $uniqId;
    private $microtime;
    private $logFileName;
    private $logFilePath;
    private static $instances = [];

    private function __construct(string $logFileName)
    {
        $this->logFileName = $logFileName;
        $this->uniqId = $this->getUniqId(static::$uniqIdLength);

        $module = Yii::$app->controller->module->id;
        $controller = Yii::$app->controller->id;

        if($module !== "basic" && mb_strpos(static::$logFileDir, $module) === false)
        {
            static::$logFileDir = static::$logFileDir . "{$module}/";
        }

        if(mb_strpos(static::$logFileDir, $controller) === false)
        {
            static::$logFileDir = static::$logFileDir . "{$controller}/";
        }
    }

    public static function instance(string $logFileName): DebugLoggerInterface
    {
        if (! isset(static::$instances[ $logFileName ])) {
            static::$instances[ $logFileName ] = new static($logFileName);
        }

        return new static($logFileName);
    }

    public function save($info, $object = null, string $header = null)
    {
        // Если не активен (выключен)
        if (! $this->isActive) {
            return;
        }

        // Устанавливаем полный путь к лог файлу
        if (! isset($this->logFilePath)) {
            $this->logFilePath = static::$logFileDir . $this->logFileName;
            $this->logFilePath = $this->getAbsoluteFileName($this->logFilePath) . " " . preg_replace('-\W-','_',date('m-d-Y H:i:s')) .".php";
            if (empty($this->logFilePath)) {
                throw new DebugLoggerException("Не удалось определить путь к лог файлу '{$this->logFileName}'");
            }
        }

        // Вычисляем время, прошедшее с последнего сохранения
        $microtime = microtime(true);
        $deltaMicrotime = isset($this->microtime) ? sprintf('%.6f', $microtime - $this->microtime) : '-';
        $this->microtime = $microtime;

        // Форматирует время запроса
        /** @noinspection PrintfScanfArgumentsInspection */
        $dateTime = DateTime::createFromFormat('U.u', sprintf('%.f', $microtime));
        $timeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime->setTimeZone($timeZone);
        $requestTime = $dateTime->format('Y-m-d H:i:s.u P') . " Δ{$deltaMicrotime} s";

        $memoryUsage = $this->getMemoryPeakUsage();

        $message = "";

        if(!file_exists($this->logFilePath))
        {
            $message .= "<?php\n/*\n";
        }

        // Заголовок сообщения для лог файла
        $message .= "--- [{$requestTime}, {$memoryUsage}] " . str_repeat('-', 20) . PHP_EOL;

        // Добавляем название класса переданного объекта
        if (! empty($object) && is_object($object)) {
            $className = get_class($object);
            $message .= "Class: {$className}" . PHP_EOL;
        }

        // Добавляем заголовок
        if (! empty($header)) {
            $message .= "{$header}" . PHP_EOL;
        }

        if (! is_string($info)) {
            $jsonInfo = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($jsonInfo === false) {
                $errorMessage = json_last_error_msg();
                throw new DebugLoggerException("Ошибка кодирования JSON ({$errorMessage}): " . print_r($info, true));
            }
            $info = $jsonInfo;
        }

        $message .=  $info . PHP_EOL . PHP_EOL;

        // Записывает сообщение в лог файл
        if (! @file_put_contents($this->logFilePath, $message, FILE_APPEND | LOCK_EX)) {
            throw new DebugLoggerException("Не удалось записать в лог файл '{$this->logFilePath}'");
        }
    }

    protected function getAbsoluteFileName(string $relativeFileName, bool $createDir = true)
    {
        $includePath = explode(PATH_SEPARATOR, get_include_path());
        foreach ($includePath as $path) {
            $absoluteFileName = $path . DIRECTORY_SEPARATOR . $relativeFileName;
            $checkDir = dirname($absoluteFileName);
            if (is_dir($checkDir)) {
                return $absoluteFileName;
            }
            if ($createDir) {
                if (!mkdir($checkDir, self::$mkdirMode, $recursive = true) && !is_dir($checkDir)) {
                    throw new DebugLoggerException("Не удалось создать каталог для лог файлов '{$checkDir}'");
                }
                return $absoluteFileName;
            }
        }
        return null;
    }

    protected function getMemoryPeakUsage(): string
    {
        return sprintf('%0.2f', memory_get_peak_usage(false) / 1024 / 1024) . '/' .
            sprintf('%0.2f', memory_get_peak_usage(true) / 1024 / 1024) . ' MiB';
    }

    protected function getUniqId(int $length): string
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $length);
    }
}
