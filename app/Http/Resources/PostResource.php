<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID,
            'title' => $this->post_title,
            'slug' => $this->post_name,
            'content' => $this->post_content,
            'excerpt' => $this->post_excerpt,
            'status' => $this->post_status,
            'author' => [
                'id' => $this->author?->ID,
                'name' => $this->author?->display_name,
                'email' => $this->author?->user_email,
            ],
            'featured_image' => $this->getThumbnailUrl(),
            'categories' => $this->categories->map(fn($cat) => [
                'id' => $cat->term_taxonomy_id,
                'name' => $cat->term->name ?? null,
                'slug' => $cat->term->slug ?? null,
            ]),
            'tags' => $this->tags->map(fn($tag) => [
                'id' => $tag->term_taxonomy_id,
                'name' => $tag->term->name ?? null,
                'slug' => $tag->term->slug ?? null,
            ]),
            'views' => (int) $this->getMeta('post_views_count', 0),
            'published_at' => $this->post_date?->toIso8601String(),
            'updated_at' => $this->post_modified?->toIso8601String(),
            'seo' => [
                'title' => $this->getMeta('_yoast_wpseo_title'),
                'description' => $this->getMeta('_yoast_wpseo_metadesc'),
                'focus_keyword' => $this->getMeta('_yoast_wpseo_focuskw'),
            ],
        ];
    }
    
    /**
     * Получить URL миниатюры
     */
    protected function getThumbnailUrl(): ?string
    {
        $thumbnailId = $this->getMeta('_thumbnail_id');
        if ($thumbnailId) {
            $attachment = \App\Models\WordPress\Post::find($thumbnailId);
            return $attachment?->guid;
        }
        return null;
    }
}
