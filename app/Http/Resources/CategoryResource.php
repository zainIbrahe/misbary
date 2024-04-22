<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
	protected $lang = "ar";

    public function lang($value){
		if(!$value){
			$this->lang = "ar";
		}
		else{
			$this->lang = $value;
		}
        
        return $this;
    }
    public function toArray(Request $request): array
    {
		if($this->image == null){
			$this->image = "logo.jpg";
		}
        return [
            'id' => $this->id,
            'name' => $this->lang != "ar"  ? $this->{"{$this->lang}_name"} : $this->name,
            'parent_id' => $this->parent_id ?? 0,
			'image' => $this->image,
            'order' => $this->order,
        ];
    }
}
