<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class AttributeValue extends Model
{

    protected $table = "attribute_value";


    public function attributeType()
    {
        return $this->hasOne(Attribute::class, "id", "attribute_type");
    }


    public function sku()
    {
        return $this->hasOne(ProductsSku::class, "sku_id", "id");
    }
}
