<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Story extends Model
{
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
	
	 public function scopePending($query)
	 {
		 return $query->where('status', 0);
	 }
	
	public function getFileAttribute($value){
		return str_replace("/var/www/vhosts/misbary.app/httpdocs/public/storage/","",$value);
	}
}
