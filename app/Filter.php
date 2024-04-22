<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Filter extends Model
{
    public function filterValues()
    {
        return $this->hasMany(FilterValue::class, 'filter_id');
    }
}
