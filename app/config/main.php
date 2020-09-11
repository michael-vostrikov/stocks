<?php

return [
    'id' => 'stocks',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/api/orm/close-doc' => 'document/close',
                '/api/sql/close-doc' => 'document/close-by-procedure',
            ],
        ],
    ],
];
