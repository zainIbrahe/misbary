<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Advertisment;
use TCG\Voyager\Models\Category;
use TCG\Voyager\Traits\Spatial;
use Auth;

class Product extends Model
{
    use Spatial;

    protected $spatial = ['location'];
    public $fillable = ['files', 'en_name', 'ku_name', 'en_description', 'ku_description', 'name', 'description', 'price', 'category_id', 'location'];

    public function ads(): MorphMany
    {
        return $this->morphMany(Advertisment::class, 'adable');
    }

    public function sku()
    {
        return $this->hasMany(ProductsSku::class, "product_id", "id");
    }
	
	 public function scopeStatus($query)
	 {
		 return $query->where('status', 1);
	 }
	public function isFav(){
		if(Auth::user()){
			$userFav = UserFavourite::where("user_id",Auth::user()->id)->where("product_id",$this->id)->get();
			$res = 0;
			if(count($userFav) > 0){
				$res = 1;
			}
		}
		else{
			$res = 0;
		}
		return $res;
	}
	public function getFilesAttribute($value)
    {
		$array = [];
		if(json_decode($value) != null){
			foreach(json_decode($value) as $val){
				$val = str_replace("/var/www/vhosts/misbary.app/httpdocs/public/storage/","",$val);
				array_push($array,str_replace("/var/www/vhosts/misbary.app/httpdocs/public/storage/","",$val));
			}
			return json_encode($array); // Assuming files are stored as JSON string
		}
		return $value;
    }
	
	 public function scopePending($query)
	 {
		 return $query->where('status', 0);
	 }

	
	public function getNameAttribute($value){
		$catName = $this->category != null ? $this->category->name." - " : "";
		$createdBy = $this->createdBy != null ? $this->createdBy->name." - " : "";
		$value = $catName .$createdBy . date('d-m-Y', strtotime($this->created_at));
		
		return $value;
	}
	public function createdBy(){
		return $this->belongsTo(\App\Models\User::class,"created_by");
	}
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

  
	
	
	public function getFeaturesAttribute(){
		return "Asd";
	}
	
	public function region(){
		return $this->belongsTo(City::class);	
	}
}
