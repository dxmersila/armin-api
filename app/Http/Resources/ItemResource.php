<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_visible' => $this->is_visible,

            'categories' => CategoryResource::collection(
                $this->whenLoaded('categories')
            ),

            'tags' => TagResource::collection(
                $this->whenLoaded('tags')
            ),

            'media' => MediaResource::collection(
                $this->whenLoaded('media')
            ),
        ];
    }
}
