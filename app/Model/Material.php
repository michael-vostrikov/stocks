<?php

namespace App\Model;

class Material extends \App\Db\ActiveRecord
{
    public static function tableName()
    {
        return 'materials';
    }
}
