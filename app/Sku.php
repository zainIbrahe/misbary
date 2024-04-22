<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Sku extends Model
{
    public function attributes()
    {
        return $this->hasMany(AttributeValue::class, "sku_id", "id");
    }
	
	public function pros(){
		return $this->hasMany(ProductProsCons::class, "product_id", "id");
	}
}
