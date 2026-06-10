<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post as WPPost;
use App\Models\WordPress\User as WPUser;
use App\Models\WordPress\TermTaxonomy;
use App\Models\LaravelPost;
use App\Models\LaravelCategory;
use App\Models\LaravelTag;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MigrateWordPressData extends Command
{
    protected $signature = 'migrate:wordpress {--type=all : users|posts|categories|tags|all}';
    protected $description = 'Миграция данных из WordPress в Laravel таблицы';

    public function handle()
    {
        $type = $this->option('type');

        $this->info('🚀 Начинаем миграцию данных из WordPress...');
        $this->newLine();

        if ($type === 'all' || $type === 'users') {
            $this->migrateUsers();
        }

        if ($type === 'all' || $type === 'categories') {
            $this->migrateCategories();
        }

        if ($type === 'all' || $type === 'tags') {
            $this->migrateTags();
        }

        if ($type === 'all' || $type === 'posts') {
            $this->migratePosts();
        }

        $this->newLine();
        $this->info('✅ Миграция завершена успешно!');
    }

    private function migrateUsers()
    {
        $this->info('👥 Миграция пользователей...');

        $wpUsers = WPUser::all();

        $bar = $this->output->createProgressBar($wpUsers->count());
        $bar->start();

        $mapping = [];

        foreach ($wpUsers as $wpUser) {
            $user = User::updateOrCreate(
                ['email' => $wpUser->user_email],
                [
                    'name' => $wpUser->display_name ?: $wpUser->user_login,
                    'password' => $wpUser->user_pass, // Уже хэшированный WP пароль
                    'created_at' => $wpUser->user_registered,
                ]
            );

            $mapping[$wpUser->ID] = $user->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Пользователей перенесено: {$wpUsers->count()}");
        $this->newLine();

        return $mapping;
    }

    private function migrateCategories()
    {
        $this->info('📂 Миграция категорий...');

        $wpCategories = TermTaxonomy::where('taxonomy', 'category')
            ->with('term')
            ->get();

        $bar = $this->output->createProgressBar($wpCategories->count());
        $bar->start();

        $mapping = [];

        foreach ($wpCategories as $wpCat) {
            if (!$wpCat->term) continue;

            $category = LaravelCategory::updateOrCreate(
                ['slug' => $wpCat->term->slug],
                [
                    'name' => $wpCat->term->name,
                    'description' => $wpCat->description,
                    'order' => 0,
                ]
            );

            $mapping[$wpCat->term_taxonomy_id] = $category->id;
            $bar->advance();
        }

        // Обновляем parent_id после создания всех категорий
        foreach ($wpCategories as $wpCat) {
            if ($wpCat->parent && isset($mapping[$wpCat->parent])) {
                $category = LaravelCategory::where('slug', $wpCat->term->slug)->first();
                if ($category) {
                    $category->parent_id = $mapping[$wpCat->parent];
                    $category->save();
                }
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Категорий перенесено: {$wpCategories->count()}");
        $this->newLine();

        return $mapping;
    }

    private function migrateTags()
    {
        $this->info('🏷️  Миграция тегов...');

        $wpTags = TermTaxonomy::where('taxonomy', 'post_tag')
            ->with('term')
            ->get();

        $bar = $this->output->createProgressBar($wpTags->count());
        $bar->start();

        $mapping = [];

        foreach ($wpTags as $wpTag) {
            if (!$wpTag->term) continue;

            $tag = LaravelTag::updateOrCreate(
                ['slug' => $wpTag->term->slug],
                [
                    'name' => $wpTag->term->name,
                    'description' => $wpTag->description,
                ]
            );

            $mapping[$wpTag->term_taxonomy_id] = $tag->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Тегов перенесено: {$wpTags->count()}");
        $this->newLine();

        return $mapping;
    }

    private function migratePosts()
    {
        $this->info('📰 Миграция постов...');

        // Получаем маппинг пользователей, категорий и тегов
        $userMapping = $this->getUserMapping();
        $categoryMapping = $this->getCategoryMapping();
        $tagMapping = $this->getTagMapping();

        $wpPosts = WPPost::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->with(['author', 'categories', 'tags'])
            ->get();

        $bar = $this->output->createProgressBar($wpPosts->count());
        $bar->start();

        foreach ($wpPosts as $wpPost) {
            // Получаем миниатюру
            $featuredImage = null;
            $thumbnailId = $wpPost->getMeta('_thumbnail_id');
            if ($thumbnailId) {
                $attachment = WPPost::find($thumbnailId);
                if ($attachment) {
                    $featuredImage = $attachment->guid;
                }
            }

            // Получаем author_id из маппинга или используем первого пользователя
            $authorId = isset($userMapping[$wpPost->post_author]) 
                ? $userMapping[$wpPost->post_author] 
                : User::first()->id ?? 1;

            // Создаем или обновляем пост
            $post = LaravelPost::updateOrCreate(
                ['slug' => $wpPost->post_name ?: Str::slug($wpPost->post_title)],
                [
                    'title' => $wpPost->post_title,
                    'excerpt' => $wpPost->post_excerpt,
                    'content' => $wpPost->post_content,
                    'status' => 'published',
                    'author_id' => $authorId,
                    'featured_image' => $featuredImage,
                    'views' => (int) $wpPost->getMeta('post_views_count', 0),
                    'created_at' => $wpPost->post_date,
                    'updated_at' => $wpPost->post_modified,
                    
                    // SEO
                    'meta_title' => $wpPost->getMeta('_yoast_wpseo_title'),
                    'meta_description' => $wpPost->getMeta('_yoast_wpseo_metadesc'),
                    'meta_keywords' => $wpPost->getMeta('_yoast_wpseo_focuskw'),
                    'canonical_url' => $wpPost->getMeta('_yoast_wpseo_canonical'),
                    
                    // Open Graph
                    'og_title' => $wpPost->getMeta('_yoast_wpseo_opengraph-title'),
                    'og_description' => $wpPost->getMeta('_yoast_wpseo_opengraph-description'),
                    'og_image' => $wpPost->getMeta('_yoast_wpseo_opengraph-image'),
                    
                    // Twitter
                    'twitter_title' => $wpPost->getMeta('_yoast_wpseo_twitter-title'),
                    'twitter_description' => $wpPost->getMeta('_yoast_wpseo_twitter-description'),
                    'twitter_image' => $wpPost->getMeta('_yoast_wpseo_twitter-image'),
                ]
            );

            // Привязываем категории
            $categoryIds = [];
            foreach ($wpPost->categories as $wpCat) {
                if (isset($categoryMapping[$wpCat->term_taxonomy_id])) {
                    $categoryIds[] = $categoryMapping[$wpCat->term_taxonomy_id];
                }
            }
            if (!empty($categoryIds)) {
                $post->categories()->sync($categoryIds);
            }

            // Привязываем теги
            $tagIds = [];
            foreach ($wpPost->tags as $wpTag) {
                if (isset($tagMapping[$wpTag->term_taxonomy_id])) {
                    $tagIds[] = $tagMapping[$wpTag->term_taxonomy_id];
                }
            }
            if (!empty($tagIds)) {
                $post->tags()->sync($tagIds);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Постов перенесено: {$wpPosts->count()}");
    }

    private function getUserMapping()
    {
        $mapping = [];
        $wpUsers = WPUser::all();
        
        foreach ($wpUsers as $wpUser) {
            $laravelUser = User::where('email', $wpUser->user_email)->first();
            if ($laravelUser) {
                $mapping[$wpUser->ID] = $laravelUser->id;
            }
        }
        
        return $mapping;
    }

    private function getCategoryMapping()
    {
        $mapping = [];
        $wpCategories = TermTaxonomy::where('taxonomy', 'category')->with('term')->get();
        
        foreach ($wpCategories as $wpCat) {
            if (!$wpCat->term) continue;
            $laravelCat = LaravelCategory::where('slug', $wpCat->term->slug)->first();
            if ($laravelCat) {
                $mapping[$wpCat->term_taxonomy_id] = $laravelCat->id;
            }
        }
        
        return $mapping;
    }

    private function getTagMapping()
    {
        $mapping = [];
        $wpTags = TermTaxonomy::where('taxonomy', 'post_tag')->with('term')->get();
        
        foreach ($wpTags as $wpTag) {
            if (!$wpTag->term) continue;
            $laravelTag = LaravelTag::where('slug', $wpTag->term->slug)->first();
            if ($laravelTag) {
                $mapping[$wpTag->term_taxonomy_id] = $laravelTag->id;
            }
        }
        
        return $mapping;
    }
}
