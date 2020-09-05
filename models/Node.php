<?php

namespace app\models;

use yii\db\ActiveRecord;

class Node extends ActiveRecord
{
    public static function tableName()
    {
        return 'nodes';
    }
}