<?php

namespace App\Model;

class CurrentStock extends \App\Db\ActiveRecord
{
    public static function tableName()
    {
        return 'current_stock';
    }
}
