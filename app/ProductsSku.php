<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProductsSku extends Model
{
    protected $table = "products_sku";
    public function attributes()
    {
        return $this->hasMany(AttributeValue::class, "sku_id", "id");
    }
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id");
    }
	
	public function pro(){
		return $this->hasMany(ProductProsCon::class,'product_id');
	}

    public function pros()
    {
        $pros = [];
        foreach ($this->pro as $pro) {
            if ($pro->type == "pros") {
                $pros = [...$pros, $pro];
            }
        }
        return $pros;
    }
	public function whatsnew()
    {
        $whatsnews = [];
        foreach ($this->pro as $whatsnew) {
            if ($whatsnew->type == "whatsnew") {
                $whatsnews = [...$whatsnews, $whatsnew];
            }
        }
        return $whatsnews;
    }
    public function cons()
    {
        $cons = [];
        foreach ($this->pro as $pro) {
            if ($pro->type == "cons") {
                $cons = [...$cons, $pro];
            }
        }

        return $cons;
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
