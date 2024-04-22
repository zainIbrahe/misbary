<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Attribute extends Model
{
    protected $table = "attributes";
    public function attributesValues()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_type');
    }

    public function values()
    {
        return $this->hasMany(NewAttributesValue::class, 'attribute_type');
    }
}
