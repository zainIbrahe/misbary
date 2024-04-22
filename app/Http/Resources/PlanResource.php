<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'monthly_paid' => $this->resource->monthly_paid,
            'yearly_paid' => $this->resource->yearly_paid,
            'monthly_discount' => $this->resource->monthly_discount,
            'yearly_discount' => $this->resource->yearly_discount,
            'posts_num' => $this->resource->posts_num,
        ];
    }
}
