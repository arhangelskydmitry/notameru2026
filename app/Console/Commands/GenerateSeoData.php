<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Helpers\ContentHelper;

class GenerateSeoData extends Command
{
    protected $signature = 'seo:generate {--force : Force update all posts}';
    protected $description = 'Generate and update SEO data for all posts';

    public function handle()
    {
        $this->info('🚀 Начинаем генерацию SEO данных...');
        $this->newLine();

        $posts = Post::with(['seo'])
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID', 'desc')
            ->get();

        $this->info("📊 Найдено постов: {$posts->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $stats = [
            'updated' => 0,
            'skipped' => 0,
            'og_image_updated' => 0,
            'keywords_added' => 0,
            'description_improved' => 0,
        ];

        foreach ($posts as $post) {
            if (!$post->seo) {
                $bar->advance();
                continue;
            }

            $needsUpdate = false;
            $updates = [];

            // 1. Исправляем OG Image URL
            if ($post->seo->og_image) {
                // Заменяем localhost:8001 на правильный путь
                if (strpos($post->seo->og_image, 'localhost:8001') !== false) {
                    $featuredImage = ContentHelper::getFeaturedImage($post);
                    if ($featuredImage && $featuredImage !== '/images/default-post.jpg') {
                        $updates['og_image'] = url($featuredImage);
                        $needsUpdate = true;
                        $stats['og_image_updated']++;
                    }
                }
            } else {
                // Добавляем OG Image если его нет
                $featuredImage = ContentHelper::getFeaturedImage($post);
                if ($featuredImage && $featuredImage !== '/images/default-post.jpg') {
                    $updates['og_image'] = url($featuredImage);
                    $needsUpdate = true;
                    $stats['og_image_updated']++;
                }
            }

            // 2. Генерируем ключевые слова из заголовка и контента
            if (empty($post->seo->seo_keywords) || $this->option('force')) {
                $keywords = $this->generateKeywords($post);
                if (!empty($keywords)) {
                    $updates['seo_keywords'] = $keywords;
                    $needsUpdate = true;
                    $stats['keywords_added']++;
                }
            }

            // 3. Улучшаем описание если оно короткое
            if (strlen($post->seo->seo_description) < 100 || $this->option('force')) {
                $description = $this->generateDescription($post);
                if (strlen($description) >= 100) {
                    $updates['seo_description'] = $description;
                    $needsUpdate = true;
                    $stats['description_improved']++;
                }
            }

            // 4. Добавляем OG Type если его нет
            if (empty($post->seo->og_type)) {
                $updates['og_type'] = 'article';
                $needsUpdate = true;
            }

            // 5. Добавляем Twitter Card если его нет
            if (empty($post->seo->twitter_card)) {
                $updates['twitter_card'] = 'summary_large_image';
                $needsUpdate = true;
            }

            // Обновляем если нужно
            if ($needsUpdate && !empty($updates)) {
                $post->seo->update($updates);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Выводим статистику
        $this->info('✅ Генерация завершена!');
        $this->newLine();
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего постов', $posts->count()],
                ['Обновлено', $stats['updated']],
                ['Пропущено', $stats['skipped']],
                ['OG изображений исправлено', $stats['og_image_updated']],
                ['Ключевых слов добавлено', $stats['keywords_added']],
                ['Описаний улучшено', $stats['description_improved']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Генерирует ключевые слова из заголовка и контента
     */
    private function generateKeywords(Post $post): array
    {
        $text = $post->post_title . ' ' . strip_tags($post->post_content);
        
        // Удаляем спецсимволы
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Разбиваем на слова
        $words = preg_split('/\s+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        
        // Стоп-слова (частые слова, которые не несут смысловой нагрузки)
        $stopWords = [
            'это', 'как', 'его', 'что', 'для', 'при', 'или', 'все', 'так', 'был',
            'есть', 'еще', 'уже', 'был', 'быть', 'может', 'если', 'года', 'году',
            'лет', 'год', 'более', 'также', 'они', 'она', 'оно', 'мы', 'вы', 'ты',
            'чем', 'том', 'этот', 'эта', 'эти', 'тот', 'та', 'те', 'кто', 'где',
            'когда', 'который', 'которая', 'которые', 'под', 'над', 'между', 'через',
        ];
        
        // Фильтруем стоп-слова и короткие слова
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) >= 4 && !in_array($word, $stopWords);
        });
        
        // Подсчитываем частоту
        $frequency = array_count_values($words);
        
        // Сортируем по частоте
        arsort($frequency);
        
        // Берем топ-10 слов
        $keywords = array_slice(array_keys($frequency), 0, 10);
        
        return $keywords;
    }

    /**
     * Генерирует описание из контента
     */
    private function generateDescription(Post $post): string
    {
        // Если есть excerpt, используем его
        if (!empty($post->post_excerpt)) {
            $description = strip_tags($post->post_excerpt);
            if (strlen($description) >= 100) {
                return mb_substr($description, 0, 160);
            }
        }

        // Иначе берем из контента
        $content = strip_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // Находим первое предложение или 160 символов
        $sentences = preg_split('/[.!?]+/', $content);
        $description = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 20) continue; // Пропускаем короткие предложения
            
            if (strlen($description . $sentence) <= 160) {
                $description .= $sentence . '. ';
            } else {
                break;
            }
        }

        // Если получилось слишком коротко, берем первые 160 символов
        if (strlen($description) < 100) {
            $description = mb_substr($content, 0, 160);
            // Обрезаем по последнему слову
            $lastSpace = mb_strrpos($description, ' ');
            if ($lastSpace !== false) {
                $description = mb_substr($description, 0, $lastSpace) . '...';
            }
        }

        return trim($description);
    }
}


