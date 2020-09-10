<?php

namespace App\Db;

class ActiveQuery extends \yii\db\ActiveQuery
{
    public $isForUpdate = false;

    public function forUpdate()
    {
        $this->isForUpdate = true;
        return $this;
    }

    public function createCommand($db = null)
    {
        $command = parent::createCommand($db);
        if ($this->isForUpdate) {
            $params = $command->params;
            $command->setSql($command->getSql() . ' FOR UPDATE');
            $command->bindValues($params);
        }

        return $command;
    }

    public function delete(\yii\db\Connection $db = null)
    {
        $db = ($db ?? $this->modelClass::getDb());
        $command = $this->createCommand($db);
        $table = $db->quoteTableName(((array)$this->from)[0]);
        $command->delete($table, $this->where);

        return $command->execute();
    }
}
