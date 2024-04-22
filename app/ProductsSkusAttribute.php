<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProductsSkusAttribute extends Model
{
    public function attributes()
    {
        return $this->hasMany(AttributeValue::class, "attribute_value_id", "id");
    }

    public function skus()
    {
        return $this->belongsTo(ProductsSku::class, "sku_id", "id");
    }
}
