<?php

namespace App\Model;

class DataForm extends \yii\base\Model
{
    public $name;
    public $login;
    public $email;
    public $password;
    public $agreed;
    public $date;
    public $ipv4;
    public $guid;

    public function rules()
    {
        return [
            [['name'], 'match', 'pattern' => '/^[A-Za-z]+\s[A-Za-z]+$/u'],
            [['login'], 'match', 'pattern' => '/^[a-zA-Z0-9\-_]+$/'],
            [['email'], 'email'],
            [['email', 'password'], 'required'],
            [['password'], 'string', 'length' => [8, 64]],
            [['agreed'], 'boolean'],
            [['date'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['ipv4'], 'ip', 'ipv6' => false],
            [['guid'], 'match', 'pattern' => '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i'],
        ];
    }
}
