<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Reel extends Model
{
    public function created_bys(){
		return $this->belongsTo(User::class,"created_by");
	}
	
	public function product(){
		return $this->belongsTo(Product::class,"product_id");		
	}
	
	
	


}
