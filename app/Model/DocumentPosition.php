<?php

namespace App\Model;

class DocumentPosition extends \App\Db\ActiveRecord
{
    public static function tableName()
    {
        return 'document_positions';
    }

    public function getDocument(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }
}
