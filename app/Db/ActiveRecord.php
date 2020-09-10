<?php

namespace App\Db;

/**
 * @method ActiveQuery hasMany($class, array $link)
 * @method ActiveQuery hasOne($class, array $link)
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    public static function find(): \App\Db\ActiveQuery
    {
        return \Yii::createObject(\App\Db\ActiveQuery::class, [get_called_class()]);
    }

    public static function batchReplace(array $data)
    {
        if (empty($data)) return true;

        $db = static::getDb();
        $sql = $db->queryBuilder->batchInsert(static::tableName(), array_keys(reset($data)), $data);
        $sql = preg_replace('/^INSERT INTO/', 'REPLACE INTO', $sql);

        return $db->createCommand($sql)->execute();
    }
}
