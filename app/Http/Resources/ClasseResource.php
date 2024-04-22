<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
		
        return [
            'id' => $this->id,
            'name' => $this->name,
            'en_name' => $this->en_name,
            'ku_name' => $this->ku_name,
            'model' => new CategoryResource($this->model),
        ];
    }
}
