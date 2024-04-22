<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\AttributeResource;
class AttributeResourceCollection extends ResourceCollection
{
	
	protected $lang;

    public function lang($value){
        $this->lang = $value;
        return $this;
    }
   
   
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
		return $this->collection->map(function(AttributeResource $resource) use($request){
            return $resource->lang($this->lang)->toArray($request);
    })->all();
    }
}
