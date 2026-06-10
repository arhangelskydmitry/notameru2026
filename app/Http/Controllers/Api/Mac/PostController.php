<?php

namespace App\Http\Controllers\Api\Mac;

use App\Http\Controllers\Controller;
use App\Models\PostSeo;
use App\Models\WordPress\Post;
use App\Models\WordPress\TermTaxonomy;
use App\Models\WordPress\User;
use App\Services\ArticleSummarizeService;
use App\Services\SeoGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = mac_app_user($request);
        $query = Post::query()->where('post_type', 'post')->orderByDesc('post_date');

        if (!$user->isSuperAdmin() && !$user->isEditor()) {
            $query->where('post_author', $user->ID);
        }

        if ($status = $request->query('status')) {
            $query->where('post_status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where('post_title', 'like', '%' . $search . '%');
        }

        $posts = $query->paginate(min((int) $request->query('per_page', 30), 50));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (Post $p) => $this->listItem($p)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorizePost($request, $post);

        return response()->json(['data' => $this->detailItem($post)]);
    }

    public function store(Request $request)
    {
        $user = mac_app_user($request);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'status' => 'required|in:publish,draft,pending,future',
            'post_date' => 'nullable|date',
            'category_ids' => 'nullable|array',
            'author_id' => 'nullable|integer',
        ]);

        $authorId = $validated['author_id'] ?? $user->ID;
        if (!$user->isSuperAdmin() && !$user->isEditor()) {
            $authorId = $user->ID;
        }

        $postDate = isset($validated['post_date']) ? Carbon::parse($validated['post_date']) : now();
        $status = $this->resolveStatus($validated['status'], $postDate);
        $slug = $this->uniqueSlug($validated['title']);

        $post = Post::create([
            'post_author' => $authorId,
            'post_date' => $postDate,
            'post_date_gmt' => $postDate->copy()->utc(),
            'post_content' => $validated['content'],
            'post_title' => $validated['title'],
            'post_excerpt' => $validated['excerpt'] ?? '',
            'post_status' => $status,
            'post_name' => $slug,
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'post_type' => 'post',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => '',
        ]);

        $this->syncCategories($post->ID, $validated['category_ids'] ?? []);
        $this->autoSeo($post);

        return response()->json(['data' => $this->detailItem($post->fresh())], 201);
    }

    public function update(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorizePost($request, $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'status' => 'required|in:publish,draft,pending,future',
            'post_date' => 'nullable|date',
            'category_ids' => 'nullable|array',
            'seo' => 'nullable|array',
        ]);

        $postDate = isset($validated['post_date']) ? Carbon::parse($validated['post_date']) : Carbon::parse($post->post_date);

        $post->update([
            'post_title' => $validated['title'],
            'post_content' => $validated['content'],
            'post_excerpt' => $validated['excerpt'] ?? '',
            'post_status' => $this->resolveStatus($validated['status'], $postDate),
            'post_date' => $postDate,
            'post_date_gmt' => $postDate->copy()->utc(),
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
        ]);

        if (array_key_exists('category_ids', $validated)) {
            DB::connection('wordpress')->table('wp_term_relationships')->where('object_id', $post->ID)->delete();
            $this->syncCategories($post->ID, $validated['category_ids'] ?? []);
        }

        if (!empty($validated['seo'])) {
            PostSeo::updateOrCreate(['post_id' => $post->ID], $this->mapSeo($validated['seo']));
        }

        return response()->json(['data' => $this->detailItem($post->fresh())]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorizePost($request, $post);

        $validated = $request->validate([
            'status' => 'required|in:publish,draft,pending,future',
        ]);

        $postDate = Carbon::parse($post->post_date);
        $post->update([
            'post_status' => $this->resolveStatus($validated['status'], $postDate),
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
        ]);

        return response()->json(['data' => $this->detailItem($post->fresh())]);
    }

    public function generateSeo(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
        ]);

        $seo = (new SeoGeneratorService())->generateSeoData(
            $validated['title'],
            $validated['excerpt'] ?? null,
            $validated['content'] ?? null
        );

        return response()->json(['data' => $seo]);
    }

    public function summarize(Request $request, ArticleSummarizeService $summarizer)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'required|string',
        ]);

        return response()->json([
            'data' => $summarizer->summarize($validated['title'], $validated['content']),
        ]);
    }

    public function categories()
    {
        $categories = TermTaxonomy::query()
            ->where('taxonomy', 'category')
            ->with('term')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->term_taxonomy_id,
                'name' => $c->term->name ?? '',
                'slug' => $c->term->slug ?? '',
            ]);

        return response()->json(['data' => $categories]);
    }

    private function authorizePost(Request $request, Post $post): void
    {
        $user = mac_app_user($request);
        if (!$user->canEditPost($post)) {
            abort(403, 'Нет доступа к статье');
        }
    }

    private function listItem(Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'author_id' => (int) $post->post_author,
            'post_date' => $post->post_date,
            'modified' => $post->post_modified,
            'slug' => $post->post_name,
        ];
    }

    private function detailItem(Post $post): array
    {
        $seo = PostSeo::where('post_id', $post->ID)->first();

        return [
            ...$this->listItem($post),
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'seo' => $seo ? [
                'seo_title' => $seo->seo_title,
                'seo_description' => $seo->seo_description,
                'focus_keyword' => $seo->focus_keyword,
                'seo_keywords' => $seo->seo_keywords,
                'og_title' => $seo->og_title,
                'og_description' => $seo->og_description,
            ] : null,
            'url' => url('/' . $post->post_name),
        ];
    }

    private function resolveStatus(string $status, Carbon $postDate): string
    {
        if ($status === 'future' || ($status === 'publish' && $postDate->isFuture())) {
            return $postDate->isFuture() ? 'future' : 'publish';
        }

        return $status;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;
        while (Post::where('post_name', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    private function syncCategories(int $postId, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            DB::connection('wordpress')->table('wp_term_relationships')->insert([
                'object_id' => $postId,
                'term_taxonomy_id' => $categoryId,
            ]);
        }
    }

    private function autoSeo(Post $post): void
    {
        try {
            $seo = (new SeoGeneratorService())->generateSeoData($post->post_title, $post->post_content);
            PostSeo::create(array_merge(['post_id' => $post->ID], $this->mapSeo($seo)));
        } catch (\Throwable) {
            PostSeo::create([
                'post_id' => $post->ID,
                'seo_title' => $post->post_title,
                'seo_description' => Str::limit(strip_tags($post->post_content), 155),
            ]);
        }
    }

    private function mapSeo(array $seo): array
    {
        return array_filter([
            'seo_title' => $seo['seo_title'] ?? null,
            'seo_description' => $seo['seo_description'] ?? null,
            'focus_keyword' => $seo['focus_keyword'] ?? null,
            'seo_keywords' => $seo['seo_keywords'] ?? null,
            'og_title' => $seo['og_title'] ?? null,
            'og_description' => $seo['og_description'] ?? null,
            'og_image' => $seo['og_image'] ?? null,
            'twitter_title' => $seo['twitter_title'] ?? null,
            'twitter_description' => $seo['twitter_description'] ?? null,
        ], fn ($v) => $v !== null);
    }
}
