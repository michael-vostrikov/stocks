<?php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../app/config/main.php',
    require __DIR__ . '/../app/config/main-local.php'
);

$config['components']['response'] = [
    'formatters' => [
        \yii\web\Response::FORMAT_JSON => [
            'class' => 'yii\web\JsonResponseFormatter',
            'prettyPrint' => true,
            'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ],
    ],
];

$config['controllerNamespace'] = 'App\Controller';

(new yii\web\Application($config))->run();
