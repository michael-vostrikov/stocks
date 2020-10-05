<?php

use yii\db\Migration;

/**
 * Class m201004_163540_validation
 */
class m201004_163540_validation extends Migration
{
    public function up()
    {
        $this->createTable('data_for_validation_app', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->null(),
            'login' => $this->string()->null(),
            'email' => $this->string()->notNull(),
            'password' => $this->string()->notNull(),
            'agreed' => $this->boolean()->null(),
            'date' => $this->dateTime()->null(),
            'ipv4' => $this->string()->null(),
            'guid' => $this->string()->null(),
        ]);

        $this->createTable('data_for_validation_db', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->null(),
            'login' => $this->string()->null(),
            'email' => $this->string()->notNull(),
            'password' => $this->string()->notNull(),
            'agreed' => $this->boolean()->null(),
            'date' => $this->dateTime()->null(),
            'ipv4' => $this->string()->null(),
            'guid' => $this->string()->null(),
        ]);

        $sql = "
          ALTER TABLE data_for_validation_db
            ADD CONSTRAINT chk_name CHECK (name REGEXP '^[A-Za-z]+ [A-Za-z]+$'),
            ADD CONSTRAINT chk_login CHECK (login REGEXP '^[a-zA-Z0-9\-_]+$'),
            ADD CONSTRAINT chk_email CHECK (email REGEXP :email_pattern),
            ADD CONSTRAINT chk_password CHECK (LENGTH(password) BETWEEN 8 AND 64),
            ADD CONSTRAINT chk_agreed CHECK (agreed BETWEEN 0 AND 1),
            ADD CONSTRAINT chk_ipv4 CHECK (ipv4 REGEXP '^(?:(?:2(?:[0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9])\.){3}(?:(?:2([0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9]))$'),
            ADD CONSTRAINT chk_guid CHECK (guid REGEXP '^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$')
        ";

        $this->execute($sql, [':email_pattern' => trim((new \yii\validators\EmailValidator())->pattern, '/')]);
    }

    public function down()
    {
        $this->dropTable('data_for_validation_db');
        $this->dropTable('data_for_validation_app');
    }
}
