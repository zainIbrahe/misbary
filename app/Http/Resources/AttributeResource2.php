<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource2 extends JsonResource
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
			'type'=>$this->resource->type,
			'ar_name'=>$this->resource->ar_name,
			'ku_name'=>$this->resource->ku_name,
			'order' => $this->resource->order,
			'isrequired' => (int)$this->resource->isrequired,
			'values'=>$this->resource->values
            
        ];
    }
}
