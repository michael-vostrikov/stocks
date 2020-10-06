<?php

namespace App\Console;

use Yii;
use App\Model\DataForm;

class ValidationController extends \yii\console\Controller
{
    public function actionInsertApp($needError)
    {
        $needError = (int)$needError;
        $data = $this->generateData($needError);

        $form = new DataForm();

        if ($form->load($data, '') && $form->validate()) {
            $this->saveData($data, 'data_for_validation_app');

        } else {
            echo implode("\n", $form->getErrorSummary(false)) . "\n";
        }
    }

    public function actionInsertDb($needError)
    {
        $needError = (int)$needError;
        $data = $this->generateData($needError);
        try {
            $this->saveData($data, 'data_for_validation_db');

        } catch (\yii\db\Exception $e) {
            $errorMsg = $e->errorInfo[2];
            if ($e->errorInfo[1] === 3819) {
                preg_match("/Check constraint 'chk_(.*)' is violated/", $errorMsg, $matches);
                $field = $matches[1];
                echo "Field '" . $field . "'" . ' is incorrect' . "\n";
            } elseif ($e->errorInfo[1] === 1292) {
                preg_match("/Incorrect .* value: '.*' for column '(.*)'/", $errorMsg, $matches);
                $field = $matches[1];
                echo "Field '" . $field . "'" . ' is incorrect' . "\n";
            } else {
                echo $e;
            }

            return;
        }
    }

    public function generateData($needError)
    {
        $data = [
            'name' => $this->randomString() . ' ' . $this->randomString(),
            'login' => $this->randomString(),
            'email' => $this->randomString() . '@example.com',
            'password' => $this->randomString(8, 64),
            'agreed' => rand(0, 1),
            'date' => date('Y-m-d H:i:s', time() - rand(0, 86400 - 1)),
            'ipv4' => implode('.', [rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255)]),
            'guid' => implode('-', [$this->randomHex(8), $this->randomHex(4), '4' . $this->randomHex(3), '8' . $this->randomHex(3), $this->randomHex(12)]),
        ];

        $incorrectData = [
            'name' => $this->randomString() . $this->randomString(),
            'login' => '#' . rand(100000000, 999999999),
            'email' => $this->randomString(),
            'password' => $this->randomString(4, 6),
            'agreed' => rand(10, 20),
            'date' => $this->randomString(),
            'ipv4' => $this->randomString(),
            'guid' => $this->randomString(),
        ];

        if ($needError) {
            $fields = array_keys($data);
            $field = $fields[rand(0, count($fields) - 1)];
            $data[$field] = $incorrectData[$field];
        }

        return $data;
    }

    protected $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected $hexDigits = '0123456789ABCDEF';

    protected function randomString($minLen = 1, $maxLen = 30)
    {
        $len = rand($minLen, $maxLen);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $ch = $this->letters[rand(0, strlen($this->letters) - 1)];
            $str .= $ch;
        }

        return $str;
    }

    protected function randomHex($len)
    {
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $ch = $this->hexDigits[rand(0, strlen($this->hexDigits) - 1)];
            $str .= $ch;
        }

        return $str;
    }


    public function saveData($data, $tbl)
    {
        $sql = "
            INSERT INTO {$tbl}(`name`, `login`, `email`, `password`, `agreed`, `date`, `ipv4`, `guid`)
            VALUES (:name, :login, :email, :password, :agreed, :date, :ipv4, :guid)
        ";
        $params = [
            ':name' => $data['name'],
            ':login' => $data['login'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':agreed' => $data['agreed'],
            ':date' => $data['date'],
            ':ipv4' => $data['ipv4'],
            ':guid' => $data['guid'],
        ];
        \Yii::$app->db->createCommand($sql, $params)->execute();
    }
}
