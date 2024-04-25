<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Plan;
use App\Reel;
use App\ProductsSku;
use App\UserFavourite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Auth;
use App\City;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'plan_id',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
	
	// public function isFav(){
    //         $id = 0;
    //         $userFav = Favourite::where("user_id",$id)->where("product_id",$this->id)->get();
            
	// 		$res = 0;
            
	// 		if(count($userFav) > 0){
	// 			$res = 1;
	// 		}
    //         return $res;
	// }
	
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
	
	public function carPosts(){
    	return $this->hasMany(ProductsSku::class, 'created_by')->whereHas('product');
	}

	public function userPhoneVerified()
	{
	  return $this->verified != 0;
	}
	
    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }
	public function stories(){
		return $this->hasMany(\App\Story::class,"created_by");
	}
	public function reels(){
		return $this->hasMany(Reel::class,"created_by");
	}

    public function city(){
        return $this->belongsTo(City::class,"location");
	}
	

}
