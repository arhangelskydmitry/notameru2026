<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Models\WordPress\PostMeta;
use App\Models\PostSeo;
use Illuminate\Support\Facades\DB;

class MigrateSeoData extends Command
{
    protected $signature = 'migrate:seo {--force : Force migration even if data exists}';
    protected $description = 'Migrate SEO data from WordPress AIOSEO to Laravel post_seo table';

    private $aioseoTags = [
        '#post_title' => 'post_title',
        '#post_excerpt' => 'post_excerpt',
        '#separator_sa' => ' - ',
        '#site_title' => 'notame.ru',
        '#post_year' => 'post_year',
        '#taxonomy_title' => 'category_name',
    ];

    public function handle()
    {
        $this->info('🔄 Начинаем миграцию SEO данных из WordPress AIOSEO...');

        if (!$this->option('force') && PostSeo::count() > 0) {
            if (!$this->confirm('В таблице post_seo уже есть данные. Продолжить? (это перезапишет существующие данные)')) {
                $this->info('Миграция отменена.');
                return 0;
            }
        }

        $totalPosts = Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();

        $this->info("Найдено постов: " . $totalPosts);
        
        $bar = $this->output->createProgressBar($totalPosts);
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $chunkSize = 100;

        // Обрабатываем посты порциями для экономии памяти
        Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->chunk($chunkSize, function($posts) use (&$migrated, &$skipped, $bar) {
                foreach ($posts as $post) {
                    try {
                        // Загружаем связи для текущего поста
                        $post->load(['meta', 'categories.term']);
                        
                        $seoData = $this->extractSeoData($post);
                        
                        PostSeo::updateOrCreate(
                            ['post_id' => $post->ID],
                            $seoData
                        );
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("\nОшибка при обработке поста {$post->ID}: " . $e->getMessage());
                        $skipped++;
                    }
                    
                    $bar->advance();
                }
                
                // Очищаем память после каждой порции
                gc_collect_cycles();
            });

        $bar->finish();
        
        $this->newLine(2);
        $this->info("✅ Миграция завершена!");
        $this->info("📊 Статистика:");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего постов', $totalPosts],
                ['Мигрировано', $migrated],
                ['Пропущено', $skipped],
            ]
        );

        return 0;
    }

    private function extractSeoData(Post $post): array
    {
        $data = [
            'seo_title' => $this->processSeoField($post, '_aioseo_title'),
            'seo_description' => $this->processSeoField($post, '_aioseo_description'),
            'seo_keywords' => $this->processKeywords($post->getMeta('_aioseo_keywords')),
            'canonical_url' => route('post', $post->post_name),
            'robots' => 'index, follow',
            
            // Open Graph
            'og_title' => $this->processSeoField($post, '_aioseo_og_title'),
            'og_description' => $this->processSeoField($post, '_aioseo_og_description'),
            'og_image' => $this->getPostImage($post),
            'og_type' => 'article',
            'og_article_section' => $post->getMeta('_aioseo_og_article_section'),
            'og_article_tags' => $this->processKeywords($post->getMeta('_aioseo_og_article_tags')),
            
            // Twitter
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $this->processSeoField($post, '_aioseo_twitter_title'),
            'twitter_description' => $this->processSeoField($post, '_aioseo_twitter_description'),
            'twitter_image' => $this->getPostImage($post),
        ];

        return $data;
    }

    private function processSeoField(Post $post, string $metaKey): ?string
    {
        $value = $post->getMeta($metaKey);
        
        if (empty($value)) {
            return null;
        }

        // Заменяем AIOSEO теги на реальные значения
        $value = str_replace('#post_title', $post->post_title, $value);
        $value = str_replace('#separator_sa', ' - ', $value);
        $value = str_replace('#site_title', 'notame.ru', $value);
        $value = str_replace('#post_year', $post->post_date->format('Y'), $value);
        
        // Заменяем #post_excerpt
        if (strpos($value, '#post_excerpt') !== false) {
            $excerpt = $post->post_excerpt ?: mb_substr(strip_tags($post->post_content), 0, 160);
            $value = str_replace('#post_excerpt', $excerpt, $value);
        }
        
        // Заменяем #taxonomy_title (категорию)
        if (strpos($value, '#taxonomy_title') !== false) {
            $category = $post->categories->first();
            $categoryName = $category ? $category->term->name : '';
            $value = str_replace('#taxonomy_title', $categoryName, $value);
        }

        // Убираем множественные пробелы и лишние разделители
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/\s*-\s*-\s*/', ' - ', $value);
        $value = trim($value);
        $value = trim($value, '-');
        $value = trim($value);

        return empty($value) ? null : $value;
    }

    private function processKeywords(?string $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        // AIOSEO хранит keywords как сериализованный массив
        $keywords = @unserialize($value);
        
        if (is_array($keywords) && !empty($keywords)) {
            return array_values($keywords);
        }

        return null;
    }

    private function getPostImage(Post $post): ?string
    {
        $thumbnailId = $post->getMeta('_thumbnail_id');
        
        if ($thumbnailId) {
            $attachment = Post::find($thumbnailId);
            if ($attachment) {
                $file = $attachment->getMeta('_wp_attached_file');
                if ($file) {
                    return 'http://localhost:8001/wp-content/uploads/' . $file;
                }
            }
        }

        return null;
    }
}

