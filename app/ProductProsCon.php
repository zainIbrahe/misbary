<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProductProsCon extends Model
{

    protected $table = 'product_pros_cons';
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
