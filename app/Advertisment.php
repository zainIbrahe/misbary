<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Advertisment extends Model
{
    public function adv_type(): MorphTo
    {
        return $this->morphTo();
    }

    public function product()
    {
        return $this->belongsTo(ProductsSku::class, "linked_id")->whereHas("product");
    }
}
