<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
		$file = str_replace('/var/www/vhosts/corline.store/misbary.corline.store/public/storage/','',$this->resource->file);
        return [
            'file' => $file,
            'created_by' => new UserResource($this->resource->createdBy),
			'created_at' => $this->resource->updated_at->format("Y-m-d h:s")
            
        ];
    }
}
