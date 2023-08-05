<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@bitrix_logs' =>  '@app/logs/bitrix',
        '@modules' =>  '@app/modules',
        '@processor_post' =>  '@app/modules/processor_post',
    ],
    'modules' => [
        'handle-post' => [
            'class' => 'app\modules\handle_post\Module',
        ],
        'interface-app' => [
            'class' => 'app\modules\interface_app\Module',
        ],
        'no-interface-app' => [
            'class' => 'app\modules\no_interface_app\Module',
        ],
        'processor-post' => [
            'class' => 'app\modules\processor_post\Module',
        ],
        'chat-bot' => [
            'class' => 'app\modules\chat_bot\Module',
        ],
        'chat-report' => [
            'class' => 'app\modules\chat_report\Module',
        ],
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'hq8CuyPZXVLr4opJPBcs7mvcD83YVr09',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                '/<module:tilda>' => 'tilda/main/index',
                '/<module:tilda>/<action>' => 'tilda/main/<action>',
                '/<module:bitrix-app>' => 'bitrix-app/main/index',
                '/<module:bitrix-app>/<action>' => 'bitrix-app/main/<action>',
                '/<module:handle-post>' => 'handle-post/main/index',
                '/<module:handle-post>/<action>' => 'handle-post/main/<action>',
                '/<module:interface-app>' => 'interface-app/main/index',
                '/<module:interface-app>/<action>' => 'interface-app/main/<action>',
                '/<module:no-interface-app>' => 'no-interface-app/main/index',
                '/<module:no-interface-app>/<action>' => 'no-interface-app/main/<action>',
                '/<module:processor-post>/<controller>/<action>' => '/processor-post/<controller>/<action>',
                '/<module:chat-bot>/<controller>/<action>' => '/chat-bot/<controller>/<action>',
                '/<module:chat-report>/<controller>/<action>' => '/chat-report/<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
