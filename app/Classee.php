<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Models\Category;

class Classee extends Model
{
    public function model(){
		return $this->belongsTo(Category::class,"model_id");
	}
}
