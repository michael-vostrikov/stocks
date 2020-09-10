<?php

namespace App\Model;

class DocumentType extends \App\Db\ActiveRecord
{
    const ZPS = 6;
    const PLAN_PROIZVODSTVA = 8;

    public static function tableName()
    {
        return 'document_types';
    }
}
