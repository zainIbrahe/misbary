<?php

namespace App\Models;

use App\Product;
use App\ProductsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    use HasFactory;
    protected $table = 'user_favourites';
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsTo(ProductsSku::class, 'product_id');
    }
	
	public function dealer()
    {
        return $this->belongsTo(User::class, 'product_id');
    }
}
