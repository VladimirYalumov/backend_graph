<?php

namespace app\models;

use yii\db\ActiveRecord;

class Connections extends ActiveRecord
{
    public static function tableName()
    {
        return 'connections';
    }
}