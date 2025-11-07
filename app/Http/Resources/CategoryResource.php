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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->term_taxonomy_id,
            'name' => $this->term->name ?? null,
            'slug' => $this->term->slug ?? null,
            'description' => $this->description,
            'count' => $this->count,
            'parent_id' => $this->parent ?: null,
        ];
    }
}
