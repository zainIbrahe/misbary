<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class City extends Model
{
 	protected $table ="city";   
	
	public function parent(){
		return $this->belongsTo(City::class,"parent_id");
	}
}
