<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Models\PostSeo;
use App\Services\SeoGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Команда для пакетной регенерации SEO через AI
 * Генерирует SQL-файл с обновлениями
 */
class SeoBatchRegenerate extends Command
{
    protected $signature = 'seo:batch-regenerate 
                            {--batch=50 : Размер пакета для обработки}
                            {--from=0 : ID поста с которого начать}
                            {--provider=auto : AI провайдер (chatinfo, gigachat, openai, auto)}
                            {--delay=1000 : Задержка между запросами в мс}
                            {--output=storage/seo_regenerate.sql : Путь к SQL-файлу}
                            {--continue : Продолжить с последнего обработанного}
                            {--only-empty : Только статьи без SEO данных}
                            {--only-bad : Только статьи с score < 50}';
    
    protected $description = 'Пакетная регенерация SEO через AI с записью в SQL';
    
    protected ?SeoGeneratorService $seoService = null;
    protected string $stateFile = 'storage/seo_regenerate_state.json';
    protected int $processed = 0;
    protected int $success = 0;
    protected int $failed = 0;
    protected array $sqlStatements = [];

    public function handle(): int
    {
        $this->info('🤖 Пакетная регенерация SEO через AI');
        $this->info('=' . str_repeat('=', 50));
        
        $batchSize = (int) $this->option('batch');
        $fromId = (int) $this->option('from');
        $provider = $this->option('provider');
        $delay = (int) $this->option('delay');
        $outputFile = $this->option('output');
        $continue = $this->option('continue');
        $onlyEmpty = $this->option('only-empty');
        $onlyBad = $this->option('only-bad');
        
        // Загружаем состояние если продолжаем
        if ($continue && File::exists(base_path($this->stateFile))) {
            $state = json_decode(File::get(base_path($this->stateFile)), true);
            $fromId = $state['last_id'] ?? 0;
            $this->info("📌 Продолжаем с ID: {$fromId}");
        }
        
        // Инициализируем SEO-сервис
        try {
            $this->seoService = new SeoGeneratorService();
            $providers = $this->seoService->getAvailableProviders();
            $available = array_filter($providers, fn($p) => $p['configured']);
            
            if (empty($available)) {
                $this->error('❌ Нет настроенных AI-провайдеров!');
                return 1;
            }
            
            $this->info('✅ Доступные AI-провайдеры: ' . implode(', ', array_keys($available)));
            
            // Тестируем выбранный провайдер
            if ($provider !== 'auto') {
                $test = $this->seoService->testProvider($provider);
                if (!$test['success']) {
                    $this->error("❌ Провайдер {$provider} недоступен: " . $test['message']);
                    return 1;
                }
                $this->info("✅ Провайдер {$provider} готов");
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка инициализации AI: ' . $e->getMessage());
            return 1;
        }
        
        // Получаем статьи для обработки
        $query = Post::where('post_status', 'publish')
            ->where('post_type', 'post')
            ->where('ID', '>', $fromId)
            ->orderBy('ID', 'asc');
        
        if ($onlyEmpty) {
            // Только статьи без SEO
            $query->whereDoesntHave('seo');
        } elseif ($onlyBad) {
            // Только статьи с плохим score
            $query->whereHas('seo', function($q) {
                $q->where(function($q2) {
                    $q2->whereNull('seo_score')
                       ->orWhere('seo_score', '<', 50);
                });
            });
        }
        
        $total = $query->count();
        $this->info("📊 Статей для обработки: {$total}");
        
        if ($total === 0) {
            $this->info('✅ Все статьи уже обработаны!');
            return 0;
        }
        
        // Подтверждение
        if (!$this->confirm("Начать обработку {$batchSize} статей? (API запросы)")) {
            return 0;
        }
        
        // Инициализируем SQL-файл
        $this->initSqlFile($outputFile);
        
        // Обрабатываем пакет
        $posts = $query->take($batchSize)->get();
        $bar = $this->output->createProgressBar($posts->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->start();
        
        foreach ($posts as $post) {
            $bar->setMessage("ID: {$post->ID}");
            
            $result = $this->regeneratePost($post, $provider);
            
            if ($result['success']) {
                $this->success++;
                if (!empty($result['sql'])) {
                    $this->sqlStatements[] = $result['sql'];
                }
            } else {
                $this->failed++;
                Log::warning("SEO regeneration failed for post {$post->ID}: " . ($result['error'] ?? 'Unknown'));
            }
            
            $this->processed++;
            
            // Сохраняем состояние после каждой статьи
            $this->saveState($post->ID);
            
            $bar->advance();
            
            // Задержка между запросами
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Записываем SQL
        $this->writeSqlFile($outputFile);
        
        // Отчёт
        $this->printReport($outputFile, $total - $posts->count());
        
        return 0;
    }
    
    /**
     * Регенерирует SEO для одной статьи
     */
    protected function regeneratePost(Post $post, string $provider): array
    {
        try {
            // Генерируем новые SEO данные
            $seoData = $this->seoService->generateSeoData(
                $post->post_title,
                $post->post_excerpt,
                $post->post_content,
                $provider === 'auto' ? null : $provider
            );
            
            // Получаем существующую SEO запись
            $existingSeo = PostSeo::where('post_id', $post->ID)->first();
            
            // Строим SQL
            if ($existingSeo) {
                $sql = $this->buildUpdateSql($existingSeo->id, $post->ID, $seoData);
            } else {
                $sql = $this->buildInsertSql($post->ID, $seoData);
            }
            
            return [
                'success' => true,
                'sql' => $sql,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Сохраняет состояние для продолжения
     */
    protected function saveState(int $lastId): void
    {
        $state = [
            'last_id' => $lastId,
            'processed' => $this->processed,
            'success' => $this->success,
            'failed' => $this->failed,
            'timestamp' => now()->toIso8601String(),
        ];
        
        File::put(base_path($this->stateFile), json_encode($state, JSON_PRETTY_PRINT));
    }
    
    /**
     * Инициализирует SQL-файл
     */
    protected function initSqlFile(string $path): void
    {
        $fullPath = base_path($path);
        $dir = dirname($fullPath);
        
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        // Если продолжаем, добавляем к файлу
        if ($this->option('continue') && File::exists($fullPath)) {
            File::append($fullPath, "\n-- Batch continued at " . now()->format('Y-m-d H:i:s') . "\n\n");
            return;
        }
        
        $header = "-- =====================================================\n";
        $header .= "-- SEO REGENERATION - Generated by seo:batch-regenerate\n";
        $header .= "-- Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $header .= "-- =====================================================\n\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET CHARACTER SET utf8mb4;\n\n";
        
        File::put($fullPath, $header);
    }
    
    /**
     * Записывает SQL в файл
     */
    protected function writeSqlFile(string $path): void
    {
        $fullPath = base_path($path);
        
        if (empty($this->sqlStatements)) {
            File::append($fullPath, "-- Нет изменений в этом пакете\n");
            return;
        }
        
        File::append($fullPath, "-- Batch: {$this->success} успешных изменений\n\n");
        
        foreach ($this->sqlStatements as $sql) {
            File::append($fullPath, $sql . "\n\n");
        }
    }
    
    /**
     * Строит INSERT SQL
     */
    protected function buildInsertSql(int $postId, array $seoData): string
    {
        $now = now()->format('Y-m-d H:i:s');
        
        $keywords = $seoData['seo_keywords'] ?? '';
        if (is_array($keywords)) {
            $keywords = json_encode($keywords, JSON_UNESCAPED_UNICODE);
        }
        
        $focusKeyword = $seoData['focus_keyword'] ?? '';
        if (!empty($focusKeyword)) {
            $focusKeyword = json_encode([$focusKeyword], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "-- Post ID: {$postId} (INSERT)\n";
        $sql .= "INSERT INTO post_seo (post_id, seo_title, seo_description, seo_keywords, focus_keywords, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, seo_score, created_at, updated_at) VALUES (\n";
        $sql .= "    {$postId},\n";
        $sql .= "    " . $this->escapeSql($seoData['seo_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['seo_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($keywords) . ",\n";
        $sql .= "    " . $this->escapeSql($focusKeyword) . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_image'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_image'] ?? '') . ",\n";
        $sql .= "    100,\n";
        $sql .= "    '{$now}',\n";
        $sql .= "    '{$now}'\n";
        $sql .= ");";
        
        return $sql;
    }
    
    /**
     * Строит UPDATE SQL
     */
    protected function buildUpdateSql(int $seoId, int $postId, array $seoData): string
    {
        $now = now()->format('Y-m-d H:i:s');
        
        $keywords = $seoData['seo_keywords'] ?? '';
        if (is_array($keywords)) {
            $keywords = json_encode($keywords, JSON_UNESCAPED_UNICODE);
        }
        
        $focusKeyword = $seoData['focus_keyword'] ?? '';
        if (!empty($focusKeyword)) {
            $focusKeyword = json_encode([$focusKeyword], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "-- Post ID: {$postId}, SEO ID: {$seoId} (UPDATE)\n";
        $sql .= "UPDATE post_seo SET\n";
        $sql .= "    seo_title = " . $this->escapeSql($seoData['seo_title'] ?? '') . ",\n";
        $sql .= "    seo_description = " . $this->escapeSql($seoData['seo_description'] ?? '') . ",\n";
        $sql .= "    seo_keywords = " . $this->escapeSql($keywords) . ",\n";
        $sql .= "    focus_keywords = " . $this->escapeSql($focusKeyword) . ",\n";
        $sql .= "    og_title = " . $this->escapeSql($seoData['og_title'] ?? '') . ",\n";
        $sql .= "    og_description = " . $this->escapeSql($seoData['og_description'] ?? '') . ",\n";
        $sql .= "    og_image = " . $this->escapeSql($seoData['og_image'] ?? '') . ",\n";
        $sql .= "    twitter_title = " . $this->escapeSql($seoData['twitter_title'] ?? '') . ",\n";
        $sql .= "    twitter_description = " . $this->escapeSql($seoData['twitter_description'] ?? '') . ",\n";
        $sql .= "    twitter_image = " . $this->escapeSql($seoData['twitter_image'] ?? '') . ",\n";
        $sql .= "    seo_score = 100,\n";
        $sql .= "    updated_at = '{$now}'\n";
        $sql .= "WHERE id = {$seoId};";
        
        return $sql;
    }
    
    /**
     * Экранирует строку для SQL
     */
    protected function escapeSql(?string $value): string
    {
        if (empty($value)) {
            return 'NULL';
        }
        
        $escaped = str_replace("'", "''", $value);
        $escaped = str_replace("\\", "\\\\", $escaped);
        
        return "'{$escaped}'";
    }
    
    /**
     * Выводит отчёт
     */
    protected function printReport(string $outputFile, int $remaining): void
    {
        $this->info('📊 ОТЧЁТ О РЕГЕНЕРАЦИИ');
        $this->info('=' . str_repeat('=', 50));
        $this->info("✅ Обработано: {$this->processed}");
        $this->info("✅ Успешно: {$this->success}");
        $this->info("❌ Ошибок: {$this->failed}");
        $this->info("⏳ Осталось: {$remaining}");
        $this->newLine();
        $this->info("📝 SQL сохранён: {$outputFile}");
        
        if ($remaining > 0) {
            $this->newLine();
            $this->info('💡 Для продолжения:');
            $this->line('   php artisan seo:batch-regenerate --continue');
        }
        
        $this->newLine();
        $this->info('💡 Применить на сервере:');
        $this->line('   mysql -u USER -p DATABASE < ' . $outputFile);
    }
}
