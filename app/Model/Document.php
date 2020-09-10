<?php

namespace App\Model;

use App\Db\ActiveQuery;

class Document extends \App\Db\ActiveRecord
{
    const CLOSING_TYPE_CLOSE = 1;
    const CLOSING_TYPE_OPEN = -1;

    public static function tableName()
    {
        return 'documents';
    }

    public function getPositions(): ActiveQuery
    {
        return $this->hasMany(DocumentPosition::class, ['document_id' => 'id']);
    }

    public function getType(): ActiveQuery
    {
        return $this->hasOne(DocumentType::class, ['id' => 'document_type_id']);
    }

    public function getPositionStocks(): ActiveQuery
    {
        return CurrentStock::find()->where([
            'organization_id_address' => $this->organization_id_address,
            'organization_id_ur' => $this->organization_id_ur,
            'material_id' => $this->getPositions()->select('material_id'),
        ])->indexBy('material_id');
    }
}
