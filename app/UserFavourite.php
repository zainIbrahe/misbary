<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class UserFavourite extends Model
{
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
